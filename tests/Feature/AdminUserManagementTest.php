<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/usuarios')
            ->assertOk();
    }

    public function test_attendant_can_not_access_user_management(): void
    {
        $attendant = User::factory()->create(['role' => 'atendente']);

        $this->actingAs($attendant)
            ->get('/admin/usuarios')
            ->assertForbidden();

        $this->actingAs($attendant)
            ->post('/admin/usuarios', $this->validPayload())
            ->assertForbidden();
    }

    public function test_guest_can_not_access_user_management(): void
    {
        $this->get('/admin/usuarios')->assertRedirect('/login');
    }

    public function test_admin_can_create_user_with_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/usuarios', $this->validPayload([
            'username' => 'novo.atendente',
            'matricula' => '123456',
        ]));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHasNoErrors();

        $user = User::where('username', 'novo.atendente')->firstOrFail();

        $this->assertSame('atendente', $user->role);
        $this->assertSame('123456', $user->matricula);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('SenhaTemp@2026', $user->password));
    }

    public function test_username_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'duplicado']);

        $response = $this->actingAs($admin)->post('/admin/usuarios', $this->validPayload([
            'username' => 'duplicado',
        ]));

        $response->assertSessionHasErrors('username');
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAntiga@2026'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/usuarios/{$user->id}/reset-password", [
            'password' => 'NovaTemp@2026',
            'password_confirmation' => 'NovaTemp@2026',
        ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('NovaTemp@2026', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_admin_can_not_reset_own_password_through_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch("/admin/usuarios/{$admin->id}/reset-password", [
            'password' => 'NovaTemp@2026',
            'password_confirmation' => 'NovaTemp@2026',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($admin->refresh()->must_change_password);
    }

    public function test_admin_can_deactivate_and_reactivate_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$user->id}/toggle-active")
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->is_active);

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$user->id}/toggle-active")
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_admin_can_delete_user_but_not_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/usuarios/{$user->id}")
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh());

        $this->actingAs($admin)
            ->delete("/admin/usuarios/{$admin->id}")
            ->assertSessionHas('error');

        $this->assertNotNull($admin->fresh());
    }

    /**
     * Payload padrão para criação de usuário pelo administrador.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Novo Servidor SCEA',
            'username' => 'novo.servidor',
            'matricula' => null,
            'password' => 'SenhaTemp@2026',
            'password_confirmation' => 'SenhaTemp@2026',
            'role' => 'atendente',
            'sector' => 'ALMOXARIFADO CENTRAL',
            'is_active' => true,
        ], $overrides);
    }
}
