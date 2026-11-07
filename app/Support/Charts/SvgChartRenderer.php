<?php

namespace App\Support\Charts;

class SvgChartRenderer
{
    private const PRIMARY = '#2b6cb0';

    private const PRIMARY_LIGHT = '#dbeafe';

    private const GRID = '#e5e7eb';

    private const TEXT = '#6b7280';

    private const TEXT_DARK = '#212529';

    /**
     * Gráfico de linha/área com a evolução do consumo no período.
     *
     * @param  array<int, array{label: string, full_label?: string, bottles: int}>  $series
     */
    public function trend(array $series): string
    {
        $width = 680;
        $height = 230;
        $padding = ['top' => 18, 'right' => 16, 'bottom' => 34, 'left' => 46];

        $plotWidth = $width - $padding['left'] - $padding['right'];
        $plotHeight = $height - $padding['top'] - $padding['bottom'];
        $baseY = $padding['top'] + $plotHeight;

        $count = count($series);
        $maxValue = max(1, ...array_map(
            fn (array $point) => (int) ($point['bottles'] ?? 0),
            $series ?: [['bottles' => 0]]
        ));
        $axisMax = $this->niceMax($maxValue);

        $points = [];
        foreach ($series as $index => $point) {
            $x = $count > 1
                ? $padding['left'] + ($plotWidth * $index / ($count - 1))
                : $padding['left'] + ($plotWidth / 2);

            $y = $baseY - (((int) ($point['bottles'] ?? 0) / $axisMax) * $plotHeight);
            $points[] = ['x' => $x, 'y' => $y, 'data' => $point];
        }

        $svg = $this->openSvg($width, $height);

        // Linhas de grade e rótulos do eixo Y
        for ($tick = 0; $tick <= 4; $tick++) {
            $value = $axisMax - (($axisMax / 4) * $tick);
            $y = $padding['top'] + (($plotHeight / 4) * $tick);

            $svg .= sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1" />',
                $this->num((float) $padding['left']),
                $this->num((float) $y),
                $this->num((float) ($width - $padding['right'])),
                $this->num((float) $y),
                self::GRID
            );

            $svg .= sprintf(
                '<text x="%s" y="%s" font-size="9" fill="%s" text-anchor="end">%s</text>',
                $this->num((float) ($padding['left'] - 8)),
                $this->num((float) ($y + 3)),
                self::TEXT,
                $this->num(round($value), 0)
            );
        }

        // Área sob a linha
        if ($count > 0) {
            $areaPath = 'M '.$this->num((float) $points[0]['x']).' '.$this->num((float) $baseY);

            foreach ($points as $point) {
                $areaPath .= ' L '.$this->num((float) $point['x']).' '.$this->num((float) $point['y']);
            }

            $areaPath .= ' L '.$this->num((float) $points[$count - 1]['x']).' '.$this->num((float) $baseY).' Z';

            $svg .= sprintf(
                '<path d="%s" fill="%s" stroke="none" />',
                $areaPath,
                self::PRIMARY_LIGHT
            );

            // Linha principal
            $polyline = implode(' ', array_map(
                fn (array $point) => $this->num((float) $point['x']).','.$this->num((float) $point['y']),
                $points
            ));

            $svg .= sprintf(
                '<polyline points="%s" fill="none" stroke="%s" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />',
                $polyline,
                self::PRIMARY
            );

            // Pontos com tooltip nativo do SVG
            foreach ($points as $point) {
                $tooltip = ($point['data']['full_label'] ?? $point['data']['label'] ?? '')
                    .': '.($point['data']['bottles'] ?? 0).' garrafões';

                $svg .= sprintf(
                    '<circle cx="%s" cy="%s" r="2.6" fill="%s"><title>%s</title></circle>',
                    $this->num((float) $point['x']),
                    $this->num((float) $point['y']),
                    self::PRIMARY,
                    $this->escape($tooltip)
                );
            }
        }

        // Rótulos do eixo X (evita poluição visual no modo mensal)
        $labelStep = $count > 12 ? (int) ceil($count / 8) : 1;

