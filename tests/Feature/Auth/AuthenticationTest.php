<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'joao.silva',
        ]);

        $response = $this->post('/login', [
            'username' => 'joao.silva',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('entregas.nova', absolute: false));
    }

    public function test_username_is_case_insensitive_on_login(): void
    {
        $user = User::factory()->create([
            'username' => 'maria.souza',
        ]);

        $this->post('/login', [
            'username' => 'MARIA.SOUZA',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_users_with_temporary_password_are_redirected_to_password_change(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('password.force'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
