<template>
  <v-app class="bg-background">
    <Head title="Entrar no Sistema" />

    <v-main class="d-flex align-center justify-center" style="min-height: 100vh;">
      <v-container max-width="440">
        <!-- Cabeçalho Institucional PCPA -->
        <div class="text-center mb-6">
          <img
            src="https://srh.pc.pa.gov.br/images/policia2.png"
            alt="Brasão da Polícia Civil do Pará"
            height="72"
            class="mb-3 mx-auto"
            style="object-fit: contain;"
          />
          <div class="text-subtitle-1 font-weight-bold text-secondary">
            Polícia Civil do Estado do Pará
          </div>
          <div class="text-caption text-grey-darken-1">
            Sistema de Controle de Entrega de Água — SCEA
          </div>
        </div>

        <!-- Card de Login -->
        <v-card class="elevation-2 border rounded-lg" color="surface">
          <v-card-title class="bg-grey-lighten-4 py-3 px-5 border-b text-subtitle-2 font-weight-bold text-secondary d-flex align-center ga-2">
            <v-icon icon="mdi-lock-outline" size="small" />
            Acesso ao Sistema
          </v-card-title>

          <!-- Mensagem de status (ex: senha redefinida) -->
          <v-alert
            v-if="status"
            type="success"
            variant="tonal"
            density="compact"
            class="ma-4 mb-0"
          >
            {{ status }}
          </v-alert>

          <v-card-text class="pa-5">
            <v-form @submit.prevent="submit">
              <!-- E-mail -->
              <v-text-field
                v-model="form.email"
                label="E-mail Institucional"
                type="email"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="form.errors.email"
                autofocus
                autocomplete="username"
                class="mb-3"
              />

              <!-- Senha -->
              <v-text-field
                v-model="form.password"
                label="Senha"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                :error-messages="form.errors.password"
                autocomplete="current-password"
                class="mb-3"
              />

              <!-- Lembrar-me e Esqueci a senha -->
              <div class="d-flex align-center justify-space-between mb-5">
                <v-checkbox
                  v-model="form.remember"
                  label="Lembrar-me"
                  density="compact"
                  hide-details
                  color="primary"
                />
                <Link
                  v-if="canResetPassword"
                  :href="route('password.request')"
                  class="text-caption text-primary text-decoration-none"
                >
                  Esqueceu a senha?
                </Link>
              </div>

              <!-- Botão de Entrar -->
              <v-btn
                type="submit"
                color="primary"
                variant="elevated"
                block
                size="large"
                :loading="form.processing"
                prepend-icon="mdi-login"
              >
                Entrar
              </v-btn>
            </v-form>
          </v-card-text>

          <v-divider />

          <!-- Link para cadastro -->
          <v-card-text class="text-center py-3">
            <span class="text-caption text-grey-darken-1">Não tem acesso?</span>
            <Link
              :href="route('register')"
              class="text-caption text-primary font-weight-bold text-decoration-none ms-1"
            >
              Solicitar Cadastro
            </Link>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: { type: Boolean },
  status: { type: String },
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const showPassword = ref(false);

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
