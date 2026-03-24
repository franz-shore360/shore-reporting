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
    <div class="auth-flow-card card card--split">
      <div class="card-header">
        <h1 class="auth-flow-app-title">{{ appName }}</h1>
      </div>
      <div class="card-body">
        <h2 class="auth-flow-title">Forgot Password</h2>
        <p class="auth-flow-subtitle">
          Enter your email and we will send you a link to choose a new password.
        </p>

        <form v-if="!submitted" @submit.prevent="submit" class="form">
          <div v-if="error" class="error">{{ error }}</div>

          <div class="form-group">
            <label for="forgot-email">Email</label>
            <input
              id="forgot-email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              autofocus
              placeholder="you@example.com"
            />
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
              {{ loading ? 'Sending…' : 'Send Reset Link' }}
            </button>
          </div>
        </form>

        <div v-else class="success success-block" role="status">
          {{ successMessage }}
        </div>

        <p class="auth-flow-footer">
          <router-link :to="{ name: 'login' }">Back To Sign In</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useTheme } from '../composables/useTheme';
import { getAppName } from '../composables/useAppName';

const appName = getAppName();
const { theme, toggleTheme } = useTheme();

const loading = ref(false);
const error = ref('');
const submitted = ref(false);
const successMessage = ref('');

const form = reactive({
  email: '',
});

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const { data } = await axios.post('/forgot-password', {
      email: form.email,
      _token: window.Laravel?.csrfToken ?? '',
    });
    successMessage.value = data.message ?? 'Check your email for the reset link.';
    submitted.value = true;
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors).flat().join(' ');
    } else {
      error.value = e.response?.data?.message ?? 'Something went wrong. Please try again.';
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
.auth-flow-app-title {
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--color-primary);
  margin: 0;
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.auth-flow-title {
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text);
  margin: 0 0 var(--space-2);
  letter-spacing: -0.02em;
  text-align: center;
}
.auth-flow-subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--space-8);
  line-height: 1.5;
  text-align: center;
}
.auth-flow-footer {
  margin: var(--space-6) 0 0;
  font-size: var(--text-sm);
  text-align: center;
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
</style>
