<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

abstract class ControllerTestCase extends TestCase
{
    use RefreshDatabase;

    protected const TODAY = '2026-11-22';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel resets the frozen clock after every test.
        Carbon::setTestNow(Carbon::parse(self::TODAY . ' 09:00:00'));
        Storage::fake('public');
        $this->registerMySqlDateFunctionsForSqlite();
    }

    // Data builders

    protected function makeUser(array $attributes = []): User
    {
        $n = ++$this->sequence;

        $user = new User();
        $user->forceFill(array_merge([
            'nik' => str_pad((string) $n, 16, '0', STR_PAD_LEFT),
            'fullName' => 'User ' . $n,
            'phone' => '0812' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'email' => 'user' . $n . '@example.com',
            'password' => 'secret123',
            'isAdmin' => false,
            'isApproved' => true,
        ], $attributes));
        $user->save();

        return $user->fresh();
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser(array_merge(['isAdmin' => true, 'isApproved' => true], $attributes));
    }

    protected function makeVehicle(User $owner, string $type = 'car', array $attributes = []): Vehicle
    {
        $defaults = [
            'user_id' => $owner->id,
            'type' => $type,
            'brand' => 'Brand ' . (++$this->sequence),
            'plateNum' => $type === 'bicycle' ? null : 'L ' . $this->sequence . ' AB',
            'battCapacity' => $type === 'electric car' ? 20 : null,
            'battMaximum' => $type === 'electric car' ? 60 : null,
        ];

        return Vehicle::create(array_merge($defaults, $attributes));
    }

    /** The garage type is derived from the id prefix: S = bicycle, C = car, E = electric car. */
    protected function makeGarage(string $id, int $rentPrice = 10000, int $electricPrice = 0): Garage
    {
        $type = ['S' => 'bicycle', 'C' => 'car', 'E' => 'electric car'][$id[0]];

        return Garage::create([
            'id' => $id,
            'garageType' => $type,
            'rentPrice' => $rentPrice,
            'electricPrice' => $electricPrice,
        ]);
    }

    protected function makeRent(
        User $user,
        Vehicle $vehicle,
        Garage $garage,
        string $start,
        string $end,
        array $attributes = []
    ): Rent {
        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));

        return Rent::create(array_merge([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => $garage->id,
            'rentType' => 'daily',
            'rentStatus' => 'active',
            'dayAmount' => (int) $days,
            'startDate' => $start,
            'endDate' => $end,
            'paymentProof' => 'payments/existing.jpg',
            'totalCost' => (int) $days * $garage->rentPrice,
        ], $attributes));
    }

    // Request helpers

    /**
     * Build a request the way the router would hand it to a controller:
     * with a session, the logged-in user and any uploaded files.
     */
    protected function makeRequest(array $data = [], ?User $user = null, array $files = [], string $method = 'POST'): Request
    {
        $request = Request::create('/test', $method, $data, [], $files);
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $user);

        if ($user) {
            $this->loginAs($user);
        }

        return $request;
    }

    /** Log a user in on the default guard (same effect as Laravel's actingAs). */
    protected function loginAs(User $user): void
    {
        Auth::setUser($user);
    }

    /** Asserts a file exists on the faked "public" disk. */
    protected function assertStoredOnPublicDisk(?string $path): void
    {
        $this->assertNotEmpty($path, 'No file path was saved.');
        $this->assertTrue(Storage::disk('public')->exists($path), "File '{$path}' was not stored on the public disk.");
    }

    /** A valid payment proof image (no GD extension needed). */
    protected function proofImage(string $name = 'proof.jpg'): File
    {
        return UploadedFile::fake()->create($name, 50, 'image/jpeg');
    }

    protected function proofPdf(): File
    {
        return UploadedFile::fake()->create('proof.pdf', 50, 'application/pdf');
    }

    // Assertions

    /** Asserts the call throws a ValidationException with an error on every given field. */
    protected function assertValidationFails(callable $call, string|array $fields): ValidationException
    {
        try {
            $call();
        } catch (ValidationException $e) {
            foreach ((array) $fields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $e->errors(),
                    "Expected a validation error on '{$field}', got: " . implode(', ', array_keys($e->errors()))
                );
            }

            return $e;
        }

        $this->fail('Expected a ValidationException on ' . implode(', ', (array) $fields) . ', none was thrown.');
    }

    /** Asserts the call aborts with the given HTTP status (e.g. abort(403)). */
    protected function assertAborts(int $status, callable $call): void
    {
        try {
            $call();
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());

            return;
        }

        $this->fail("Expected the call to abort with HTTP {$status}.");
    }

    /** First error flashed to the session for a field by ->withErrors(). */
    protected function sessionError(string $field): ?string
    {
        $errors = session('errors');

        return $errors ? ($errors->first($field) ?: null) : null;
    }

    // SQLite compatibility

    /**
     * The report uses MySQL's MONTH() and DAY(). The test database is SQLite,
     * so register equivalents; on MySQL this does nothing.
     */
    private function registerMySqlDateFunctionsForSqlite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $functions = [
            'MONTH' => fn ($value) => $value === null ? null : (int) date('n', strtotime($value)),
            'DAY' => fn ($value) => $value === null ? null : (int) date('j', strtotime($value)),
        ];

        foreach ($functions as $name => $callback) {
            method_exists($pdo, 'createFunction')
                ? $pdo->createFunction($name, $callback, 1)
                : $pdo->sqliteCreateFunction($name, $callback, 1);
        }
    }
}
