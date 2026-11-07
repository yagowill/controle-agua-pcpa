<template>
  <AppLayout>
    <v-progress-linear
      v-if="loading"
      indeterminate
      color="primary"
      class="mb-3"
      rounded
    ></v-progress-linear>

    <!-- FILTROS DO RELATÓRIO -->
    <v-card class="elevation-1 border rounded mb-4">
      <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex flex-column flex-sm-row justify-space-between align-sm-center ga-2">
        <span class="text-subtitle-2 font-weight-bold text-secondary">
          <v-icon icon="mdi-chart-box-outline" class="me-1"></v-icon>
          Relatórios de Consumo
        </span>

        <div class="d-flex flex-wrap align-center ga-2">
          <v-btn
            color="error"
            variant="tonal"
            size="small"
            prepend-icon="mdi-file-pdf-box"
            :disabled="loading"
            @click="downloadReport('pdf')"
          >
            PDF
          </v-btn>

          <v-btn
            color="success"
            variant="tonal"
            size="small"
            prepend-icon="mdi-microsoft-excel"
            :disabled="loading"
            @click="downloadReport('xlsx')"
          >
            XLSX
          </v-btn>

          <v-btn
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="mdi-file-delimited-outline"
            :disabled="loading"
            @click="downloadReport('csv')"
          >
            CSV
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text class="pa-4">
        <v-row dense align="center">
          <v-col cols="12" sm="5" md="3">
            <label class="filter-label">Período</label>
            <v-btn-toggle
              v-model="mode"
              mandatory
              density="compact"
              variant="outlined"
              color="primary"
              divided
              class="w-100"
              @update:model-value="applyFilters"
            >
              <v-btn value="annual" class="flex-grow-1 text-none">
                <v-icon icon="mdi-calendar-year" start size="small"></v-icon>
                Anual
              </v-btn>
              <v-btn value="monthly" class="flex-grow-1 text-none">
                <v-icon icon="mdi-calendar-month" start size="small"></v-icon>
                Mensal
              </v-btn>
            </v-btn-toggle>
          </v-col>

          <v-col cols="6" sm="3" md="2">
            <label class="filter-label">Ano</label>
            <v-select
              v-model="year"
              :items="yearOptions"
              density="compact"
              variant="outlined"
              hide-details
              prepend-inner-icon="mdi-calendar"
              @update:model-value="applyFilters"
            ></v-select>
          </v-col>

          <v-col v-if="isMonthly" cols="6" sm="4" md="3">
            <label class="filter-label">Mês</label>
            <v-select
              v-model="month"
              :items="monthOptions"
              density="compact"
              variant="outlined"
              hide-details
              prepend-inner-icon="mdi-calendar-month-outline"
              @update:model-value="applyFilters"
            ></v-select>
          </v-col>

          <v-col cols="12" :md="isMonthly ? 4 : 7" class="d-flex align-center justify-sm-end">
            <div class="text-caption text-grey-darken-1">
              <v-icon icon="mdi-calendar-range" size="small" class="me-1"></v-icon>
              {{ report.period.start_label }} a {{ report.period.end_label }}
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-alert
      v-if="report.totals.orders === 0"
      type="info"
      variant="tonal"
      density="compact"
      class="mb-4"
    >
      Nenhuma ordem foi registrada no período selecionado. Os indicadores serão exibidos assim que houver movimentação.
    </v-alert>

    <!-- INDICADORES CONSOLIDADOS -->
    <v-row dense class="mb-2">
      <v-col cols="12" sm="6" md="4">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">GARRAFÕES CONSUMIDOS</div>
              <div class="text-h4 font-weight-black text-primary">{{ formatNumber(report.totals.bottles) }}</div>
              <div class="kpi-hint">Somente ordens concluídas</div>
            </div>
            <v-avatar color="blue-lighten-4" size="48">
              <v-icon icon="mdi-water" color="primary" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="4">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">SETORES ATENDIDOS</div>
              <div class="text-h4 font-weight-black text-secondary">{{ formatNumber(report.totals.sectors_served) }}</div>
              <div class="kpi-hint">Setores com consumo concluído</div>
            </div>
            <v-avatar color="grey-lighten-3" size="48">
              <v-icon icon="mdi-office-building" color="secondary" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="4">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card kpi-card--warning">
          <div class="d-flex align-center justify-space-between ga-2">
            <div class="min-w-0">
              <div class="kpi-label">SETOR COM MAIOR CONSUMO</div>
              <div class="text-subtitle-1 font-weight-black text-truncate" :title="topSectorName">
                {{ topSectorName }}
              </div>
              <div class="kpi-hint">
                <template v-if="report.totals.top_sector">
                  {{ formatNumber(report.totals.top_sector.bottles) }} garrafões
                  ({{ formatDecimal(report.totals.top_sector.percentage) }}%)
                </template>
                <template v-else>Sem consumo concluído</template>
              </div>
            </div>
            <v-avatar color="amber-lighten-4" size="48">
              <v-icon icon="mdi-trophy-outline" color="warning" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">ORDENS REGISTRADAS</div>
              <div class="text-h5 font-weight-black text-primary">{{ formatNumber(report.totals.orders) }}</div>
              <div class="kpi-hint">
                {{ formatNumber(report.totals.concluded_orders) }} concluídas /
                {{ formatNumber(report.totals.pending_orders) }} pendentes
              </div>
            </div>
            <v-avatar color="blue-lighten-4" size="44">
              <v-icon icon="mdi-file-document-multiple" color="primary" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card kpi-card--warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">GARRAFÕES PENDENTES</div>
              <div class="text-h5 font-weight-black text-warning">{{ formatNumber(report.totals.pending_bottles) }}</div>
              <div class="kpi-hint">Aguardando retirada do cheio</div>
            </div>
            <v-avatar color="amber-lighten-4" size="44">
              <v-icon icon="mdi-clock-outline" color="warning" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">TAXA DE CONCLUSÃO</div>
              <div class="text-h5 font-weight-black text-success">{{ formatDecimal(report.totals.conclusion_rate) }}%</div>
              <v-progress-linear
                :model-value="report.totals.conclusion_rate"
                color="success"
                height="6"
                rounded
                class="mt-2"
              ></v-progress-linear>
            </div>
            <v-avatar color="green-lighten-4" size="44">
              <v-icon icon="mdi-check-circle-outline" color="success" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="elevation-1 border rounded pa-4 h-100 kpi-card" :class="variationClass">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="kpi-label">VARIAÇÃO VS. ANTERIOR</div>
              <div class="text-h5 font-weight-black" :class="variationTextClass">{{ variationText }}</div>
              <div class="kpi-hint">
                {{ formatNumber(report.totals.previous_bottles) }} garrafões em {{ report.period.previous_label }}
              </div>
            </div>
            <v-avatar :color="variationAvatarColor" size="44">
              <v-icon :icon="variationIcon" :color="variationIconColor" size="large"></v-icon>
            </v-avatar>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- GRÁFICOS PRINCIPAIS -->
    <v-row dense class="mb-2">
      <v-col cols="12" lg="8">
        <v-card class="elevation-1 border rounded h-100">
          <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b">
            <span class="text-subtitle-2 font-weight-bold text-secondary">
              <v-icon icon="mdi-chart-line" class="me-1"></v-icon>
              Evolução do consumo — {{ report.period.label }}
            </span>
          </v-card-title>
          <v-card-text class="pa-3 pa-sm-4">
            <ConsumptionTrendChart :series="report.series" :mode="report.period.mode" />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card class="elevation-1 border rounded h-100">
          <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b">
            <span class="text-subtitle-2 font-weight-bold text-secondary">
              <v-icon icon="mdi-swap-horizontal" class="me-1"></v-icon>
              Tipo de operação
            </span>
          </v-card-title>
          <v-card-text class="pa-4 d-flex justify-center">
            <OperationDonut
              :segments="operationSegments"
              center-label="garrafões"
              class="w-100"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense class="mb-2">
      <v-col cols="12" lg="7">
        <v-card class="elevation-1 border rounded h-100">
          <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex justify-space-between align-center">
            <span class="text-subtitle-2 font-weight-bold text-secondary">
              <v-icon icon="mdi-podium" class="me-1"></v-icon>
              Ranking de setores por consumo
            </span>
            <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
              TOP {{ topSectors.length }}
            </v-chip>
          </v-card-title>
          <v-card-text class="pa-4">
            <SectorRankingChart :sectors="topSectors" />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="5">
        <v-card class="elevation-1 border rounded h-100">
          <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b">
            <span class="text-subtitle-2 font-weight-bold text-secondary">
              <v-icon icon="mdi-clipboard-check-outline" class="me-1"></v-icon>
              Situação das ordens
            </span>
          </v-card-title>
          <v-card-text class="pa-4">
            <OperationDonut
              :segments="statusSegments"
              center-label="ordens"
              class="w-100"
            />

            <div class="mt-4 pt-3 border-t">
              <div class="d-flex justify-space-between text-caption mb-1">
                <span class="text-grey-darken-1">Taxa de conclusão</span>
                <span class="font-weight-bold text-success">{{ formatDecimal(report.totals.conclusion_rate) }}%</span>
              </div>
              <v-progress-linear
                :model-value="report.totals.conclusion_rate"
                color="success"
                height="8"
                rounded
              ></v-progress-linear>
              <div class="text-caption text-grey-darken-1 mt-2">
                Média de {{ formatDecimal(report.totals.avg_bottles_per_order) }} garrafões por ordem concluída.
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- DETALHAMENTO DO PERÍODO -->
    <v-card class="elevation-1 border rounded">
      <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex justify-space-between align-center">
        <span class="text-subtitle-2 font-weight-bold text-secondary">
          <v-icon icon="mdi-table-eye" class="me-1"></v-icon>
          Detalhamento por {{ report.period.series_label.toLowerCase() }}
        </span>
        <v-chip size="x-small" color="secondary" variant="tonal" class="font-weight-bold">
          {{ report.period.label }}
        </v-chip>
      </v-card-title>

      <div class="table-scroll">
        <v-table density="compact" hover>
          <thead>
            <tr class="bg-grey-lighten-4 text-caption font-weight-bold">
              <th>{{ report.period.series_label }}</th>
              <th class="text-right">GARRAFÕES CONSUMIDOS</th>
              <th class="text-center">ORDENS CONCLUÍDAS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="point in report.series" :key="point.full_label" class="text-caption">
              <td class="font-weight-medium">{{ point.full_label }}</td>
              <td class="text-right font-weight-black text-primary">{{ formatNumber(point.bottles) }}</td>
              <td class="text-center">{{ formatNumber(point.orders) }}</td>
            </tr>
            <tr class="bg-blue-lighten-5 text-caption">
              <td class="font-weight-black">TOTAL DO PERÍODO</td>
              <td class="text-right font-weight-black text-primary">{{ formatNumber(report.totals.bottles) }}</td>
              <td class="text-center font-weight-black">{{ formatNumber(report.totals.concluded_orders) }}</td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConsumptionTrendChart from '@/Components/Charts/ConsumptionTrendChart.vue';
