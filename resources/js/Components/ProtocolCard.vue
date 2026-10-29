<template>
  <div class="protocol-card-wrapper">
    <div id="protocol-card-printable" class="protocol-card pa-3 rounded bg-white elevation-1">
      <!-- Cabeçalho do Ticket -->
      <div class="ticket-header text-center py-1 px-2 mb-2">
        <div class="text-caption font-weight-black text-uppercase tracking-tight text-black">
          PROTOCOLO DE ENTREGA
        </div>
        <div class="text-caption font-weight-bold text-uppercase text-black">
          GARRAFÃO VAZIO - ÁGUA VIDA
        </div>
        <div class="text-caption text-black" style="font-size: 9px !important; font-weight: 600;">
          ALMOXARIFADO PCPA
        </div>
      </div>

      <!-- Linhas de Dados em Estilo Formulário/Recibo -->
      <div class="ticket-body text-caption font-mono font-weight-bold">
        <div class="ticket-row border-dashed-bottom">
          <span class="text-grey-darken-3 label-col">LOCAL:</span>
          <span class="text-black text-right value-col">
            {{ delivery.sector || '---' }}
          </span>
        </div>

        <div class="ticket-row border-dashed-bottom">
          <span class="text-grey-darken-3 label-col">QUANTIDADE:</span>
          <span class="text-black font-weight-black value-col text-right">{{ delivery.empty_bottles_count || 1 }}</span>
        </div>

        <div class="ticket-row border-dashed-bottom">
          <span class="text-grey-darken-3 label-col">DATA ENTREGA:</span>
          <span class="text-black value-col text-right">{{ formatDate(delivery.reception_date) }}</span>
        </div>

        <div v-if="delivery.operation_type !== 'BATE_VOLTA'" class="ticket-row border-dashed-bottom">
          <span class="text-grey-darken-3 label-col">PREVISÃO P/ ÁGUA:</span>
          <span class="text-black font-weight-bold value-col text-right">{{ formatDate(delivery.estimated_date) }}</span>
        </div>

        <div class="ticket-row border-dashed-bottom">
          <span class="text-grey-darken-3 label-col">Nº DE ORDEM:</span>
          <span class="text-black font-weight-black value-col text-right">{{ delivery.order_number || '#0000' }}</span>
        </div>

        <div class="ticket-row">
          <span class="text-grey-darken-3 label-col">TIPO:</span>
          <span class="text-black value-col text-right">{{ delivery.operation_type === 'BATE_VOLTA' ? 'BATE VOLTA' : 'PADRÃO (2 ETAPAS)' }}</span>
        </div>
      </div>

      <!-- Seção do QR Code e Status -->
      <div class="my-2 d-flex flex-column align-center justify-center">
        <img
          :src="qrCodeUrl"
          alt="QR Code da Ordem"
          class="qr-code-img border pa-1 bg-white"
        />
        <div
          class="status-badge mt-2"
          :class="delivery.status === 'CONCLUIDO' ? 'status-concluido' : 'status-pendente'"
        >
          {{ delivery.status === 'CONCLUIDO' ? 'CONCLUÍDO / BAIXADO' : 'PENDENTE DE RETIRADA' }}
        </div>
      </div>

      <!-- Rodapé Obrigatório -->
      <div class="border-t-solid pt-1 mt-1 text-center">
        <p class="text-black font-weight-black" style="font-size: 7.5px; line-height: 1.2;">
          É OBRIGATÓRIO APRESENTAR ESTE DOC. P/ RECEBIMENTO DO GARRAFÃO DE ÁGUA CHEIO.
        </p>
      </div>
    </div>

    <!-- Botões de Ação do Comprovante -->
    <div class="mt-4 w-100 d-flex flex-column ga-2">
      <v-btn
        color="secondary"
        variant="elevated"
        size="small"
        prepend-icon="mdi-download"
        :loading="downloading"
        @click="downloadAsPng"
        block
      >
        Baixar Comprovante (PNG)
      </v-btn>

      <v-btn
        color="success"
        variant="elevated"
        size="small"
        prepend-icon="mdi-whatsapp"
        @click="shareWhatsApp"
        block
      >
        Compartilhar via WhatsApp
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import html2canvas from 'html2canvas';

