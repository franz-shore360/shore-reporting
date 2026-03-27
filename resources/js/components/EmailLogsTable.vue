<template>
  <DataTable
    ref="dataTableRef"
    storage-key="email-logs"
    :columns="emailLogColumns"
    :data="rows"
    :sortable="true"
    :filterable="true"
    :initial-sorting="emailLogsTableInitialSorting"
    server-side
    :loading="loading"
    :total="total"
    :page-count="pageCount"
    :pagination="tablePagination"
    :sorting="tableSorting"
    :column-filters="tableColumnFilters"
    :row-selection-enabled="canDelete"
    :bulk-actions="bulkActions"
    @update:pagination="onPaginationUpdate"
    @update:sorting="onSortingUpdate"
    @update:column-filters="onColumnFiltersUpdate"
    @server-reset="onServerReset"
    @bulk-action="onBulkAction"
  >
    <template #cell="{ cell, value, row }">
      <span v-if="cell.column.columnDef.accessorKey === 'actions'" class="actions-cell">
        <button
          type="button"
          class="btn btn-sm btn-outline btn-icon"
          title="View"
          aria-label="View"
          @click="emit('view', row.original.id)"
        >
          <GridIcon name="view" />
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
      <span
        v-else-if="isWrapColumn(cell.column.columnDef.accessorKey)"
        class="email-logs-cell-wrap"
      >
        {{ displayText(value) }}
      </span>
      <template v-else>{{ formatTableCellValue(cell.column.columnDef, value) }}</template>
    </template>
    <template #empty>{{ emptyMessage }}</template>
  </DataTable>

  <ConfirmModal
    v-model:open="bulkDeleteModalOpen"
    title="Delete Email Logs"
    :confirm-label="bulkDeleteLoading ? 'Deleting…' : 'Delete'"
    variant="danger"
    :loading="bulkDeleteLoading"
    @confirm="executeBulkDelete"
  >
    <p>
      You are about to delete <strong>{{ bulkDeletePendingCount }} email log(s)</strong>. This cannot be undone.
    </p>
  </ConfirmModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import DataTable from './DataTable.vue';
import ConfirmModal from './ConfirmModal.vue';
import GridIcon from './icons/GridIcons.vue';
import { formatTableCellValue } from '../utils/date';

const props = defineProps({
  canFetch: {
    type: Boolean,
    default: true,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: 'No sent emails logged yet.',
  },
});

const emit = defineEmits(['view', 'delete-request', 'notify']);

const dataTableRef = ref(null);

const bulkDeleteIds = ref(null);
const bulkDeleteLoading = ref(false);

const bulkDeleteModalOpen = computed({
  get: () => Array.isArray(bulkDeleteIds.value) && bulkDeleteIds.value.length > 0,
  set(isOpen) {
    if (!isOpen && !bulkDeleteLoading.value) {
      bulkDeleteIds.value = null;
    }
  },
});

const bulkDeletePendingCount = computed(() =>
  Array.isArray(bulkDeleteIds.value) ? bulkDeleteIds.value.length : 0,
);

const bulkActions = computed(() => {
  const actions = [];
  if (props.canDelete) {
    actions.push({ value: 'delete', label: 'Delete selected' });
  }
  return actions;
});

function onBulkAction({ action, ids }) {
  if (!ids?.length) return;
  if (action === 'delete' && props.canDelete) {
    bulkDeleteIds.value = [...ids.map(String)];
  }
}

async function executeBulkDelete() {
  const ids = bulkDeleteIds.value;
  if (!ids?.length || !props.canDelete) return;
  bulkDeleteLoading.value = true;
  try {
    const { data } = await axios.post('/api/email-logs/bulk-destroy', {
      ids: ids.map((id) => Number(id)),
    });
    bulkDeleteIds.value = null;
    dataTableRef.value?.clearRowSelection();
    await fetchRows();
    const parts = [`${data.deleted ?? 0} email log(s) deleted.`];
    if ((data.skipped_missing ?? 0) > 0) {
      parts.push(`${data.skipped_missing} could not be removed.`);
    }
    emit('notify', { text: parts.join(' ') });
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Bulk delete failed.';
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const err = e.response.data.errors;
      emit('notify', { text: Object.values(err).flat().join(' ') || msg });
    } else {
      emit('notify', { text: msg });
    }
  } finally {
    bulkDeleteLoading.value = false;
  }
}

const emailLogsTableInitialSorting = Object.freeze([{ id: 'sent_at', desc: true }]);

const rows = ref([]);
const loading = ref(false);
const total = ref(0);
const pageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);

const WRAP_KEYS = new Set(['subject', 'to_addresses', 'from_address']);

function isWrapColumn(key) {
  return WRAP_KEYS.has(key);
}

function displayText(value) {
  if (value == null || value === '') return '—';
  return value;
}

const emailLogColumns = computed(() => [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
    sortingFn: 'basic',
  },
  {
    accessorKey: 'subject',
    header: 'Subject',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
  },
  {
    accessorKey: 'to_addresses',
    header: 'To',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
  },
  {
    accessorKey: 'from_address',
    header: 'From',
    enableColumnFilter: true,
    filterFn: 'includesString',
    enableSorting: true,
  },
  {
    accessorKey: 'sent_at',
    header: 'Sent',
    enableColumnFilter: false,
    enableSorting: true,
    meta: { type: 'datetime' },
  },
  {
    accessorKey: 'actions',
    header: 'Actions',
    enableColumnFilter: false,
    enableHiding: false,
    enableSorting: false,
  },
]);

function buildEmailLogsListParams() {
  const params = {
    page: tablePagination.value.pageIndex + 1,
    per_page: tablePagination.value.pageSize,
  };
  const sort = tableSorting.value[0];
  if (sort?.id) {
    params.sort = sort.id;
    params.direction = sort.desc ? 'desc' : 'asc';
  } else {
    params.sort = 'sent_at';
    params.direction = 'desc';
  }
  for (const { id, value } of tableColumnFilters.value) {
    if (value === '' || value == null) continue;
    if (id === 'id') params.filter_id = value;
    else if (id === 'subject') params.filter_subject = value;
    else if (id === 'to_addresses') params.filter_to_addresses = value;
    else if (id === 'from_address') params.filter_from_address = value;
  }
  return params;
}

async function fetchRows() {
  if (!props.canFetch) return;
  loading.value = true;
  try {
    const { data } = await axios.get('/api/email-logs', {
      params: buildEmailLogsListParams(),
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

function queueFetchRows() {
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
    queueFetchRows();
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
  tableSorting.value = emailLogsTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
  tablePagination.value = { ...tablePagination.value, pageIndex: 0 };
  fetchRows();
}

onMounted(() => {
  if (props.canFetch) {
    fetchRows();
  }
});

watch(
  () => props.canFetch,
  (ok) => {
    if (ok) fetchRows();
  },
);

defineExpose({
  refresh: fetchRows,
});
</script>

<style scoped>
.email-logs-cell-wrap {
  max-width: 16rem;
  white-space: normal;
  word-break: break-word;
}
</style>