import SectorRankingChart from '@/Components/Charts/SectorRankingChart.vue';
import OperationDonut from '@/Components/Charts/OperationDonut.vue';

const props = defineProps({
  report: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  yearOptions: {
    type: Array,
    default: () => [],
  },
  monthOptions: {
    type: Array,
    default: () => [],
  },
});

const mode = ref(props.filters.mode || 'annual');
const year = ref(props.filters.year || new Date().getFullYear());
const month = ref(props.filters.month || new Date().getMonth() + 1);
const loading = ref(false);

const isMonthly = computed(() => mode.value === 'monthly');

const topSectors = computed(() => (props.report.sector_ranking || []).slice(0, 8));

const topSectorName = computed(() => props.report.totals.top_sector?.sector || 'Sem dados');

const operationSegments = computed(() => [
  {
    label: 'Fluxo Padrão',
    value: props.report.operations?.PADRAO?.bottles || 0,
    color: '#2b6cb0',
  },
  {
    label: 'Bate Volta',
    value: props.report.operations?.BATE_VOLTA?.bottles || 0,
    color: '#28a745',
  },
]);

const statusSegments = computed(() => [
  {
    label: 'Concluídas',
    value: props.report.status?.CONCLUIDO || 0,
    color: '#28a745',
  },
  {
    label: 'Pendentes',
    value: props.report.status?.DISPONIVEL || 0,
    color: '#ffc107',
  },
]);

