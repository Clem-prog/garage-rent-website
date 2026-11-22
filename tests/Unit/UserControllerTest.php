<?php

namespace Tests\Unit;

use App\Http\Controllers\UserController;
use App\Models\User;

class UserControllerTest extends ControllerTestCase
{
    private UserController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new UserController();
        $this->loginAs($this->makeAdmin());
    }

    // reviewUsers

    public function testReviewUsersListsOnlyPendingNonAdminUsers(): void
    {
        $pending1 = $this->makeUser(['isApproved' => false]);
        $pending2 = $this->makeUser(['isApproved' => false]);
        $this->makeUser(['isApproved' => true]);
        $this->makeUser(['isApproved' => false, 'isAdmin' => true]);

        $view = $this->controller->reviewUsers();

        $this->assertSame('admin.reviewuser', $view->name());
        $this->assertEqualsCanonicalizing(
            [$pending1->id, $pending2->id],
            $view->getData()['users']->pluck('id')->all()
        );
    }

    public function testReviewUsersWithNobodyPendingReturnsEmptyList(): void
    {
        $this->makeUser(['isApproved' => true]);

        $view = $this->controller->reviewUsers();

        $this->assertTrue($view->getData()['users']->isEmpty());
    }

    // approve

    public function testApproveMarksPendingUserAsApproved(): void
    {
        $user = $this->makeUser(['isApproved' => false]);

        $this->controller->approve($user);

        $this->assertTrue($user->fresh()->isApproved);
        $this->assertTrue(User::whereKey($user->id)->where('isApproved', true)->exists());
    }

    public function testApproveAlreadyApprovedUserKeepsThemApproved(): void
    {
        $user = $this->makeUser(['isApproved' => true]);

        $this->controller->approve($user);

        $this->assertTrue($user->fresh()->isApproved);
    }

    // decline

    public function testDeclineDeletesPendingNonAdminUser(): void
    {
        $user = $this->makeUser(['isApproved' => false]);

        $this->controller->decline($user);

        $this->assertNull(User::find($user->id));
    }

    public function testDeclineApprovedUserIsForbidden(): void
    {
        $user = $this->makeUser(['isApproved' => true]);

        $this->assertAborts(403, fn () => $this->controller->decline($user));
        $this->assertNotNull(User::find($user->id));
    }

    public function testDeclineAdminAccountIsForbidden(): void
    {
        $admin = $this->makeUser(['isAdmin' => true, 'isApproved' => false]);

        $this->assertAborts(403, fn () => $this->controller->decline($admin));
        $this->assertNotNull(User::find($admin->id));
    }
}
