<template>
  <div class="trend-chart">
    <svg
      :viewBox="`0 0 ${width} ${height}`"
      class="chart-svg"
      role="img"
      aria-label="Evolução do consumo de garrafões no período"
    >
      <defs>
        <linearGradient id="trend-area-gradient" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#2b6cb0" stop-opacity="0.28" />
          <stop offset="100%" stop-color="#2b6cb0" stop-opacity="0.02" />
        </linearGradient>
      </defs>

      <!-- Grade e eixo Y -->
      <g v-for="tick in yTicks" :key="`tick-${tick.index}`">
        <line
          :x1="padding.left"
          :y1="tick.y"
          :x2="width - padding.right"
          :y2="tick.y"
          stroke="#e5e7eb"
          stroke-width="1"
        />
        <text
          :x="padding.left - 8"
          :y="tick.y + 3"
          font-size="10"
          fill="#6b7280"
          text-anchor="end"
        >{{ tick.label }}</text>
      </g>

      <!-- Área e linha -->
      <path v-if="areaPath" :d="areaPath" fill="url(#trend-area-gradient)" />
      <polyline
        v-if="polyline"
        :points="polyline"
        fill="none"
        stroke="#2b6cb0"
        stroke-width="2.5"
        stroke-linejoin="round"
        stroke-linecap="round"
      />

      <!-- Pontos com tooltip -->
      <g v-for="(point, index) in points" :key="`point-${index}`">
        <circle
          :cx="point.x"
          :cy="point.y"
          r="3.2"
          fill="#ffffff"
          stroke="#2b6cb0"
          stroke-width="2"
        >
          <title>{{ point.fullLabel }}: {{ formatNumber(point.bottles) }} garrafões</title>
        </circle>
      </g>

      <!-- Rótulos do eixo X -->
      <text
        v-for="label in xLabels"
        :key="`label-${label.index}`"
        :x="label.x"
        :y="baseY + 20"
        font-size="10"
        fill="#6b7280"
        text-anchor="middle"
      >{{ label.text }}</text>
    </svg>

    <div v-if="isAllZero" class="empty-note">
      Sem consumo concluído no período selecionado.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  series: {
    type: Array,
    required: true,
  },
  mode: {
    type: String,
    default: 'annual',
  },
});

const width = 720;
const height = 250;
const padding = { top: 20, right: 16, bottom: 38, left: 52 };

const plotWidth = width - padding.left - padding.right;
const plotHeight = height - padding.top - padding.bottom;
const baseY = padding.top + plotHeight;

const maxBottles = computed(() => Math.max(
  1,
  ...props.series.map((point) => Number(point.bottles) || 0),
));

const axisMax = computed(() => niceMax(maxBottles.value));

const points = computed(() => {
  const count = props.series.length;

  return props.series.map((point, index) => {
    const x = count > 1
      ? padding.left + (plotWidth * index) / (count - 1)
      : padding.left + plotWidth / 2;

    const bottles = Number(point.bottles) || 0;
    const y = baseY - ((bottles / axisMax.value) * plotHeight);

    return {
      x,
      y,
      bottles,
      label: String(point.label ?? ''),
      fullLabel: String(point.full_label ?? point.label ?? ''),
    };
  });
});

const yTicks = computed(() => Array.from({ length: 5 }, (_, index) => {
  const ratio = index / 4;
  const value = axisMax.value - (axisMax.value * ratio);

  return {
    index,
    y: padding.top + (plotHeight * ratio),
    label: formatNumber(Math.round(value)),
  };
}));

const areaPath = computed(() => {
  if (points.value.length === 0) {
    return '';
  }

  const first = points.value[0];
  const last = points.value[points.value.length - 1];
  const linePoints = points.value.map((point) => `L ${point.x} ${point.y}`).join(' ');

  return `M ${first.x} ${baseY} ${linePoints} L ${last.x} ${baseY} Z`;
});

const polyline = computed(() => points.value
  .map((point) => `${point.x},${point.y}`)
  .join(' '));

const xLabels = computed(() => {
  if (points.value.length === 0) {
    return [];
  }

  const step = points.value.length > 12
    ? Math.ceil(points.value.length / 8)
    : 1;

  return points.value
    .map((point, index) => ({ ...point, index }))
    .filter((point, index) => index === points.value.length - 1 || index % step === 0)
    .map((point) => ({
      index: point.index,
      x: point.x,
      text: point.label,
    }));
});

const isAllZero = computed(() => points.value.every((point) => point.bottles <= 0));

function niceMax(value) {
  if (value <= 0) {
    return 5;
  }

  const magnitude = 10 ** Math.floor(Math.log10(value));
  const normalized = value / magnitude;

  let nice = 10;
  if (normalized <= 1) nice = 1;
  else if (normalized <= 2) nice = 2;
  else if (normalized <= 5) nice = 5;

  // Evita eixos com rótulos repetidos em períodos de consumo muito baixo.
  return Math.max(4, nice * magnitude);
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-BR').format(value);
}
</script>

<style scoped>
.trend-chart {
  width: 100%;
}

.chart-svg {
  display: block;
  width: 100%;
  height: auto;
  overflow: visible;
}

.empty-note {
  margin-top: 4px;
  text-align: center;
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
