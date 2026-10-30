<template>
  <v-app class="bg-background">
    <!-- BARRA SUPERIOR INSTITUCIONAL (TOP BAR ESTILO SRH) -->
    <v-app-bar color="secondary" density="compact" flat class="px-2 px-sm-6 border-b">
      <div class="d-flex align-center">
        <span class="text-subtitle-2 font-weight-bold text-slate-100">SCEA</span>
        <span class="text-caption text-grey-lighten-1 ms-2 d-none d-sm-inline">
          | Sistema de Controle de Entrega de Água - PCPA
        </span>
      </div>

      <v-spacer></v-spacer>

      <div class="d-flex align-center ga-2">
        <!-- Informações do usuário autenticado -->
        <div v-if="authUser" class="d-none d-md-flex align-center ga-1">
          <v-icon icon="mdi-account-circle" size="small" color="grey-lighten-2" />
          <div class="text-caption text-grey-lighten-2">
            <span class="font-weight-bold">{{ authUser.name }}</span>
            <v-chip
              size="x-small"
              :color="authUser.role === 'admin' ? 'warning' : 'primary'"
              variant="tonal"
              class="ms-1 text-uppercase"
            >{{ authUser.role }}</v-chip>
          </div>
        </div>

        <v-btn
          variant="text"
          size="small"
          color="white"
          prepend-icon="mdi-home"
          :href="route('entregas.nova')"
        >
          Início
        </v-btn>

        <v-btn
          v-if="$page.props.auth?.user"
          variant="text"
          size="small"
          color="white"
          prepend-icon="mdi-lock-reset"
          :href="route('password.force')"
        >
          Alterar Senha
        </v-btn>

        <v-btn
          v-if="$page.props.auth?.user"
          variant="text"
          size="small"
          color="white"
          prepend-icon="mdi-logout"
          @click="logout"
        >
          Sair
        </v-btn>
      </div>
    </v-app-bar>

    <!-- CABEÇALHO COM BRASÃO OFICIAL DA PCPA -->
    <div class="header-branding bg-white border-b py-5 px-4 text-center mt-12 sm:mt-14">
      <div class="d-flex justify-center mb-2">
        <img
          src="https://srh.pc.pa.gov.br/images/policia2.png"
          alt="Brasão da Polícia Civil do Estado do Pará"
          class="brasao-pcpa"
        />
      </div>

      <h1 class="text-h6 text-sm-h5 font-weight-bold text-secondary mb-0">
        Polícia Civil do Estado do Pará
      </h1>
      <h2 class="text-subtitle-2 text-sm-subtitle-1 text-grey-darken-1 font-weight-medium mt-1">
        Controle de Entrega de Água - Almoxarifado
      </h2>
    </div>

    <!-- ABAS PRINCIPAIS DE NAVEGAÇÃO -->
    <div class="nav-tabs-wrapper bg-white border-b">
      <v-container class="py-0 px-2 max-w-lg">
        <v-tabs
          :model-value="currentTab"
          color="primary"
          align-tabs="center"
          density="comfortable"
        >
          <v-tab
            :value="'nova'"
            :href="route('entregas.nova')"
            class="text-body-2 font-weight-bold text-none px-4"
          >
            <v-icon icon="mdi-water-plus" class="me-2"></v-icon>
            Nova Entrega
          </v-tab>

          <v-tab
            :value="'baixa'"
            :href="route('entregas.baixa')"
            class="text-body-2 font-weight-bold text-none px-4"
          >
            <v-icon icon="mdi-qrcode-scan" class="me-2"></v-icon>
            Validação e Baixa
          </v-tab>

          <v-tab
            :value="'relatorios'"
            :href="route('entregas.relatorios')"
            class="text-body-2 font-weight-bold text-none px-4"
          >
            <v-icon icon="mdi-chart-box-outline" class="me-2"></v-icon>
            Histórico e Relatórios
          </v-tab>

          <!-- Aba exclusiva do administrador: gestão de usuários -->
          <v-tab
            v-if="isAdmin"
            :value="'usuarios'"
            :href="route('admin.users.index')"
            class="text-body-2 font-weight-bold text-none px-4"
          >
            <v-icon icon="mdi-account-cog-outline" class="me-2"></v-icon>
            Usuários
          </v-tab>
        </v-tabs>
      </v-container>
    </div>

    <!-- ÁREA DE CONTEÚDO PRINCIPAL -->
    <v-main class="py-6">
      <v-container style="max-width: 1080px;">
        <slot />
      </v-container>
    </v-main>

    <!-- RODAPÉ INSTITUCIONAL -->
    <v-footer class="bg-white border-t text-center py-4 d-flex flex-column">
      <div class="text-caption text-grey-darken-1">
        Polícia Civil do Estado do Pará &copy; {{ new Date().getFullYear() }} — Diretoria de Administração / Almoxarifado
      </div>
    </v-footer>
  </v-app>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

// Usuário autenticado atual (disponível via middleware Inertia Share)
const authUser = computed(() => page.props.auth?.user ?? null);

// Apenas administradores visualizam a gestão de usuários
const isAdmin = computed(() => authUser.value?.role === 'admin');

const currentTab = computed(() => {
  const url = page.url;
  if (url.includes('/admin/usuarios')) return 'usuarios';
  if (url.includes('/entregas/baixa')) return 'baixa';
  if (url.includes('/entregas/relatorios')) return 'relatorios';
  return 'nova';
});

const logout = () => {
  router.post(route('logout'));
};
</script>

<style scoped>
.brasao-pcpa {
  height: 80px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
}


.max-w-lg {
  max-width: 1080px;
}
</style>

