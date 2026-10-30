<template>
  <AppLayout>
    <div>
      <!-- ALERTAS DE FEEDBACK -->
      <v-alert
        v-if="flash?.success"
        type="success"
        variant="tonal"
        density="compact"
        class="mb-4"
        closable
      >
        {{ flash.success }}
      </v-alert>
      <v-alert
        v-if="flash?.error"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-4"
        closable
      >
        {{ flash.error }}
      </v-alert>

      <v-card class="elevation-1 border rounded">
        <!-- Barra de Título, Busca e Novo Usuário -->
        <v-card-title class="bg-grey-lighten-4 py-3 px-4 border-b d-flex flex-column flex-sm-row justify-space-between align-sm-center ga-3">
          <span class="text-subtitle-2 font-weight-bold text-secondary">
            <v-icon icon="mdi-account-group-outline" class="me-1"></v-icon>
            Gestão de Usuários
          </span>

          <div class="d-flex flex-wrap align-center ga-2">
            <v-text-field
              v-model="search"
              placeholder="Buscar nome, usuário, matrícula..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
              style="width: 260px;"
              @keyup.enter="applySearch"
              @click:clear="clearSearch"
            ></v-text-field>

            <v-btn
              color="primary"
              variant="elevated"
              size="medium"
              class="py-1 px-3"
              prepend-icon="mdi-account-plus"
              @click="openCreateDialog"
            >
              Novo Usuário
            </v-btn>
          </div>
        </v-card-title>

        <!-- TABELA DE USUÁRIOS -->
        <v-table density="comfortable" hover>
          <thead>
            <tr class="bg-grey-lighten-4 text-caption font-weight-bold">
              <th>NOME COMPLETO</th>
              <th>USUÁRIO</th>
              <th>MATRÍCULA</th>
              <th>PERFIL</th>
              <th>SETOR / LOTAÇÃO</th>
              <th class="text-center">STATUS</th>
              <th class="text-center">SENHA</th>
              <th class="text-center">AÇÕES</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id" class="text-caption">
              <td class="font-weight-medium">{{ user.name }}</td>
              <td class="font-weight-black font-mono text-primary">{{ user.username }}</td>
              <td>{{ user.matricula || '-' }}</td>
              <td>
                <v-chip
                  size="x-small"
                  :color="user.role === 'admin' ? 'warning' : 'primary'"
                  variant="tonal"
                  class="text-uppercase font-weight-bold"
                >
                  {{ user.role }}
                </v-chip>
              </td>
              <td>{{ user.sector || '-' }}</td>
              <td class="text-center">
                <v-chip
                  size="x-small"
                  :color="user.is_active ? 'success' : 'grey'"
                  variant="flat"
                  class="font-weight-bold"
                >
                  {{ user.is_active ? 'ATIVO' : 'INATIVO' }}
                </v-chip>
              </td>
              <td class="text-center">
                <v-chip
                  v-if="user.must_change_password"
                  size="x-small"
                  color="warning"
                  variant="tonal"
                >
                  PROVISÓRIA
                </v-chip>
                <span v-else class="text-grey">Definida</span>
              </td>
              <td class="text-center text-no-wrap">
                <!-- Reset de senha -->
                <v-btn
                  size="x-small"
                  variant="text"
                  icon="mdi-lock-reset"
                  color="warning"
                  title="Resetar Senha"
                  @click="openResetDialog(user)"
                ></v-btn>

                <!-- Ativar / Desativar acesso -->
                <v-btn
                  size="x-small"
                  variant="text"
                  :icon="user.is_active ? 'mdi-account-off-outline' : 'mdi-account-check-outline'"
                  :color="user.is_active ? 'grey-darken-2' : 'success'"
                  :title="user.is_active ? 'Desativar Acesso' : 'Ativar Acesso'"
                  @click="toggleActive(user)"
                ></v-btn>

                <!-- Excluir usuário -->
                <v-btn
                  size="x-small"
                  variant="text"
                  icon="mdi-trash-can-outline"
                  color="error"
                  title="Excluir Usuário"
                  @click="openDeleteDialog(user)"
                ></v-btn>
              </td>
            </tr>

            <tr v-if="!users.data || users.data.length === 0">
              <td colspan="8" class="text-center py-6 text-grey-darken-1">
                Nenhum usuário encontrado.
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- PAGINAÇÃO -->
        <v-card-actions v-if="users.links && users.links.length > 3" class="justify-center border-t py-2">
          <div class="d-flex ga-1">
            <v-btn
              v-for="(link, idx) in users.links"
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

    <!-- DIALOG: CADASTRO DE NOVO USUÁRIO -->
    <v-dialog v-model="dialogCreate" max-width="620" persistent>
      <v-card class="rounded">
        <v-card-title class="bg-grey-lighten-4 py-3 px-5 border-b text-subtitle-2 font-weight-bold text-secondary d-flex align-center ga-2">
          <v-icon icon="mdi-account-plus-outline" size="small" />
          Novo Usuário
        </v-card-title>

        <v-card-text class="pa-5">
          <v-form @submit.prevent="submitCreate">
            <v-row dense>
              <v-col cols="12">
                <label for="create-name" class="field-label">
                  Nome Completo <span class="field-label__required">*</span>
                </label>

                <v-text-field
                  id="create-name"
                  v-model="createForm.name"
                  placeholder="Nome Sobrenome"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-account-outline"
                  :error-messages="createForm.errors.name"
                  autofocus
                  autocomplete="off"
                  hide-details="auto"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-username" class="field-label">
                  Nome de Usuário <span class="field-label__required">*</span>
                </label>

                <v-text-field
                  id="create-username"
                  v-model="createForm.username"
                  placeholder="ex: joao.silva"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-at"
                  hint="Usado para login (letras, números, ., - e _)"
                  persistent-hint
                  :error-messages="createForm.errors.username"
                  autocomplete="off"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-matricula" class="field-label">
                  Matrícula <span class="field-label__optional">(Opcional)</span>
                </label>

                <v-text-field
                  id="create-matricula"
                  v-model="createForm.matricula"
                  placeholder="Matrícula"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-card-account-details-outline"
                  :error-messages="createForm.errors.matricula"
                  autocomplete="off"
                  hide-details="auto"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-role" class="field-label">
                  Perfil de Acesso <span class="field-label__required">*</span>
                </label>

                <v-select
                  id="create-role"
                  v-model="createForm.role"
                  :items="roleOptions"
                  placeholder="Selecione o perfil"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-shield-account-outline"
                  :error-messages="createForm.errors.role"
                  hide-details="auto"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-sector" class="field-label">
                  Setor / Lotação <span class="field-label__optional">(Opcional)</span>
                </label>

                <v-combobox
                  id="create-sector"
                  v-model="createForm.sector"
                  :items="defaultSectors"
                  placeholder="Selecione ou digite o setor"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-office-building-outline"
                  :error-messages="createForm.errors.sector"
                  persistent-placeholder
                  clearable
                  hide-details="auto"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-password" class="field-label">
                  Senha Provisória <span class="field-label__required">*</span>
                </label>

                <v-text-field
                  id="create-password"
                  v-model="createForm.password"
                  placeholder="Mínimo de 8 caracteres"
                  :type="showPasswords ? 'text' : 'password'"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="showPasswords = !showPasswords"
                  hint="Mínimo de 8 caracteres"
                  persistent-hint
                  :error-messages="createForm.errors.password"
                  autocomplete="new-password"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <label for="create-password-confirmation" class="field-label">
                  Confirmar Senha <span class="field-label__required">*</span>
                </label>

                <v-text-field
                  id="create-password-confirmation"
                  v-model="createForm.password_confirmation"
                  placeholder="Repita a senha provisória"
                  :type="showPasswords ? 'text' : 'password'"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :error-messages="createForm.errors.password_confirmation"
                  autocomplete="new-password"
                  hide-details="auto"
                />
              </v-col>

              <v-col cols="12">
                <v-switch
                  v-model="createForm.is_active"
                  label="Conta ativa (pode acessar o sistema)"
                  color="primary"
                  density="compact"
                  hide-details
                  inset
                />
              </v-col>
            </v-row>

            <v-alert type="info" variant="tonal" density="compact" class="mt-3">
              O usuário será obrigado a alterar esta senha no primeiro acesso.
            </v-alert>

            <div class="d-flex justify-end ga-2 mt-4">
              <v-btn variant="text" @click="closeCreateDialog">Cancelar</v-btn>
              <v-btn
                type="submit"
                color="primary"
                variant="elevated"
                class="px-4"
                prepend-icon="mdi-content-save"
                :loading="createForm.processing"
              >
                Cadastrar Usuário
              </v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- DIALOG: RESET DE SENHA -->
    <v-dialog v-model="dialogReset" max-width="460" persistent>
      <v-card class="rounded">
        <v-card-title class="bg-grey-lighten-4 py-3 px-5 border-b text-subtitle-2 font-weight-bold text-secondary d-flex align-center ga-2">
          <v-icon icon="mdi-lock-reset" size="small" />
          Resetar Senha
        </v-card-title>

        <v-card-text class="pa-5">
          <template v-if="selectedUser">
            <v-alert type="warning" variant="tonal" density="compact" class="mb-4">
              Será definida uma senha temporária para
              <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.username }}).
              O usuário deverá alterá-la no próximo acesso.
            </v-alert>

            <v-form @submit.prevent="submitReset">
              <label for="reset-password" class="field-label">
                Senha Temporária <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="reset-password"
                v-model="resetForm.password"
                placeholder="Mínimo de 8 caracteres"
                :type="showPasswords ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPasswords = !showPasswords"
                hint="Mínimo de 8 caracteres"
                persistent-hint
                :error-messages="resetForm.errors.password"
                autocomplete="new-password"
                class="mb-4"
              />

              <label for="reset-password-confirmation" class="field-label">
                Confirmar Senha Temporária <span class="field-label__required">*</span>
              </label>

              <v-text-field
                id="reset-password-confirmation"
                v-model="resetForm.password_confirmation"
                placeholder="Repita a senha temporária"
                :type="showPasswords ? 'text' : 'password'"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-check-outline"
                :error-messages="resetForm.errors.password_confirmation"
                autocomplete="new-password"
                hide-details="auto"
              />

              <div class="d-flex justify-end ga-2 mt-4">
                <v-btn variant="text" @click="closeResetDialog">Cancelar</v-btn>
                <v-btn
                  type="submit"
                  color="warning"
                  variant="elevated"
                  prepend-icon="mdi-lock-reset"
                  :loading="resetForm.processing"
                >
                  Redefinir Senha
                </v-btn>
              </div>
            </v-form>
          </template>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- DIALOG: CONFIRMAÇÃO DE EXCLUSÃO -->
    <v-dialog v-model="dialogDelete" max-width="420">
      <v-card class="rounded">
        <v-card-title class="text-subtitle-2 font-weight-bold text-error py-3 px-5 border-b">
          <v-icon icon="mdi-alert-outline" class="me-1"></v-icon>
          Excluir Usuário
        </v-card-title>
        <v-card-text class="pa-5">
          <template v-if="selectedUser">
            Deseja realmente excluir o usuário
            <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.username }})?
            Esta ação não pode ser desfeita.
          </template>
        </v-card-text>
        <v-card-actions class="px-5 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" variant="elevated" :loading="deleting" @click="confirmDelete">
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters?.search ?? '');
const showPasswords = ref(false);

