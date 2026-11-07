<template>
  <div class="donut-wrapper">
    <svg
      :viewBox="`0 0 ${size} ${size}`"
      class="donut-svg"
      role="img"
      :aria-label="`Distribuição: ${centerLabel}`"
    >
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        fill="none"
        stroke="#eef2f7"
        :stroke-width="strokeWidth"
      />

      <circle
        v-for="segment in visibleSegments"
        :key="segment.label"
        :cx="center"
        :cy="center"
        :r="radius"
        fill="none"
        :stroke="segment.color"
        :stroke-width="strokeWidth"
        :stroke-dasharray="segment.dashArray"
        :stroke-dashoffset="segment.dashOffset"
        :transform="`rotate(-90 ${center} ${center})`"
      >
        <title>{{ segment.label }}: {{ formatNumber(segment.value) }} ({{ percentage(segment.value) }}%)</title>
      </circle>

      <text
        :x="center"
        :y="center + 4"
        text-anchor="middle"
        font-size="22"
        font-weight="800"
        fill="#212529"
      >{{ formatNumber(total) }}</text>
      <text
        :x="center"
        :y="center + 20"
        text-anchor="middle"
        font-size="10"
        fill="#6b7280"
      >{{ centerLabel }}</text>
    </svg>

    <div v-if="showLegend" class="donut-legend">
      <div
        v-for="segment in segments"
        :key="`legend-${segment.label}`"
        class="legend-item"
      >
        <span class="legend-dot" :style="{ backgroundColor: segment.color }"></span>
        <span class="legend-label">{{ segment.label }}</span>
        <span class="legend-value">{{ formatNumber(segment.value) }}</span>
        <span class="legend-percent">{{ percentage(segment.value) }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  segments: {
    type: Array,
    required: true,
  },
  centerLabel: {
    type: String,
    default: 'Total',
  },
  showLegend: {
    type: Boolean,
    default: true,
  },
});

const size = 180;
const center = size / 2;
const radius = 66;
const strokeWidth = 24;
const circumference = 2 * Math.PI * radius;

const total = computed(() => props.segments.reduce(
  (sum, segment) => sum + (Number(segment.value) || 0),
  0,
));

const visibleSegments = computed(() => {
  let offset = 0;

  return props.segments
    .filter((segment) => (Number(segment.value) || 0) > 0)
    .map((segment) => {
      const value = Number(segment.value) || 0;
      const length = total.value > 0
        ? (value / total.value) * circumference
        : 0;

      const item = {
        ...segment,
        value,
        dashArray: `${length} ${Math.max(0, circumference - length)}`,
        dashOffset: -offset,
      };

      offset += length;

      return item;
    });
});

function formatNumber(value) {
  return new Intl.NumberFormat('pt-BR').format(Number(value) || 0);
}

function percentage(value) {
  if (total.value <= 0) {
    return '0,0';
  }

  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  }).format(((Number(value) || 0) / total.value) * 100);
}
</script>

<style scoped>
.donut-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.donut-svg {
  display: block;
  width: 180px;
  max-width: 100%;
  height: auto;
}

.donut-legend {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.74rem;
}

.legend-dot {
  flex: 0 0 8px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend-label {
  flex: 1 1 auto;
  color: #4b5563;
}

.legend-value {
  font-weight: 700;
  color: #212529;
}

.legend-percent {
  min-width: 46px;
  text-align: right;
  color: #6b7280;
}
</style>
