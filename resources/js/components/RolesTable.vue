<template>
  <DataTable
    ref="dataTableRef"
    storage-key="roles"
    :columns="roleColumns"
    :data="roles"
    :sortable="true"
    :filterable="true"
    :initial-sorting="rolesTableInitialSorting"
    server-side
    :loading="rolesLoading"
    :total="rolesTotal"
    :page-count="rolesPageCount"
    :pagination="tablePagination"
    :sorting="tableSorting"
    :column-filters="tableColumnFilters"
    @update:pagination="onPaginationUpdate"
    @update:sorting="onSortingUpdate"
    @update:column-filters="onColumnFiltersUpdate"
    @server-reset="onServerReset"
  >
    <template #cell="{ cell, value, row }">
      <span v-if="cell.column.columnDef.accessorKey === 'actions'" class="actions-cell">
        <button
          v-if="canEdit && row.original.name !== adminRoleName"
          type="button"
          class="btn btn-sm btn-outline btn-icon"
          title="Edit"
          @click="emit('edit', row.original)"
        >
          <GridIcon name="edit" />
        </button>
        <button
          v-if="canDelete && row.original.name !== adminRoleName"
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
    default: true,
  },
  canDelete: {
    type: Boolean,
    default: true,
  },
  /** Role name that cannot be edited or deleted in the grid. */
  adminRoleName: {
    type: String,
    default: 'Admin',
  },
  emptyMessage: {
    type: String,
    default: 'No roles found.',
  },
});

const emit = defineEmits(['edit', 'delete-request']);

const dataTableRef = ref(null);

const rolesTableInitialSorting = Object.freeze([{ id: 'id', desc: true }]);

const roles = ref([]);
const rolesLoading = ref(false);
const rolesTotal = ref(0);
const rolesPageCount = ref(1);
const tablePagination = ref({ pageIndex: 0, pageSize: 25 });
const tableSorting = ref([]);
const tableColumnFilters = ref([]);

const roleColumns = computed(() => {
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
      accessorKey: 'users_count',
      header: 'Users',
      enableColumnFilter: false,
      enableSorting: true,
      sortingFn: 'basic',
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

function buildRolesListParams() {
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
  }
  return params;
}

async function fetchRoles() {
  if (!props.canFetch) return;
  rolesLoading.value = true;
  try {
    const { data } = await axios.get('/api/roles', {
      params: buildRolesListParams(),
    });
    roles.value = Array.isArray(data.data) ? data.data : [];
    rolesTotal.value = data.meta?.total ?? data.total ?? 0;
    rolesPageCount.value = data.meta?.last_page ?? data.last_page ?? 1;
  } catch (e) {
    roles.value = [];
    rolesTotal.value = 0;
    rolesPageCount.value = 1;
  } finally {
    rolesLoading.value = false;
  }
}

let listFetchTimer = null;

function queueFetchRoles() {
  if (!props.canFetch) return;
  clearTimeout(listFetchTimer);
  listFetchTimer = setTimeout(() => {
    listFetchTimer = null;
    fetchRoles();
  }, 100);
}

watch(
  [tablePagination, tableSorting, tableColumnFilters],
  () => {
    queueFetchRoles();
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
  tableSorting.value = rolesTableInitialSorting.map((s) => ({ id: s.id, desc: s.desc }));
  tablePagination.value = { ...tablePagination.value, pageIndex: 0 };
  fetchRoles();
}

onMounted(() => {
  if (props.canFetch) {
    fetchRoles();
  }
});

defineExpose({
  refresh: fetchRoles,
});
</script>
