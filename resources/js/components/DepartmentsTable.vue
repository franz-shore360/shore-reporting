<template>
  <DataTable
    storage-key="departments"
    :columns="departmentColumns"
    :data="departments"
    :sortable="true"
    :filterable="true"
    :initial-sorting="departmentsTableInitialSorting"
    server-side
    :loading="departmentsLoading"
    :total="departmentsTotal"
    :page-count="departmentsPageCount"
    :pagination="tablePagination"
    :sorting="tableSorting"
    :column-filters="tableColumnFilters"
    @update:pagination="onPaginationUpdate"
    @update:sorting="onSortingUpdate"
    @update:column-filters="onColumnFiltersUpdate"
    @server-reset="onServerReset"
  >
    <template #cell="{ cell, value, row }">
      <span v-if="cell.column.id === 'is_active'" class="badge-cell">
        <span :class="['badge', value === 'Active' ? 'badge-success' : 'badge-inactive']">
          {{ value }}
        </span>
      </span>
      <span v-else-if="cell.column.columnDef.accessorKey === 'actions'" class="actions-cell">
        <button
          v-if="canEdit"
          type="button"
          class="btn btn-sm btn-outline btn-icon"
          title="Edit"
          @click="emit('edit', row.original)"
        >
          <GridIcon name="edit" />
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="btn btn-sm btn-danger btn-icon"
          title="Delete"
          @click="emit('delete-request', row.original)"
        >
          <GridIcon name="trash" />
        </button>
      </span>
      <template v-else>{{ formatTableCellValue(cell.column.columnDef, value) }}</template>
    </template>
    <template #empty>{{ emptyMessage }}</template>
  </DataTable>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import DataTable from './DataTable.vue';
import GridIcon from './icons/GridIcons.vue';
import { formatTableCellValue } from '../utils/date';

const props = defineProps({
  /** When false, list requests are skipped (e.g. no permission). */
  canFetch: {
    type: Boolean,
    default: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: 'No departments yet.',
  },
});

const emit = defineEmits(['edit', 'delete-request']);

/** Default grid sort: ID descending */
const departmentsTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);

const departments = ref([]);
const departmentsLoading = ref(false);
const departmentsTotal = ref(0);
const departmentsPageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);

const departmentColumns = computed(() => {
  const base = [
    {
      accessorKey: 'id',
      header: 'ID',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
      sortingFn: 'basic',
    },
    {
      accessorKey: 'name',
      header: 'Name',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
    },
    {
      id: 'is_active',
      accessorFn: (row) => (row.is_active ? 'Active' : 'Inactive'),
      header: 'Status',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
      filterOptions: [
        { value: '', label: 'All' },
        { value: 'Active', label: 'Active' },
        { value: 'Inactive', label: 'Inactive' },
      ],
    },
    {
      accessorKey: 'created_at',
      header: 'Created At',
      enableColumnFilter: false,
      enableSorting: true,
      meta: { type: 'datetime' },
    },
    {
      accessorKey: 'updated_at',
      header: 'Updated At',
      enableColumnFilter: false,
      enableSorting: true,
      meta: { type: 'datetime' },
    },
  ];
  if (props.canEdit || props.canDelete) {
    base.push({
      accessorKey: 'actions',
      header: 'Actions',
      enableColumnFilter: false,
      enableHiding: false,
      enableSorting: false,
    });
  }
  return base;
});

function buildDepartmentsListParams() {
  const params = {
    page: tablePagination.value.pageIndex + 1,
    per_page: tablePagination.value.pageSize,
  };
  const sort = tableSorting.value[0];
  if (sort?.id) {
    params.sort = sort.id;
    params.direction = sort.desc ? 'desc' : 'asc';
  } else {
    params.sort = 'id';
    params.direction = 'desc';
  }
  for (const { id, value } of tableColumnFilters.value) {
    if (value === '' || value == null) continue;
    if (id === 'id') params.filter_id = value;
    else if (id === 'name') params.filter_name = value;
    else if (id === 'is_active') params.filter_is_active = value;
  }
  return params;
}

async function fetchDepartments() {
  if (!props.canFetch) return;
  departmentsLoading.value = true;
  try {
    const { data } = await axios.get('/api/departments', {
      params: buildDepartmentsListParams(),
    });
    departments.value = Array.isArray(data.data) ? data.data : [];
    departmentsTotal.value = data.meta?.total ?? data.total ?? 0;
    departmentsPageCount.value = data.meta?.last_page ?? data.last_page ?? 1;
  } catch (e) {
    departments.value = [];
    departmentsTotal.value = 0;
    departmentsPageCount.value = 1;
  } finally {
    departmentsLoading.value = false;
  }
}

let listFetchTimer = null;

function queueFetchDepartments() {
  if (!props.canFetch) return;
  clearTimeout(listFetchTimer);
  listFetchTimer = setTimeout(() => {
    listFetchTimer = null;
    fetchDepartments();
  }, 100);
}

watch(
  [tablePagination, tableSorting, tableColumnFilters],
  () => {
    queueFetchDepartments();
  },
  { deep: true },
);

function onPaginationUpdate(p) {
  tablePagination.value = p;
}

function onSortingUpdate(s) {
  tableSorting.value = s;
}

function onColumnFiltersUpdate(f) {
  tableColumnFilters.value = f;
}

function onServerReset() {
  clearTimeout(listFetchTimer);
  listFetchTimer = null;
  tableColumnFilters.value = [];
  tableSorting.value = departmentsTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
  tablePagination.value = { ...tablePagination.value, pageIndex: 0 };
  fetchDepartments();
}

onMounted(() => {
  if (props.canFetch) {
    fetchDepartments();
  }
});

defineExpose({
  refresh: fetchDepartments,
});
</script>
