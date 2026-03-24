<template>
  <div class="card stat-widget">
    <h2 class="stat-widget-label">Users</h2>
    <p class="stat-widget-value" :aria-busy="loading">
      {{ loading ? '…' : formatStatCount(usersCount) }}
    </p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useDashboardStats, formatStatCount } from '../composables/useDashboardStats';

const { usersCount, loading, ensureLoaded } = useDashboardStats();

onMounted(() => {
  ensureLoaded();
});
</script>

<style scoped>
.stat-widget {
  padding: var(--space-6);
}
.stat-widget-label {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
  margin: 0 0 var(--space-2);
  letter-spacing: 0.02em;
}
.stat-widget-value {
  font-size: 2rem;
  font-weight: var(--font-bold);
  color: var(--color-text);
  margin: 0;
  line-height: 1.2;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
}
</style>