const variation = computed(() => props.report.totals.variation);

const variationText = computed(() => {
  if (variation.value === null || variation.value === undefined) {
    return '—';
  }

  const prefix = variation.value > 0 ? '+' : '';
  return `${prefix}${formatDecimal(variation.value)}%`;
});

const variationClass = computed(() => {
  if (variation.value === null || variation.value === undefined) return '';
  return variation.value < 0 ? 'kpi-card--danger' : 'kpi-card--success';
});

const variationTextClass = computed(() => {
  if (variation.value === null || variation.value === undefined) return 'text-grey-darken-1';
  return variation.value < 0 ? 'text-error' : 'text-success';
});

const variationIcon = computed(() => {
  if (variation.value === null || variation.value === undefined) return 'mdi-minus';
  return variation.value < 0 ? 'mdi-trending-down' : 'mdi-trending-up';
});

const variationIconColor = computed(() => {
  if (variation.value === null || variation.value === undefined) return 'grey';
  return variation.value < 0 ? 'error' : 'success';
});

const variationAvatarColor = computed(() => {
  if (variation.value === null || variation.value === undefined) return 'grey-lighten-3';
  return variation.value < 0 ? 'red-lighten-4' : 'green-lighten-4';
});

const formatNumber = (value) => new Intl.NumberFormat('pt-BR').format(Number(value) || 0);

