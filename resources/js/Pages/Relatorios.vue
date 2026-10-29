<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- CARDS DE ESTATÍSTICAS CONSOLIDADAS -->
      <v-row dense class="mb-4">
        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1 border rounded pa-3">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-bold">TOTAL GARRAFÕES</div>
                <div class="text-h5 font-weight-black text-primary">{{ stats.total_bottles }}</div>
              </div>
              <v-avatar color="blue-lighten-4" size="44">
                <v-icon icon="mdi-water" color="primary" size="large"></v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1 border rounded pa-3">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-bold">TOTAL ORDENS</div>
                <div class="text-h5 font-weight-black text-secondary">{{ stats.total_deliveries }}</div>
              </div>
              <v-avatar color="grey-lighten-3" size="44">
                <v-icon icon="mdi-file-document-multiple" color="secondary" size="large"></v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1 border rounded pa-3">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-bold">PENDENTES</div>
                <div class="text-h5 font-weight-black text-warning">{{ stats.total_pendentes }}</div>
              </div>
              <v-avatar color="amber-lighten-4" size="44">
                <v-icon icon="mdi-clock-outline" color="warning" size="large"></v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1 border rounded pa-3">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-bold">CONCLUÍDAS</div>
                <div class="text-h5 font-weight-black text-success">{{ stats.total_concluidas }}</div>
              </div>
              <v-avatar color="green-lighten-4" size="44">
                <v-icon icon="mdi-check-circle" color="success" size="large"></v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- TABELA DE HISTÓRICO COM FILTROS -->
      <v-card class="elevation-1 border rounded">
        <!-- Barra de Filtros -->
        <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex flex-column flex-sm-row justify-space-between align-sm-center ga-3">
          <span class="text-subtitle-2 font-weight-bold text-secondary">
            <v-icon icon="mdi-history" class="me-1"></v-icon>
            Histórico de Registros
          </span>

          <div class="d-flex flex-wrap align-center ga-2">
            <!-- Filtro Status -->
            <v-select
              v-model="filterStatus"
              :items="statusOptions"
              label="Status"
              density="compact"
              variant="outlined"
              hide-details
              style="width: 140px;"
              @update:model-value="applyFilters"
            ></v-select>

            <!-- Busca Geral -->
            <v-text-field
              v-model="searchQuery"
              placeholder="Buscar setor ou servidor..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="mdi-magnifying-glass"
              hide-details
              clearable
              style="width: 220px;"
              @keyup.enter="applyFilters"
              @click:clear="clearSearch"
            ></v-text-field>

            <v-btn
              color="primary"
              variant="elevated"
              size="small"
              icon="mdi-printer"
              title="Imprimir / Salvar PDF"
              @click="printReport"
            ></v-btn>
          </div>
        </v-card-title>

        <!-- Tabela -->
        <v-table density="comfortable" hover>
          <thead>
            <tr class="bg-grey-lighten-4 text-caption font-weight-bold">
              <th>Nº ORDEM</th>
              <th>TIPO</th>
              <th>SETOR / LOTAÇÃO</th>
              <th class="text-center">QTD</th>
              <th>DATA ENTRADA</th>
              <th>PREVISÃO / BAIXA</th>
              <th class="text-center">STATUS</th>
              <th class="text-center">AÇÕES</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in deliveries.data" :key="d.id" class="text-caption">
              <td class="font-weight-black font-mono text-primary">{{ d.order_number }}</td>
              <td>
                <v-chip size="x-small" :color="d.operation_type === 'BATE_VOLTA' ? 'teal' : 'blue-grey'" variant="tonal">
                  {{ d.operation_type === 'BATE_VOLTA' ? 'BATE VOLTA' : 'PADRÃO' }}
                </v-chip>
              </td>
              <td class="font-weight-medium">{{ d.sector }}</td>
              <td class="text-center font-weight-black">{{ d.empty_bottles_count }}</td>
              <td>{{ formatDate(d.reception_date) }}</td>
              <td>
                {{ d.status === 'CONCLUIDO' ? formatDate(d.delivery_date) : formatDate(d.estimated_date) }}
              </td>
              <td class="text-center">
                <v-chip
                  size="x-small"
                  :color="d.status === 'CONCLUIDO' ? 'success' : 'warning'"
                  variant="flat"
                  class="font-weight-bold"
                >
                  {{ d.status }}
                </v-chip>
              </td>
              <td class="text-center">
                <v-btn
                  size="x-small"
                  variant="text"
                  icon="mdi-eye"
                  color="primary"
                  title="Ver Comprovante"
                  @click="openProtocolDialog(d)"
                ></v-btn>
              </td>
            </tr>

            <tr v-if="!deliveries.data || deliveries.data.length === 0">
              <td colspan="8" class="text-center py-6 text-grey-darken-1">
                Nenhum registro encontrado para os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- Paginação -->
        <v-card-actions v-if="deliveries.links && deliveries.links.length > 3" class="justify-center border-t py-2">
          <div class="d-flex ga-1">
            <v-btn
              v-for="(link, idx) in deliveries.links"
              :key="idx"
              size="small"
              :variant="link.active ? 'elevated' : 'text'"
              :color="link.active ? 'primary' : 'default'"
              :disabled="!link.url"
              @click="goToPage(link.url)"
            >
              <span v-html="link.label"></span>
            </v-btn>
          </div>
        </v-card-actions>
      </v-card>
    </div>

    <!-- MODAL / DIALOG VISUALIZAR PROTOCOLO -->
    <v-dialog v-model="dialogProtocol" max-width="380">
      <v-card class="pa-4 bg-white rounded elevation-3 d-flex flex-column align-center">
        <ProtocolCard v-if="selectedDeliveryForModal" :delivery="selectedDeliveryForModal" />
        <v-btn class="mt-3" variant="text" size="small" @click="dialogProtocol = false">
          Fechar
        </v-btn>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProtocolCard from '@/Components/ProtocolCard.vue';

