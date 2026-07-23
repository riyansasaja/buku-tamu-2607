<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('admin@ptamanado.com|127.0.0.1');

        parent::tearDown();
    }

    public function test_active_admin_can_sign_in_and_reach_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Tampilkan password"', false);
        $previousSessionId = session()->getId();

        $this->post('/login', [
            'email' => ' ADMIN@PTAMANADO.COM ',
            'password' => 'testing-password-12345',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($previousSessionId, session()->getId());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee($admin->name);
    }

    public function test_password_is_hashed_and_hidden_from_serialization(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'testing-password-12345']);

        $this->assertNotSame('testing-password-12345', $admin->password);
        $this->assertTrue(Hash::check('testing-password-12345', $admin->password));
        $this->assertArrayNotHasKey('password', $admin->toArray());
    }

    public function test_invalid_credentials_are_rejected_with_a_generic_message(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Email atau password tidak valid.',
            ]);

        $this->assertGuest();
    }

    public function test_inactive_admin_is_rejected_with_the_same_generic_message(): void
    {
        User::factory()->admin()->inactive()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Email atau password tidak valid.',
            ]);

        $this->assertGuest();
    }

    public function test_non_admin_is_rejected_with_the_same_generic_message(): void
    {
        User::factory()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
            'role' => UserRole::Employee,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Email atau password tidak valid.',
            ]);

        $this->assertGuest();
    }

    public function test_sixth_failed_attempt_within_a_minute_is_rate_limited(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'admin@ptamanado.com',
                'password' => 'wrong-password-'.$attempt,
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_successful_login_clears_the_failed_attempt_counter(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ]);

        $this->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => 'admin@ptamanado.com',
            'password' => 'testing-password-12345',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertSame(0, RateLimiter::attempts('admin@ptamanado.com|127.0.0.1'));
    }

    public function test_guest_cannot_access_the_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_loses_access_as_soon_as_the_account_is_deactivated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();

        $admin->is_active = false;
        $admin->save();

        $this->get('/admin')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_sign_out_and_the_session_is_invalidated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_authentication_mutations_are_registered_with_csrf_middleware(): void
    {
        $loginMiddleware = app('router')->getRoutes()->getByName('login.store')?->gatherMiddleware() ?? [];
        $logoutMiddleware = app('router')->getRoutes()->getByName('logout')?->gatherMiddleware() ?? [];

        $this->assertContains('web', $loginMiddleware);
        $this->assertContains('web', $logoutMiddleware);
        $this->assertSame(['POST'], app('router')->getRoutes()->getByName('login.store')?->methods());
        $this->assertSame(['POST'], app('router')->getRoutes()->getByName('logout')?->methods());
    }
}
