<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaterDelivery;
use App\Services\DeliveryReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 15, 10, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_aggregates_monthly_consumption_only_from_completed_orders(): void
    {
        $user = User::factory()->create();

        $this->seedDeliveries();

        $response = $this->actingAs($user)->get('/entregas/relatorios?mode=monthly&year=2026&month=11');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Relatorios')
            ->where('filters.mode', 'monthly')
            ->where('report.period.label', 'Novembro de 2026')
            ->where('report.totals.bottles', 12)
            ->where('report.totals.received_bottles', 19)
            ->where('report.totals.pending_bottles', 7)
            ->where('report.totals.orders', 4)
            ->where('report.totals.concluded_orders', 3)
            ->where('report.totals.pending_orders', 1)
            ->where('report.totals.sectors_served', 2)
            ->where('report.totals.top_sector.sector', 'SECCIONAL A')
            ->where('report.totals.top_sector.bottles', 8)
            ->where('report.operations.PADRAO.bottles', 7)
            ->where('report.operations.BATE_VOLTA.bottles', 5)
            ->where('report.status.CONCLUIDO', 3)
            ->where('report.status.DISPONIVEL', 1)
            ->has('report.series', 30)
            ->where('report.series.9.bottles', 5)
            ->where('report.series.11.bottles', 3)
            ->where('report.series.13.bottles', 4)
            ->where('report.series.14.bottles', 0)
        );

        // O consumo do mês anterior deve alimentar a variação percentual
        $report = app(DeliveryReportService::class)->build([
            'mode' => 'monthly',
            'year' => 2026,
            'month' => 11,
        ]);

        $this->assertSame(10, $report['totals']['previous_bottles']);
        $this->assertEquals(20.0, (float) $report['totals']['variation']);
    }

    public function test_dashboard_builds_annual_series_with_twelve_months(): void
    {
        $user = User::factory()->create();

        $this->seedDeliveries();

        $response = $this->actingAs($user)->get('/entregas/relatorios?mode=annual&year=2026');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Relatorios')
            ->where('filters.mode', 'annual')
            ->where('report.period.label', 'Ano de 2026')
            ->where('report.totals.bottles', 22)
            ->where('report.totals.received_bottles', 29)
            ->where('report.totals.orders', 5)
            ->where('report.totals.concluded_orders', 4)
            ->has('report.series', 12)
            ->where('report.series.9.bottles', 10)
            ->where('report.series.10.bottles', 12)
            ->where('report.series.0.bottles', 0)
            ->where('report.totals.variation', null)
        );
    }

    public function test_attendant_can_access_the_dashboard(): void
    {
        $attendant = User::factory()->create(['role' => 'atendente']);

        $response = $this->actingAs($attendant)->get('/entregas/relatorios');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Relatorios'));
    }

    public function test_invalid_report_filters_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/entregas/relatorios?mode=invalid&month=99');

        $response->assertSessionHasErrors(['mode', 'month']);
    }

    private function seedDeliveries(): void
    {
        // Consumo concluído em novembro/2026
        $this->createDelivery('PCPA-2026-0001', 'SECCIONAL A', 5, 'CONCLUIDO', 'BATE_VOLTA', '2026-11-10 09:00:00');
        $this->createDelivery('PCPA-2026-0002', 'SECCIONAL A', 3, 'CONCLUIDO', 'PADRAO', '2026-11-12 09:00:00');
        $this->createDelivery('PCPA-2026-0003', 'SECCIONAL B', 4, 'CONCLUIDO', 'PADRAO', '2026-11-14 09:00:00');

        // Pendente: entra nos totais operacionais, mas não no consumo
        $this->createDelivery('PCPA-2026-0004', 'SECCIONAL C', 7, 'DISPONIVEL', 'PADRAO', '2026-11-15 09:00:00');

        // Consumo do mês anterior (composição da variação)
        $this->createDelivery('PCPA-2026-0005', 'SECCIONAL A', 10, 'CONCLUIDO', 'PADRAO', '2026-10-20 09:00:00');
    }

    private function createDelivery(
        string $orderNumber,
        string $sector,
        int $bottles,
        string $status,
        string $operationType,
        string $receptionDate,
    ): WaterDelivery {
        $reception = Carbon::parse($receptionDate);
        $isConcluded = $status === 'CONCLUIDO';

        return WaterDelivery::create([
            'order_number' => $orderNumber,
            'operation_type' => $operationType,
            'sector' => $sector,
            'empty_bottles_count' => $bottles,
            'reception_date' => $reception,
            'estimated_date' => $reception->copy()->addDays(2),
            'received_from_person' => 'SERVIDOR TESTE',
            'reception_attendant' => 'ATENDENTE TESTE',
            'delivery_date' => $isConcluded ? $reception->copy()->addDay() : null,
            'delivered_to_person' => $isConcluded ? 'SERVIDOR TESTE' : null,
            'delivery_attendant' => $isConcluded ? 'ATENDENTE TESTE' : null,
            'status' => $status,
        ]);
    }
}