const props = defineProps({
  deliveries: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const searchQuery = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || 'TODOS');

const statusOptions = [
  { title: 'Todos os Status', value: 'TODOS' },
  { title: 'Disponível', value: 'DISPONIVEL' },
  { title: 'Concluído', value: 'CONCLUIDO' },
];

const dialogProtocol = ref(false);
const selectedDeliveryForModal = ref(null);

const formatDate = (dateVal) => {
  if (!dateVal) return '-';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return dateVal;
  return d.toLocaleDateString('pt-BR');
};

const applyFilters = () => {
  router.get(
    route('entregas.relatorios'),
    {
      search: searchQuery.value || undefined,
      status: filterStatus.value !== 'TODOS' ? filterStatus.value : undefined,
    },
    { preserveState: true, replace: true }
  );
};

const clearSearch = () => {
  searchQuery.value = '';
  applyFilters();
};

const goToPage = (url) => {
  if (url) {
    router.visit(url, { preserveState: true });
  }
};

const openProtocolDialog = (delivery) => {
  selectedDeliveryForModal.value = delivery;
  dialogProtocol.value = true;
};

const printReport = () => {
  window.print();
};
</script>

<style scoped>
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* Estilos de Impressão/Exportação PDF do Relatório SCEA/PCPA */
@media print {
  /* Oculta elementos de navegação e controles ao imprimir */
  .v-app-bar,
  .v-footer,
  .nav-tabs-wrapper,
  .header-branding,
  .no-print,
  .v-btn,
  .v-pagination,
  .v-select,
  .v-text-field {
    display: none !important;
  }

  /* Exibe cabeçalho institucional na impressão */
  .print-header {
    display: block !important;
    text-align: center;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #2b6cb0;
  }

  /* Garante que a tabela caiba na página */
  .v-data-table {
    font-size: 11px !important;
  }

  body {
    background: #fff !important;
  }
}
</style>