const roleOptions = [
  { title: 'Atendente', value: 'atendente' },
  { title: 'Administrador', value: 'admin' },
];

const defaultSectors = [
  'ALMOXARIFADO',
  'PATRIMÔNIO'
];

const dialogCreate = ref(false);
const dialogReset = ref(false);
const dialogDelete = ref(false);
const selectedUser = ref(null);
const deleting = ref(false);

const createForm = useForm({
  name: '',
  username: '',
  matricula: '',
  password: '',
  password_confirmation: '',
  role: 'atendente',
  sector: null,
  is_active: true,
});

const resetForm = useForm({
  password: '',
  password_confirmation: '',
});

const applySearch = () => {
  router.get(
    route('admin.users.index'),
    { search: search.value || undefined },
    { preserveState: true, replace: true }
  );
};

const clearSearch = () => {
  search.value = '';
  applySearch();
};

const goToPage = (url) => {
  if (url) {
    router.visit(url, { preserveState: true });
  }
};

const openCreateDialog = () => {
  createForm.clearErrors();
  createForm.reset();
  createForm.role = 'atendente';
  createForm.is_active = true;
  showPasswords.value = false;
  dialogCreate.value = true;
};

const closeCreateDialog = () => {
  dialogCreate.value = false;
  createForm.reset();
  createForm.clearErrors();
};

