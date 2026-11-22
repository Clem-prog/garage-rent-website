<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends ControllerTestCase
{
    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'nik' => '3578010101010001',
            'fullName' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    // login
    public function testLoginApprovedUserRedirectsToHome(): void
    {
        $user = $this->makeUser(['email' => 'ok@example.com', 'isApproved' => true]);

        $response = $this->controller->login($this->makeRequest([
            'email' => 'ok@example.com',
            'password' => 'secret123',
        ]));

        $this->assertStringEndsWith('/home', $response->getTargetUrl());
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function testLoginUnapprovedUserIsRejectedAndLoggedOut(): void
    {
        $this->makeUser(['email' => 'pending@example.com', 'isApproved' => false]);

        $this->controller->login($this->makeRequest([
            'email' => 'pending@example.com',
            'password' => 'secret123',
        ]));

        $this->assertTrue(Auth::guest());
        $this->assertSame('Your account is not approved yet.', $this->sessionError('email'));
    }

    public function testLoginWrongPasswordIsRejected(): void
    {
        $this->makeUser(['email' => 'ok@example.com']);

        $this->controller->login($this->makeRequest([
            'email' => 'ok@example.com',
            'password' => 'wrong-password',
        ]));

        $this->assertTrue(Auth::guest());
        $this->assertSame('Wrong email or password.', $this->sessionError('email'));
    }

    public function testLoginUnknownEmailIsRejected(): void
    {
        $this->controller->login($this->makeRequest([
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ]));

        $this->assertTrue(Auth::guest());
        $this->assertSame('Wrong email or password.', $this->sessionError('email'));
    }

    public function testLoginInvalidEmailFormatFailsValidation(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->login($this->makeRequest([
                'email' => 'not-an-email',
                'password' => 'secret123',
            ])),
            'email'
        );
    }

    public function testLoginMissingPasswordFailsValidation(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->login($this->makeRequest(['email' => 'ok@example.com'])),
            'password'
        );
    }

    // register

    public function testRegisterValidDataCreatesUserAndRedirectsToLogin(): void
    {
        $response = $this->controller->register($this->makeRequest($this->validRegistration()));

        $this->assertTrue(User::where([
            'email' => 'budi@example.com',
            'nik' => '3578010101010001',
            'fullName' => 'Budi Santoso',
        ])->exists());
        $this->assertStringEndsWith('/login', $response->getTargetUrl());
        $this->assertSame(
            'Registration successful. Please wait for admin approval.',
            session('success')
        );
    }

    public function testRegisterNewAccountIsNotApprovedByDefault(): void
    {
        $this->controller->register($this->makeRequest($this->validRegistration()));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertFalse($user->isApproved);
        $this->assertFalse($user->isAdmin);
    }

    public function testRegisterPasswordIsStoredHashed(): void
    {
        $this->controller->register($this->makeRequest($this->validRegistration()));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function testRegisterNikMustBe16Characters(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest($this->validRegistration(['nik' => '12345']))),
            'nik'
        );
        $this->assertSame(0, User::count());
    }

    public function testRegisterDuplicateEmailIsRejected(): void
    {
        $this->makeUser(['email' => 'budi@example.com']);

        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest($this->validRegistration())),
            'email'
        );
        $this->assertSame(1, User::count());
    }

    public function testRegisterDuplicateNikIsRejected(): void
    {
        $this->makeUser(['nik' => '3578010101010001']);

        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest($this->validRegistration())),
            'nik'
        );
        $this->assertSame(1, User::count());
    }

    public function testRegisterPhoneShorterThan10IsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest($this->validRegistration(['phone' => '08123']))),
            'phone'
        );
    }

    public function testRegisterPasswordConfirmationMismatchIsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest(
                $this->validRegistration(['password_confirmation' => 'different123'])
            )),
            'password'
        );
    }

    public function testRegisterPasswordShorterThan8IsRejected(): void
    {
        $this->assertValidationFails(
            fn () => $this->controller->register($this->makeRequest(
                $this->validRegistration(['password' => 'short', 'password_confirmation' => 'short'])
            )),
            'password'
        );
    }

    // logout

    public function testLogoutClearsAuthAndRedirectsToLogin(): void
    {
        $user = $this->makeUser();
        $request = $this->makeRequest([], $user);
        $this->assertTrue(Auth::check());

        $response = $this->controller->logout($request);

        $this->assertTrue(Auth::guest());
        $this->assertStringEndsWith('/login', $response->getTargetUrl());
    }
}
