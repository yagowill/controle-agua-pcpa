<template>
  <v-app class="bg-background">
    <Head title="Cadastro de Usuário" />

    <v-main class="d-flex align-center justify-center" style="min-height: 100vh;">
      <v-container max-width="520">
        <!-- Cabeçalho Institucional PCPA -->
        <div class="text-center mb-6">
          <img
            src="https://srh.pc.pa.gov.br/images/policia2.png"
            alt="Brasão da Polícia Civil do Pará"
            height="72"
            class="mb-3"
            style="object-fit: contain;"
          />
          <div class="text-subtitle-1 font-weight-bold text-secondary">
            Polícia Civil do Estado do Pará
          </div>
          <div class="text-caption text-grey-darken-1">
            Cadastro de Acesso — SCEA
          </div>
        </div>

        <!-- Card de Cadastro -->
        <v-card class="elevation-2 border rounded-lg" color="surface">
          <v-card-title class="bg-grey-lighten-4 py-3 px-5 border-b text-subtitle-2 font-weight-bold text-secondary d-flex align-center ga-2">
            <v-icon icon="mdi-account-plus-outline" size="small" />
            Novo Usuário
          </v-card-title>

          <v-card-text class="pa-5">
            <v-form @submit.prevent="submit">
              <!-- Nome Completo -->
              <v-text-field
                v-model="form.name"
                label="Nome Completo do Servidor"
                type="text"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-account-outline"
                :error-messages="form.errors.name"
                autofocus
                autocomplete="name"
                class="mb-3"
              />

              <!-- E-mail Institucional -->
              <v-text-field
                v-model="form.email"
                label="E-mail Institucional"
                type="email"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="form.errors.email"
                autocomplete="username"
                class="mb-3"
              />

              <!-- Perfil de Acesso e Setor em linha -->
              <v-row dense class="mb-1">
                <v-col cols="12" sm="5">
                  <v-select
                    v-model="form.role"
                    :items="roleOptions"
                    label="Perfil de Acesso*"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-shield-account-outline"
                    :error-messages="form.errors.role"
                  />
                </v-col>
                <v-col cols="12" sm="7">
                  <v-combobox
                    v-model="form.sector"
                    :items="defaultSectors"
                    label="Setor / Lotação"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-office-building-outline"
                    :error-messages="form.errors.sector"
                    clearable
                  />
                </v-col>
              </v-row>

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
                autocomplete="new-password"
                class="mb-3"
              />

              <!-- Confirmar Senha -->
              <v-text-field
                v-model="form.password_confirmation"
                label="Confirmar Senha"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-check-outline"
                :error-messages="form.errors.password_confirmation"
                autocomplete="new-password"
                class="mb-5"
              />

              <!-- Botão de Cadastrar -->
              <v-btn
                type="submit"
                color="primary"
                variant="elevated"
                block
                size="large"
                :loading="form.processing"
                prepend-icon="mdi-account-plus"
              >
                Cadastrar
              </v-btn>
            </v-form>
          </v-card-text>

          <v-divider />

          <!-- Link para login -->
          <v-card-text class="text-center py-3">
            <span class="text-caption text-grey-darken-1">Já tem acesso?</span>
            <Link
              :href="route('login')"
              class="text-caption text-primary font-weight-bold text-decoration-none ms-1"
            >
              Entrar no Sistema
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

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'atendente',
  sector: '',
});

const showPassword = ref(false);

const roleOptions = [
  { title: 'Atendente', value: 'atendente' },
  { title: 'Administrador', value: 'admin' },
];

const defaultSectors = [
  'ALMOXARIFADO CENTRAL',
  'METROPOLITANO',
  'DELEGACIA GERAL (DG)',
  'NOP (NÚCLEO DE OPERAÇÕES)',
  'DIP (DIVISÃO DE INTELIGÊNCIA POLICIAL)',
  'SECCIONAL DE SÃO BRÁS',
  'SECCIONAL DA PEDREIRA',
  'SECCIONAL DO COMÉRCIO',
  'SECCIONAL DA MARAMBAIA',
  'SECCIONAL DA CIDADE NOVA',
  'DIRETORIA DE RECURSOS MATERIAIS',
];

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>
