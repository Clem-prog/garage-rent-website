<?php

namespace Tests\Unit;

use App\Http\Controllers\GarageController;
use App\Models\Garage;

class GarageControllerTest extends ControllerTestCase
{
    private GarageController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new GarageController();
    }

    private function add(array $data)
    {
        return $this->controller->addGarage($this->makeRequest($data, $this->makeAdmin()));
    }

    private function idsOfType(string $type): array
    {
        return Garage::where('garageType', $type)->orderBy('id')->pluck('id')->all();
    }

    // addGarage

    public function testAddBicycleGaragesUsesSPrefixAndZeroElectricPrice(): void
    {
        $this->add(['garageType' => 'bicycle', 'rentPrice' => 2000, 'amount' => 3]);

        $this->assertSame(['S001', 'S002', 'S003'], $this->idsOfType('bicycle'));
        foreach (Garage::all() as $garage) {
            $this->assertEquals(2000, $garage->rentPrice);
            $this->assertEquals(0, $garage->electricPrice);
        }
        $this->assertSame('3 garage(s) created.', session('success'));
    }

    // Row 22: car, rentPrice 10000, amount 2, electricPrice 500
    public function testAddCarGaragesUsesCPrefixAndStoresElectricPrice(): void
    {
        $this->add(['garageType' => 'car', 'rentPrice' => 10000, 'amount' => 2, 'electricPrice' => 500]);

        $this->assertSame(['C001', 'C002'], $this->idsOfType('car'));
        foreach (Garage::all() as $garage) {
            $this->assertEquals(10000, $garage->rentPrice);
            $this->assertEquals(500, $garage->electricPrice);
        }
    }

    // Row 23: electric car, amount 1, electricPrice 1500
    public function testAddElectricCarGaragesUsesEPrefix(): void
    {
        $this->add(['garageType' => 'electric car', 'rentPrice' => 15000, 'amount' => 1, 'electricPrice' => 1500]);

        $garage = Garage::findOrFail('E001');
        $this->assertSame('electric car', $garage->garageType);
        $this->assertEquals(1500, $garage->electricPrice);
    }

    // Row 24: C005 already exists, add 2 cars
    public function testAddGarageContinuesNumberingFromLastOfSameType(): void
    {
        $this->makeGarage('C005');

        $this->add(['garageType' => 'car', 'rentPrice' => 10000, 'amount' => 2, 'electricPrice' => 0]);

        $this->assertSame(['C005', 'C006', 'C007'], $this->idsOfType('car'));
    }

    // Row 25: C010 exists, add 1 bicycle
    public function testAddGarageNumberingIsIndependentPerType(): void
    {
        $this->makeGarage('C010');

        $this->add(['garageType' => 'bicycle', 'rentPrice' => 2000, 'amount' => 1]);

        $this->assertSame(['S001'], $this->idsOfType('bicycle'));
    }

    // Row 26: C998 exists, add 1 car
    public function testAddGarageAcceptsThe999LimitExactly(): void
    {
        $this->makeGarage('C998');

        $this->add(['garageType' => 'car', 'rentPrice' => 10000, 'amount' => 1, 'electricPrice' => 0]);

        $this->assertNotNull(Garage::find('C999'));
    }

    // Row 27: C998 exists, add 5 cars
    public function testAddGarageOver999PerTypeIsRejected(): void
    {
        $this->makeGarage('C998');

        $this->add(['garageType' => 'car', 'rentPrice' => 10000, 'amount' => 5, 'electricPrice' => 0]);

        $this->assertSame('Max 999 garages per type. Only 1 left for this type.', $this->sessionError('amount'));
        $this->assertSame(1, Garage::count());
    }

    // Row 28: garageType = 'truck'
    public function testAddGarageInvalidTypeIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['garageType' => 'truck', 'rentPrice' => 10000, 'amount' => 1, 'electricPrice' => 0]),
            'garageType'
        );
    }

    // Row 29: car without electricPrice
    public function testAddCarGarageWithoutElectricPriceIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['garageType' => 'car', 'rentPrice' => 10000, 'amount' => 1]),
            'electricPrice'
        );
    }

    // Row 30: amount = 0
    public function testAddGarageAmountZeroIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['garageType' => 'bicycle', 'rentPrice' => 2000, 'amount' => 0]),
            'amount'
        );
    }

    // Row 31: amount = 1000
    public function testAddGarageAmountAbove999IsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['garageType' => 'bicycle', 'rentPrice' => 2000, 'amount' => 1000]),
            'amount'
        );
    }

    // Row 32: rentPrice = -1
    public function testAddGarageNegativeRentPriceIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['garageType' => 'bicycle', 'rentPrice' => -1, 'amount' => 1]),
            'rentPrice'
        );
    }

    // ------------------------------------------------------- showAllGarages

    // Row 33: S001, S002 (2000), S003 (3000), C001 (10000)
    public function testShowAllGaragesGroupsConsecutiveGaragesIntoBatches(): void
    {
        $this->makeGarage('S001', 2000);
        $this->makeGarage('S002', 2000);
        $this->makeGarage('S003', 3000);
        $this->makeGarage('C001', 10000);

        $view = $this->controller->showAllGarages();

        $this->assertSame('admin.listgarage', $view->name());
        $summary = array_map(
            fn ($b) => [$b['firstId'], $b['lastId'], $b['count']],
            $view->getData()['batches']
        );
        $this->assertSame([
            ['S001', 'S002', 2],
            ['S003', 'S003', 1],
            ['C001', 'C001', 1],
        ], $summary);
    }

    // Row 34: no garages in the database
    public function testShowAllGaragesWithNoGaragesReturnsEmptyList(): void
    {
        $view = $this->controller->showAllGarages();

        $this->assertSame([], $view->getData()['batches']);
    }
}
