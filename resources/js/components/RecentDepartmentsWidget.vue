<template>
  <div class="card table-card">
    <h2 class="section-title">{{ title }}</h2>
    <DataTable
      :columns="departmentColumns"
      :data="departments"
      :sortable="true"
      :column-picker="false"
      :show-reset-button="false"
      :enable-column-reorder="false"
    >
      <template #cell="{ cell, value, row }">
        <span v-if="cell.column.id === 'is_active'" class="badge-cell">
          <span :class="['badge', value === 'Active' ? 'badge-success' : 'badge-inactive']">
            {{ value }}
          </span>
        </span>
        <template v-else>{{ formatTableCellValue(cell.column.columnDef, value) }}</template>
      </template>
      <template #empty>No departments found.</template>
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
    default: 'Recent Departments',
  },
  /** Maximum number of departments to show (most recent by created_at) */
  limit: {
    type: Number,
    default: 5,
  },
});

const departments = ref([]);

const departmentColumns = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'name', header: 'Name' },
  {
    id: 'is_active',
    accessorFn: (row) => (row.is_active ? 'Active' : 'Inactive'),
    header: 'Status',
  },
  { accessorKey: 'users_count', header: 'Users' },
  { accessorKey: 'created_at', header: 'Created At', meta: { type: 'datetime' } },
];

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/departments', {
      params: {
        page: 1,
        per_page: Math.min(Math.max(props.limit, 1), 100),
        sort: 'created_at',
        direction: 'desc',
      },
    });
    departments.value = Array.isArray(data.data) ? data.data : [];
  } catch (e) {
    departments.value = [];
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
