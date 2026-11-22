<?php

namespace Tests\Unit;

use App\Http\Controllers\RentController;
use App\Models\Rent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class RentControllerTest extends ControllerTestCase
{
    private RentController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RentController();
    }

    private function rentRequest(array $data, User $user, bool $withProof = true): Request
    {
        $files = $withProof ? ['paymentProof' => $this->proofImage()] : [];

        return $this->makeRequest($data, $user, $files);
    }

    // availableGarages

    public function testAvailableGaragesListsCompatibleFreeGaragesCheapestFirst(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $c001 = $this->makeGarage('C001', 10000);
        $this->makeGarage('C002', 12000);
        $this->makeGarage('C003', 8000);
        $this->makeGarage('S001', 2000);
        $this->makeGarage('E001', 15000, 1500);
        $other = $this->makeUser();
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $c001, '2026-11-20', '2026-11-25');

        $view = $this->controller->availableGarages($this->makeRequest([], $user, [], 'GET'), $car);

        $this->assertSame('user.availablegarage', $view->name());
        $this->assertSame(['C003', 'C002'], $view->getData()['garages']->pluck('id')->all());
    }

    public function testAvailableGaragesForElectricCarIncludesCarAndElectricGarages(): void
    {
        $user = $this->makeUser();
        $ev = $this->makeVehicle($user, 'electric car');
        $this->makeGarage('C001', 10000);
        $this->makeGarage('E001', 15000, 1500);
        $this->makeGarage('S001', 2000);

        $view = $this->controller->availableGarages($this->makeRequest([], $user, [], 'GET'), $ev);

        $this->assertEqualsCanonicalizing(['C001', 'E001'], $view->getData()['garages']->pluck('id')->all());
    }

    public function testAvailableGaragesForMotorcycleUsesBicycleGarages(): void
    {
        $user = $this->makeUser();
        $motor = $this->makeVehicle($user, 'motorcycle');
        $this->makeGarage('S001', 2000);
        $this->makeGarage('C001', 10000);

        $view = $this->controller->availableGarages($this->makeRequest([], $user, [], 'GET'), $motor);

        $this->assertSame(['S001'], $view->getData()['garages']->pluck('id')->all());
    }

    public function testAvailableGaragesForSomeoneElsesVehicleIsForbidden(): void
    {
        $owner = $this->makeUser();
        $vehicle = $this->makeVehicle($owner, 'car');
        $intruder = $this->makeUser();

        $this->assertAborts(403, fn () => $this->controller->availableGarages(
            $this->makeRequest([], $intruder, [], 'GET'),
            $vehicle
        ));
    }

    // viewUserRents

    public function testViewUserRentsReturnsOnlyOwnActiveRents(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $other = $this->makeUser();
        $c1 = $this->makeGarage('C001');
        $c2 = $this->makeGarage('C002');
        $c3 = $this->makeGarage('C003');

        $active = $this->makeRent($user, $car, $c1, '2026-11-20', '2026-11-25');
        $this->makeRent($user, $car, $c2, '2026-11-18', self::TODAY);
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $c3, '2026-11-20', '2026-11-25');

        $view = $this->controller->viewUserRents($this->makeRequest([], $user, [], 'GET'));

        $this->assertSame('user.viewrent', $view->name());
        $this->assertSame([$active->id], $view->getData()['rents']->pluck('id')->all());
    }

    // rentForm

    public function testRentFormCalculatesChargeForElectricCar(): void
    {
        $user = $this->makeUser();
        $ev = $this->makeVehicle($user, 'electric car', ['battCapacity' => 20, 'battMaximum' => 60]);
        $garage = $this->makeGarage('E001', 15000, 1500);

        $view = $this->controller->rentForm($this->makeRequest([], $user, [], 'GET'), $ev, $garage);

        $this->assertSame('user.rentform', $view->name());
        $this->assertEquals(60000, $view->getData()['charge']);
        $this->assertSame('2026-11-22', $view->getData()['today']);
    }

    public function testRentFormChargeIsZeroForNonElectricVehicle(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001', 10000, 1500);

        $view = $this->controller->rentForm($this->makeRequest([], $user, [], 'GET'), $car, $garage);

        $this->assertEquals(0, $view->getData()['charge']);
    }

    public function testRentFormForGarageInUseRedirectsWithError(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');
        $other = $this->makeUser();
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $garage, '2026-11-21', '2026-11-24');

        $response = $this->controller->rentForm($this->makeRequest([], $user, [], 'GET'), $car, $garage);

        $this->assertStringEndsWith('/rent/' . $car->id, $response->getTargetUrl());
        $this->assertSame('That garage is already in use.', $this->sessionError('garage'));
    }

    public function testRentFormWithIncompatibleGarageIsForbidden(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $bikeGarage = $this->makeGarage('S001', 2000);

        $this->assertAborts(403, fn () => $this->controller->rentForm(
            $this->makeRequest([], $user, [], 'GET'),
            $car,
            $bikeGarage
        ));
    }

    public function testRentFormWithSomeoneElsesVehicleIsForbidden(): void
    {
        $owner = $this->makeUser();
        $car = $this->makeVehicle($owner, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertAborts(403, fn () => $this->controller->rentForm(
            $this->makeRequest([], $this->makeUser(), [], 'GET'),
            $car,
            $garage
        ));
    }

    // addRent

    public function testAddRentDailyCreatesRentWithCorrectDatesAndCost(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001', 10000);

        $response = $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 5,
        ], $user), $car, $garage);

        $rent = Rent::sole();
        $this->assertSame('2026-11-22', $rent->startDate->toDateString());
        $this->assertSame('2026-11-27', $rent->endDate->toDateString());
        $this->assertEquals(50000, $rent->totalCost);
        $this->assertEquals(5, $rent->dayAmount);
        $this->assertSame('active', $rent->rentStatus);
        $this->assertSame($user->id, $rent->user_id);
        $this->assertStoredOnPublicDisk($rent->paymentProof);
        $this->assertStringStartsWith('payments/', $rent->paymentProof);
        $this->assertStringEndsWith('/rent/' . $car->id, $response->getTargetUrl());
        $this->assertSame('Garage C001 rented.', session('success'));
    }

    public function testAddRentMonthlyCosts25DaysAndEndsOneMonthLater(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001', 10000);

        $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'monthly',
        ], $user), $car, $garage);

        $rent = Rent::sole();
        $this->assertSame('2026-12-22', $rent->endDate->toDateString());
        $this->assertEquals(250000, $rent->totalCost);
        $this->assertNull($rent->dayAmount);
    }

    public function testAddRentForElectricCarAddsChargingCost(): void
    {
        $user = $this->makeUser();
        $ev = $this->makeVehicle($user, 'electric car', ['battCapacity' => 20, 'battMaximum' => 60]);
        $garage = $this->makeGarage('E001', 20000, 1500);

        $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 2,
        ], $user), $ev, $garage);

        // 2 x 20000 rent + (60 - 20) x 1500 charging
        $this->assertEquals(100000, Rent::sole()->totalCost);
    }

    public function testAddRentAcceptsTheMaximumDaysForTheMonth(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 29,
        ], $user), $car, $garage);

        $this->assertSame('2026-12-21', Rent::sole()->endDate->toDateString());
    }

    public function testAddRentAllowsBackToBackBooking(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');
        $other = $this->makeUser();
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $garage, '2026-11-22', '2026-11-25');

        $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-25',
            'rentType' => 'daily',
            'dayAmount' => 2,
        ], $user), $car, $garage);

        $this->assertSame(2, Rent::count());
        $this->assertNull($this->sessionError('startDate'));
    }

    public function testAddRentOverlappingExistingBookingIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');
        $other = $this->makeUser();
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $garage, '2026-11-24', '2026-11-27');

        $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 3,
        ], $user), $car, $garage);

        $this->assertSame('That garage is already booked for those dates.', $this->sessionError('startDate'));
        $this->assertSame(1, Rent::count());
    }

    public function testAddRentStartDateInThePastIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertValidationFails(fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-21',
            'rentType' => 'daily',
            'dayAmount' => 2,
        ], $user), $car, $garage), 'startDate');
        $this->assertSame(0, Rent::count());
    }

    public function testAddRentDailyDaysAboveMonthLimitIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertValidationFails(fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 30,
        ], $user), $car, $garage), 'dayAmount');
    }

    public function testAddRentDailyWithoutDayAmountIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertValidationFails(fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
        ], $user), $car, $garage), 'dayAmount');
    }

    public function testAddRentInvalidRentTypeIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertValidationFails(fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'weekly',
        ], $user), $car, $garage), 'rentType');
    }

    public function testAddRentWithoutPaymentProofIsRejected(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $this->assertValidationFails(fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 2,
        ], $user, withProof: false), $car, $garage), 'paymentProof');
    }

    public function testAddRentPaymentProofMustBeAnImage(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $garage = $this->makeGarage('C001');

        $request = $this->makeRequest(
            ['startDate' => '2026-11-22', 'rentType' => 'daily', 'dayAmount' => 2],
            $user,
            ['paymentProof' => $this->proofPdf()]
        );

        $this->assertValidationFails(
            fn () => $this->controller->addRent($request, $car, $garage),
            'paymentProof'
        );
    }

    public function testAddRentWithIncompatibleGarageIsForbidden(): void
    {
        $user = $this->makeUser();
        $car = $this->makeVehicle($user, 'car');
        $bikeGarage = $this->makeGarage('S001', 2000);

        $this->assertAborts(403, fn () => $this->controller->addRent($this->rentRequest([
            'startDate' => '2026-11-22',
            'rentType' => 'daily',
            'dayAmount' => 2,
        ], $user), $car, $bikeGarage));
        $this->assertSame(0, Rent::count());
    }

    // viewRents

    public function testViewRentsListsOnlyCurrentlyRunningRentsOrderedByStartDate(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $carA = $this->makeVehicle($a, 'car');
        $carB = $this->makeVehicle($b, 'car');

        $later = $this->makeRent($a, $carA, $this->makeGarage('C001'), '2026-11-22', '2026-11-24');
        $earlier = $this->makeRent($b, $carB, $this->makeGarage('C002'), '2026-11-20', '2026-11-25');
        $this->makeRent($a, $carA, $this->makeGarage('C003'), '2026-11-23', '2026-11-26');
        $this->makeRent($b, $carB, $this->makeGarage('C004'), '2026-11-10', '2026-11-15');

        $view = $this->controller->viewRents();

        $this->assertSame('admin.allrent', $view->name());
        $this->assertSame([$earlier->id, $later->id], $view->getData()['rents']->pluck('id')->all());
    }

    // extendRent

    private function rentToExtend(): Rent
    {
        $user = $this->makeUser();

        return $this->makeRent(
            $user,
            $this->makeVehicle($user, 'car'),
            $this->makeGarage('C001', 10000),
            '2026-11-22',
            '2026-11-25',
            ['totalCost' => 30000]
        );
    }

    private function extend(Rent $rent, array $data, bool $withProof = true)
    {
        $files = $withProof ? ['paymentProof' => $this->proofImage('new-proof.jpg')] : [];

        return $this->controller->extendRent($this->makeRequest($data, $this->makeAdmin(), $files), $rent);
    }

    public function testExtendRentAddsDaysAndDailyCost(): void
    {
        $rent = $this->rentToExtend();

        $response = $this->extend($rent, ['days' => 3]);

        $rent->refresh();
        $this->assertSame('2026-11-28', $rent->endDate->toDateString());
        $this->assertEquals(60000, $rent->totalCost);
        $this->assertNotSame('payments/existing.jpg', $rent->paymentProof);
        $this->assertStoredOnPublicDisk($rent->paymentProof);
        $this->assertStringEndsWith('/admin/rents', $response->getTargetUrl());
    }

    public function testExtendRentPricesFull30DayBlocksAs25Days(): void
    {
        $rent = $this->rentToExtend();

        $this->extend($rent, ['days' => 30]);

        $this->assertEquals(280000, $rent->refresh()->totalCost);
    }

    public function testExtendRentMixesMonthlyBlockAndDailyRemainder(): void
    {
        $rent = $this->rentToExtend();

        $this->extend($rent, ['days' => 35]);

        $this->assertEquals(330000, $rent->refresh()->totalCost);
    }

    public function testExtendRentClashingWithNextBookingIsRejected(): void
    {
        $rent = $this->rentToExtend();
        $other = $this->makeUser();
        $this->makeRent($other, $this->makeVehicle($other, 'car'), $rent->garage, '2026-11-26', '2026-11-30');

        $this->extend($rent, ['days' => 3]);

        $this->assertSame('The garage is booked by someone else during that period.', $this->sessionError('days'));
        $rent->refresh();
        $this->assertSame('2026-11-25', $rent->endDate->toDateString());
        $this->assertEquals(30000, $rent->totalCost);
    }

    public function testExtendRentZeroDaysIsRejected(): void
    {
        $rent = $this->rentToExtend();

        $this->assertValidationFails(fn () => $this->extend($rent, ['days' => 0]), 'days');
    }

    public function testExtendRentWithoutPaymentProofIsRejected(): void
    {
        $rent = $this->rentToExtend();

        $this->assertValidationFails(fn () => $this->extend($rent, ['days' => 3], withProof: false), 'paymentProof');
    }

    // extendForm

    public function testExtendFormShowsTheSelectedRent(): void
    {
        $rent = $this->rentToExtend();

        $view = $this->controller->extendForm($rent);

        $this->assertSame('admin.extendrent', $view->name());
        $this->assertTrue($rent->is($view->getData()['rent']));
    }

    // report

    /** Rents: Jan 10 (bicycle, 20000), Nov 22 (car, 30000), Nov 22 (electric car, 60000). */
    private function seedReportRents(): void
    {
        $a = $this->makeUser(['fullName' => 'Renter A']);
        $b = $this->makeUser(['fullName' => 'Renter B']);

        $this->makeRent($a, $this->makeVehicle($a, 'bicycle'), $this->makeGarage('S001', 2000),
            '2026-01-10', '2026-01-20', ['totalCost' => 20000]);
        $this->makeRent($a, $this->makeVehicle($a, 'car'), $this->makeGarage('C001', 10000),
            '2026-11-22', '2026-11-25', ['totalCost' => 30000]);
        $this->makeRent($b, $this->makeVehicle($b, 'electric car'), $this->makeGarage('E001', 15000, 1500),
            '2026-11-22', '2026-11-24', ['totalCost' => 60000]);
    }

    private function report(array $query = [])
    {
        return $this->controller->report($this->makeRequest($query, $this->makeAdmin(), [], 'GET'));
    }

    private function rowsOf(View $view): array
    {
        return array_map(
            fn ($row) => [$row['label'], (int) $row['count'], (int) $row['total']],
            $view->getData()['rows']
        );
    }

    public function testReportDefaultsToRentsPerMonthOfCurrentYear(): void
    {
        $this->seedReportRents();

        $view = $this->report();

        $this->assertSame('admin.reportrent', $view->name());
        $this->assertSame('month', $view->getData()['type']);
        $this->assertSame('Rents per month in 2026', $view->getData()['title']);
        $rows = $this->rowsOf($view);
        $this->assertCount(12, $rows);
        $this->assertSame(['January', 1, 20000], $rows[0]);
        $this->assertSame(['February', 0, 0], $rows[1]);
        $this->assertSame(['November', 2, 90000], $rows[10]);
    }

    public function testReportPerDayCoversEveryDayOfTheMonth(): void
    {
        $this->seedReportRents();

        $view = $this->report(['type' => 'day', 'year' => 2026, 'month' => 11]);

        $this->assertSame('Rents per day in November 2026', $view->getData()['title']);
        $rows = $this->rowsOf($view);
        $this->assertCount(30, $rows);
        $this->assertSame(['1', 0, 0], $rows[0]);
        $this->assertSame(['22', 2, 90000], $rows[21]);
    }

    public function testReportPerGarageTypeHasThreeFixedRows(): void
    {
        $this->seedReportRents();

        $view = $this->report(['type' => 'garage']);

        $this->assertSame([
            ['bicycle', 1, 20000],
            ['car', 1, 30000],
            ['electric car', 1, 60000],
        ], $this->rowsOf($view));
    }

    public function testReportPerRenterIsSortedByTotalDescending(): void
    {
        $this->seedReportRents();

        $view = $this->report(['type' => 'renter']);

        $this->assertSame([
            ['Renter B', 1, 60000],
            ['Renter A', 2, 50000],
        ], $this->rowsOf($view));
    }

    public function testReportWithNoRentsReturnsZeroRows(): void
    {
        $view = $this->report(['type' => 'month']);

        $rows = $this->rowsOf($view);
        $this->assertCount(12, $rows);
        foreach ($rows as [$label, $count, $total]) {
            $this->assertSame(0, $count, "{$label} count");
            $this->assertSame(0, $total, "{$label} total");
        }
    }

    public function testReportInvalidTypeIsRejected(): void
    {
        $this->assertValidationFails(fn () => $this->report(['type' => 'week']), 'type');
    }

    public function testReportMonthOutOfRangeIsRejected(): void
    {
        $this->assertValidationFails(fn () => $this->report(['type' => 'day', 'month' => 13]), 'month');
    }

    public function testReportYearOutOfRangeIsRejected(): void
    {
        $this->assertValidationFails(fn () => $this->report(['year' => 1999]), 'year');
    }
}
