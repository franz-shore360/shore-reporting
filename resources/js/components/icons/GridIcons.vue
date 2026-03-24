<template>
  <span class="grid-icon" aria-hidden="true">
    <!-- Pencil / Edit -->
    <svg v-if="name === 'edit'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
      <path d="m15 5 4 4" />
    </svg>
    <!-- Trash / Delete -->
    <svg v-else-if="name === 'trash'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 6h18" />
      <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
      <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
      <line x1="10" x2="10" y1="11" y2="17" />
      <line x1="14" x2="14" y1="11" y2="17" />
    </svg>
  </span>
</template>

<script setup>
defineProps({
  name: { type: String, required: true },
});
</script>

<style scoped>
.grid-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.grid-icon svg {
  width: 1.125rem;
  height: 1.125rem;
  flex-shrink: 0;
}
</style>
