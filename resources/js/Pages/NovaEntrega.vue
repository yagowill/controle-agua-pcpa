<template>
  <AppLayout>
    <v-row justify="center">
      <!-- FORMULÁRIO DE LANÇAMENTO CENTRALIZADO -->
      <v-col cols="12" md="9" lg="8">
        <v-card class="elevation-1 border rounded">
          <!-- Cabeçalho do Card -->
          <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex justify-space-between align-center">
            <span class="text-subtitle-2 font-weight-bold text-secondary">
              <v-icon icon="mdi-file-document-edit-outline" class="me-1"></v-icon>
              Dados da Entrega
            </span>
            <div class="d-flex align-center ga-2">
              <v-chip size="small" color="primary" variant="tonal" class="font-weight-black font-mono">
                Nº Ordem: {{ nextOrderNumber }}
              </v-chip>
            </div>
          </v-card-title>

          <!-- Alerta de Sucesso -->
          <v-alert
            v-if="flash?.success"
            type="success"
            variant="tonal"
            density="compact"
            class="ma-4 mb-0"
            closable
          >
            {{ flash.success }}
          </v-alert>

          <!-- Formulário -->
          <v-card-text class="pa-4 pa-sm-6">
            <v-form @submit.prevent="submitDelivery">
              <!-- Tipo de Operação (cartões selecionáveis) -->
              <div class="mb-4">
                <label class="field-label">
                  Tipo de Operação <span class="field-label__required">*</span>
                </label>

                <div
                  class="d-flex flex-column flex-sm-row ga-3"
                  role="radiogroup"
                  aria-label="Tipo de Operação"
                >
                  <!-- Opção: Fluxo Padrão (azul) -->
                  <div
                    class="operation-option operation-option--primary flex-grow-1"
                    :class="{ 'operation-option--active': form.operation_type === 'PADRAO' }"
                    role="radio"
                    :aria-checked="form.operation_type === 'PADRAO'"
                    tabindex="0"
                    @click="form.operation_type = 'PADRAO'"
                    @keydown.enter.prevent="form.operation_type = 'PADRAO'"
                    @keydown.space.prevent="form.operation_type = 'PADRAO'"
                  >
                    <div class="operation-icon">
                      <v-icon icon="mdi-water-outline" size="24" />
                    </div>

                    <div class="operation-body">
                      <div class="operation-title">Fluxo Padrão</div>
                      <div class="operation-subtitle">Deixa garrafão seco / Retira cheio depois</div>
                    </div>

                    <span class="operation-badge">NORMAL</span>
                  </div>

                  <!-- Opção: Bate Volta (verde) -->
                  <div
                    class="operation-option operation-option--success flex-grow-1"
                    :class="{ 'operation-option--active': form.operation_type === 'BATE_VOLTA' }"
                    role="radio"
                    :aria-checked="form.operation_type === 'BATE_VOLTA'"
                    tabindex="0"
                    @click="form.operation_type = 'BATE_VOLTA'"
                    @keydown.enter.prevent="form.operation_type = 'BATE_VOLTA'"
                    @keydown.space.prevent="form.operation_type = 'BATE_VOLTA'"
                  >
                    <div class="operation-icon">
                      <v-icon icon="mdi-swap-horizontal" size="24" />
                    </div>

                    <div class="operation-body">
                      <div class="operation-title">Bate Volta</div>
                      <div class="operation-subtitle">Troca imediata por garrafão abastecido</div>
                    </div>

                    <span class="operation-badge">IMEDIATO</span>
                  </div>
                </div>
              </div>

              <!-- Setor e Quantidade -->
              <v-row dense>
                <v-col cols="12" sm="8">
                  <label for="sector" class="field-label">
                    Setor / Delegacia <span class="field-label__required">*</span>
                  </label>

                  <v-combobox
                    id="sector"
                    v-model="form.sector"
                    :items="defaultSectors"
                    placeholder="Selecione a unidade policial ou setor"
                    persistent-placeholder
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-office-building"
                    :error-messages="form.errors.sector"
                    hide-details="auto"
                  ></v-combobox>
                </v-col>

                <v-col cols="12" sm="4">
                  <label for="empty-bottles" class="field-label">
                    Qtd. Garrafões <span class="field-label__required">*</span>
                  </label>

                  <v-text-field
                    id="empty-bottles"
                    v-model.number="form.empty_bottles_count"
                    type="number"
                    min="1"
                    max="100"
                    placeholder="1"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-water"
                    :error-messages="form.errors.empty_bottles_count"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Servidor que Entregou e Atendente -->
              <v-row dense>
                <v-col cols="12" sm="6">
                  <label for="received-from-person" class="field-label">
                    Servidor que Entregou o Seco <span class="field-label__required">*</span>
                  </label>

                  <v-text-field
                    id="received-from-person"
                    v-model="form.received_from_person"
                    placeholder="Nome completo ou Matrícula"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-account"
                    :error-messages="form.errors.received_from_person"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>

                <v-col cols="12" sm="6">
                  <!-- Campo preenchido automaticamente pelo atendente logado (somente leitura) -->
                  <label for="reception-attendant" class="field-label">
                    Atendente do Almoxarifado (Entregando)
                  </label>

                  <v-text-field
                    id="reception-attendant"
                    v-model="form.reception_attendant"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-badge-account"
                    :error-messages="form.errors.reception_attendant"
                    readonly
                    class="field-readonly"
                    :hint="loggedUserName ? 'Preenchido automaticamente pelo servidor logado' : ''"
                    persistent-hint
                    bg-color="grey-lighten-4"
                  >
                    <template v-if="loggedUserName" #append-inner>
                      <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold">
                        Logado
                      </v-chip>
                    </template>
                  </v-text-field>
                </v-col>
              </v-row>

              <!-- Aviso do Bate Volta: o cheio é entregue ao mesmo servidor que trouxe o seco -->
              <v-fade-transition>
                <v-alert
                  v-if="form.operation_type === 'BATE_VOLTA'"
                  type="success"
                  variant="tonal"
                  density="compact"
                  class="mb-3"
                >
                  <div class="font-weight-bold text-caption mb-1">
                    Troca Imediata (Bate Volta)
                  </div>
                  <span class="text-caption">
                    O garrafão cheio será entregue ao mesmo servidor que trouxe o seco:
                    <strong>{{ form.received_from_person || 'informe o servidor acima' }}</strong>.
                  </span>
                </v-alert>
              </v-fade-transition>

              <!-- Data Prevista e Observações -->
              <v-row dense>
                <v-col v-if="form.operation_type === 'PADRAO'" cols="12" sm="6">
                  <label for="estimated-date" class="field-label">
                    Previsão de Retirada
                  </label>

                  <v-text-field
                    id="estimated-date"
                    v-model="form.estimated_date"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-calendar-clock"
                    hint="Calculada automaticamente (Seg / Qui)"
                    persistent-hint
                  ></v-text-field>
                </v-col>

                <v-col cols="12" :sm="form.operation_type === 'PADRAO' ? 6 : 12">
                  <label for="notes" class="field-label">
                    Observações <span class="field-label__optional">(Opcional)</span>
                  </label>

                  <v-text-field
                    id="notes"
                    v-model="form.notes"
                    placeholder="Ex: Garrafão avariado, etc."
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-note-text-outline"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Botão Salvar -->
              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                class="mt-4 font-weight-bold"
                prepend-icon="mdi-content-save"
                :loading="form.processing"
              >
                Salvar Registro de Entrega
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- MODAL / DIALOG DO COMPROVANTE DO PROTOCOLO -->
    <v-dialog v-model="dialogProtocol" max-width="380">
      <v-card class="pa-4 bg-white rounded elevation-3 d-flex flex-column align-center">
        <div class="text-caption font-weight-bold text-grey-darken-2 text-uppercase mb-3 text-center border-b pb-2 w-100">
          Comprovante do Protocolo
        </div>

        <ProtocolCard :delivery="cardDeliveryData" />

        <v-btn class="mt-3 w-100" variant="text" size="small" @click="dialogProtocol = false">
          Fechar
        </v-btn>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProtocolCard from '@/Components/ProtocolCard.vue';

