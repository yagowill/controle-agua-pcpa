<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaterDelivery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterDeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/entregas/nova');
        $response->assertRedirect('/login');

        $response = $this->get('/entregas/baixa');
        $response->assertRedirect('/login');

        $response = $this->get('/entregas/relatorios');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_attendant_can_create_standard_delivery(): void
    {
        $user = User::factory()->create([
            'name' => 'MARIA ATENDENTE',
            'role' => 'atendente',
        ]);

        $response = $this->actingAs($user)->post('/entregas', [
            'operation_type' => 'PADRAO',
            'sector' => 'SECCIONAL DE SÃO BRÁS',
            'empty_bottles_count' => 5,
            'received_from_person' => 'JOÃO POLICIAL',
            'notes' => 'TUDO OK',
        ]);

        $response->assertRedirect(route('entregas.nova'));
        $this->assertDatabaseHas('water_deliveries', [
            'operation_type' => 'PADRAO',
            'sector' => 'SECCIONAL DE SÃO BRÁS',
            'empty_bottles_count' => 5,
            'received_from_person' => 'JOÃO POLICIAL',
            'reception_attendant' => 'MARIA ATENDENTE',
            'status' => 'DISPONIVEL',
        ]);
    }

    public function test_authenticated_attendant_can_create_bate_volta_delivery(): void
    {
        $user = User::factory()->create([
            'name' => 'CARLOS ATENDENTE',
            'role' => 'atendente',
        ]);

        $response = $this->actingAs($user)->post('/entregas', [
            'operation_type' => 'BATE_VOLTA',
            'sector' => 'DELEGACIA GERAL (DG)',
            'empty_bottles_count' => 2,
            'received_from_person' => 'PEDRO INVESTIGADOR',
            'delivered_to_person' => 'PEDRO INVESTIGADOR',
        ]);

        $response->assertRedirect(route('entregas.nova'));
        $this->assertDatabaseHas('water_deliveries', [
            'operation_type' => 'BATE_VOLTA',
            'sector' => 'DELEGACIA GERAL (DG)',
            'status' => 'CONCLUIDO',
            'reception_attendant' => 'CARLOS ATENDENTE',
            'delivery_attendant' => 'CARLOS ATENDENTE',
        ]);
    }

    public function test_strict_anti_reuse_rule_blocks_redeem_of_completed_order(): void
    {
        $attendant = User::factory()->create([
            'name' => 'ANA ATENDENTE',
        ]);

        $delivery = WaterDelivery::create([
            'order_number' => 'PCPA-2026-9999',
            'operation_type' => 'PADRAO',
            'sector' => 'NOP',
            'empty_bottles_count' => 3,
            'reception_date' => Carbon::now()->subDays(2),
            'received_from_person' => 'MARCOS',
            'reception_attendant' => 'ANA ATENDENTE',
            'delivery_date' => Carbon::now()->subDay(),
            'delivered_to_person' => 'MARCOS',
            'delivery_attendant' => 'ANA ATENDENTE',
            'status' => 'CONCLUIDO',
        ]);

        $response = $this->actingAs($attendant)->post("/entregas/redeem/{$delivery->order_number}", [
            'delivered_to_person' => 'OUTRO SERVIDOR',
        ]);

        $response->assertSessionHasErrors('message');
        $delivery->refresh();
        $this->assertEquals('MARCOS', $delivery->delivered_to_person);
    }
}

