<template>
  <div class="sector-ranking">
    <template v-if="sectors.length > 0">
      <div
        v-for="(sector, index) in sectors"
        :key="`${sector.sector}-${index}`"
        class="ranking-row"
      >
        <div class="ranking-rank">{{ index + 1 }}</div>

        <div class="ranking-content">
          <div class="ranking-head">
            <span class="ranking-name" :title="sector.sector">{{ sector.sector }}</span>
            <span class="ranking-value">
              {{ formatNumber(sector.bottles) }}
              <span class="ranking-share">{{ formatPercent(sector.percentage) }}</span>
            </span>
          </div>

          <div class="ranking-track">
            <div
              class="ranking-bar"
              :style="{ width: barWidth(sector.bottles) }"
            ></div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="ranking-empty">
      <v-icon icon="mdi-chart-bar" size="28" class="mb-1" />
      <div>Sem setores com consumo concluído no período.</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  sectors: {
    type: Array,
    required: true,
  },
});

const maxBottles = computed(() => Math.max(
  1,
  ...props.sectors.map((sector) => Number(sector.bottles) || 0),
));

function barWidth(bottles) {
  const percentage = ((Number(bottles) || 0) / maxBottles.value) * 100;
  return `${Math.max(2, percentage)}%`;
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-BR').format(Number(value) || 0);
}

function formatPercent(value) {
  return `${new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  }).format(Number(value) || 0)}%`;
}
</script>

<style scoped>
.sector-ranking {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ranking-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.ranking-rank {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 22px;
  height: 22px;
  border-radius: 50%;
  background: #e7eef8;
  color: #2b6cb0;
  font-size: 0.7rem;
  font-weight: 800;
}

.ranking-content {
  flex: 1 1 auto;
  min-width: 0;
}

.ranking-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 4px;
}

.ranking-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.78rem;
  font-weight: 600;
  color: #212529;
}

.ranking-value {
  flex: 0 0 auto;
  font-size: 0.75rem;
  font-weight: 800;
  color: #2b6cb0;
}

.ranking-share {
  margin-left: 4px;
  font-size: 0.66rem;
  font-weight: 500;
  color: #6b7280;
}

.ranking-track {
  width: 100%;
  height: 8px;
  border-radius: 999px;
  background: #eef2f7;
  overflow: hidden;
}

.ranking-bar {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #2b6cb0 0%, #4a90d9 100%);
  transition: width 0.35s ease;
}

.ranking-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 150px;
  padding: 16px;
  border: 1px dashed #ced4da;
  border-radius: 8px;
  color: #6b7280;
  font-size: 0.8rem;
  text-align: center;
}
</style>
