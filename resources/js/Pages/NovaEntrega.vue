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
              <!-- Tipo de Operação -->
              <div class="mb-4">
                <label class="text-caption font-weight-bold text-grey-darken-3 d-block mb-1">
                  Tipo de Operação*
                </label>
                <v-radio-group
                  v-model="form.operation_type"
                  inline
                  hide-details
                  class="mt-0"
                >
                  <v-radio
                    label="Fluxo Padrão (Deixa seco / Retira depois)"
                    value="PADRAO"
                    color="primary"
                  ></v-radio>
                  <v-radio
                    label="Bate Volta (Troca Imediata)"
                    value="BATE_VOLTA"
                    color="success"
                  ></v-radio>
                </v-radio-group>
              </div>

              <!-- Setor e Quantidade -->
              <v-row dense>
                <v-col cols="12" sm="8">
                  <v-combobox
                    v-model="form.sector"
                    :items="defaultSectors"
                    label="Setor / Lotação / Delegacia*"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-office-building"
                    :error-messages="form.errors.sector"
                    required
                  ></v-combobox>
                </v-col>

                <v-col cols="12" sm="4">
                  <v-text-field
                    v-model.number="form.empty_bottles_count"
                    label="Qtd. Garrafões*"
                    type="number"
                    min="1"
                    max="100"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-water"
                    :error-messages="form.errors.empty_bottles_count"
                    required
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Servidor que Entregou e Atendente -->
              <v-row dense>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.received_from_person"
                    label="Servidor que Entregou o Seco*"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-account"
                    :error-messages="form.errors.received_from_person"
                    required
                  ></v-text-field>
                </v-col>

                <v-col cols="12" sm="6">
                  <!-- Campo preenchido automaticamente pelo atendente logado (somente leitura) -->
                  <v-text-field
                    v-model="form.reception_attendant"
                    label="Atendente do Almoxarifado"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-badge-account"
                    :error-messages="form.errors.reception_attendant"
                    readonly
                    :hint="loggedUserName ? 'Preenchido automaticamente pelo servidor logado' : ''"
                    persistent-hint
                    bg-color="grey-lighten-4"
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Campos extras no Bate Volta -->
              <v-fade-transition>
                <v-card
                  v-if="form.operation_type === 'BATE_VOLTA'"
                  variant="tonal"
                  color="success"
                  class="pa-3 mb-3 border rounded"
                >
                  <div class="text-caption font-weight-bold mb-2">
                    <v-icon icon="mdi-information-outline" class="me-1"></v-icon>
                    Troca Imediata (Bate Volta)
                  </div>
                  <v-text-field
                    v-model="form.delivered_to_person"
                    label="Quem está retirando o garrafão cheio?"
                    variant="outlined"
                    density="compact"
                    bg-color="white"
                    hide-details
                  ></v-text-field>
                </v-card>
              </v-fade-transition>

              <!-- Data Prevista e Observações -->
              <v-row dense>
                <v-col v-if="form.operation_type === 'PADRAO'" cols="12" sm="6">
                  <v-text-field
                    v-model="form.estimated_date"
                    label="Previsão de Retirada"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-calendar-clock"
                    hint="Calculada automaticamente (Seg / Qui)"
                    persistent-hint
                  ></v-text-field>
                </v-col>

                <v-col cols="12" :sm="form.operation_type === 'PADRAO' ? 6 : 12">
                  <v-text-field
                    v-model="form.notes"
                    label="Observações (Opcional)"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-note-text-outline"
                    hide-details
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
  'METROPOLITANO',
  'DELEGACIA GERAL (DG)',
  'NOP (NÚCLEO DE OPERAÇÕES)',
  'DIP (DIVISÃO DE INTELIGÊNCIA POLICIAL)',
  'SECCIONAL DE SÃO BRÁS',
  'SECCIONAL DA PEDREIRA',
  'SECCIONAL DO COMÉRCIO',
  'SECCIONAL DA MARAMBAIA',
  'SECCIONAL DA CIDADE NOVA',
  'ALMOXARIFADO CENTRAL',
  'DIRETORIA DE RECURSOS MATERIAIS',
];

const form = useForm({
  operation_type: 'PADRAO',
  sector: '',
  empty_bottles_count: 1,
  received_from_person: '',
  // Campo preenchido automaticamente pelo usuário autenticado (não editável pelo operador)
  reception_attendant: loggedUserName.value,
  estimated_date: props.defaultEstimatedDate,
  delivered_to_person: '',
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
      form.reset('sector', 'received_from_person', 'notes', 'delivered_to_person');
      form.empty_bottles_count = 1;
      form.reception_attendant = loggedUserName.value;
    },
  });
};
</script>