const props = defineProps({
  delivery: {
    type: Object,
    required: true,
  },
});

const downloading = ref(false);

const qrCodeUrl = computed(() => {
  const code = props.delivery?.order_number || 'PCPA-0000';
  return `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(code)}`;
});

const formatDate = (dateVal) => {
  if (!dateVal) return '--/--/----';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return dateVal;
  return d.toLocaleDateString('pt-BR');
};

const downloadAsPng = async () => {
  downloading.value = true;
  try {
    const cardEl = document.getElementById('protocol-card-printable');
    if (!cardEl) return;

    const canvas = await html2canvas(cardEl, {
      scale: 3,
      useCORS: true,
      backgroundColor: '#ffffff',
    });

    const link = document.createElement('a');
    const orderClean = (props.delivery.order_number || '0000').replace(/[^a-zA-Z0-9_-]/g, '_');
    link.download = `Protocolo_Agua_PCPA_${orderClean}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
  } catch (err) {
    console.error('Erro ao gerar PNG do comprovante:', err);
  } finally {
    downloading.value = false;
  }
};

const shareWhatsApp = () => {
  const d = props.delivery;
  const order = d.order_number || '---';
  const sector = d.sector || '---';
  const qtd = d.empty_bottles_count || 1;
  const data = formatDate(d.reception_date);
  const previsao = d.operation_type === 'BATE_VOLTA' ? 'IMEDIATA' : formatDate(d.estimated_date);

  const text =
    `*POLÍCIA CIVIL DO PARÁ - ALMOXARIFADO*\n` +
    `*PROTOCOLO DE ENTREGA DE ÁGUA*\n\n` +
    `• *Ordem:* ${order}\n` +
    `• *Setor:* ${sector}\n` +
    `• *Qtd. Garrafões:* ${qtd}\n` +
    `• *Data:* ${data}\n` +
    `• *Previsão Cheio:* ${previsao}\n\n` +
    `• *QRCODE:* https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${order}\n\n` +
    `_Apresente este protocolo ou QR Code para retirar os garrafões cheios._`;

  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
};
</script>

<style scoped>
.protocol-card-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 320px;
}

.protocol-card {
  width: 320px;
  background-color: #ffffff;
  border: 2px solid #000000;
  color: #000000;
  font-family: Arial, Helvetica, sans-serif;
  box-sizing: border-box;
}

.ticket-header {
  border: 1px solid #000000;
  background-color: #f1f3f5;
}

.ticket-body {
  font-size: 11px;
  line-height: 1.4;
}

.ticket-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
  width: 100%;
}

.label-col {
  flex-shrink: 0;
  width: 115px;
  font-weight: bold;
}

.value-col {
  flex: 1;
  text-align: right;
  word-break: break-word;
  white-space: normal;
  line-height: 1.2;
}

.border-dashed-bottom {
  border-bottom: 1px dashed #ced4da;
}

.border-t-solid {
  border-top: 1px solid #000000;
}

.qr-code-img {
  width: 130px;
  height: 130px;
  border: 1px solid #ced4da;
}

.font-mono {
  font-family: Consolas, "Courier New", monospace;
}

/* Badge nativo compatível com renderização do html2canvas */
.status-badge {
  display: inline-block;
  padding: 3px 12px;
  font-size: 9.5px;
  font-weight: 800;
  border-radius: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: center;
}

.status-pendente {
  background-color: #ffc107;
  color: #000000;
  border: 1px solid #e0a800;
}

.status-concluido {
  background-color: #28a745;
  color: #ffffff;
  border: 1px solid #1e7e34;
}
</style>

