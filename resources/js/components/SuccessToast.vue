<template>
  <Teleport to="body">
    <Transition name="success-toast">
      <div
        v-if="message"
        class="success-toast"
        :class="{ 'success-toast--error': isError }"
        :role="isError ? 'alert' : 'status'"
        :aria-live="isError ? 'assertive' : 'polite'"
      >
        <span class="success-toast-text">{{ message }}</span>
        <button
          type="button"
          class="success-toast-dismiss"
          aria-label="Dismiss"
          @click="dismiss"
        >
          &times;
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';

const message = ref('');
const isError = ref(false);
let timer = null;

function dismiss() {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
  message.value = '';
  isError.value = false;
}

function show(text) {
  dismiss();
  isError.value = false;
  message.value = text;
  timer = setTimeout(() => {
    message.value = '';
    isError.value = false;
    timer = null;
  }, 4500);
}

function showError(text) {
  dismiss();
  isError.value = true;
  message.value = text;
  timer = setTimeout(() => {
    message.value = '';
    isError.value = false;
    timer = null;
  }, 6500);
}

defineExpose({ show, showError, dismiss });

onUnmounted(() => {
  dismiss();
});
</script>

<style>
.success-toast {
  position: fixed;
  top: var(--space-4);
  left: 50%;
  transform: translateX(-50%);
  z-index: 10000;
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  max-width: min(22rem, calc(100vw - 2rem));
  width: max-content;
  padding: var(--space-3) var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-success);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
}
.success-toast-text {
  flex: 1;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text);
  line-height: 1.4;
}
.success-toast-dismiss {
  flex-shrink: 0;
  margin: -0.25rem -0.25rem -0.25rem 0;
  padding: 0 0.35rem;
  border: none;
  background: none;
  font-size: 1.25rem;
  line-height: 1;
  color: var(--color-text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
}
.success-toast-dismiss:hover {
  color: var(--color-text);
  background: var(--color-surface-hover);
}
.success-toast--error {
  border-color: var(--color-danger);
}
.success-toast-enter-active,
.success-toast-leave-active {
  transition:
    opacity 0.2s ease,
    margin-top 0.2s ease;
}
.success-toast-enter-from,
.success-toast-leave-to {
  opacity: 0;
  margin-top: -0.5rem;
}
</style>
