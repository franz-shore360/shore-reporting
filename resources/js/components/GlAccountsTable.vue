<template>
  <DataTable
    storage-key="gl-accounts"
    filter-layout="panel"
    :columns="glAccountColumns"
    :data="rows"
    :sortable="true"
    :filterable="true"
    :initial-sorting="glAccountsTableInitialSorting"
    server-side
    :loading="loading"
    :total="total"
    :page-count="pageCount"
    :pagination="tablePagination"
    :sorting="tableSorting"
    :column-filters="tableColumnFilters"
    @update:pagination="onPaginationUpdate"
    @update:sorting="onSortingUpdate"
    @update:column-filters="onColumnFiltersUpdate"
    @server-reset="onServerReset"
    :export-url="canFetch ? '/api/gl-accounts/export' : ''"
    :build-export-query-params="buildExportParams"
  >
    <template #cell="{ cell, value, row }">
      <span v-if="cell.column.columnDef.accessorKey === 'actions'" class="actions-cell">
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
    default: 'No GL accounts found.',
  },
});

const emit = defineEmits(['edit', 'delete-request']);

const glAccountsTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);

const rows = ref([]);
const loading = ref(false);
const total = ref(0);
const pageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);

const glAccountColumns = computed(() => {
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
      accessorKey: 'code',
      header: 'Code',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
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

function buildExportParams() {
  const params = {};
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
    else if (id === 'code') params.filter_code = value;
  }
  return params;
}

function buildListParams() {
  return {
    page: tablePagination.value.pageIndex + 1,
    per_page: tablePagination.value.pageSize,
    ...buildExportParams(),
  };
}

async function fetchRows() {
  if (!props.canFetch) return;
  loading.value = true;
  try {
    const { data } = await axios.get('/api/gl-accounts', {
      params: buildListParams(),
    });
    rows.value = Array.isArray(data.data) ? data.data : [];
    total.value = data.meta?.total ?? data.total ?? 0;
    pageCount.value = data.meta?.last_page ?? data.last_page ?? 1;
  } catch {
    rows.value = [];
    total.value = 0;
    pageCount.value = 1;
  } finally {
    loading.value = false;
  }
}

let listFetchTimer = null;

function queueFetch() {
  if (!props.canFetch) return;
  clearTimeout(listFetchTimer);
  listFetchTimer = setTimeout(() => {
    listFetchTimer = null;
    fetchRows();
  }, 100);
}

watch(
  [tablePagination, tableSorting, tableColumnFilters],
  () => {
    queueFetch();
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
  tableSorting.value = glAccountsTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
  tablePagination.value = { ...tablePagination.value, pageIndex: 0 };
  fetchRows();
}

onMounted(() => {
  if (props.canFetch) {
    fetchRows();
  }
});

defineExpose({
  refresh: fetchRows,
});
</script>
