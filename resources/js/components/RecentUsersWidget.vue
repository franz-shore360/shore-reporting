<template>
  <div class="card table-card">
    <h2 class="section-title">{{ title }}</h2>
    <DataTable
      :columns="userColumns"
      :data="users"
      :sortable="true"
      :column-picker="false"
      :show-reset-button="false"
      :enable-column-reorder="false"
    >
      <template #cell="{ cell, value, row }">
        <span v-if="cell.column.columnDef.accessorKey === 'profile_image_url'" class="user-avatar-cell">
          <img
            v-if="value && !avatarLoadFailed[row.original.id]"
            :src="value"
            :alt="row.original.full_name"
            class="user-avatar"
            @error="avatarLoadFailed[row.original.id] = true"
          />
          <span v-else class="user-avatar-placeholder">
            {{ row.original.full_name?.charAt(0) || '?' }}
          </span>
        </span>
        <span v-else-if="cell.column.columnDef.accessorKey === 'department_name'">
          {{ row.original.department?.name ?? '—' }}
        </span>
        <template v-else>{{ formatTableCellValue(cell.column.columnDef, value) }}</template>
      </template>
      <template #empty>No users found.</template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from './DataTable.vue';
import { formatTableCellValue } from '../utils/date';

const props = defineProps({
  /** Section title */
  title: {
    type: String,
    default: 'Recent Users',
  },
  /** Maximum number of users to show (most recent by ID) */
  limit: {
    type: Number,
    default: 5,
  },
});

const users = ref([]);
const avatarLoadFailed = ref({});

const userColumns = [
  { accessorKey: 'profile_image_url', header: 'Photo' },
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'full_name', header: 'Name' },
  {
    id: 'role_names',
    accessorFn: (row) => (row.roles && row.roles.length ? row.roles.map((r) => r.name).join(', ') : '—'),
    header: 'Role',
  },
  { accessorKey: 'email', header: 'Email' },
  {
    id: 'department_name',
    accessorFn: (row) => row.department?.name ?? '',
    header: 'Department',
  },
  { accessorKey: 'created_at', header: 'Created At', meta: { type: 'datetime' } },
];

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/users', {
      params: {
        page: 1,
        per_page: Math.min(Math.max(props.limit, 1), 100),
        sort: 'id',
        direction: 'desc',
      },
    });
    users.value = Array.isArray(data.data) ? data.data : [];
  } catch (e) {
    users.value = [];
  }
});
</script>

<style scoped>
.section-title {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--color-text);
  margin: 0 0 var(--space-4);
}
</style>
