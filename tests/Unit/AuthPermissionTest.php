<?php

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

class AuthPermissionTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_has_permission_true_when_code_present(): void
    {
        $_SESSION = ['user_id' => 1, 'permissions' => ['crops.view', 'crops.edit']];

        $this->assertTrue(Auth::hasPermission('crops.view'));
    }

    public function test_has_permission_false_when_code_absent(): void
    {
        $_SESSION = ['user_id' => 1, 'permissions' => ['crops.view']];

        $this->assertFalse(Auth::hasPermission('users.delete'));
    }

    public function test_has_permission_false_when_not_logged_in(): void
    {
        $_SESSION = ['permissions' => ['crops.view']];

        $this->assertFalse(Auth::hasPermission('crops.view'));
    }

    public function test_check_false_with_empty_session(): void
    {
        $_SESSION = [];

        $this->assertFalse(Auth::check());
    }

    public function test_check_true_with_user_id_in_session(): void
    {
        $_SESSION = ['user_id' => 42];

        $this->assertTrue(Auth::check());
    }
}
