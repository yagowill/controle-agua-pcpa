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

          <!-- Mensagem de status (ex: senha redefinida/alterada) -->
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
              <!-- Nome de Usuário -->
              <label for="username" class="field-label">
                Nome de Usuário <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="username"
                v-model="form.username"
                placeholder="Digite o seu nome de usuário"
                type="text"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-account-outline"
                :error-messages="form.errors.username"
                autofocus
                autocomplete="username"
                hide-details="auto"
                class="mb-4"
              />

              <!-- Senha -->
              <label for="password" class="field-label">
                Senha <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="password"
                v-model="form.password"
                placeholder="Digite a sua senha"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                :error-messages="form.errors.password"
                autocomplete="current-password"
                hide-details="auto"
                class="mb-2"
              />

              <!-- Lembrar-me -->
              <div class="d-flex align-center mb-5">
                <v-checkbox
                  v-model="form.remember"
                  label="Lembrar-me"
                  density="compact"
                  hide-details
                  color="primary"
                />
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

          <!-- Aviso institucional: cadastro e reset de senha são feitos pelo administrador -->
          <v-card-text class="text-center py-3">
            <div class="text-caption text-grey-darken-1 d-flex align-center justify-center ga-1">
              <v-icon icon="mdi-information-outline" size="x-small" />
              Esqueceu a senha? Solicite o reset ao administrador do SCEA.
            </div>
            <div class="text-caption text-grey-darken-1 mt-1">
              O cadastro de novos usuários é realizado exclusivamente pelo administrador.
            </div>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
  status: { type: String },
});

const form = useForm({
  username: '',
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
