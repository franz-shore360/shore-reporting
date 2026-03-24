<template>
  <div class="auth-flow-page">
    <button
      type="button"
      class="theme-toggle-login btn btn-ghost btn-sm"
      :aria-label="theme === 'dark' ? 'Switch To Light Mode' : 'Switch To Dark Mode'"
      @click="toggleTheme"
    >
      <span v-if="theme === 'dark'" aria-hidden="true">☀️</span>
      <span v-else aria-hidden="true">🌙</span>
      <span class="theme-toggle-login-label">{{ theme === 'dark' ? 'Light Mode' : 'Dark Mode' }}</span>
    </button>
    <div class="auth-flow-card card">
      <h1 class="auth-flow-title">Set New Password</h1>
      <p v-if="!linkInvalid" class="auth-flow-subtitle">
        Choose a new password for <strong>{{ emailDisplay }}</strong>.
      </p>

      <div v-if="linkInvalid" class="error error-block" role="alert">
        This reset link is invalid or incomplete.
        <span class="error-next-step">
          <router-link :to="{ name: 'forgot-password' }">Request a new link</router-link>.
        </span>
      </div>

      <form v-else-if="!success" @submit.prevent="submit" class="form">
        <div v-if="error" class="error">{{ error }}</div>

        <div class="form-group">
          <label for="reset-password">New Password</label>
          <input
            id="reset-password"
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
          />
        </div>
        <div class="form-group">
          <label for="reset-password-confirmation">Confirm New Password</label>
          <input
            id="reset-password-confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
          />
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            {{ loading ? 'Saving…' : 'Reset Password' }}
          </button>
        </div>
      </form>

      <div v-else class="success success-block" role="status">
        {{ successMessage }}
        <p class="auth-flow-footer auth-flow-footer--tight">
          <router-link :to="{ name: 'login' }">Sign In</router-link>
        </p>
      </div>

      <p v-if="!success" class="auth-flow-footer">
        <router-link :to="{ name: 'login' }">Back To Sign In</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useTheme } from '../composables/useTheme';

const { theme, toggleTheme } = useTheme();
const route = useRoute();

const loading = ref(false);
const error = ref('');
const success = ref(false);
const successMessage = ref('');
const linkInvalid = ref(false);

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const emailDisplay = computed(() => form.email || 'your account');

onMounted(() => {
  const token = typeof route.query.token === 'string' ? route.query.token : '';
  const email = typeof route.query.email === 'string' ? route.query.email : '';
  if (!token || !email) {
    linkInvalid.value = true;
    return;
  }
  form.token = token;
  form.email = email;
});

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const { data } = await axios.post('/reset-password', {
      token: form.token,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      _token: window.Laravel?.csrfToken ?? '',
    });
    successMessage.value = data.message ?? 'Your password has been reset.';
    success.value = true;
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors).flat().join(' ');
    } else {
      error.value = e.response?.data?.message ?? 'Could not reset password. The link may have expired.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-flow-page {
  width: 100%;
  max-width: 24rem;
  position: relative;
}
.theme-toggle-login {
  position: fixed;
  top: var(--space-4);
  right: var(--space-4);
  z-index: 50;
}
.theme-toggle-login-label {
  margin-left: var(--space-1);
}
.auth-flow-card {
  padding: var(--space-10);
}
.auth-flow-title {
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--color-text);
  margin: 0 0 var(--space-2);
  letter-spacing: -0.02em;
}
.auth-flow-subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--space-8);
  line-height: 1.5;
}
.auth-flow-footer {
  margin: var(--space-6) 0 0;
  font-size: var(--text-sm);
  text-align: center;
}
.auth-flow-footer--tight {
  margin-top: var(--space-4);
}
.auth-flow-footer a {
  color: var(--color-primary);
  font-weight: var(--font-semibold);
  text-decoration: none;
}
.auth-flow-footer a:hover {
  text-decoration: underline;
}
.btn-block {
  width: 100%;
}
.success-block {
  margin-bottom: 0;
  line-height: 1.5;
}
.error-block {
  margin-bottom: var(--space-6);
  line-height: 1.5;
}
.error-next-step {
  display: block;
  margin-top: var(--space-2);
}
.error-next-step a {
  color: var(--color-primary);
  font-weight: var(--font-semibold);
}
</style>
