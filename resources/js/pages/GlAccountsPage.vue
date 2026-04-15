<template>
  <div class="gl-accounts-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">GL Accounts</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
          <button v-if="canCreate" type="button" class="btn btn-primary" @click="openAddModal">Add GL Account</button>
        </div>
      </div>
    </div>

    <div v-if="!canViewList" class="card">
      <p class="permission-message">You do not have permission to view the GL account list.</p>
    </div>
    <div v-else class="card table-card">
      <GlAccountsTable
        ref="tableRef"
        :can-fetch="canViewList"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @edit="openEditModal"
        @delete-request="confirmDelete"
      />
    </div>

    <div v-if="showModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-header">
          <h2 class="modal-title">{{ editingRow ? 'Edit GL Account' : 'Add GL Account' }}</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="submitForm" class="modal-form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="form-group">
            <label for="modal_gl_name">Name <span class="required-asterisk">*</span></label>
            <input id="modal_gl_name" v-model="form.name" type="text" required />
          </div>
          <div class="form-group">
            <label for="modal_gl_code">Code <span class="required-asterisk">*</span></label>
            <input id="modal_gl_code" v-model="form.code" type="text" required autocomplete="off" />
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="submitLoading">
              {{ submitLoading ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmModal
      v-model:open="deleteModalOpen"
      title="Delete GL Account"
      :confirm-label="deleteLoading ? 'Deleting…' : 'Delete'"
      variant="danger"
      :loading="deleteLoading"
      @confirm="deleteRow"
    >
      <p v-if="!deleteError">
        Are you sure you want to delete <strong>{{ rowToDelete?.name }}</strong> ({{ rowToDelete?.code }})? This cannot be undone.
      </p>
      <p v-else class="form-error">{{ deleteError }}</p>
    </ConfirmModal>

    <SuccessToast ref="successToastRef" />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
import GlAccountsTable from '../components/GlAccountsTable.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import SuccessToast from '../components/SuccessToast.vue';

const tableRef = ref(null);
const showModal = ref(false);
const editingRow = ref(null);
const rowToDelete = ref(null);
const submitLoading = ref(false);
const deleteLoading = ref(false);
const formError = ref('');
const deleteError = ref('');
const successToastRef = ref(null);

const form = reactive({
  name: '',
  code: '',
});

const permissionNames = computed(() => authState.user?.permission_names ?? []);
const canViewList = computed(() => permissionNames.value.includes('gl-account-list'));
const canCreate = computed(() => permissionNames.value.includes('gl-account-create'));
const canEdit = computed(() => permissionNames.value.includes('gl-account-edit'));
const canDelete = computed(() => permissionNames.value.includes('gl-account-delete'));

const deleteModalOpen = computed({
  get: () => rowToDelete.value != null,
  set(isOpen) {
    if (!isOpen && !deleteLoading.value) {
      rowToDelete.value = null;
      deleteError.value = '';
    }
  },
});

function notifySuccess(text) {
  successToastRef.value?.show(text);
}

function resetForm() {
  form.name = '';
  form.code = '';
  formError.value = '';
}

function openAddModal() {
  editingRow.value = null;
  resetForm();
  showModal.value = true;
}

function openEditModal(row) {
  editingRow.value = row;
  form.name = row.name ?? '';
  form.code = row.code ?? '';
  formError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingRow.value = null;
  resetForm();
}

async function submitForm() {
  formError.value = '';
  submitLoading.value = true;
  const wasEditing = !!editingRow.value;
  try {
    if (editingRow.value) {
      await axios.patch(`/api/gl-accounts/${editingRow.value.id}`, {
        name: form.name,
        code: form.code,
      });
    } else {
      await axios.post('/api/gl-accounts', {
        name: form.name,
        code: form.code,
      });
    }
    closeModal();
    await tableRef.value?.refresh();
    notifySuccess(wasEditing ? 'GL account updated successfully.' : 'GL account added successfully.');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      formError.value = Object.values(errors).flat().join(' ');
    } else {
      formError.value = e.response?.data?.message ?? 'Something went wrong.';
    }
  } finally {
    submitLoading.value = false;
  }
}

function confirmDelete(row) {
  rowToDelete.value = row;
  deleteError.value = '';
}

async function deleteRow() {
  if (!rowToDelete.value) return;
  deleteLoading.value = true;
  deleteError.value = '';
  try {
    await axios.delete(`/api/gl-accounts/${rowToDelete.value.id}`);
    rowToDelete.value = null;
    await tableRef.value?.refresh();
    notifySuccess('GL account deleted successfully.');
  } catch (e) {
    deleteError.value = e.response?.data?.message ?? 'Failed to delete GL account.';
  } finally {
    deleteLoading.value = false;
  }
}
</script>

<style scoped>
.gl-accounts-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.gl-accounts-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
</style>
