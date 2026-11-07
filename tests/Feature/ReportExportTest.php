<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaterDelivery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExportTest extends TestCase
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

    public function test_pdf_export_returns_a_downloadable_pdf(): void
    {
        $user = User::factory()->create();

        $this->createDelivery('PCPA-2026-0001', 'SECCIONAL A', 5, 'CONCLUIDO');
        $this->createDelivery('PCPA-2026-0002', 'SECCIONAL B', 3, 'DISPONIVEL');

        $response = $this->actingAs($user)->get('/entregas/relatorios/pdf?mode=monthly&year=2026&month=11');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="relatorio-consumo-2026-11.pdf"');

        $pdf = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);

        // Garante que os gráficos foram convertidos em vetores no PDF,
        // e não silenciosamente descartados pelo Dompdf.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

        $vectorOperators = 0;
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);

            if ($decoded === false) {
                continue;
            }

            $vectorOperators += substr_count($decoded, " m\n")
                + substr_count($decoded, " l\n")
                + substr_count($decoded, " c\n");
        }

        $this->assertGreaterThan(0, $vectorOperators, 'O PDF deve conter os vetores dos gráficos.');
    }

    public function test_xlsx_export_returns_a_valid_spreadsheet_file(): void
    {
        $user = User::factory()->create();

        $this->createDelivery('PCPA-2026-0001', 'SECCIONAL A', 5, 'CONCLUIDO');

        $response = $this->actingAs($user)->get('/entregas/relatorios/xlsx?mode=annual&year=2026');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertDownload('relatorio-consumo-2026.xlsx');

        $file = $response->getFile();
        $this->assertNotNull($file);
        $this->assertStringStartsWith('PK', (string) file_get_contents($file->getPathname()));

        $spreadsheet = IOFactory::load($file->getPathname());

        $this->assertSame(
            ['Resumo', 'Consumo por Período', 'Consumo por Setor', 'Registros'],
            $spreadsheet->getSheetNames()
        );

        $recordsSheet = $spreadsheet->getSheetByName('Registros');
        $this->assertSame('PCPA-2026-0001', $recordsSheet->getCell('A2')->getValue());
        $this->assertSame('SECCIONAL A', $recordsSheet->getCell('C2')->getValue());
        $this->assertEquals(5, $recordsSheet->getCell('D2')->getValue());
        $this->assertSame('CONCLUIDO', $recordsSheet->getCell('K2')->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    public function test_csv_export_contains_the_filtered_records(): void
    {
        $user = User::factory()->create();

        $this->createDelivery('PCPA-2026-0001', 'SECCIONAL A', 5, 'CONCLUIDO');
        $this->createDelivery('PCPA-2026-0002', 'SECCIONAL B', 3, 'DISPONIVEL');

        $response = $this->actingAs($user)->get('/entregas/relatorios/csv?mode=monthly&year=2026&month=11');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('relatorio-consumo-2026-11.csv');

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Protocolo;Tipo;', $content);
        $this->assertStringContainsString('Setor / Lotação', $content);
        $this->assertStringContainsString('PCPA-2026-0001', $content);
        $this->assertStringContainsString('PCPA-2026-0002', $content);
        $this->assertStringContainsString('SECCIONAL A', $content);
        $this->assertStringContainsString('DISPONIVEL', $content);
    }

    public function test_exports_handle_a_period_without_records(): void
    {
        $user = User::factory()->create();

        $pdf = $this->actingAs($user)->get('/entregas/relatorios/pdf?mode=monthly&year=2025&month=1');
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');

        $xlsx = $this->actingAs($user)->get('/entregas/relatorios/xlsx?mode=annual&year=2025');
        $xlsx->assertOk();
        $xlsx->assertDownload('relatorio-consumo-2025.xlsx');

        $csv = $this->actingAs($user)->get('/entregas/relatorios/csv?mode=monthly&year=2025&month=1');
        $csv->assertOk();
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Protocolo', $csv->streamedContent());
    }

    private function createDelivery(
        string $orderNumber,
        string $sector,
        int $bottles,
        string $status,
    ): WaterDelivery {
        $reception = Carbon::parse('2026-11-10 09:00:00');
        $isConcluded = $status === 'CONCLUIDO';

        return WaterDelivery::create([
            'order_number' => $orderNumber,
            'operation_type' => 'PADRAO',
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