        foreach ($points as $index => $point) {
            $isLast = $index === $count - 1;

            if (! $isLast && $index % max(1, $labelStep) !== 0) {
                continue;
            }

            $svg .= sprintf(
                '<text x="%s" y="%s" font-size="9" fill="%s" text-anchor="middle">%s</text>',
                $this->num((float) $point['x']),
                $this->num((float) ($baseY + 18)),
                self::TEXT,
                $this->escape((string) ($point['data']['label'] ?? ''))
            );
        }

        $totalBottles = array_sum(array_map(
            fn (array $point) => (int) ($point['bottles'] ?? 0),
            $series
        ));

        if ($totalBottles <= 0) {
            $svg .= sprintf(
                '<text x="%s" y="%s" font-size="9" fill="%s" text-anchor="middle">Sem consumo concluído no período</text>',
                $this->num((float) ($width / 2)),
                $this->num((float) ($padding['top'] + 14)),
                self::TEXT
            );
        }

        return $svg.$this->closeSvg();
    }

    /**
     * Ranking horizontal dos setores com maior consumo.
     *
     * @param  array<int, array{sector: string, bottles: int, percentage?: float}>  $sectors
     */
    public function sectorBars(array $sectors, int $limit = 8): string
    {
        $sectors = array_slice($sectors, 0, $limit);
        $width = 680;
        $rowHeight = 28;
        $padding = 12;
        $labelWidth = 205;
        $valueWidth = 54;
        $barMaxWidth = $width - $labelWidth - $valueWidth - $padding;
        $height = max(90, ($padding * 2) + (count($sectors) * $rowHeight));

        $svg = $this->openSvg($width, $height);

        if ($sectors === []) {
            $svg .= sprintf(
                '<text x="%s" y="%s" font-size="11" fill="%s" text-anchor="middle">Sem setores com consumo concluído no período</text>',
                $this->num((float) ($width / 2)),
                $this->num((float) ($height / 2)),
                self::TEXT
            );

            return $svg.$this->closeSvg();
        }

        $maxValue = max(1, ...array_map(
            fn (array $sector) => (int) ($sector['bottles'] ?? 0),
            $sectors
        ));

        foreach ($sectors as $index => $sector) {
            $bottles = (int) ($sector['bottles'] ?? 0);
            $label = $this->truncate((string) ($sector['sector'] ?? 'NÃO INFORMADO'), 31);
            $y = $padding + ($index * $rowHeight);
            $barWidth = max(2, ($bottles / $maxValue) * $barMaxWidth);
            $barY = $y + 6;

            $svg .= sprintf(
                '<text x="0" y="%s" font-size="10" fill="%s">%s. %s</text>',
                $this->num((float) ($barY + 10)),
                self::TEXT_DARK,
                $index + 1,
                $this->escape($label)
            );

            // Trilha de fundo
            $svg .= sprintf(
                '<rect x="%s" y="%s" width="%s" height="13" rx="3" fill="#eef2f7" />',
                $this->num((float) $labelWidth),
                $this->num((float) $barY),
                $this->num((float) $barMaxWidth)
            );

            // Barra proporcional
            $svg .= sprintf(
                '<rect x="%s" y="%s" width="%s" height="13" rx="3" fill="%s" />',
                $this->num((float) $labelWidth),
                $this->num((float) $barY),
                $this->num((float) $barWidth),
                self::PRIMARY
            );

            $svg .= sprintf(
                '<text x="%s" y="%s" font-size="10" font-weight="bold" fill="%s">%s</text>',
                $this->num((float) ($labelWidth + $barWidth + 7)),
                $this->num((float) ($barY + 10)),
                self::PRIMARY,
                $this->num($bottles, 0)
            );
        }

        return $svg.$this->closeSvg();
    }

    /**
     * Gráfico de rosca (donut) para divisões percentuais.
     *
     * @param  array<int, array{label: string, value: int|float, color: string}>  $segments
     */
    public function donut(array $segments, string $centerLabel = 'Total', int $size = 190): string
    {
        $center = $size / 2;
        $radius = ($size / 2) - 10;
        $holeRadius = $radius * 0.58;

        $positiveSegments = array_values(array_filter(
            $segments,
            fn (array $segment) => (float) ($segment['value'] ?? 0) > 0
        ));

        $total = array_sum(array_map(
            fn (array $segment) => (float) ($segment['value'] ?? 0),
            $positiveSegments
        ));

        $svg = $this->openSvg($size, $size);

        if ($total <= 0) {
            $svg .= sprintf(
                '<circle cx="%s" cy="%s" r="%s" fill="none" stroke="#eef2f7" stroke-width="24" />',
                $this->num((float) $center),
                $this->num((float) $center),
                $this->num((float) ($radius - 12))
            );
        } elseif (count($positiveSegments) === 1) {
            $svg .= sprintf(
                '<circle cx="%s" cy="%s" r="%s" fill="%s" />',
                $this->num((float) $center),
                $this->num((float) $center),
                $this->num((float) $radius),
                $positiveSegments[0]['color'] ?? self::PRIMARY
            );
        } else {
            $angle = -90.0;

            foreach ($positiveSegments as $segment) {
                $sweep = 360 * ((float) $segment['value'] / $total);
                $endAngle = $angle + $sweep;

                $x1 = $center + ($radius * cos(deg2rad($angle)));
                $y1 = $center + ($radius * sin(deg2rad($angle)));
                $x2 = $center + ($radius * cos(deg2rad($endAngle)));
                $y2 = $center + ($radius * sin(deg2rad($endAngle)));
                $largeArc = $sweep > 180 ? 1 : 0;

                $svg .= sprintf(
                    '<path d="M %s %s L %s %s A %s %s 0 %d 1 %s %s Z" fill="%s" stroke="#ffffff" stroke-width="1" />',
                    $this->num((float) $center),
                    $this->num((float) $center),
                    $this->num((float) $x1),
                    $this->num((float) $y1),
                    $this->num((float) $radius),
                    $this->num((float) $radius),
                    $largeArc,
                    $this->num((float) $x2),
                    $this->num((float) $y2),
                    $segment['color'] ?? self::PRIMARY
                );

                $angle = $endAngle;
            }
        }

        // Furo central para o efeito de rosca
        $svg .= sprintf(
            '<circle cx="%s" cy="%s" r="%s" fill="#ffffff" />',
            $this->num((float) $center),
            $this->num((float) $center),
            $this->num((float) $holeRadius)
        );

        $svg .= sprintf(
            '<text x="%s" y="%s" font-size="20" font-weight="bold" fill="%s" text-anchor="middle">%s</text>',
            $this->num((float) $center),
            $this->num((float) ($center + 4)),
            self::TEXT_DARK,
            $this->num($total, 0)
        );

        $svg .= sprintf(
            '<text x="%s" y="%s" font-size="8" fill="%s" text-anchor="middle">%s</text>',
            $this->num((float) $center),
            $this->num((float) ($center + 18)),
            self::TEXT,
            $this->escape($centerLabel)
        );

        return $svg.$this->closeSvg();
    }

    private function openSvg(int $width, int $height): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img">',
            $width,
            $height,
            $width,
            $height
        );
    }

    private function closeSvg(): string
    {
        return '</svg>';
    }

    private function niceMax(int $value): int
    {
        if ($value <= 0) {
            return 5;
        }

        $magnitude = 10 ** (int) floor(log10($value));
        $normalized = $value / $magnitude;

        $nice = match (true) {
            $normalized <= 1 => 1,
            $normalized <= 2 => 2,
            $normalized <= 5 => 5,
            default => 10,
        };

        // Evita eixos com rótulos repetidos em períodos de consumo muito baixo.
        return max(4, (int) ($nice * $magnitude));
    }

    private function num(float|int $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function truncate(string $value, int $limit): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit - 3, 'UTF-8').'...';
    }
}
