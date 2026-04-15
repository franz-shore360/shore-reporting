<template>
  <div class="imports-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Imports</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
          <button v-if="canCreate" type="button" class="btn btn-primary" @click="openAddModal">Add Import</button>
        </div>
      </div>
    </div>

    <div v-if="!canViewList && !canCreate" class="card">
      <p class="permission-message">You do not have permission to access imports.</p>
    </div>

    <template v-else>
      <div v-if="!canViewList" class="card">
        <p class="permission-message">You do not have permission to view the import list.</p>
      </div>
      <div v-else class="card table-card">
        <ImportsTable ref="tableRef" :can-fetch="canViewList" />
      </div>
    </template>

    <div v-if="showModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-header">
          <h2 class="modal-title">Add Import</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeModal">&times;</button>
        </div>
        <form class="modal-form" @submit.prevent="submitImport">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="form-group">
            <label for="import_entity_type">Entity Type <span class="required-asterisk">*</span></label>
            <select id="import_entity_type" v-model="form.entity_type" required>
              <option value="" disabled>Select entity…</option>
              <option v-if="canImportDepartment" value="department">Departments</option>
              <option v-if="canCreate" value="gl_account">GL Accounts</option>
            </select>
          </div>
          <div class="form-group">
            <label for="import_file">File <span class="required-asterisk">*</span></label>
            <input
              id="import_file"
              ref="fileInputRef"
              type="file"
              required
              accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            />
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="uploadLoading">
              {{ uploadLoading ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <SuccessToast ref="successToastRef" />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
import ImportsTable from '../components/ImportsTable.vue';
import SuccessToast from '../components/SuccessToast.vue';

const tableRef = ref(null);
const fileInputRef = ref(null);
const showModal = ref(false);
const form = reactive({
  entity_type: '',
});
const formError = ref('');
const uploadLoading = ref(false);
const successToastRef = ref(null);

const permissionNames = computed(() => authState.user?.permission_names ?? []);
const canViewList = computed(() => permissionNames.value.includes('import-list'));
const canCreate = computed(() => permissionNames.value.includes('import-create'));
const canImportDepartment = computed(() => permissionNames.value.includes('department-import'));

function resetForm() {
  form.entity_type = '';
  formError.value = '';
  const input = fileInputRef.value;
  if (input) input.value = '';
}

function openAddModal() {
  resetForm();
  if (!canImportDepartment.value && canCreate.value) {
    form.entity_type = 'gl_account';
  }
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

async function submitImport() {
  formError.value = '';
  const input = fileInputRef.value;
  const file = input?.files?.[0];
  if (!form.entity_type) {
    formError.value = 'Please select an entity type.';
    return;
  }
  if (!file) {
    formError.value = 'Please choose a file.';
    return;
  }

  uploadLoading.value = true;
  const fd = new FormData();
  fd.append('entity_type', form.entity_type);
  fd.append('file', file);

  try {
    await axios.post('/api/imports', fd);
    closeModal();
    await tableRef.value?.refresh();
    successToastRef.value?.show('Import file uploaded successfully.');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      formError.value = Object.values(errors).flat().join(' ');
    } else {
      formError.value = e.response?.data?.message ?? 'Upload failed.';
    }
  } finally {
    uploadLoading.value = false;
  }
}
</script>

<style scoped>
.imports-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.imports-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
</style>