const submitCreate = () => {
  createForm.post(route('admin.users.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeCreateDialog();
    },
    onError: () => {
      // Mantém o diálogo aberto para correção dos campos
      dialogCreate.value = true;
    },
  });
};

const openResetDialog = (user) => {
  selectedUser.value = user;
  resetForm.reset();
  resetForm.clearErrors();
  showPasswords.value = false;
  dialogReset.value = true;
};

const closeResetDialog = () => {
  dialogReset.value = false;
  resetForm.reset();
  resetForm.clearErrors();
  selectedUser.value = null;
};

const submitReset = () => {
  if (!selectedUser.value) return;

  resetForm.patch(route('admin.users.reset-password', selectedUser.value.id), {
    preserveScroll: true,
    onSuccess: () => closeResetDialog(),
  });
};

const toggleActive = (user) => {
  router.patch(route('admin.users.toggle-active', user.id), {}, { preserveScroll: true });
};

const openDeleteDialog = (user) => {
  selectedUser.value = user;
  dialogDelete.value = true;
};

const confirmDelete = () => {
  if (!selectedUser.value) return;

  deleting.value = true;

  router.delete(route('admin.users.destroy', selectedUser.value.id), {
    preserveScroll: true,
    onFinish: () => {
      deleting.value = false;
      dialogDelete.value = false;
      selectedUser.value = null;
    },
  });
};
</script>

<style scoped>
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>
