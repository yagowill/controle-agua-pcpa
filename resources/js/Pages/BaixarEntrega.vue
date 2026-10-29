<template>
  <AppLayout>
    <v-card class="elevation-1 border rounded mx-auto" max-width="760">
      <!-- Cabeçalho -->
      <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b">
        <span class="text-subtitle-2 font-weight-bold text-secondary">
          <v-icon icon="mdi-qrcode-scan" class="me-1"></v-icon>
          Validação e Baixa de Comprovante de Água
        </span>
      </v-card-title>

      <!-- Mensagens de Sucesso ou Erro de Sessão -->
      <v-alert
        v-if="$page.props.flash?.success"
        type="success"
        variant="tonal"
        density="compact"
        class="ma-4 mb-0"
        closable
      >
        {{ $page.props.flash.success }}
      </v-alert>

      <v-alert
        v-if="$page.props.errors?.message"
        type="error"
        variant="elevated"
        class="ma-4 mb-0"
        icon="mdi-alert-octagon"
      >
        {{ $page.props.errors.message }}
      </v-alert>

      <v-card-text class="pa-5 space-y-4">
        <!-- BLOCO DA CÂMERA (LEITOR QR CODE) -->
        <v-card variant="outlined" class="pa-4 bg-grey-lighten-5 rounded mb-4">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-subtitle-2 font-weight-bold text-secondary">
                <v-icon icon="mdi-camera" class="me-1"></v-icon>
                Leitor de QR Code
              </div>
              <div class="text-caption text-grey-darken-1">
                Aponte a câmera para o QR Code do protocolo impresso ou celular.
              </div>
            </div>

            <v-btn
              :color="isCameraActive ? 'error' : 'primary'"
              variant="elevated"
              size="small"
              :prepend-icon="isCameraActive ? 'mdi-camera-off' : 'mdi-camera'"
              @click="toggleCamera"
            >
              {{ isCameraActive ? 'Desligar Câmera' : 'Ligar Câmera' }}
            </v-btn>
          </div>

          <!-- Wrapper do Leitor de Vídeo -->
          <v-expand-transition>
            <div v-show="isCameraActive" class="mt-4 d-flex justify-center">
              <div id="qr-reader" style="width: 100%; max-width: 320px;" class="rounded border overflow-hidden bg-black"></div>
            </div>
          </v-expand-transition>
        </v-card>

        <!-- BUSCA MANUAL POR NÚMERO DE ORDEM -->
        <v-row dense class="align-center">
          <v-col cols="12" sm="9">
            <v-text-field
              v-model="searchQuery"
              label="Número de Ordem / Protocolo*"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-pound"
              class="font-mono"
              hide-details
              @keyup.enter="searchProtocol"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="3">
            <v-btn
              color="primary"
              size="large"
              block
              prepend-icon="mdi-magnifying-glass"
              :loading="searching"
              @click="searchProtocol"
            >
              Buscar
            </v-btn>
          </v-col>
        </v-row>

        <!-- ALERTA DE CRÍTICO: COMPROVANTE JÁ BAIXADO (ANTI-REUSO) -->
        <v-alert
          v-if="selectedDelivery && selectedDelivery.status === 'CONCLUIDO'"
          type="error"
          variant="elevated"
          icon="mdi-alert-octagon"
          class="mt-4"
        >
          <div class="font-weight-black text-subtitle-2">
            ATENÇÃO: ESTE COMPROVANTE JÁ FOI UTILIZADO!
          </div>
          <div class="text-caption mt-1">
            • <strong>Ordem:</strong> {{ selectedDelivery.order_number }} ({{ selectedDelivery.sector }})<br />
            • <strong>Data da Baixa:</strong> {{ formatDateTime(selectedDelivery.delivery_date) }}<br />
            • <strong>Servidor que Retirou:</strong> {{ selectedDelivery.delivered_to_person || 'NÃO INFORMADO' }}<br />
            • <strong>Atendente que Entregou:</strong> {{ selectedDelivery.delivery_attendant || 'NÃO INFORMADO' }}
          </div>
        </v-alert>

        <!-- ALERTA DE SUCESSO: COMPROVANTE DISPONÍVEL -->
        <v-alert
          v-if="selectedDelivery && selectedDelivery.status === 'DISPONIVEL'"
          type="success"
          variant="tonal"
          icon="mdi-check-circle"
          class="mt-4"
        >
          <div class="font-weight-bold">
            Ordem {{ selectedDelivery.order_number }} pronta para liberação!
          </div>
          <div class="text-caption">
            Status atual: <strong>DISPONÍVEL</strong>. Preencha os dados abaixo para confirmar a entrega.
          </div>
        </v-alert>

        <!-- DETALHES DA ORDEM LOCALIZADA -->
        <v-card
          v-if="selectedDelivery"
          variant="outlined"
          class="pa-4 bg-blue-grey-lighten-5 rounded mt-4"
        >
          <v-row dense class="text-caption">
            <v-col cols="12" sm="6">
              <strong>Ordem:</strong> {{ selectedDelivery.order_number }}
            </v-col>
            <v-col cols="12" sm="6">
              <strong>Setor:</strong> {{ selectedDelivery.sector }}
            </v-col>
            <v-col cols="12" sm="6">
              <strong>Qtd. Garrafões:</strong> {{ selectedDelivery.empty_bottles_count }}
            </v-col>
            <v-col cols="12" sm="6">
              <strong>Data Entrada do Seco:</strong> {{ formatDateTime(selectedDelivery.reception_date) }}
            </v-col>
            <v-col cols="12" sm="6">
              <strong>Quem Trouxe o Seco:</strong> {{ selectedDelivery.received_from_person }}
            </v-col>
            <v-col cols="12" sm="6">
              <strong>Atendente Recepção:</strong> {{ selectedDelivery.reception_attendant }}
            </v-col>
          </v-row>
        </v-card>

        <!-- FORMULÁRIO DE CONFIRMAÇÃO DE BAIXA -->
        <v-form
          v-if="selectedDelivery && selectedDelivery.status === 'DISPONIVEL'"
          @submit.prevent="confirmRedemption"
          class="mt-4"
        >
          <v-row dense>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="redeemForm.delivered_to_person"
                label="Nome do Servidor (Retirando o Cheio)*"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-account-check"
                :error-messages="redeemForm.errors.delivered_to_person"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="redeemForm.delivery_attendant"
                label="Atendente do Almoxarifado (Entregando)*"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-badge-account"
                :error-messages="redeemForm.errors.delivery_attendant"
                required
              ></v-text-field>
            </v-col>
          </v-row>

          <v-btn
            type="submit"
            color="success"
            size="large"
            block
            class="mt-3 font-weight-bold"
            prepend-icon="mdi-check-all"
            :loading="redeemForm.processing"
          >
            Confirmar Entrega dos Cheios e Dar Baixa
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </AppLayout>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Html5QrcodeScanner } from 'html5-qrcode';
import AppLayout from '@/Layouts/AppLayout.vue';

