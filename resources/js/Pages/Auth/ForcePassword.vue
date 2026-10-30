<template>
  <v-app class="bg-background">
    <Head :title="required ? 'Troca Obrigatória de Senha' : 'Alterar Senha'" />

    <v-main class="d-flex align-center justify-center py-6" style="min-height: 100vh;">
      <v-container max-width="500">
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

        <v-card class="elevation-2 border rounded-lg" color="surface">
          <v-card-title class="bg-grey-lighten-4 py-3 px-5 border-b text-subtitle-2 font-weight-bold text-secondary d-flex align-center ga-2 whitespace-normal">
            <v-icon icon="mdi-shield-lock-outline" size="small" />
            {{ required ? 'Primeiro Acesso — Nova Senha' : 'Alteração de Senha' }}
          </v-card-title>

          <v-card-text class="pa-5">
            <!-- Aviso de troca obrigatória -->
            <v-alert
              :type="required ? 'warning' : 'info'"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              {{ warning || (required
                ? 'Sua senha é provisória. Para acessar o sistema, defina uma nova senha pessoal.'
                : 'Informe a senha atual e defina uma nova senha de acesso.') }}
            </v-alert>

            <div class="text-caption text-grey-darken-2 mb-4 d-flex align-center ga-1">
              <v-icon icon="mdi-account-check-outline" size="small" />
              Usuário: <strong class="ms-1">{{ username }}</strong>
            </div>

            <v-form @submit.prevent="submit">
              <!-- Senha provisória -->
              <label for="current-password" class="field-label">
                Senha Provisória <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="current-password"
                v-model="form.current_password"
                placeholder="Digite a senha provisória recebida"
                :type="showPasswords ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-open-variant-outline"
                :error-messages="form.errors.current_password"
                autofocus
                autocomplete="current-password"
                hide-details="auto"
                class="mb-4"
              />

              <!-- Nova senha -->
              <label for="new-password" class="field-label">
                Nova Senha <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="new-password"
                v-model="form.password"
                placeholder="Digite a nova senha"
                :type="showPasswords ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPasswords = !showPasswords"
                :error-messages="form.errors.password"
                hint="Mínimo de 8 caracteres, diferente da senha provisória"
                persistent-hint
                autocomplete="new-password"
                class="mb-4"
              />

              <!-- Confirmação -->
              <label for="confirm-password" class="field-label">
                Confirmar Nova Senha <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="confirm-password"
                v-model="form.password_confirmation"
                placeholder="Repita a nova senha"
                :type="showPasswords ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-check-outline"
                :error-messages="form.errors.password_confirmation"
                autocomplete="new-password"
                hide-details="auto"
                class="mb-5"
              />

              <v-btn
                type="submit"
                color="primary"
                variant="elevated"
                block
                size="large"
                :loading="form.processing"
                prepend-icon="mdi-content-save-check"
              >
                Salvar Nova Senha
              </v-btn>
            </v-form>
          </v-card-text>

          <v-divider />

          <v-card-text class="d-flex justify-space-between align-center py-2">
            <v-btn
              v-if="!required"
              variant="text"
              size="small"
              color="secondary"
              prepend-icon="mdi-arrow-left"
              @click="backToSystem"
            >
              Voltar ao Sistema
            </v-btn>
            <span v-else class="text-caption text-grey-darken-1">
              A troca de senha é obrigatória por segurança.
            </span>
            <v-btn
              variant="text"
              size="small"
              color="secondary"
              prepend-icon="mdi-logout"
              @click="logout"
            >
              Sair
            </v-btn>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({
  username: { type: String, default: '' },
  warning: { type: String, default: '' },
  required: { type: Boolean, default: true },
});

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showPasswords = ref(false);

const submit = () => {
  form.post(route('password.force.update'), {
    onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
  });
};

const backToSystem = () => {
  router.visit(route('entregas.nova'));
};

const logout = () => {
  router.post(route('logout'));
};
</script>
