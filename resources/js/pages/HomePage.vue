<template>
  <div class="home-page">
    <div class="card home-welcome">
      <h1 class="page-title">Welcome, {{ authState.user?.full_name }}</h1>
    </div>

    <div class="home-stats">
      <UserCounterWidget />
      <DepartmentCounterWidget />
    </div>

    <RecentUsersWidget title="Recent Users" :limit="5" />

    <RecentDepartmentsWidget
      v-if="canViewDepartmentList"
      title="Recent Departments"
      :limit="5"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { authState } from '../auth';
import RecentUsersWidget from '../components/RecentUsersWidget.vue';
import RecentDepartmentsWidget from '../components/RecentDepartmentsWidget.vue';
import UserCounterWidget from '../components/UserCounterWidget.vue';
import DepartmentCounterWidget from '../components/DepartmentCounterWidget.vue';

const canViewDepartmentList = computed(() =>
  (authState.user?.permission_names ?? []).includes('department-list'),
);
</script>

<style scoped>
.home-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.home-welcome {
  padding: var(--space-6);
}
.page-title {
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--color-text);
  margin: 0;
  letter-spacing: -0.02em;
}
.home-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  gap: var(--space-6);
}
</style>
