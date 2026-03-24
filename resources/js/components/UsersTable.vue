<template>
  <DataTable
    ref="dataTableRef"
    :key="'users-' + roles.length"
    storage-key="users"
    :columns="userColumns"
    :data="users"
    :sortable="true"
    :filterable="true"
    :initial-sorting="usersTableInitialSorting"
    server-side
    :loading="usersLoading"
    :total="usersTotal"
    :page-count="usersPageCount"
    :pagination="tablePagination"
    :sorting="tableSorting"
    :column-filters="tableColumnFilters"
    :row-selection-enabled="canDelete"
    :bulk-actions="bulkActions"
    :is-row-selectable="isUserRowSelectable"
    @update:pagination="onPaginationUpdate"
    @update:sorting="onSortingUpdate"
    @update:column-filters="onColumnFiltersUpdate"
    @server-reset="onServerReset"
    @bulk-action="onBulkAction"
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
      <span v-else-if="cell.column.id === 'department_name'">
        {{ row.original.department?.name ?? '—' }}
      </span>
      <span v-else-if="cell.column.id === 'is_active'">
        <span
          class="badge"
          :class="row.original.is_active === false ? 'badge-inactive' : 'badge-success'"
        >
          {{ row.original.is_active === false ? 'Inactive' : 'Active' }}
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
          v-if="canDelete && row.original.id !== authState.user?.id"
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
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { authState } from '../auth';
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
  /** Role list for Role column filter options (same source as user form). */
  roles: {
    type: Array,
    default: () => [],
  },
  emptyMessage: {
    type: String,
    default: 'No users yet.',
  },
});

const emit = defineEmits(['edit', 'delete-request', 'notify']);

const dataTableRef = ref(null);

const bulkActions = computed(() => {
  const actions = [];
  if (props.canDelete) {
    actions.push({ value: 'delete', label: 'Delete selected' });
  }
  return actions;
});

function isUserRowSelectable(row) {
  const id = row?.original?.id;
  return id != null && id !== authState.user?.id;
}

async function onBulkAction({ action, ids }) {
  if (!ids?.length) return;

  switch (action) {
    case 'delete': {
      if (!props.canDelete) return;
      const n = ids.length;
      if (!window.confirm(`Delete ${n} user(s)? This cannot be undone.`)) return;
      try {
        const { data } = await axios.post('/api/users/bulk-destroy', {
          ids: ids.map((id) => Number(id)),
        });
        dataTableRef.value?.clearRowSelection();
        await fetchUsers();
        const parts = [`${data.deleted ?? 0} user(s) deleted.`];
        if (data.skipped_self) {
          parts.push('Your own account was not included.');
        }
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
      }
      break;
    }
    default:
      break;
  }
}

const usersTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);

const users = ref([]);
const usersLoading = ref(false);
const usersTotal = ref(0);
const usersPageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);
const avatarLoadFailed = reactive({});

const userColumns = computed(() => {
  const base = [
    { accessorKey: 'profile_image_url', header: 'Photo', enableColumnFilter: false, enableSorting: false },
    {
      accessorKey: 'id',
      header: 'ID',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
      sortingFn: 'basic',
    },
    { accessorKey: 'full_name', header: 'Name', enableSorting: true },
    {
      id: 'role_names',
      accessorFn: (row) => (row.roles && row.roles.length ? row.roles.map((r) => r.name).join(', ') : '—'),
      header: 'Role',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: false,
      filterOptions: [
        { value: '', label: 'All' },
        ...props.roles.map((r) => ({ value: r.name, label: r.name })),
      ],
    },
    {
      accessorKey: 'email',
      header: 'Email',
      enableColumnFilter: true,
      filterFn: 'includesString',
      enableSorting: true,
    },
    {
      id: 'is_active',
      accessorFn: (row) => (row.is_active === false ? 'Inactive' : 'Active'),
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
      id: 'department_name',
      accessorFn: (row) => row.department?.name ?? '',
      header: 'Department',
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

function buildUsersListParams() {
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
    else if (id === 'email') params.filter_email = value;
    else if (id === 'full_name') params.filter_full_name = value;
    else if (id === 'role_names') params.filter_role = value;
    else if (id === 'is_active') params.filter_is_active = value;
    else if (id === 'department_name') params.filter_department_name = value;
  }
  return params;
}

async function fetchUsers() {
  if (!props.canFetch) return;
  usersLoading.value = true;
  try {
    const { data } = await axios.get('/api/users', { params: buildUsersListParams() });
    users.value = Array.isArray(data.data) ? data.data : [];
    usersTotal.value = data.meta?.total ?? data.total ?? 0;
    usersPageCount.value = data.meta?.last_page ?? data.last_page ?? 1;
  } catch (e) {
    users.value = [];
    usersTotal.value = 0;
    usersPageCount.value = 1;
  } finally {
    usersLoading.value = false;
  }
}

let listFetchTimer = null;

function queueFetchUsers() {
  if (!props.canFetch) return;
  clearTimeout(listFetchTimer);
  listFetchTimer = setTimeout(() => {
    listFetchTimer = null;
    fetchUsers();
  }, 100);
}

watch(
  [tablePagination, tableSorting, tableColumnFilters],
  () => {
    queueFetchUsers();
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
  tableSorting.value = usersTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
  tablePagination.value = { ...tablePagination.value, pageIndex: 0 };
  fetchUsers();
}

onMounted(() => {
  if (props.canFetch) {
    fetchUsers();
  }
});

defineExpose({
  refresh: fetchUsers,
});
</script>
