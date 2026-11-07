<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Consumo de Água - SCEA/PCPA</title>
    <style>
        @page {
            margin: 16mm 13mm 15mm 13mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #212529;
            background: #ffffff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .org-name {
            font-size: 13px;
            font-weight: bold;
            color: #212529;
            letter-spacing: 0.4px;
        }

        .org-system {
            font-size: 8.5px;
            color: #4b5563;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
            font-size: 7.5px;
            color: #6b7280;
            line-height: 1.5;
        }

        .header-line {
            border: 0;
            border-top: 2px solid #2b6cb0;
            margin: 0 0 10px 0;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #2b6cb0;
            margin: 0 0 2px 0;
        }

        .report-period {
            font-size: 9px;
            color: #4b5563;
            margin-bottom: 10px;
        }

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 5px;
            margin: 0 -5px;
        }

        .kpi-table td {
            width: 33.33%;
            vertical-align: top;
        }

        .kpi-card {
            border: 1px solid #ced4da;
            border-left: 4px solid #2b6cb0;
            background: #ffffff;
            padding: 7px 8px;
            min-height: 52px;
        }

        .kpi-card.kpi-success {
            border-left-color: #28a745;
        }

        .kpi-card.kpi-warning {
            border-left-color: #ffc107;
        }

        .kpi-card.kpi-secondary {
            border-left-color: #212529;
        }

        .kpi-label {
            font-size: 7px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .kpi-value {
            font-size: 17px;
            font-weight: bold;
            color: #212529;
            margin-top: 2px;
        }

        .kpi-value.primary {
            color: #2b6cb0;
        }

        .kpi-hint {
            font-size: 6.8px;
            color: #6b7280;
            margin-top: 1px;
        }

        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #212529;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 3px;
            margin: 14px 0 7px 0;
        }

        .section {
            page-break-inside: avoid;
        }

        .chart-box {
            border: 1px solid #ced4da;
            background: #fbfcfe;
            padding: 6px;
            text-align: center;
        }

        .chart-image {
            display: block;
            width: 100%;
            height: auto;
        }

        .chart-image--donut {
            display: inline-block;
            width: 170px;
            height: 170px;
        }

        .two-columns {
            width: 100%;
            border-collapse: collapse;
        }

        .two-columns > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .two-columns > tbody > tr > td:first-child {
            padding-right: 5px;
        }

        .two-columns > tbody > tr > td:last-child {
            padding-left: 5px;
        }

        .legend {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 8px;
        }

        .legend td {
            padding: 2px 3px;
            vertical-align: middle;
        }

        .legend .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
        }

        .legend .value {
            text-align: right;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .data-table thead th {
            background: #2b6cb0;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 4px;
            border: 1px solid #2b6cb0;
        }

        .data-table tbody td {
            padding: 4px;
            border: 1px solid #ced4da;
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8f9fa;
        }

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        .data-table .center {
            text-align: center;
        }

        .data-table .right {
            text-align: right;
        }

        .data-table .total-row td {
            background: #e7eef8;
            font-weight: bold;
        }

        .empty-state {
            border: 1px dashed #ced4da;
            background: #f8f9fa;
            padding: 16px;
            text-align: center;
            color: #6b7280;
            font-size: 9px;
        }

        .note {
            margin-top: 8px;
            font-size: 7px;
            color: #6b7280;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="org-name">POLÍCIA CIVIL DO ESTADO DO PARÁ</div>
                <div class="org-system">SCEA — Sistema de Controle de Entrega de Água / Almoxarifado</div>
            </td>
            <td class="header-meta">
                Relatório gerado em {{ $report['generated_at'] }}<br>
                Responsável: {{ $userName }}<br>
                Período: {{ $report['period']['label'] }}
            </td>
        </tr>
    </table>

    <hr class="header-line">

    <div class="report-title">Relatório de Consumo de Água</div>
    <div class="report-period">
        {{ $report['period']['label'] }}
        ({{ $report['period']['start_label'] }} a {{ $report['period']['end_label'] }})
        — consumo calculado somente sobre ordens concluídas
    </div>

    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-card">
                    <div class="kpi-label">Garrafões consumidos</div>
                    <div class="kpi-value primary">{{ number_format($report['totals']['bottles'], 0, ',', '.') }}</div>
                    <div class="kpi-hint">Ordens concluídas no período</div>
                </div>
            </td>
            <td>
                <div class="kpi-card kpi-success">
                    <div class="kpi-label">Setores atendidos</div>
                    <div class="kpi-value">{{ number_format($report['totals']['sectors_served'], 0, ',', '.') }}</div>
                    <div class="kpi-hint">Setores com consumo concluído</div>
                </div>
            </td>
            <td>
                <div class="kpi-card kpi-secondary">
                    <div class="kpi-label">Setor com maior consumo</div>
                    <div class="kpi-value" style="font-size: 11px;">
                        {{ $report['totals']['top_sector']['sector'] ?? 'Sem dados' }}
                    </div>
                    <div class="kpi-hint">
                        @if ($report['totals']['top_sector'])
                            {{ number_format($report['totals']['top_sector']['bottles'], 0, ',', '.') }} garrafões
                            ({{ number_format($report['totals']['top_sector']['percentage'], 1, ',', '.') }}%)
                        @else
                            Nenhum consumo concluído
                        @endif
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-card">
                    <div class="kpi-label">Ordens registradas</div>
                    <div class="kpi-value">{{ number_format($report['totals']['orders'], 0, ',', '.') }}</div>
                    <div class="kpi-hint">
                        {{ number_format($report['totals']['concluded_orders'], 0, ',', '.') }} concluídas /
                        {{ number_format($report['totals']['pending_orders'], 0, ',', '.') }} pendentes
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card kpi-warning">
                    <div class="kpi-label">Garrafões pendentes</div>
                    <div class="kpi-value">{{ number_format($report['totals']['pending_bottles'], 0, ',', '.') }}</div>
                    <div class="kpi-hint">Aguardando retirada do garrafão cheio</div>
                </div>
            </td>
            <td>
                <div class="kpi-card kpi-success">
                    <div class="kpi-label">Variação vs. período anterior</div>
                    <div class="kpi-value" style="color: {{ ($report['totals']['variation'] ?? 0) < 0 ? '#dc3545' : '#28a745' }};">
                        @if ($report['totals']['variation'] === null)
                            —
                        @else
                            {{ $report['totals']['variation'] > 0 ? '+' : '' }}{{ number_format($report['totals']['variation'], 1, ',', '.') }}%
                        @endif
                    </div>
                    <div class="kpi-hint">
                        Comparado com {{ $report['period']['previous_label'] }}
                        ({{ number_format($report['totals']['previous_bottles'], 0, ',', '.') }} garrafões)
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Evolução do consumo — {{ $report['period']['label'] }}</div>
        <div class="chart-box">
            <img class="chart-image" src="{{ $charts['trend'] }}" alt="Evolução do consumo no período">
        </div>
    </div>

    <div class="section">
        <table class="two-columns">
            <tr>
                <td>
                    <div class="section-title">Consumo por tipo de operação</div>
                    <div class="chart-box">
                        <img class="chart-image chart-image--donut" src="{{ $charts['operations'] }}" alt="Consumo por tipo de operação">
                        <table class="legend">
                            <tr>
                                <td style="width: 12px;"><span class="dot" style="background: #2b6cb0;"></span></td>
                                <td>Fluxo Padrão</td>
                                <td class="value">{{ number_format($report['operations']['PADRAO']['bottles'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><span class="dot" style="background: #28a745;"></span></td>
                                <td>Bate Volta</td>
                                <td class="value">{{ number_format($report['operations']['BATE_VOLTA']['bottles'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="section-title">Situação das ordens</div>
                    <div class="chart-box">
                        <img class="chart-image chart-image--donut" src="{{ $charts['status'] }}" alt="Situação das ordens">
                        <table class="legend">
                            <tr>
                                <td style="width: 12px;"><span class="dot" style="background: #28a745;"></span></td>
                                <td>Concluídas</td>
                                <td class="value">{{ number_format($report['status']['CONCLUIDO'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><span class="dot" style="background: #ffc107;"></span></td>
                                <td>Pendentes</td>
                                <td class="value">{{ number_format($report['status']['DISPONIVEL'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Setores com maior consumo</div>

        @if (count($report['sector_ranking']) > 0)
            <div class="chart-box">
                <img class="chart-image" src="{{ $charts['sectors'] }}" alt="Setores com maior consumo">
            </div>

            <table class="data-table" style="margin-top: 6px;">
                <thead>
                    <tr>
                        <th style="width: 8%;" class="center">#</th>
                        <th>Setor / Lotação</th>
                        <th style="width: 15%;" class="right">Garrafões</th>
                        <th style="width: 13%;" class="center">Ordens</th>
                        <th style="width: 15%;" class="right">Participação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['sector_ranking'] as $index => $sector)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ $sector['sector'] }}</td>
                            <td class="right">{{ number_format($sector['bottles'], 0, ',', '.') }}</td>
                            <td class="center">{{ number_format($sector['orders'], 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($sector['percentage'], 1, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td class="right">{{ number_format($report['totals']['bottles'], 0, ',', '.') }}</td>
                        <td class="center">{{ number_format($report['totals']['concluded_orders'], 0, ',', '.') }}</td>
                        <td class="right">100%</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty-state">
                Nenhum consumo concluído foi registrado para o período selecionado.
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Detalhamento por {{ strtolower($report['period']['series_label']) }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">{{ $report['period']['series_label'] }}</th>
                    <th style="width: 35%;" class="right">Garrafões consumidos</th>
                    <th style="width: 35%;" class="center">Ordens concluídas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['series'] as $point)
                    <tr>
                        <td>{{ $point['full_label'] }}</td>
                        <td class="right">{{ number_format($point['bottles'], 0, ',', '.') }}</td>
                        <td class="center">{{ number_format($point['orders'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL DO PERÍODO</td>
                    <td class="right">{{ number_format($report['totals']['bottles'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($report['totals']['concluded_orders'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="note">
        Consumo calculado exclusivamente sobre ordens com status CONCLUIDO. Os totais de garrafões recebidos
        e pendentes são apresentados apenas como indicadores operacionais. Relatório gerado automaticamente
        pelo SCEA/PCPA em {{ $report['generated_at'] }}.
    </div>
</body>
</html>
