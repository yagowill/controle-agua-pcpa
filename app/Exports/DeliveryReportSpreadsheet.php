<?php

namespace App\Exports;

use App\Models\WaterDelivery;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DeliveryReportSpreadsheet
{
    private const HEADER_BG = 'FF2B6CB0';

    private const HEADER_FG = 'FFFFFFFF';

    private const BORDER_COLOR = 'FFCED4DA';

    private const ZEBRA_BG = 'FFF8F9FA';

    /**
     * Monta a planilha completa do relatório com resumo, séries, setores e registros.
     *
     * @param  array<string, mixed>  $report
     * @param  Collection<int, WaterDelivery>  $records
     */
    public function build(array $report, Collection $records, string $userName): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('SCEA - Polícia Civil do Estado do Pará')
            ->setLastModifiedBy($userName)
            ->setTitle('Relatório de Consumo de Água')
            ->setSubject('Consumo mensal/anual por setor')
            ->setDescription('Relatório gerado pelo Sistema de Controle de Entrega de Água (SCEA/PCPA).');

        $this->buildSummarySheet($spreadsheet, $report, $userName);
        $this->buildPeriodSheet($spreadsheet, $report);
        $this->buildSectorSheet($spreadsheet, $report);
        $this->buildRecordsSheet($spreadsheet, $records);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Salva o arquivo XLSX no caminho informado.
     */
    public function save(Spreadsheet $spreadsheet, string $path): void
    {
        (new Xlsx($spreadsheet))->save($path);
    }

    private function buildSummarySheet(Spreadsheet $spreadsheet, array $report, string $userName): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumo');

        $totals = $report['totals'];
        $topSector = $totals['top_sector'];

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'SCEA — RELATÓRIO DE CONSUMO DE ÁGUA');
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => self::HEADER_FG]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Polícia Civil do Estado do Pará — Almoxarifado / Controle de Entrega de Água');
        $sheet->getStyle('A2:D2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle('A2:D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $summaryRows = [
            ['Período', $report['period']['label']],
            ['Intervalo', $report['period']['start_label'].' a '.$report['period']['end_label']],
            ['Gerado em', $report['generated_at']],
            ['Responsável', $userName],
        ];

        $row = 4;
        foreach ($summaryRows as $summaryRow) {
            $this->writeRow($sheet, $row, $summaryRow);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $row++;
        }

        $row++;
        $sheet->mergeCells('A'.$row.':D'.$row);
        $sheet->setCellValue('A'.$row, 'INDICADORES CONSOLIDADOS');
        $this->applyHeaderStyle($sheet, 'A'.$row.':D'.$row);
        $row++;

        $indicators = [
            ['Garrafões consumidos (concluídos)', $totals['bottles'], '#,##0'],
            ['Garrafões recebidos (todos os registros)', $totals['received_bottles'], '#,##0'],
            ['Garrafões pendentes de entrega', $totals['pending_bottles'], '#,##0'],
            ['Total de ordens registradas', $totals['orders'], '#,##0'],
            ['Ordens concluídas', $totals['concluded_orders'], '#,##0'],
            ['Ordens pendentes', $totals['pending_orders'], '#,##0'],
            ['Setores atendidos', $totals['sectors_served'], '#,##0'],
            ['Média de garrafões por ordem concluída', $totals['avg_bottles_per_order'], '#,##0.0'],
            ['Taxa de conclusão', $totals['conclusion_rate'], '0.0"%"'],
            ['Setor com maior consumo', $topSector['sector'] ?? 'Sem dados no período', null],
            ['Consumo do setor líder', $topSector['bottles'] ?? 0, '#,##0'],
            ['Participação do setor líder', $topSector['percentage'] ?? 0, '0.0"%"'],
            ['Consumo no período anterior', $totals['previous_bottles'], '#,##0'],
            ['Variação em relação ao período anterior', $totals['variation'] ?? 0, '+0.0"%";-0.0"%";0.0"%"'],
        ];

        foreach ($indicators as [$label, $value, $format]) {
            $this->writeRow($sheet, $row, [$label, $value]);

            if ($format !== null) {
                $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode($format);
            }

            $row++;
        }

        $this->applyBorder($sheet, 'A4:B'.($row - 1));

        foreach (['A' => 42, 'B' => 34, 'C' => 18, 'D' => 18] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function buildPeriodSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Consumo por Período');

        $this->writeRow($sheet, 1, [
            $report['period']['series_label'],
            'Garrafões consumidos',
            'Ordens concluídas',
        ]);

        $this->applyHeaderStyle($sheet, 'A1:C1');

        $row = 2;
        foreach ($report['series'] as $point) {
            $this->writeRow($sheet, $row, [
                $point['full_label'],
                $point['bottles'],
                $point['orders'],
            ]);
            $row++;
        }

        $lastDataRow = $row - 1;

        $this->writeRow($sheet, $row, [
            'TOTAL',
            $report['totals']['bottles'],
            $report['totals']['concluded_orders'],
        ]);
        $sheet->getStyle('A'.$row.':C'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row.':C'.$row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE7EEF8');

        $this->applyZebra($sheet, 'A2:C'.$lastDataRow);
        $this->applyBorder($sheet, 'A1:C'.$row);

        $sheet->getStyle('B2:B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('C2:C'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:C'.$lastDataRow);
    }

    private function buildSectorSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Consumo por Setor');

        $this->writeRow($sheet, 1, [
            'Posição',
            'Setor / Lotação',
            'Garrafões consumidos',
            'Ordens concluídas',
            'Participação (%)',
        ]);

        $this->applyHeaderStyle($sheet, 'A1:E1');

        $row = 2;
        foreach ($report['sector_ranking'] as $index => $sector) {
            $this->writeRow($sheet, $row, [
                $index + 1,
                $sector['sector'],
                $sector['bottles'],
                $sector['orders'],
                $sector['percentage'],
            ]);
            $row++;
        }

        $lastDataRow = max(1, $row - 1);

        $this->writeRow($sheet, $row, [
            '',
            'TOTAL',
            $report['totals']['bottles'],
            $report['totals']['concluded_orders'],
            100,
        ]);
        $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row.':E'.$row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE7EEF8');

        $this->applyZebra($sheet, 'A2:E'.$lastDataRow);
        $this->applyBorder($sheet, 'A1:E'.$row);

        $sheet->getStyle('C2:C'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:D'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E2:E'.$row)->getNumberFormat()->setFormatCode('0.0"%"');
        $sheet->getStyle('A2:A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(17);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E'.$lastDataRow);
    }

    private function buildRecordsSheet(Spreadsheet $spreadsheet, Collection $records): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Registros');

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

        $this->writeRow($sheet, 1, $headers);
        $this->applyHeaderStyle($sheet, 'A1:L1');

        $row = 2;
        foreach ($records as $delivery) {
            $this->writeRow($sheet, $row, [
                $delivery->order_number,
                $delivery->operation_type,
                $delivery->sector,
                $delivery->empty_bottles_count,
                $delivery->reception_date?->format('d/m/Y H:i') ?? '',
                $delivery->estimated_date?->format('d/m/Y H:i') ?? '',
                $delivery->delivery_date?->format('d/m/Y H:i') ?? '',
                $delivery->delivered_to_person ?? '',
                $delivery->reception_attendant ?? '',
                $delivery->delivery_attendant ?? '',
                $delivery->status,
                $delivery->notes ?? '',
            ]);
            $row++;
        }

        $lastDataRow = max(1, $row - 1);

        $this->applyZebra($sheet, 'A2:L'.$lastDataRow);
        $this->applyBorder($sheet, 'A1:L'.$lastDataRow);
        $sheet->getStyle('D2:D'.$row)->getNumberFormat()->setFormatCode('#,##0');

        $widths = [
            'A' => 18,
            'B' => 15,
            'C' => 38,
            'D' => 12,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 32,
            'I' => 28,
            'J' => 26,
            'K' => 14,
            'L' => 40,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L'.$lastDataRow);
        $sheet->getStyle('A1:L'.$lastDataRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $coordinate = Coordinate::stringFromColumnIndex($index + 1).$row;

            if (is_int($value) || is_float($value)) {
                $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);

                continue;
            }

            $sheet->setCellValueExplicit($coordinate, (string) ($value ?? ''), DataType::TYPE_STRING);
        }
    }

    private function applyHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['argb' => self::HEADER_FG],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::BORDER_COLOR],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    private function applyBorder(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::BORDER_COLOR],
                ],
            ],
        ]);
    }

    private function applyZebra(Worksheet $sheet, string $range): void
    {
        // Evita faixas invertidas quando o período não possui registros.
        if (preg_match('/^[A-Z]+(\d+):[A-Z]+(\d+)$/', $range, $matches)
            && (int) $matches[1] > (int) $matches[2]
        ) {
            return;
        }

        $sheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::ZEBRA_BG],
            ],
        ]);
    }
}