const page = usePage();

// Nome do atendente autenticado para preenchimento automático (auditoria)
const loggedUserName = computed(() => page.props.auth?.user?.name ?? '');

const props = defineProps({
  nextOrderNumber: {
    type: String,
    default: 'PCPA-2026-0001',
  },
  defaultEstimatedDate: {
    type: String,
    default: '',
  },
  recentDeliveries: {
    type: Array,
    default: () => [],
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

const dialogProtocol = ref(false);

// Abre automaticamente o modal ao salvar uma nova entrega
watch(
  () => props.flash?.createdDelivery,
  (newVal) => {
    if (newVal) {
      dialogProtocol.value = true;
    }
  },
  { immediate: true }
);

const defaultSectors = [
  'GABINETE DO DELEGADO-GERAL (DG)',
  'GABINETE DA DIRETORIA DE ADMINISTRAÇÃO (DA)',
  'ALMOXARIFADO',
  'PATRIMÔNIO',
  'SETOR DE CONTRATOS',
  'SETOR DE LICITAÇÕES',
  'DIVISÃO DE RECURSOS MATERIAIS (DRM)',
  'CONSULTORIA JURIDICA (CONJUR)',
  'CONTROLE INTERNO (CI)',
  'DIRETORIA DE INFORMÁTICA MANUTENÇÃO E ESTATISTICA (DIME)',
  'DIME - REDES',
  'DIME - DESENVOLVIMENTO',
  'DIME - ESTATÍSTICA (DIVEST)',
  'DIME - MANUTENÇÃO',
  'DIME - SECRETARIA',
  'MANUTENÇÃO PREDIAL',
  'ENGENNHARIA E ARQUITETURA',
  'COPA (DA)',
  'CORREDOR DE BAIXO',
  'PREFEIURA',
  'NIP (NÚCLEO DE INTELIGÊNCIA POLICIAL)',
  'NIP - CYBER LAB',
  'SECCIONAL DE SÃO BRÁS',
  'SECCIONAL DA PEDREIRA',
  'SECCIONAL DO COMÉRCIO',
  'SECCIONAL DA MARAMBAIA',
  'SECCIONAL DA CIDADE NOVA'
];

const form = useForm({
  operation_type: 'PADRAO',
  // Inicia nulo para que o placeholder do combobox seja exibido
  sector: null,
  empty_bottles_count: 1,
  received_from_person: '',
  // Campo preenchido automaticamente pelo usuário autenticado (não editável pelo operador)
  reception_attendant: loggedUserName.value,
  estimated_date: props.defaultEstimatedDate,
  notes: '',
});

// Mantém o atendente sincronizado com o usuário logado sempre que mudar
watch(loggedUserName, (name) => {
  form.reception_attendant = name;
}, { immediate: true });

// Dados refletidos no modal
const cardDeliveryData = computed(() => {
  if (props.flash?.createdDelivery) {
    return props.flash.createdDelivery;
  }

  return {
    order_number: props.nextOrderNumber,
    operation_type: form.operation_type,
    sector: form.sector || 'NOME DO SETOR',
    empty_bottles_count: form.empty_bottles_count || 1,
    reception_date: new Date().toISOString(),
    estimated_date: form.operation_type === 'BATE_VOLTA' ? new Date().toISOString() : form.estimated_date,
    status: form.operation_type === 'BATE_VOLTA' ? 'CONCLUIDO' : 'DISPONIVEL',
  };
});

const submitDelivery = () => {
  form.post(route('entregas.store'), {
    preserveScroll: true,
    onSuccess: () => {
      dialogProtocol.value = true;
      form.reset('sector', 'received_from_person', 'notes');
      form.empty_bottles_count = 1;
      form.reception_attendant = loggedUserName.value;
    },
  });
};
</script>

<style scoped>
/* Cartões de seleção do Tipo de Operação */
.operation-option {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 82px;
  padding: 12px 14px;
  border: 2px solid #ced4da;
  border-radius: 12px;
  background-color: #ffffff;
  cursor: pointer;
  outline: none;
  transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
}

.operation-option:hover {
  border-color: #adb5bd;
}

.operation-option:focus-visible {
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.25);
}

/* Ícone em bloco */
.operation-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  border-radius: 10px;
  background-color: #f1f3f5;
  color: #6c757d;
  transition: background-color 0.15s ease, color 0.15s ease;
}

/* Textos */
.operation-body {
  flex: 1 1 auto;
  min-width: 0;
}

.operation-title {
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.2;
  color: #212529;
}

.operation-subtitle {
  font-size: 0.78rem;
  line-height: 1.25;
  color: #6c757d;
  margin-top: 2px;
}

/* Selo à direita */
.operation-badge {
  flex: 0 0 auto;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  background-color: #f1f3f5;
  color: #495057;
  transition: background-color 0.15s ease, color 0.15s ease;
}

/* Estado ativo — Fluxo Padrão (azul institucional) */
.operation-option--primary.operation-option--active {
  border-color: #2b6cb0;
  background-color: #f0f7ff;
}

.operation-option--primary.operation-option--active .operation-icon {
  background-color: #2b6cb0;
  color: #ffffff;
}

.operation-option--primary.operation-option--active .operation-badge {
  background-color: #dbeafe;
  color: #2b6cb0;
}

/* Estado ativo — Bate Volta (verde) */
.operation-option--success.operation-option--active {
  border-color: #28a745;
  background-color: #f1fbf4;
}

.operation-option--success.operation-option--active .operation-icon {
  background-color: #28a745;
  color: #ffffff;
}

.operation-option--success.operation-option--active .operation-badge {
  background-color: #d1fae5;
  color: #1b7a43;
}
</style>
