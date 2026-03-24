<template>
  <Teleport to="body">
    <Transition name="confirm-modal">
      <div
        v-if="open"
        class="confirm-modal-backdrop"
        aria-hidden="false"
        @click.self="onBackdropClick"
      >
        <div
          class="confirm-modal-panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          @keydown.stop
        >
          <button
            v-if="!loading"
            type="button"
            class="confirm-modal-dismiss"
            aria-label="Close"
            @click="close"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div v-if="showIcon" class="confirm-modal-icon-wrap" :data-variant="variant">
            <svg v-if="variant === 'danger'" class="confirm-modal-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
              <path d="M12 9v4" />
              <path d="M12 17h.01" />
            </svg>
            <svg v-else class="confirm-modal-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 16v-4" />
              <path d="M12 8h.01" />
            </svg>
          </div>

          <h2 :id="titleId" class="confirm-modal-title">{{ title }}</h2>
          <div class="confirm-modal-body">
            <slot />
          </div>
          <div class="confirm-modal-actions">
            <button type="button" class="btn btn-secondary" :disabled="loading" @click="close">
              {{ cancelLabel }}
            </button>
            <button
              type="button"
              :class="confirmButtonClass"
              :disabled="loading"
              @click="$emit('confirm')"
            >
              <span v-if="loading" class="confirm-modal-spinner" aria-hidden="true" />
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue';

let confirmModalSeq = 0;

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  cancelLabel: {
    type: String,
    default: 'Cancel',
  },
  /** Destructive actions use danger styling on the primary button. */
  variant: {
    type: String,
    default: 'danger',
    validator: (v) => ['danger', 'primary'].includes(v),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:open', 'confirm']);

const titleId = `confirm-modal-title-${++confirmModalSeq}`;

const confirmButtonClass = computed(() =>
  props.variant === 'danger' ? 'btn btn-danger' : 'btn btn-primary',
);

function close() {
  if (props.loading) return;
  emit('update:open', false);
}

function onBackdropClick() {
  if (!props.closeOnBackdrop || props.loading) return;
  close();
}

function onKeydown(ev) {
  if (!props.open || props.loading) return;
  if (ev.key === 'Escape') {
    ev.preventDefault();
    close();
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (typeof document === 'undefined') return;
    if (isOpen) {
      document.addEventListener('keydown', onKeydown);
      document.body.style.overflow = 'hidden';
    } else {
      document.removeEventListener('keydown', onKeydown);
      document.body.style.overflow = '';
    }
  },
);

onUnmounted(() => {
  if (typeof document === 'undefined') return;
  document.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.confirm-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-5);
  background: rgb(15 23 42 / 0.45);
  backdrop-filter: blur(6px);
}

[data-theme='dark'] .confirm-modal-backdrop {
  background: rgb(0 0 0 / 0.55);
}

.confirm-modal-panel {
  position: relative;
  width: 100%;
  max-width: 26rem;
  padding: var(--space-8) var(--space-6) var(--space-6);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  box-shadow:
    0 25px 50px -12px rgb(0 0 0 / 0.18),
    0 0 0 1px rgb(0 0 0 / 0.04);
  text-align: center;
}

[data-theme='dark'] .confirm-modal-panel {
  box-shadow:
    0 25px 50px -12px rgb(0 0 0 / 0.45),
    0 0 0 1px rgb(255 255 255 / 0.06);
}

.confirm-modal-dismiss {
  position: absolute;
  top: var(--space-3);
  right: var(--space-3);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  border: none;
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color 0.15s ease, background 0.15s ease;
}

.confirm-modal-dismiss:hover {
  color: var(--color-text);
  background: var(--color-surface-hover);
}

.confirm-modal-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  margin: 0 auto var(--space-4);
  border-radius: 50%;
}

.confirm-modal-icon-wrap[data-variant='danger'] {
  color: var(--color-danger);
  background: color-mix(in srgb, var(--color-danger) 14%, transparent);
}

.confirm-modal-icon-wrap[data-variant='primary'] {
  color: var(--color-primary);
  background: var(--color-primary-muted);
}

.confirm-modal-icon {
  width: 1.5rem;
  height: 1.5rem;
  flex-shrink: 0;
}

.confirm-modal-title {
  margin: 0 0 var(--space-3);
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text);
  line-height: 1.3;
  letter-spacing: -0.02em;
}

.confirm-modal-body {
  margin-bottom: var(--space-6);
  font-size: var(--text-sm);
  line-height: 1.55;
  color: var(--color-text-muted);
  text-align: left;
}

.confirm-modal-body :deep(p) {
  margin: 0;
}

.confirm-modal-body :deep(p + p) {
  margin-top: var(--space-3);
}

.confirm-modal-body :deep(.form-error) {
  color: var(--color-danger);
  font-size: var(--text-sm);
}

.confirm-modal-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--space-3);
}

.confirm-modal-actions .btn {
  min-width: 6.5rem;
}

.confirm-modal-spinner {
  display: inline-block;
  width: 0.875rem;
  height: 0.875rem;
  margin-right: var(--space-2);
  vertical-align: -0.125rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: confirm-modal-spin 0.65s linear infinite;
}

@keyframes confirm-modal-spin {
  to {
    transform: rotate(360deg);
  }
}

.confirm-modal-enter-active,
.confirm-modal-leave-active {
  transition: opacity 0.2s ease;
}

.confirm-modal-enter-active .confirm-modal-panel,
.confirm-modal-leave-active .confirm-modal-panel {
  transition:
    transform 0.22s cubic-bezier(0.34, 1.2, 0.64, 1),
    opacity 0.2s ease;
}

.confirm-modal-enter-from,
.confirm-modal-leave-to {
  opacity: 0;
}

.confirm-modal-enter-from .confirm-modal-panel,
.confirm-modal-leave-to .confirm-modal-panel {
  transform: scale(0.94) translateY(0.5rem);
  opacity: 0;
}
</style>
