<?php

namespace Tests\Unit;

use App\Http\Controllers\VehicleController;
use App\Models\User;
use App\Models\Vehicle;

class VehicleControllerTest extends ControllerTestCase
{
    private VehicleController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new VehicleController();
    }

    private function add(array $data, ?User $user = null)
    {
        return $this->controller->addVehicle($this->makeRequest($data, $user ?? $this->makeUser()));
    }

    // addVehicle

    public function testAddBicycleWithoutPlateIsSaved(): void
    {
        $this->add(['type' => 'bicycle', 'brand' => 'Polygon']);

        $vehicle = Vehicle::sole();
        $this->assertSame('bicycle', $vehicle->type);
        $this->assertNull($vehicle->plateNum);
        $this->assertSame('Vehicle added.', session('success'));
    }

    public function testAddBicycleWithPlateDiscardsThePlate(): void
    {
        $this->add(['type' => 'bicycle', 'brand' => 'Polygon', 'plateNum' => 'B 1 XX']);

        $this->assertNull(Vehicle::sole()->plateNum);
    }

    public function testAddMotorcycleWithPlateIsSavedWithoutBattery(): void
    {
        $this->add(['type' => 'motorcycle', 'brand' => 'Honda', 'plateNum' => 'L 1234 AB']);

        $vehicle = Vehicle::sole();
        $this->assertSame('L 1234 AB', $vehicle->plateNum);
        $this->assertNull($vehicle->battCapacity);
        $this->assertNull($vehicle->battMaximum);
    }

    public function testAddCarIgnoresSubmittedBatteryValues(): void
    {
        $this->add([
            'type' => 'car',
            'brand' => 'Toyota',
            'plateNum' => 'L 1 CAR',
            'battCapacity' => 10,
            'battMaximum' => 50,
        ]);

        $vehicle = Vehicle::sole();
        $this->assertNull($vehicle->battCapacity);
        $this->assertNull($vehicle->battMaximum);
    }

    public function testAddElectricCarStoresBatteryValues(): void
    {
        $this->add([
            'type' => 'electric car',
            'brand' => 'Hyundai',
            'plateNum' => 'L 1 EV',
            'battCapacity' => 20,
            'battMaximum' => 60,
        ]);

        $vehicle = Vehicle::sole();
        $this->assertEquals(20, $vehicle->battCapacity);
        $this->assertEquals(60, $vehicle->battMaximum);
    }

    public function testAddVehicleOwnerIsAlwaysTheLoggedInUser(): void
    {
        $me = $this->makeUser();
        $someoneElse = $this->makeUser();

        $this->add([
            'type' => 'car',
            'brand' => 'Toyota',
            'plateNum' => 'L 1 CAR',
            'user_id' => $someoneElse->id,
        ], $me);

        $this->assertSame($me->id, (int) Vehicle::sole()->user_id);
    }

    public function testAddMotorcycleWithoutPlateIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['type' => 'motorcycle', 'brand' => 'Honda']),
            'plateNum'
        );
        $this->assertSame(0, Vehicle::count());
    }

    public function testAddElectricCarWithoutBatteryValuesIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['type' => 'electric car', 'brand' => 'Hyundai', 'plateNum' => 'L 1 EV']),
            ['battCapacity', 'battMaximum']
        );
    }

    public function testAddElectricCarCapacityAboveMaximumIsRejected(): void
    {
        $e = $this->assertValidationFails(
            fn () => $this->add([
                'type' => 'electric car',
                'brand' => 'Hyundai',
                'plateNum' => 'L 1 EV',
                'battCapacity' => 80,
                'battMaximum' => 60,
            ]),
            'battCapacity'
        );

        $this->assertContains(
            'Battery capacity must be less than or equal to battery maximum.',
            $e->errors()['battCapacity']
        );
    }

    public function testAddElectricCarNegativeBatteryIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add([
                'type' => 'electric car',
                'brand' => 'Hyundai',
                'plateNum' => 'L 1 EV',
                'battCapacity' => -5,
                'battMaximum' => 60,
            ]),
            'battCapacity'
        );
    }

    public function testAddVehicleInvalidTypeIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['type' => 'boat', 'brand' => 'Yamaha', 'plateNum' => 'L 1 BT']),
            'type'
        );
    }

    public function testAddVehicleMissingBrandIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['type' => 'car', 'plateNum' => 'L 1 CAR']),
            'brand'
        );
    }

    public function testAddVehiclePlateLongerThan15IsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->add(['type' => 'car', 'brand' => 'Toyota', 'plateNum' => str_repeat('A', 16)]),
            'plateNum'
        );
    }

    // chooseVehicle

    public function testChooseVehicleReturnsOnlyOwnVehicles(): void
    {
        $me = $this->makeUser();
        $mine = $this->makeVehicle($me, 'car');
        $this->makeVehicle($this->makeUser(), 'car');

        $view = $this->controller->chooseVehicle($this->makeRequest([], $me, [], 'GET'));

        $this->assertSame('user.choosevehicle', $view->name());
        $this->assertSame([$mine->id], $view->getData()['vehicles']->pluck('id')->all());
    }

    public function testChooseVehicleWithNoVehiclesReturnsEmptyList(): void
    {
        $me = $this->makeUser();
        $this->makeVehicle($this->makeUser(), 'car');

        $view = $this->controller->chooseVehicle($this->makeRequest([], $me, [], 'GET'));

        $this->assertTrue($view->getData()['vehicles']->isEmpty());
    }

    // viewVehicles

    public function testViewVehiclesReturnsOnlyOwnVehicles(): void
    {
        $me = $this->makeUser();
        $mine = $this->makeVehicle($me, 'motorcycle');
        $this->makeVehicle($this->makeUser(), 'car');

        $view = $this->controller->viewVehicles($this->makeRequest([], $me, [], 'GET'));

        $this->assertSame('user.listvehicle', $view->name());
        $this->assertSame([$mine->id], $view->getData()['vehicles']->pluck('id')->all());
    }
}
