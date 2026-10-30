<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_are_blocked_until_password_is_changed(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)
            ->get('/entregas/nova')
            ->assertRedirect(route('password.force'));

        $this->actingAs($user)
            ->get('/entregas/baixa')
            ->assertRedirect(route('password.force'));

        $this->actingAs($user)
            ->get('/entregas/relatorios')
            ->assertRedirect(route('password.force'));
    }

    public function test_users_without_pending_change_are_not_blocked(): void
    {
        $user = User::factory()->create();

        // Usuário com senha definida não deve ser redirecionado para a troca obrigatória
        $response = $this->actingAs($user)->get('/entregas/baixa');

        $response->assertOk();
        $this->assertFalse($user->mustChangePassword());
    }

    public function test_forced_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)
            ->get('/alterar-senha')
            ->assertOk();
    }

    public function test_user_can_change_temporary_password_and_unlock_the_system(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $response = $this->actingAs($user)->post('/alterar-senha', [
            'current_password' => 'password',
            'password' => 'NovaSenha@2026',
            'password_confirmation' => 'NovaSenha@2026',
        ]);

        $response->assertRedirect(route('entregas.nova'));
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NovaSenha@2026', $user->password));

        // Sistema liberado após a troca
        $this->actingAs($user)
            ->get('/entregas/baixa')
            ->assertOk();
    }

    public function test_new_password_must_differ_from_temporary_password(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $response = $this->actingAs($user)->post('/alterar-senha', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_temporary_password_must_be_correct(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $response = $this->actingAs($user)->post('/alterar-senha', [
            'current_password' => 'senha-errada',
            'password' => 'NovaSenha@2026',
            'password_confirmation' => 'NovaSenha@2026',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue($user->refresh()->must_change_password);
    }
}