const searchQuery = ref('');
const searching = ref(false);
const selectedDelivery = ref(null);
const isCameraActive = ref(false);
let scannerInstance = null;

const redeemForm = useForm({
  delivered_to_person: '',
  delivery_attendant: '',
});

const formatDateTime = (dateVal) => {
  if (!dateVal) return 'N/I';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return dateVal;
  return d.toLocaleString('pt-BR');
};

const searchProtocol = async () => {
  if (!searchQuery.value.trim()) return;

  searching.value = true;
  selectedDelivery.value = null;

  try {
    const res = await axios.get(route('entregas.api.search', { orderNumber: searchQuery.value.trim() }));
    if (res.data?.found) {
      selectedDelivery.value = res.data.delivery;
      if (selectedDelivery.value.status === 'DISPONIVEL') {
        redeemForm.delivered_to_person = selectedDelivery.value.received_from_person || '';
      }
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Comprovante não encontrado.');
  } finally {
    searching.value = false;
  }
};

const confirmRedemption = () => {
  if (!selectedDelivery.value) return;

  redeemForm.post(route('entregas.redeem', { orderNumber: selectedDelivery.value.order_number }), {
    preserveScroll: true,
    onSuccess: () => {
      searchQuery.value = '';
      selectedDelivery.value = null;
      redeemForm.reset();
    },
  });
};

const toggleCamera = () => {
  if (isCameraActive.value) {
    stopCamera();
  } else {
    startCamera();
  }
};

const startCamera = () => {
  isCameraActive.value = true;
  setTimeout(() => {
    scannerInstance = new Html5QrcodeScanner('qr-reader', {
      fps: 10,
      qrbox: { width: 220, height: 220 },
    }, false);

    scannerInstance.render((decodedText) => {
      searchQuery.value = decodedText.trim();
      stopCamera();
      searchProtocol();
    }, () => {});
  }, 100);
};

const stopCamera = () => {
  if (scannerInstance) {
    scannerInstance.clear().catch(() => {});
    scannerInstance = null;
  }
  isCameraActive.value = false;
};

onBeforeUnmount(() => {
  stopCamera();
});
</script>

<style scoped>
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>

