<template>
  <DataTable
    storage-key="imports"
    :columns="importColumns"
    :data="rows"
    :sortable="true"
    :filterable="true"
    :initial-sorting="importsTableInitialSorting"
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
  >
    <template #cell="{ cell, value, row }">
      <span v-if="cell.column.columnDef.accessorKey === 'entity_type'" class="cell-ellipsis">
        {{ entityLabel(row.original.entity_type) }}
      </span>
      <span v-else-if="cell.column.columnDef.accessorKey === 'import_file'" class="cell-ellipsis" :title="value">
        <button
          v-if="row.original?.id && value"
          type="button"
          class="file-download-btn"
          :title="`Download ${fileBasename(value)}`"
          @click="downloadImportFile(row.original)"
        >
          {{ fileBasename(value) }}
        </button>
        <span v-else>{{ fileBasename(value) }}</span>
      </span>
      <span v-else-if="cell.column.columnDef.accessorKey === 'error_file'" class="cell-ellipsis" :title="value">
        {{ fileBasename(value) }}
      </span>
      <span v-else-if="cell.column.columnDef.accessorKey === 'status'">
        {{ statusLabel(row.original.status) }}
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
import { formatTableCellValue } from '../utils/date';

const props = defineProps({
  canFetch: {
    type: Boolean,
    default: true,
  },
  emptyMessage: {
    type: String,
    default: 'No imports found.',
  },
});

const importsTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);

/** Same labels as the Status column filter (value = API / DB raw status). */
const IMPORT_STATUS_OPTIONS = Object.freeze([
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
]);

const rows = ref([]);
const loading = ref(false);
const total = ref(0);
const pageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);

function entityLabel(type) {
  if (type === 'department') return 'Departments';
  if (type === 'gl_account') return 'GL Accounts';
  return type || '—';
}

function fileBasename(path) {
  if (!path) return '—';
  const parts = String(path).split(/[/\\]/);
  return parts[parts.length - 1] || path;
}

async function downloadImportFile(row) {
  const id = row?.id;
  const storedPath = row?.import_file;
  if (!id || !storedPath) return;
  try {
    const response = await axios.get(`/api/imports/${id}/file`, { responseType: 'blob' });
    let filename = fileBasename(storedPath);
    const dispo = response.headers['content-disposition'];
    if (typeof dispo === 'string') {
      const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(dispo);
      const asciiMatch = /filename="([^"]+)"/i.exec(dispo);
      const raw = utf8Match?.[1] ?? asciiMatch?.[1];
      if (raw) {
        try {
          filename = decodeURIComponent(raw);
        } catch {
          filename = raw;
        }
      }
    }
    const url = URL.createObjectURL(response.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  } catch {
    // Optional: surface toast; keep table usable without extra deps
  }
}

function statusLabel(raw) {
  const key = String(raw ?? '').toLowerCase();
  const found = IMPORT_STATUS_OPTIONS.find((o) => o.value === key);
  return found ? found.label : raw || '—';
}

const importColumns = computed(() => [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    sortingFn: 'basic',
  },
  {
    accessorKey: 'entity_type',
    header: 'Entity',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    filterOptions: [
      { value: '', label: 'All' },
      { value: 'department', label: 'Departments' },
      { value: 'gl_account', label: 'GL Accounts' },
    ],
  },
  {
    accessorKey: 'import_file',
    header: 'File',
    enableColumnFilter: false,
    enableSorting: false,
  },
  {
    accessorKey: 'error_file',
    header: 'Error File',
    enableColumnFilter: false,
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    filterOptions: [{ value: '', label: 'All' }, ...IMPORT_STATUS_OPTIONS],
  },
  {
    accessorKey: 'total_items',
    header: 'Items',
    enableColumnFilter: false,
    enableSorting: true,
  },
  {
    accessorKey: 'total_errors',
    header: 'Errors',
    enableColumnFilter: false,
    enableSorting: true,
  },
  {
    id: 'email_sent',
    accessorFn: (row) => (row.email_sent ? 'Yes' : 'No'),
    header: 'Email Sent',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    filterOptions: [
      { value: '', label: 'All' },
      { value: 'Yes', label: 'Yes' },
      { value: 'No', label: 'No' },
    ],
  },
  {
    id: 'user_id',
    accessorFn: (row) => row.user?.full_name ?? '—',
    header: 'User',
    enableColumnFilter: false,
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
    accessorKey: 'started_at',
    header: 'Started At',
    enableColumnFilter: false,
    enableSorting: true,
    meta: { type: 'datetime' },
  },
  {
    accessorKey: 'completed_at',
    header: 'Completed At',
    enableColumnFilter: false,
    enableSorting: true,
    meta: { type: 'datetime' },
  },
]);

function buildServerParams() {
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
    else if (id === 'entity_type') params.filter_entity_type = value;
    else if (id === 'status') params.filter_status = value;
    else if (id === 'email_sent') params.filter_email_sent = value;
  }
  return params;
}

function buildListParams() {
  return {
    page: tablePagination.value.pageIndex + 1,
    per_page: tablePagination.value.pageSize,
    ...buildServerParams(),
  };
}

async function fetchRows() {
  if (!props.canFetch) return;
  loading.value = true;
  try {
    const { data } = await axios.get('/api/imports', {
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
  tableSorting.value = importsTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
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

<style scoped>
.cell-ellipsis {
  display: inline-block;
  max-width: 12rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: bottom;
}

.file-download-btn {
  display: inline;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 0;
  border: 0;
  margin: 0;
  background: none;
  font: inherit;
  color: var(--color-link, var(--color-primary, #2563eb));
  text-decoration: underline;
  text-align: left;
  cursor: pointer;
}

.file-download-btn:hover {
  text-decoration: none;
}
</style>
