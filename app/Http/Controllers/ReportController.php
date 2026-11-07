<?php

namespace App\Http\Controllers;

use App\Exports\DeliveryReportSpreadsheet;
use App\Http\Requests\ReportFilterRequest;
use App\Models\WaterDelivery;
use App\Services\DeliveryReportService;
use App\Support\Charts\SvgChartRenderer;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly DeliveryReportService $reportService,
    ) {}

    /**
     * Exibe o dashboard analítico de consumo.
     */
    public function index(ReportFilterRequest $request): InertiaResponse
    {
        $filters = $request->reportFilters();
        $report = $this->reportService->build($filters);

        return Inertia::render('Relatorios', [
            'report' => $report,
            'filters' => $filters,
            'yearOptions' => $this->yearOptions(),
            'monthOptions' => DeliveryReportService::monthOptions(),
        ]);
    }

    /**
     * Gera o relatório em PDF com layout de dashboard e gráficos vetoriais.
     */
    public function downloadPdf(
        ReportFilterRequest $request,
        SvgChartRenderer $chartRenderer,
    ): Response {
        $filters = $request->reportFilters();
        $report = $this->reportService->build($filters);
        $userName = $request->user()?->name ?? 'SISTEMA';

        // O Dompdf não renderiza <svg> inline; os gráficos são embutidos como
        // imagens SVG em data URI para que os vetores sejam preservados no PDF.
        $charts = [
            'trend' => $this->svgDataUri($chartRenderer->trend($report['series'])),
            'sectors' => $this->svgDataUri($chartRenderer->sectorBars($report['sector_ranking'], 8)),
            'operations' => $this->svgDataUri($chartRenderer->donut([
                ['label' => 'Fluxo Padrão', 'value' => $report['operations']['PADRAO']['bottles'], 'color' => '#2b6cb0'],
                ['label' => 'Bate Volta', 'value' => $report['operations']['BATE_VOLTA']['bottles'], 'color' => '#28a745'],
            ], 'garrafões')),
            'status' => $this->svgDataUri($chartRenderer->donut([
                ['label' => 'Concluídas', 'value' => $report['status']['CONCLUIDO'], 'color' => '#28a745'],
                ['label' => 'Pendentes', 'value' => $report['status']['DISPONIVEL'], 'color' => '#ffc107'],
            ], 'ordens')),
        ];

        $html = view('reports.dashboard-pdf', [
            'report' => $report,
            'charts' => $charts,
            'userName' => $userName,
        ])->render();

        $pdf = new Dompdf;
        $pdf->setPaper('A4', 'portrait');
        $pdf->set_option('defaultFont', 'DejaVu Sans');
        $pdf->set_option('isRemoteEnabled', false);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();

        // Rodapé institucional com numeração automática em todas as páginas
        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        $canvas->page_text(
            36,
            $canvas->get_height() - 24,
            'SCEA — Sistema de Controle de Entrega de Água / PCPA — Página {PAGE_NUM} de {PAGE_COUNT}',
            $font,
            7.5,
            [0.42, 0.45, 0.5]
        );

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($filters, 'pdf').'"',
        ]);
    }

    /**
     * Gera a planilha XLSX com resumo, séries, setores e registros detalhados.
     */
    public function downloadXlsx(
        ReportFilterRequest $request,
        DeliveryReportSpreadsheet $spreadsheetExporter,
    ): BinaryFileResponse {
        $filters = $request->reportFilters();
        $report = $this->reportService->build($filters);
        $records = $this->reportService->records($filters['mode'], $filters['year'], $filters['month']);
        $userName = $request->user()?->name ?? 'SISTEMA';

        $spreadsheet = $spreadsheetExporter->build($report, $records, $userName);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'scea-report-');

        if ($temporaryPath === false) {
            abort(500, 'Não foi possível gerar o arquivo temporário da planilha.');
        }

        $spreadsheetExporter->save($spreadsheet, $temporaryPath);

        // Libera a memória utilizada pelas células antes de enviar o arquivo
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()
            ->download(
                $temporaryPath,
                $this->filename($filters, 'xlsx'),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Exporta a base de registros do período em CSV (compatível com Excel pt-BR).
     */
    public function downloadCsv(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->reportFilters();
        $records = $this->reportService->records($filters['mode'], $filters['year'], $filters['month']);

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM UTF-8 para o Excel reconhecer acentos corretamente
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = [
                'Protocolo',
                'Tipo',
                'Setor / Lotação',
                'Garrafões',
                'Data de Recebimento',
                'Previsão de Entrega',
                'Data de Entrega',
                'Entregue Para',
                'Atendente do Recebimento',
                'Atendente da Entrega',
                'Status',
                'Observações',
            ];

            $this->putCsvRow($handle, $headers);

            /** @var WaterDelivery $delivery */
            foreach ($records as $delivery) {
                $this->putCsvRow($handle, [
                    $this->sanitizeCsvValue($delivery->order_number),
                    $this->sanitizeCsvValue($delivery->operation_type),
                    $this->sanitizeCsvValue($delivery->sector),
                    $delivery->empty_bottles_count,
                    $delivery->reception_date?->format('d/m/Y H:i') ?? '',
                    $delivery->estimated_date?->format('d/m/Y H:i') ?? '',
                    $delivery->delivery_date?->format('d/m/Y H:i') ?? '',
                    $this->sanitizeCsvValue($delivery->delivered_to_person),
                    $this->sanitizeCsvValue($delivery->reception_attendant),
                    $this->sanitizeCsvValue($delivery->delivery_attendant),
                    $this->sanitizeCsvValue($delivery->status),
                    $this->sanitizeCsvValue($delivery->notes),
                ]);
            }

            fclose($handle);
        }, $this->filename($filters, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Lista os anos disponíveis para o seletor do dashboard.
     *
     * @return array<int, array{title: string, value: int}>
     */
    private function yearOptions(): array
    {
        $currentYear = Carbon::now()->year;
        $firstReception = WaterDelivery::query()
            ->orderBy('reception_date')
            ->value('reception_date');

        $firstYear = $firstReception
            ? Carbon::parse($firstReception)->year
            : $currentYear;

        $startYear = min($firstYear, $currentYear);

        return collect(range($currentYear, $startYear))
            ->map(fn (int $year) => ['title' => (string) $year, 'value' => $year])
            ->values()
            ->all();
    }

    /**
     * Converte o SVG em data URI para uso em <img> no PDF (Dompdf).
     */
    private function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @param  array{mode: string, year: int, month: int}  $filters
     */
    private function filename(array $filters, string $extension): string
    {
        $suffix = $filters['mode'] === 'monthly'
            ? sprintf('%04d-%02d', $filters['year'], $filters['month'])
            : (string) $filters['year'];

        return "relatorio-consumo-{$suffix}.{$extension}";
    }

    /**
     * @param  resource  $handle
     * @param  array<int, mixed>  $row
     */
    private function putCsvRow($handle, array $row): void
    {
        fputcsv($handle, $row, ';', '"', '\\', "\r\n");
    }

    /**
     * Evita que valores iniciados por caracteres especiais sejam interpretados como fórmula no Excel.
     */
    private function sanitizeCsvValue(?string $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
