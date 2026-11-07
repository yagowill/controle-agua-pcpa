<?php

namespace App\Services;

use App\Models\WaterDelivery;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeliveryReportService
{
    /**
     * Nomes dos meses em português para exibição nos relatórios.
     *
     * @var array<int, string>
     */
    public const MONTH_NAMES = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    /**
     * Rótulos curtos dos meses para os eixos dos gráficos.
     *
     * @var array<int, string>
     */
    public const MONTH_SHORT_NAMES = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];

    /**
     * Monta todos os indicadores do dashboard em uma única passagem pelos registros.
     *
     * O "consumo" considera somente ordens CONCLUIDAS, ou seja, garrafões
     * que efetivamente foram entregues ao setor solicitante.
     *
     * @param  array{mode: string, year: int, month: int}  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $period = $this->resolvePeriod($filters['mode'], $filters['year'], $filters['month']);
        $records = $this->records($filters['mode'], $filters['year'], $filters['month']);

        $concluded = $records->filter(
            fn (WaterDelivery $delivery) => $delivery->status === 'CONCLUIDO'
        );

        $deliveredBottles = (int) $concluded->sum('empty_bottles_count');
        $receivedBottles = (int) $records->sum('empty_bottles_count');
        $pendingBottles = max(0, $receivedBottles - $deliveredBottles);

        $concludedOrders = $concluded->count();
        $pendingOrders = $records->filter(
            fn (WaterDelivery $delivery) => $delivery->status === 'DISPONIVEL'
        )->count();

        $sectorRanking = $this->buildSectorRanking($concluded, $deliveredBottles);
        $operations = $this->buildOperationTotals($concluded);
        $series = $this->buildSeries($concluded, $filters['mode'], $period);

        $previousDeliveredBottles = (int) WaterDelivery::query()
            ->whereBetween('reception_date', [$period['previous_start'], $period['previous_end']])
            ->where('status', 'CONCLUIDO')
            ->sum('empty_bottles_count');

        $variation = $previousDeliveredBottles > 0
            ? round((($deliveredBottles - $previousDeliveredBottles) / $previousDeliveredBottles) * 100, 1)
            : null;

        $totalOrders = $records->count();

        return [
            'period' => [
                'mode' => $filters['mode'],
                'year' => $filters['year'],
                'month' => $filters['month'],
                'label' => $period['label'],
                'previous_label' => $period['previous_label'],
                'start_label' => $period['start']->format('d/m/Y'),
                'end_label' => $period['end']->format('d/m/Y'),
                'previous_start_label' => $period['previous_start']->format('d/m/Y'),
                'previous_end_label' => $period['previous_end']->format('d/m/Y'),
                'series_label' => $filters['mode'] === 'monthly' ? 'Dia' : 'Mês',
            ],
            'totals' => [
                // Consumo efetivo: apenas ordens concluídas
                'bottles' => $deliveredBottles,
                'received_bottles' => $receivedBottles,
                'pending_bottles' => $pendingBottles,
                'orders' => $totalOrders,
                'concluded_orders' => $concludedOrders,
                'pending_orders' => $pendingOrders,
                'sectors_served' => $sectorRanking->count(),
                'top_sector' => $sectorRanking->first(),
                'avg_bottles_per_order' => $concludedOrders > 0
                    ? round($deliveredBottles / $concludedOrders, 1)
                    : 0.0,
                'conclusion_rate' => $totalOrders > 0
                    ? round(($concludedOrders / $totalOrders) * 100, 1)
                    : 0.0,
                'previous_bottles' => $previousDeliveredBottles,
                'variation' => $variation,
            ],
            'series' => $series,
            'sector_ranking' => $sectorRanking->values()->all(),
            'operations' => $operations,
            'status' => [
                'CONCLUIDO' => $concludedOrders,
                'DISPONIVEL' => $pendingOrders,
            ],
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Retorna os registros do período para as exportações detalhadas.
     *
     * @return Collection<int, WaterDelivery>
     */
    public function records(string $mode, int $year, int $month): Collection
    {
        $period = $this->resolvePeriod($mode, $year, $month);

        return WaterDelivery::query()
            ->whereBetween('reception_date', [$period['start'], $period['end']])
            ->orderBy('reception_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Opções de meses para os seletores do dashboard.
     *
     * @return array<int, array{title: string, value: int}>
     */
    public static function monthOptions(): array
    {
        return collect(self::MONTH_NAMES)
            ->map(fn (string $name, int $month) => ['title' => $name, 'value' => $month])
            ->values()
            ->all();
    }

    /**
     * Calcula os limites do período atual e do período anterior.
     *
     * @return array{
     *     start: Carbon,
     *     end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     label: string,
     *     previous_label: string
     * }
     */
    private function resolvePeriod(string $mode, int $year, int $month): array
    {
        if ($mode === 'monthly') {
            $start = Carbon::create($year, $month, 1, 0, 0, 0);
            $end = $start->copy()->endOfMonth();

            $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
            $previousEnd = $previousStart->copy()->endOfMonth();

            return [
                'start' => $start,
                'end' => $end,
                'previous_start' => $previousStart,
                'previous_end' => $previousEnd,
                'label' => self::MONTH_NAMES[$month].' de '.$year,
                'previous_label' => self::MONTH_NAMES[$previousStart->month].' de '.$previousStart->year,
            ];
        }

        $start = Carbon::create($year, 1, 1, 0, 0, 0);
        $end = $start->copy()->endOfYear();

        $previousStart = $start->copy()->subYear()->startOfYear();
        $previousEnd = $previousStart->copy()->endOfYear();

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'label' => 'Ano de '.$year,
            'previous_label' => 'Ano de '.$previousStart->year,
        ];
    }

    /**
     * Ranking de setores por garrafões efetivamente entregues.
     *
     * @param  Collection<int, WaterDelivery>  $concluded
     * @return Collection<int, array{sector: string, bottles: int, orders: int, percentage: float}>
     */
    private function buildSectorRanking(Collection $concluded, int $deliveredBottles): Collection
    {
        $totalForPercentage = max(1, $deliveredBottles);

        return $concluded
            ->groupBy(fn (WaterDelivery $delivery) => $delivery->sector ?: 'NÃO INFORMADO')
            ->map(function (Collection $group, string $sector) use ($totalForPercentage) {
                $bottles = (int) $group->sum('empty_bottles_count');

                return [
                    'sector' => $sector,
                    'bottles' => $bottles,
                    'orders' => $group->count(),
                    'percentage' => round(($bottles / $totalForPercentage) * 100, 1),
                ];
            })
            ->sortByDesc('bottles')
            ->values();
    }

    /**
     * Totais por tipo de operação considerando o consumo concluído.
     *
     * @param  Collection<int, WaterDelivery>  $concluded
     * @return array<string, array{bottles: int, orders: int}>
     */
    private function buildOperationTotals(Collection $concluded): array
    {
        $totals = [];

        foreach (['PADRAO', 'BATE_VOLTA'] as $operationType) {
            $group = $concluded->filter(
                fn (WaterDelivery $delivery) => $delivery->operation_type === $operationType
            );

            $totals[$operationType] = [
                'bottles' => (int) $group->sum('empty_bottles_count'),
                'orders' => $group->count(),
            ];
        }

        return $totals;
    }

    /**
     * Série temporal de consumo: 12 meses (anual) ou dias do mês (mensal).
     *
     * @param  Collection<int, WaterDelivery>  $concluded
     * @param  array<string, mixed>  $period
     * @return array<int, array{label: string, full_label: string, bottles: int, orders: int}>
     */
    private function buildSeries(Collection $concluded, string $mode, array $period): array
    {
        $series = [];

        if ($mode === 'monthly') {
            // Série mensal: um ponto por dia do mês
            for ($day = 1; $day <= $period['start']->daysInMonth; $day++) {
                $dayRecords = $concluded->filter(
                    fn (WaterDelivery $delivery) => (int) $delivery->reception_date->format('j') === $day
                );

                $series[] = [
                    'label' => (string) $day,
                    'full_label' => str_pad((string) $day, 2, '0', STR_PAD_LEFT).'/'
                        .str_pad((string) $period['start']->month, 2, '0', STR_PAD_LEFT),
                    'bottles' => (int) $dayRecords->sum('empty_bottles_count'),
                    'orders' => $dayRecords->count(),
                ];
            }

            return $series;
        }

        // Série anual: um ponto por mês
        for ($month = 1; $month <= 12; $month++) {
            $monthRecords = $concluded->filter(
                fn (WaterDelivery $delivery) => (int) $delivery->reception_date->format('n') === $month
            );

            $series[] = [
                'label' => self::MONTH_SHORT_NAMES[$month],
                'full_label' => self::MONTH_NAMES[$month],
                'bottles' => (int) $monthRecords->sum('empty_bottles_count'),
                'orders' => $monthRecords->count(),
            ];
        }

        return $series;
    }
}
