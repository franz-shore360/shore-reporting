<template>
  <div class="users-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Users</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
          <button v-if="canCreate" type="button" class="btn btn-primary" @click="openAddModal">Add User</button>
        </div>
      </div>
    </div>

    <div v-if="!canViewList" class="card">
      <p class="permission-message">You do not have permission to view the user list.</p>
    </div>
    <div v-else class="card table-card">
      <UsersTable
        ref="usersTableRef"
        :can-fetch="canViewList"
        :can-edit="canEdit"
        :can-delete="canDelete"
        :roles="roles"
        :departments="departments"
        @edit="openEditModal"
        @delete-request="confirmDelete"
        @notify="onUsersTableNotify"
      />
    </div>

    <!-- Add/Edit User Modal -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-header">
          <h2 class="modal-title">{{ editingUser ? 'Edit User' : 'Add User' }}</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="submitUser" class="modal-form">
          <div v-if="formError" class="form-error">{{ formError }}</div>

          <!-- Profile image (edit only) -->
          <div v-if="editingUser" class="form-group profile-image-group">
            <label>Profile Image</label>
            <div class="profile-image-row">
              <div class="profile-image-preview">
                <img
                  v-if="editImagePreviewUrl"
                  :src="editImagePreviewUrl"
                  alt="Profile"
                  class="profile-image-img"
                  @error="editImagePreviewUrl = null"
                />
                <span v-else class="profile-image-placeholder-inline">
                  {{ form.first_name?.charAt(0) || form.last_name?.charAt(0) || '?' }}
                </span>
              </div>
              <div class="profile-image-actions">
                <input
                  id="modal_profile_image"
                  ref="editProfileImageInput"
                  type="file"
                  accept="image/jpeg,image/png,image/jpg"
                  class="profile-image-input"
                  @change="onEditImageSelect"
                />
                <label for="modal_profile_image" class="btn btn-sm btn-outline">Choose Image</label>
                <button
                  v-if="editImagePreviewUrl || form.profile_image_file"
                  type="button"
                  class="btn btn-sm btn-ghost"
                  @click="clearEditImage"
                >
                  Remove Image
                </button>
              </div>
            </div>
            <p class="profile-image-hint">JPG or PNG, max 2 MB</p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modal_first_name">First Name <span class="required-asterisk">*</span></label>
              <input id="modal_first_name" v-model="form.first_name" type="text" required />
            </div>
            <div class="form-group">
              <label for="modal_middle_name">Middle Name</label>
              <input id="modal_middle_name" v-model="form.middle_name" type="text" />
            </div>
            <div class="form-group">
              <label for="modal_last_name">Last Name <span class="required-asterisk">*</span></label>
              <input id="modal_last_name" v-model="form.last_name" type="text" required />
            </div>
          </div>
          <div class="form-group">
            <label for="modal_email">Email <span class="required-asterisk">*</span></label>
            <input id="modal_email" v-model="form.email" type="email" required />
          </div>
          <div class="form-group">
            <label for="modal_department_id">Department</label>
            <select id="modal_department_id" v-model="form.department_id">
              <option :value="null">No Department</option>
              <option
                v-for="dept in departments"
                :key="dept.id"
                :value="dept.id"
              >
                {{ dept.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label for="modal_role">Role <span class="required-asterisk">*</span></label>
            <select id="modal_role" v-model="form.role" required>
              <option
                v-for="r in roles"
                :key="r.id"
                :value="r.name"
              >
                {{ r.name }}
              </option>
            </select>
          </div>
          <div v-if="editingUser" class="form-group checkbox-row account-active-row">
            <input
              id="modal_is_active"
              v-model="form.is_active"
              type="checkbox"
              class="checkbox"
              :disabled="editingUser.id === authState.user?.id || !canEditTargetActiveStatus"
            />
            <label for="modal_is_active">Active</label>
          </div>
          <p v-if="editingUser && editingUser.id === authState.user?.id" class="account-active-hint">
            You cannot deactivate your own account.
          </p>
          <p
            v-else-if="editingUser && editingUserIsAdmin && !isAuthAdmin"
            class="account-active-hint"
          >
            Only administrators can change the active status of an admin account.
          </p>
          <div class="form-group">
            <label for="modal_password">{{ editingUser ? 'New Password (Leave Blank To Keep)' : 'Password' }}</label>
            <input
              id="modal_password"
              v-model="form.password"
              type="password"
              :required="!editingUser"
              autocomplete="new-password"
            />
          </div>
          <div v-if="form.password" class="form-group">
            <label for="modal_password_confirmation">Confirm Password</label>
            <input
              id="modal_password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
            />
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="submitLoading">
              {{ submitLoading ? 'Saving…' : (editingUser ? 'Save Changes' : 'Add User') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmModal
      v-model:open="userDeleteModalOpen"
      title="Delete User"
      :confirm-label="deleteLoading ? 'Deleting…' : 'Delete'"
      variant="danger"
      :loading="deleteLoading"
      @confirm="deleteUser"
    >
      <p v-if="!deleteError">
        Are you sure you want to delete <strong>{{ userToDelete?.full_name }}</strong> ({{ userToDelete?.email }})? This
        cannot be undone.
      </p>
      <p v-else class="form-error">{{ deleteError }}</p>
    </ConfirmModal>

    <SuccessToast ref="successToastRef" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
import UsersTable from '../components/UsersTable.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import SuccessToast from '../components/SuccessToast.vue';

const usersTableRef = ref(null);

const departments = ref([]);
const roles = ref([]);
const showModal = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);
const submitLoading = ref(false);
const deleteLoading = ref(false);
const formError = ref('');
const deleteError = ref('');
const successToastRef = ref(null);

function onUsersTableNotify({ text }) {
  if (text) {
    successToastRef.value?.show(text);
  }
}

const editProfileImageInput = ref(null);
const editImagePreviewUrl = ref('');
const form = reactive({
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  department_id: null,
  role: null,
  password: '',
  password_confirmation: '',
  profile_image_file: null,
  remove_profile_image: false,
  is_active: true,
});

const permissionNames = computed(() => authState.user?.permission_names ?? []);
const canViewList = computed(() => permissionNames.value.includes('user-list'));
const canCreate = computed(() => permissionNames.value.includes('user-create'));
const canEdit = computed(() => permissionNames.value.includes('user-edit'));
const canDelete = computed(() => permissionNames.value.includes('user-delete'));

const userDeleteModalOpen = computed({
  get: () => userToDelete.value != null,
  set(isOpen) {
    if (!isOpen && !deleteLoading.value) {
      userToDelete.value = null;
      deleteError.value = '';
    }
  },
});

const ADMIN_ROLE_NAME = 'Admin';
const isAuthAdmin = computed(() => authState.user?.role_names?.includes(ADMIN_ROLE_NAME) ?? false);
const editingUserIsAdmin = computed(() =>
  editingUser.value?.roles?.some((r) => r.name === ADMIN_ROLE_NAME) ?? false,
);
/** Non-admins may not change active status for admin accounts (enforced on server too). */
const canEditTargetActiveStatus = computed(
  () => !editingUser.value || isAuthAdmin.value || !editingUserIsAdmin.value,
);

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/departments/options');
    departments.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch {
    departments.value = [];
  }
  try {
    const { data } = await axios.get('/api/roles/options');
    roles.value = Array.isArray(data) ? data : [];
  } catch {
    roles.value = [];
  }
});

function resetForm() {
  if (editImagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(editImagePreviewUrl.value);
  }
  editImagePreviewUrl.value = '';
  form.first_name = '';
  form.middle_name = '';
  form.last_name = '';
  form.email = '';
  form.department_id = null;
  form.role = roles.value?.length ? roles.value[0].name : null;
  form.password = '';
  form.password_confirmation = '';
  form.profile_image_file = null;
  form.remove_profile_image = false;
  form.is_active = true;
  formError.value = '';
  if (editProfileImageInput.value) {
    editProfileImageInput.value.value = '';
  }
}

function openAddModal() {
  editingUser.value = null;
  resetForm();
  showModal.value = true;
}

function openEditModal(user) {
  editingUser.value = user;
  form.first_name = user.first_name ?? '';
  form.middle_name = user.middle_name ?? '';
  form.last_name = user.last_name ?? '';
  form.email = user.email ?? '';
  form.department_id = user.department_id ?? null;
  form.role = user.roles?.length ? user.roles[0].name : null;
  form.password = '';
  form.password_confirmation = '';
  form.profile_image_file = null;
  form.remove_profile_image = false;
  form.is_active = user.is_active !== false;
  editImagePreviewUrl.value = user.profile_image_url ?? '';
  formError.value = '';
  showModal.value = true;
}

function onEditImageSelect(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (editImagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(editImagePreviewUrl.value);
  }
  form.profile_image_file = file;
  editImagePreviewUrl.value = URL.createObjectURL(file);
}

function clearEditImage() {
  if (editImagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(editImagePreviewUrl.value);
  }
  form.profile_image_file = null;
  form.remove_profile_image = true;
  editImagePreviewUrl.value = '';
  if (editProfileImageInput.value) {
    editProfileImageInput.value.value = '';
  }
}

function closeModal() {
  showModal.value = false;
  editingUser.value = null;
  resetForm();
}

async function submitUser() {
  formError.value = '';
  submitLoading.value = true;
  const wasEditing = !!editingUser.value;
  try {
    let updatedUser = null;
    if (editingUser.value) {
      const hasFile = form.profile_image_file instanceof File;
      if (hasFile) {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('first_name', form.first_name);
        formData.append('middle_name', form.middle_name || '');
        formData.append('last_name', form.last_name);
        formData.append('email', form.email);
        formData.append('department_id', form.department_id != null ? form.department_id : '');
        formData.append('role', form.role != null ? form.role : '');
        formData.append('is_active', form.is_active ? '1' : '0');
        formData.append('profile_image', form.profile_image_file);
        if (form.password) {
          formData.append('password', form.password);
          formData.append('password_confirmation', form.password_confirmation);
        }
        const { data } = await axios.post(`/api/users/${editingUser.value.id}`, formData, {
          headers: {
            Accept: 'application/json',
          },
        });
        updatedUser = data;
      } else {
        const payload = {
          first_name: form.first_name,
          middle_name: form.middle_name || null,
          last_name: form.last_name,
          email: form.email,
          department_id: form.department_id || null,
          role: form.role || null,
          is_active: form.is_active,
        };
        if (form.password) {
          payload.password = form.password;
          payload.password_confirmation = form.password_confirmation;
        }
        if (form.remove_profile_image) {
          payload.remove_profile_image = true;
        }
        const { data } = await axios.patch(`/api/users/${editingUser.value.id}`, payload);
        updatedUser = data;
      }
      if (editImagePreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(editImagePreviewUrl.value);
      }
      if (updatedUser && authState.user?.id === updatedUser.id) {
        authState.setUser(updatedUser);
      }
    } else {
      await axios.post('/api/users', {
        first_name: form.first_name,
        middle_name: form.middle_name || null,
        last_name: form.last_name,
        email: form.email,
        department_id: form.department_id || null,
        role: form.role || null,
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
    }
    closeModal();
    await usersTableRef.value?.refresh();
    successToastRef.value?.show(
      wasEditing ? 'User updated successfully.' : 'User added successfully.',
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

function confirmDelete(user) {
  userToDelete.value = user;
  deleteError.value = '';
}

async function deleteUser() {
  if (!userToDelete.value) return;
  deleteLoading.value = true;
  deleteError.value = '';
  try {
    await axios.delete(`/api/users/${userToDelete.value.id}`);
    userToDelete.value = null;
    await usersTableRef.value?.refresh();
    successToastRef.value?.show('User deleted successfully.');
  } catch (e) {
    deleteError.value = e.response?.data?.message ?? 'Failed to delete user.';
  } finally {
    deleteLoading.value = false;
  }
}
</script>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.users-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
.profile-image-group {
  margin-bottom: var(--space-5);
}
.profile-image-row {
  display: flex;
  align-items: center;
  gap: var(--space-4);
}
.profile-image-preview {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--color-border-light);
}
.profile-image-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.profile-image-placeholder-inline {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
}
.profile-image-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
}
.profile-image-input {
  position: absolute;
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  z-index: -1;
}
.profile-image-hint {
  margin: var(--space-2) 0 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.account-active-row {
  margin-bottom: var(--space-2);
}

.account-active-hint {
  margin: calc(var(--space-2) * -1) 0 var(--space-4);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
</style>
