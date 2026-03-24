<template>
  <div class="profile-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Edit Profile</h1>
        <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
      </div>

      <form @submit.prevent="submit" class="form">
        <div v-if="error" class="error">{{ error }}</div>

        <div class="form-group profile-image-group">
          <label>Profile Image</label>
          <div class="profile-image-row">
            <div class="profile-image-preview">
              <img
                v-if="displayPreviewUrl"
                :key="displayPreviewUrl"
                :src="displayPreviewUrl"
                alt="Profile preview"
                class="profile-image-img"
                @error="onImageError"
              />
              <div v-else class="profile-image-placeholder">
                {{ authState.user?.full_name?.charAt(0) || '?' }}
              </div>
            </div>
            <div class="profile-image-actions">
              <input
                id="profile_image"
                ref="profileImageInput"
                type="file"
                accept="image/jpeg,image/png,image/jpg"
                class="profile-image-input"
                @change="onImageSelect"
              />
              <label for="profile_image" class="btn btn-outline btn-sm">Choose Image</label>
              <button
                v-if="displayPreviewUrl || form.profile_image_file || authState.user?.profile_image_url"
                type="button"
                class="btn btn-ghost btn-sm"
                @click="clearImage"
              >
                Remove Image
              </button>
            </div>
          </div>
          <p class="profile-image-hint">JPG or PNG, max 2 MB</p>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              autocomplete="given-name"
            />
          </div>
          <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input
              id="middle_name"
              v-model="form.middle_name"
              type="text"
              autocomplete="additional-name"
            />
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              autocomplete="family-name"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
          />
        </div>

        <div class="form-group">
          <label for="department_id">Department</label>
          <select
            id="department_id"
            v-model="form.department_id"
          >
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
          <label for="password">New Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            placeholder="Leave blank to keep current"
          />
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm New Password</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
          />
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>

    <SuccessToast ref="successToastRef" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
import SuccessToast from '../components/SuccessToast.vue';

const loading = ref(false);
const error = ref('');
const successToastRef = ref(null);
const departments = ref([]);

const profileImageInput = ref(null);
const form = reactive({
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  department_id: null,
  password: '',
  password_confirmation: '',
  profile_image_file: null,
  remove_profile_image: false,
});

/** Blob URL when user picks a new file; revoked on clear or new selection */
const imagePreviewUrl = ref(null);
/** URL shown in preview: set explicitly so Remove updates UI for both new and existing images */
const displayPreviewUrl = ref(null);

function onImageSelect(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (imagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value);
  }
  form.remove_profile_image = false;
  form.profile_image_file = file;
  imagePreviewUrl.value = URL.createObjectURL(file);
  displayPreviewUrl.value = imagePreviewUrl.value;
}

function clearImage() {
  if (imagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value);
  }
  imagePreviewUrl.value = null;
  displayPreviewUrl.value = null;
  form.profile_image_file = null;
  form.remove_profile_image = true;
  if (profileImageInput.value) {
    profileImageInput.value.value = '';
  }
}

function onImageError() {
  displayPreviewUrl.value = null;
}

function setFormFromUser(user) {
  if (!user) return;
  form.first_name = user.first_name ?? '';
  form.middle_name = user.middle_name ?? '';
  form.last_name = user.last_name ?? '';
  form.email = user.email ?? '';
  form.department_id = user.department_id ?? null;
  form.password = '';
  form.password_confirmation = '';
  form.profile_image_file = null;
  form.remove_profile_image = false;
  if (imagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value);
  }
  imagePreviewUrl.value = null;
  displayPreviewUrl.value = user.profile_image_url ?? null;
}

onMounted(async () => {
  setFormFromUser(authState.user);
  try {
    const { data } = await axios.get('/api/departments/options');
    departments.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch {
    departments.value = [];
  }
});

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const hasFile = form.profile_image_file instanceof File;
    let response;

    if (hasFile) {
      const formData = new FormData();
      formData.append('_token', window.Laravel?.csrfToken ?? '');
      formData.append('_method', 'PATCH');
      formData.append('first_name', form.first_name);
      formData.append('middle_name', form.middle_name || '');
      formData.append('last_name', form.last_name);
      formData.append('email', form.email);
      if (form.department_id != null) {
        formData.append('department_id', form.department_id);
      } else {
        formData.append('department_id', '');
      }
      formData.append('profile_image', form.profile_image_file);
      if (form.password) {
        formData.append('password', form.password);
        formData.append('password_confirmation', form.password_confirmation);
      }
      response = await axios.post('/api/profile', formData, {
        headers: {
          'X-XSRF-TOKEN': window.Laravel?.csrfToken ?? '',
          'Accept': 'application/json',
        },
      });
    } else {
      const payload = {
        first_name: form.first_name,
        middle_name: form.middle_name || null,
        last_name: form.last_name,
        email: form.email,
        department_id: form.department_id || null,
      };
      if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }
      if (form.remove_profile_image) {
        payload.remove_profile_image = true;
      }
      response = await axios.patch('/api/profile', payload);
    }

    const { data } = response;
    if (imagePreviewUrl.value?.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreviewUrl.value);
    }
    authState.setUser(data);
    successToastRef.value?.show('Profile updated successfully.');
    setFormFromUser(data);
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors).flat().join(' ');
    } else {
      error.value = e.response?.data?.message || 'Failed to update profile.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.profile-page {
  max-width: 36rem;
  margin-left: auto;
  margin-right: auto;
}
.profile-page .card {
  padding: var(--space-8);
}
.profile-page .form {
  margin-top: 0;
}
.profile-image-group {
  margin-bottom: var(--space-6);
}
.profile-image-row {
  display: flex;
  align-items: center;
  gap: var(--space-6);
}
.profile-image-preview {
  width: 96px;
  height: 96px;
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
.profile-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
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
</style>
