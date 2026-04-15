<template>
  <div class="departments-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Departments</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
          <button v-if="canCreate" type="button" class="btn btn-primary" @click="openAddModal">Add Department</button>
        </div>
      </div>
    </div>

    <div v-if="!canViewList" class="card">
      <p class="permission-message">You do not have permission to view the department list.</p>
    </div>
    <div v-else class="card table-card">
      <DepartmentsTable
        ref="departmentsTableRef"
        :can-fetch="canViewList"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @edit="openEditModal"
        @delete-request="confirmDelete"
      />
    </div>

    <!-- Add/Edit Department Modal -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-header">
          <h2 class="modal-title">{{ editingDepartment ? 'Edit Department' : 'Add Department' }}</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="submitDepartment" class="modal-form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="form-group">
            <label for="modal_name">Name <span class="required-asterisk">*</span></label>
            <input id="modal_name" v-model="form.name" type="text" required />
          </div>
          <div class="form-group checkbox-group">
            <input
              id="modal_is_active"
              v-model="form.is_active"
              type="checkbox"
              class="checkbox"
            />
            <label for="modal_is_active">Active</label>
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
      v-model:open="departmentDeleteModalOpen"
      title="Delete Department"
      :confirm-label="deleteLoading ? 'Deleting…' : 'Delete'"
      variant="danger"
      :loading="deleteLoading"
      @confirm="deleteDepartment"
    >
      <p v-if="!deleteError">
        Are you sure you want to delete <strong>{{ departmentToDelete?.name }}</strong>? This cannot be undone.
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
import DepartmentsTable from '../components/DepartmentsTable.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import SuccessToast from '../components/SuccessToast.vue';

const departmentsTableRef = ref(null);

const showModal = ref(false);
const editingDepartment = ref(null);
const departmentToDelete = ref(null);
const submitLoading = ref(false);
const deleteLoading = ref(false);
const formError = ref('');
const deleteError = ref('');
const successToastRef = ref(null);

function notifySuccess(text) {
  successToastRef.value?.show(text);
}

const form = reactive({
  name: '',
  is_active: true,
});

const permissionNames = computed(() => authState.user?.permission_names ?? []);
const canViewList = computed(() => permissionNames.value.includes('department-list'));
const canCreate = computed(() => permissionNames.value.includes('department-create'));
const canEdit = computed(() => permissionNames.value.includes('department-edit'));
const canDelete = computed(() => permissionNames.value.includes('department-delete'));

const departmentDeleteModalOpen = computed({
  get: () => departmentToDelete.value != null,
  set(isOpen) {
    if (!isOpen && !deleteLoading.value) {
      departmentToDelete.value = null;
      deleteError.value = '';
    }
  },
});

function resetForm() {
  form.name = '';
  form.is_active = true;
  formError.value = '';
}

function openAddModal() {
  editingDepartment.value = null;
  resetForm();
  showModal.value = true;
}

function openEditModal(department) {
  editingDepartment.value = department;
  form.name = department.name ?? '';
  form.is_active = department.is_active ?? true;
  formError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingDepartment.value = null;
  resetForm();
}

async function submitDepartment() {
  formError.value = '';
  submitLoading.value = true;
  const wasEditing = !!editingDepartment.value;
  try {
    if (editingDepartment.value) {
      await axios.patch(`/api/departments/${editingDepartment.value.id}`, {
        name: form.name,
        is_active: form.is_active,
      });
    } else {
      await axios.post('/api/departments', {
        name: form.name,
        is_active: form.is_active,
      });
    }
    closeModal();
    await departmentsTableRef.value?.refresh();
    notifySuccess(
      wasEditing ? 'Department updated successfully.' : 'Department added successfully.',
    );
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

function confirmDelete(department) {
  departmentToDelete.value = department;
  deleteError.value = '';
}

async function deleteDepartment() {
  if (!departmentToDelete.value) return;
  deleteLoading.value = true;
  deleteError.value = '';
  try {
    await axios.delete(`/api/departments/${departmentToDelete.value.id}`);
    departmentToDelete.value = null;
    await departmentsTableRef.value?.refresh();
    notifySuccess('Department deleted successfully.');
  } catch (e) {
    deleteError.value = e.response?.data?.message ?? 'Failed to delete department.';
  } finally {
    deleteLoading.value = false;
  }
}

</script>

<style scoped>
.departments-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.departments-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
</style>