const formatDecimal = (value) => new Intl.NumberFormat('pt-BR', {
  minimumFractionDigits: 1,
  maximumFractionDigits: 1,
}).format(Number(value) || 0);

const applyFilters = () => {
  loading.value = true;

  router.get(
    route('entregas.relatorios'),
    {
      mode: mode.value,
      year: year.value,
      month: isMonthly.value ? month.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => {
        loading.value = false;
      },
    }
  );
};

const downloadReport = (format) => {
  const params = {
    mode: mode.value,
    year: year.value,
  };

  if (isMonthly.value) {
    params.month = month.value;
  }

  window.location.href = route(`entregas.relatorios.${format}`, params);
};
</script>

<style scoped>
.filter-label {
  display: block;
  margin-bottom: 4px;
  font-size: 0.72rem;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.kpi-card {
  border-left: 4px solid #2b6cb0 !important;
}

.kpi-card--warning {
  border-left-color: #ffc107 !important;
}

.kpi-card--success {
  border-left-color: #28a745 !important;
}

.kpi-card--danger {
  border-left-color: #dc3545 !important;
}

.kpi-label {
  font-size: 0.68rem;
  font-weight: 800;
  color: #6b7280;
  letter-spacing: 0.4px;
}

.kpi-hint {
  margin-top: 2px;
  font-size: 0.7rem;
  color: #6b7280;
}

.min-w-0 {
  min-width: 0;
}

.table-scroll {
  max-height: 380px;
  overflow-y: auto;
}

.table-scroll :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
}
</style>
