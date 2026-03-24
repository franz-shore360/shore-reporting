<template>
  <div class="roles-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Roles</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
          <button v-if="canAccessRoles" type="button" class="btn btn-primary" @click="openAddModal">Add Role</button>
        </div>
      </div>
    </div>

    <div v-if="!canAccessRoles" class="card">
      <p class="permission-message">You do not have permission to manage roles.</p>
    </div>
    <div v-else class="card table-card">
      <DataTable
        storage-key="roles"
        :columns="roleColumns"
        :data="roles"
        :sortable="true"
        :filterable="true"
        :initial-sorting="rolesTableInitialSorting"
      >
        <template #cell="{ cell, value, row }">
          <span v-if="cell.column.columnDef.accessorKey === 'actions'" class="actions-cell">
            <button
              v-if="row.original.name !== adminRoleName"
              type="button"
              class="btn btn-sm btn-outline btn-icon"
              title="Edit"
              @click="openEditModal(row.original)"
            >
              <GridIcon name="edit" />
            </button>
            <button
              v-if="row.original.name !== adminRoleName"
              type="button"
              class="btn btn-sm btn-danger btn-icon"
              title="Delete"
              @click="confirmDelete(row.original)"
            >
              <GridIcon name="trash" />
            </button>
          </span>
          <template v-else>{{ formatTableCellValue(cell.column.columnDef, value) }}</template>
        </template>
        <template #empty>No roles yet.</template>
      </DataTable>
    </div>

    <!-- Add/Edit Role Modal -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal modal--wide">
        <div class="modal-header">
          <h2 class="modal-title">{{ editingRole ? 'Edit Role' : 'Add Role' }}</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="submitRole" class="modal-form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="form-group">
            <label for="modal_role_name">Name <span class="required-asterisk">*</span></label>
            <input id="modal_role_name" v-model="form.name" type="text" required />
          </div>
          <div class="form-group" v-if="permissionGroups && Object.keys(permissionGroups).length">
            <span class="permissions-label">Permissions</span>
            <div class="permissions-groups">
              <div v-for="(group, groupKey) in permissionGroups" :key="groupKey" class="permission-group">
                <h4 class="permission-group-name">{{ group.name }}</h4>
                <div class="permission-list">
                  <label
                    v-for="(permConfig, permName) in group.permissions"
                    :key="permName"
                    class="permission-checkbox-label"
                  >
                    <input
                      type="checkbox"
                      :value="permName"
                      v-model="form.permissions"
                    />
                    <span>{{ permConfig.label }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="submitLoading">
              {{ submitLoading ? 'Saving…' : (editingRole ? 'Save Changes' : 'Add Role') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirmation -->
    <div v-if="roleToDelete" class="modal-backdrop" @click.self="roleToDelete = null">
      <div class="modal modal--delete modal-sm">
        <div class="modal-header">
          <h2 class="modal-title">Delete Role</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="roleToDelete = null">&times;</button>
        </div>
        <div class="modal-body">
          <p v-if="!deleteError">
            Are you sure you want to delete <strong>{{ roleToDelete.name }}</strong>? This cannot be undone.
          </p>
          <p v-else class="form-error">{{ deleteError }}</p>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="roleToDelete = null">Cancel</button>
          <button type="button" class="btn btn-danger" :disabled="deleteLoading" @click="deleteRole">
            {{ deleteLoading ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

    <SuccessToast ref="successToastRef" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
import DataTable from '../components/DataTable.vue';
import GridIcon from '../components/icons/GridIcons.vue';
import SuccessToast from '../components/SuccessToast.vue';
import { formatTableCellValue } from '../utils/date';

const adminRoleName = 'Admin';
/** Default grid sort: ID descending (matches Users/Departments). */
const rolesTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);
const canAccessRoles = computed(() => authState.user?.role_names?.includes('Admin') ?? false);
const roles = ref([]);
const permissionGroups = ref(null);
const showModal = ref(false);
const editingRole = ref(null);
const roleToDelete = ref(null);
const submitLoading = ref(false);
const deleteLoading = ref(false);
const formError = ref('');
const deleteError = ref('');
const successToastRef = ref(null);

const form = reactive({
  name: '',
  permissions: [],
});

const roleColumns = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    sortingFn: 'basic',
  },
  { accessorKey: 'name', header: 'Name', enableColumnFilter: true, filterFn: 'includesString', enableSorting: true },
  { accessorKey: 'actions', header: 'Actions', enableColumnFilter: false, enableHiding: false, enableSorting: false },
];

async function fetchRoles() {
  try {
    const { data } = await axios.get('/api/roles');
    roles.value = data;
  } catch (e) {
    roles.value = [];
  }
}

async function fetchPermissions() {
  try {
    const { data } = await axios.get('/api/permissions');
    permissionGroups.value = data;
  } catch (e) {
    permissionGroups.value = {};
  }
}

onMounted(() => {
  if (canAccessRoles.value) {
    fetchRoles();
    fetchPermissions();
  }
});

function resetForm() {
  form.name = '';
  form.permissions = [];
  formError.value = '';
}

function openAddModal() {
  editingRole.value = null;
  resetForm();
  showModal.value = true;
}

async function openEditModal(role) {
  editingRole.value = role;
  form.name = role.name ?? '';
  formError.value = '';
  try {
    const { data } = await axios.get(`/api/roles/${role.id}`);
    form.permissions = Array.isArray(data.permission_names) ? [...data.permission_names] : [];
  } catch (e) {
    form.permissions = [];
  }
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingRole.value = null;
  resetForm();
}

async function submitRole() {
  formError.value = '';
  submitLoading.value = true;
  const wasEditing = !!editingRole.value;
  try {
    const payload = { name: form.name, permissions: form.permissions };
    if (editingRole.value) {
      await axios.patch(`/api/roles/${editingRole.value.id}`, payload);
    } else {
      await axios.post('/api/roles', payload);
    }
    closeModal();
    await fetchRoles();
    successToastRef.value?.show(
      wasEditing ? 'Role updated successfully.' : 'Role added successfully.',
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

function confirmDelete(role) {
  roleToDelete.value = role;
  deleteError.value = '';
}

async function deleteRole() {
  if (!roleToDelete.value) return;
  deleteLoading.value = true;
  deleteError.value = '';
  try {
    await axios.delete(`/api/roles/${roleToDelete.value.id}`);
    roleToDelete.value = null;
    await fetchRoles();
    successToastRef.value?.show('Role deleted successfully.');
  } catch (e) {
    deleteError.value = e.response?.data?.message ?? 'Failed to delete role.';
  } finally {
    deleteLoading.value = false;
  }
}
</script>

<style scoped>
.roles-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.roles-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
.permissions-label {
  display: block;
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text);
  margin-bottom: var(--space-2);
}
.permissions-groups {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
.permission-group-name {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
  margin: 0 0 var(--space-2);
}
.permission-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}
.permission-checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  font-weight: var(--font-normal);
  color: var(--color-text);
  cursor: pointer;
}
.permission-checkbox-label input {
  width: auto;
}
.modal--wide {
  max-width: 28rem;
}
</style>
