<template>
  <div ref="rootRef" class="data-table-root">
    <div ref="stickyControlsRef" class="data-table-sticky-controls">
      <div
        v-if="showBulkActionBar"
        class="data-table-bulk-actions"
        role="region"
        aria-label="Bulk actions for selected rows"
      >
        <span class="data-table-bulk-actions-count">{{ selectedRowIds.length }} selected</span>
        <label class="data-table-bulk-actions-field">
          <span class="data-table-bulk-actions-field-label">Action</span>
          <select v-model="pendingBulkAction" class="data-table-bulk-actions-select">
            <option value="" disabled>Select action…</option>
            <option v-for="opt in bulkActionsNormalized" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </label>
        <button
          type="button"
          class="btn btn-sm btn-danger"
          :disabled="!pendingBulkAction || loading"
          @click="applyBulkAction"
        >
          Apply
        </button>
        <button type="button" class="btn btn-sm btn-ghost" @click="clearRowSelection">
          Clear selection
        </button>
      </div>

      <div v-if="showToolbar" class="data-table-toolbar">
      <button
        v-if="showReset"
        type="button"
        class="btn btn-sm btn-secondary"
        aria-label="Reset filters and sorting"
        @click="resetFiltersAndSort"
      >
        Reset
      </button>
      <div v-if="showPicker" ref="pickerRootRef" class="column-picker">
        <button
          type="button"
          class="btn btn-sm btn-outline column-picker-trigger"
          :aria-expanded="columnMenuOpen"
          aria-haspopup="true"
          aria-controls="column-picker-panel"
          @click="columnMenuOpen = !columnMenuOpen"
        >
          Columns
        </button>
        <div
          v-show="columnMenuOpen"
          id="column-picker-panel"
          class="column-picker-panel"
          role="menu"
          @click.stop
        >
          <div class="column-picker-header">Show columns</div>
          <ul class="column-picker-list" role="none">
            <li v-for="col in pickerLeafColumns" :key="col.id" class="column-picker-item" role="none">
              <label class="column-picker-label" :class="{ 'is-locked': !col.getCanHide() }">
                <input
                  type="checkbox"
                  class="checkbox"
                  :checked="col.getIsVisible()"
                  :disabled="!col.getCanHide()"
                  @change="(e) => col.toggleVisibility(e.target.checked)"
                />
                <span>{{ columnHeaderLabel(col) }}</span>
              </label>
            </li>
          </ul>
          <div v-if="storageKey" class="column-picker-footer">
            <button type="button" class="btn btn-sm btn-ghost column-picker-reset" @click="resetColumnVisibility">
              Reset to default
            </button>
          </div>
        </div>
      </div>
      </div>

      <div
        v-if="serverSide"
        class="data-table-pagination data-table-pagination--top"
        role="navigation"
        aria-label="Table pagination"
      >
      <div class="data-table-pagination-info">
        <span v-if="loading" class="data-table-loading-text">Loading…</span>
        <span v-else-if="total > 0" class="muted">
          Showing {{ rangeFrom }}–{{ rangeTo }} of {{ total }}
        </span>
        <span v-else class="muted">No results</span>
      </div>
      <div class="data-table-pagination-controls">
        <label class="data-table-page-size">
          <span>Rows per page</span>
          <select
            :value="pagination.pageSize"
            :disabled="loading"
            @change="onPageSizeSelect($event)"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
        <button
          type="button"
          class="btn btn-sm btn-outline"
          :disabled="loading || !canPrevPage"
          @click="goFirstPage"
        >
          First
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline"
          :disabled="loading || !canPrevPage"
          @click="goPrevPage"
        >
          Previous
        </button>
        <span class="data-table-page-label">
          Page {{ pageDisplayIndex }} of {{ pageDisplayCount }}
        </span>
        <button
          type="button"
          class="btn btn-sm btn-outline"
          :disabled="loading || !canNextPage"
          @click="goNextPage"
        >
          Next
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline"
          :disabled="loading || !canNextPage"
          @click="goLastPage"
        >
          Last
        </button>
      </div>
      </div>
    </div>

    <div class="data-table-wrapper">
      <table ref="tableRef" class="data-table">
        <thead>
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th v-if="rowSelectionEnabled" class="data-table-th data-table-th-select" scope="col">
              <input
                ref="headerSelectAllRef"
                type="checkbox"
                class="checkbox"
                :checked="headerSelectState.checked"
                :title="selectAllCheckboxTitle"
                :aria-label="selectAllCheckboxTitle"
                @change="onHeaderSelectAllChange($event)"
              />
            </th>
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              :colspan="header.colSpan"
              class="data-table-th"
              :class="{
                'data-table-th--drop-target':
                  enableColumnReorder && dragOverColumnId === header.column.id && isLeafHeader(header),
              }"
              @dragover="(e) => onColumnHeaderDragOver(e, header)"
              @drop="(e) => onColumnHeaderDrop(e, header)"
            >
              <div class="data-table-th-inner">
                <span
                  v-if="enableColumnReorder && isLeafHeader(header)"
                  class="data-table-drag-handle"
                  draggable="true"
                  title="Drag to reorder column"
                  aria-label="Drag to reorder column"
                  @dragstart="onColumnDragStart($event, header.column.id)"
                  @dragend="onColumnDragEnd"
                  @click.prevent.stop
                >⋮⋮</span>
                <div class="data-table-th-main">
                  <slot
                    name="header"
                    :header="header"
                    :column="header.column"
                    :filter-value="getColumnFilterValue(header.column.id)"
                    :set-filter="(value) => setColumnFilter(header.column.id, value)"
                    :filterable="filterable && (header.column.getCanFilter && header.column.getCanFilter())"
                  >
                    <button
                      v-if="sortable && header.column.getCanSort()"
                      type="button"
                      class="data-table-th-sort"
                      @click="header.column.getToggleSortingHandler()($event)"
                    >
                      <span>{{ headerLabelText(header) }}</span>
                      <span
                        class="data-table-sort-caret"
                        :class="{
                          'data-table-sort-caret--asc': header.column.getIsSorted() === 'asc',
                          'data-table-sort-caret--desc': header.column.getIsSorted() === 'desc',
                        }"
                        aria-hidden="true"
                      ></span>
                    </button>
                    <template v-else>{{ headerLabelText(header) }}</template>
                  </slot>
                </div>
              </div>
            </th>
          </tr>
          <tr v-if="filterable && hasFilterableColumns" class="data-table-filter-row">
            <th v-if="rowSelectionEnabled" class="data-table-th data-table-th-filter data-table-th-select" />
            <th
              v-for="header in filterRowHeaders"
              :key="'filter-' + header.id"
              class="data-table-th data-table-th-filter"
            >
              <template v-if="filterable && (header.column.getCanFilter && header.column.getCanFilter())">
                <select
                  v-if="getFilterOptions(header.column.columnDef).length"
                  class="data-table-filter-select"
                  :value="getColumnFilterValue(header.column.id)"
                  @change="setColumnFilter(header.column.id, $event.target.value)"
                >
                  <option
                    v-for="opt in getFilterOptions(header.column.columnDef)"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </option>
                </select>
                <input
                  v-else
                  type="text"
                  class="data-table-filter-input"
                  :value="getColumnFilterValue(header.column.id)"
                  :placeholder="'Filter ' + (header.column.columnDef.header || '')"
                  @input="setColumnFilter(header.column.id, $event.target.value)"
                />
              </template>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id" class="data-table-tr">
            <td v-if="rowSelectionEnabled" class="data-table-td data-table-td-select">
              <input
                type="checkbox"
                class="checkbox"
                :checked="isRowSelected(row)"
                :disabled="!isRowSelectable(row)"
                :aria-label="'Select row ' + getRowRecordId(row)"
                @change="toggleRowSelection(row, $event.target.checked)"
              />
            </td>
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              class="data-table-td"
            >
              <slot
                name="cell"
                :cell="cell"
                :value="cell.getValue()"
                :row="row"
              >
                {{ formatCellValue(cell, cell.getValue()) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="data.length === 0" class="data-table-empty">
        <slot name="empty">No data</slot>
      </p>
    </div>
  </div>
</template>

<script setup>
import { toRef, ref, computed, watch, watchEffect, onMounted, onUnmounted, nextTick } from 'vue';
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
} from '@tanstack/vue-table';
import { formatDateDisplay, formatDateTimeDisplay } from '../utils/date';

const props = defineProps({
  /** Column definitions: [{ accessorKey: 'id', header: 'ID' }, ...] */
  columns: {
    type: Array,
    required: true,
  },
  /** Array of row objects */
  data: {
    type: Array,
    default: () => [],
  },
  /** Enable column sorting */
  sortable: {
    type: Boolean,
    default: false,
  },
  /** Enable column filtering (columns must have enableColumnFilter: true) */
  filterable: {
    type: Boolean,
    default: false,
  },
  /** Persist column visibility, sorting, order, and column filters to localStorage (per grid) */
  storageKey: {
    type: String,
    default: null,
  },
  /** Show the column visibility control */
  columnPicker: {
    type: Boolean,
    default: true,
  },
  /** Show Reset (filters + sort). Set false for compact widgets e.g. dashboard cards. */
  showResetButton: {
    type: Boolean,
    default: true,
  },
  /** Drag-and-drop column reorder (header grip). Persisted when storageKey is set. */
  enableColumnReorder: {
    type: Boolean,
    default: true,
  },
  /** Initial sort (TanStack shape: [{ id: columnId, desc: boolean }]). Only applied when sortable. */
  initialSorting: {
    type: Array,
    default: () => [],
  },
  /** Server-driven pagination (parent fetches data; pass pageCount, total, pagination, sorting, columnFilters). */
  serverSide: {
    type: Boolean,
    default: false,
  },
  /** Total row count from API (meta.total). */
  total: {
    type: Number,
    default: 0,
  },
  /** Last page number from API (meta.last_page). */
  pageCount: {
    type: Number,
    default: 0,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  /** TanStack shape { pageIndex, pageSize } — required when serverSide. */
  pagination: {
    type: Object,
    default: () => ({ pageIndex: 0, pageSize: 25 }),
  },
  /** Controlled sort state when serverSide. */
  sorting: {
    type: Array,
    default: () => [],
  },
  /** Controlled column filters when serverSide [{ id, value }]. */
  columnFilters: {
    type: Array,
    default: () => [],
  },
  /** Show leading checkboxes and optional bulk action bar (current page only for “select all”). */
  rowSelectionEnabled: {
    type: Boolean,
    default: false,
  },
  /** Key on each row object used as stable id (e.g. "id"). */
  rowIdKey: {
    type: String,
    default: 'id',
  },
  /** Bulk actions for the toolbar dropdown, e.g. [{ value: 'delete', label: 'Delete' }]. */
  bulkActions: {
    type: Array,
    default: () => [],
  },
  /**
   * (row: Row) => boolean — if false, row checkbox is disabled.
   * Row is TanStack Row; use row.original for data.
   */
  isRowSelectable: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits([
  'update:pagination',
  'update:sorting',
  'update:columnFilters',
  'server-reset',
  /** { action: string, ids: string[] } — parent performs API / confirm */
  'bulk-action',
]);

const columnDefs = computed(() => (Array.isArray(props.columns) ? props.columns : []));

/** Client-only filters; server mode uses props.columnFilters. */
const clientColumnFilters = ref([]);
/** One-time load from localStorage per storageKey (client grids). */
const columnFiltersClientLoadedFromStorage = ref(false);
/** One-time hydrate from localStorage per storageKey (server grids). */
const columnFiltersServerHydratedFromStorage = ref(false);
/** One-time load sorting from localStorage per storageKey (client grids). */
const sortingClientLoadedFromStorage = ref(false);
/** One-time hydrate sorting from localStorage per storageKey (server grids). */
const sortingServerHydratedFromStorage = ref(false);
const columnVisibility = ref({});

function cloneSortingSpec(spec) {
  if (!Array.isArray(spec) || spec.length === 0) {
    return [];
  }
  return spec.map((s) => ({ id: String(s.id), desc: Boolean(s.desc) }));
}

function getColumnIdsFromDefs(defs) {
  if (!Array.isArray(defs)) return [];
  return defs
    .map((c) => (c.id != null ? String(c.id) : c.accessorKey != null ? String(c.accessorKey) : null))
    .filter(Boolean);
}

const FILTERS_STORAGE_PREFIX = 'datatable-filters:';
const SORTING_STORAGE_PREFIX = 'datatable-sorting:';

function getFilterableColumnIds(defs) {
  if (!Array.isArray(defs)) return [];
  return defs
    .filter((c) => c.enableColumnFilter === true)
    .map((c) => (c.id != null ? String(c.id) : c.accessorKey != null ? String(c.accessorKey) : null))
    .filter(Boolean);
}

/** Keep only non-empty filters for columns that still exist and are filterable */
function sanitizeColumnFilters(filters, defs) {
  if (!Array.isArray(filters)) return [];
  const allowed = new Set(getFilterableColumnIds(defs));
  if (!allowed.size) return [];
  const out = [];
  for (const f of filters) {
    if (!f || f.id == null) continue;
    const id = String(f.id);
    if (!allowed.has(id)) continue;
    const value = f.value;
    if (value === undefined || value === null || String(value).trim() === '') continue;
    out.push({ id, value: String(value) });
  }
  return out;
}

function sanitizeSortingSpec(spec, defs) {
  const allowed = new Set(getColumnIdsFromDefs(defs));
  if (!allowed.size) return [];
  return cloneSortingSpec(spec).filter((s) => allowed.has(s.id));
}

function persistSortingToStorage(sortingArr) {
  if (!props.sortable || !props.storageKey) return;
  const arr = Array.isArray(sortingArr) ? sortingArr : [];
  try {
    if (!arr.length) {
      localStorage.removeItem(`${SORTING_STORAGE_PREFIX}${props.storageKey}`);
    } else {
      const normalized = arr.map((s) => ({ id: String(s.id), desc: Boolean(s.desc) }));
      localStorage.setItem(`${SORTING_STORAGE_PREFIX}${props.storageKey}`, JSON.stringify(normalized));
    }
  } catch {
    /* ignore quota */
  }
}

/** Merge saved order with current leaf ids (append new, drop removed). */
function mergeColumnOrderWithDef(savedOrder, leafIds) {
  if (!Array.isArray(leafIds) || leafIds.length === 0) {
    return [];
  }
  const set = new Set(leafIds);
  const seen = new Set();
  const ordered = [];
  const source = Array.isArray(savedOrder) ? savedOrder : [];
  for (const id of source) {
    const sid = String(id);
    if (set.has(sid) && !seen.has(sid)) {
      ordered.push(sid);
      seen.add(sid);
    }
  }
  for (const id of leafIds) {
    const sid = String(id);
    if (!seen.has(sid)) {
      ordered.push(sid);
      seen.add(sid);
    }
  }
  return ordered;
}

const columnOrder = ref([]);

const sorting = ref(
  props.sortable ? cloneSortingSpec(props.initialSorting) : [],
);

/** Restore sorting from cache once columns exist (client + server), same pattern as filters. */
function syncSortingFromStorage() {
  if (!props.sortable) {
    if (!props.serverSide) {
      sorting.value = [];
    }
    return;
  }

  const defs = columnDefs.value;
  if (!getColumnIdsFromDefs(defs).length) return;

  if (!props.serverSide) {
    if (!props.storageKey) return;
    if (sortingClientLoadedFromStorage.value) return;
    const fallback = cloneSortingSpec(props.initialSorting);
    try {
      const raw = localStorage.getItem(`${SORTING_STORAGE_PREFIX}${props.storageKey}`);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) {
          const cleaned = sanitizeSortingSpec(parsed, defs);
          if (cleaned.length) {
            sorting.value = cleaned;
            sortingClientLoadedFromStorage.value = true;
            return;
          }
        }
      }
    } catch {
      /* ignore */
    }
    sorting.value = fallback;
    sortingClientLoadedFromStorage.value = true;
    return;
  }

  if (!props.storageKey) return;
  if (sortingServerHydratedFromStorage.value) return;
  const serverSort = Array.isArray(props.sorting) ? props.sorting : [];
  if (serverSort.length > 0) {
    sortingServerHydratedFromStorage.value = true;
    return;
  }
  try {
    const raw = localStorage.getItem(`${SORTING_STORAGE_PREFIX}${props.storageKey}`);
    const parsed = raw ? JSON.parse(raw) : null;
    const cleaned = sanitizeSortingSpec(Array.isArray(parsed) ? parsed : [], defs);
    if (cleaned.length) {
      emit('update:sorting', cleaned);
      persistSortingToStorage(cleaned);
      const pageSize = Number(props.pagination?.pageSize) || 25;
      emit('update:pagination', { ...(props.pagination || {}), pageIndex: 0, pageSize });
    }
  } catch {
    /* ignore */
  }
  sortingServerHydratedFromStorage.value = true;
}

function loadColumnOrderFromStorage() {
  if (!props.enableColumnReorder) {
    columnOrder.value = [];
    return;
  }
  const leafIds = getColumnIdsFromDefs(columnDefs.value);
  if (!leafIds.length) {
    columnOrder.value = [];
    return;
  }
  if (!props.storageKey) {
    columnOrder.value = [];
    return;
  }
  try {
    const raw = localStorage.getItem(`datatable-column-order:${props.storageKey}`);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed) && parsed.length) {
        columnOrder.value = mergeColumnOrderWithDef(parsed, leafIds);
        return;
      }
    }
  } catch {
    /* ignore */
  }
  columnOrder.value = [];
}

function loadVisibilityFromStorage() {
  if (!props.storageKey) {
    columnVisibility.value = {};
    return;
  }
  try {
    const raw = localStorage.getItem(`datatable-columns:${props.storageKey}`);
    if (!raw) {
      columnVisibility.value = {};
      return;
    }
    const parsed = JSON.parse(raw);
    columnVisibility.value = typeof parsed === 'object' && parsed !== null ? parsed : {};
  } catch {
    columnVisibility.value = {};
  }
}

function loadColumnFiltersFromStorageForClient() {
  if (!props.filterable || props.serverSide) return;
  if (!props.storageKey) {
    clientColumnFilters.value = [];
    return;
  }
  const defs = columnDefs.value;
  try {
    const raw = localStorage.getItem(`${FILTERS_STORAGE_PREFIX}${props.storageKey}`);
    if (!raw) {
      clientColumnFilters.value = [];
      return;
    }
    const parsed = JSON.parse(raw);
    clientColumnFilters.value = sanitizeColumnFilters(Array.isArray(parsed) ? parsed : [], defs);
  } catch {
    clientColumnFilters.value = [];
  }
}

/** Restore filters from cache once columns are known (client + server). */
function syncColumnFiltersFromStorage() {
  if (!props.filterable || !props.storageKey) return;
  const defs = columnDefs.value;
  if (!getFilterableColumnIds(defs).length) return;

  if (!props.serverSide) {
    if (columnFiltersClientLoadedFromStorage.value) return;
    loadColumnFiltersFromStorageForClient();
    columnFiltersClientLoadedFromStorage.value = true;
    return;
  }

  if (columnFiltersServerHydratedFromStorage.value) return;
  const serverFilters = Array.isArray(props.columnFilters) ? props.columnFilters : [];
  if (serverFilters.length > 0) {
    columnFiltersServerHydratedFromStorage.value = true;
    return;
  }
  try {
    const raw = localStorage.getItem(`${FILTERS_STORAGE_PREFIX}${props.storageKey}`);
    const parsed = raw ? JSON.parse(raw) : null;
    const cleaned = sanitizeColumnFilters(Array.isArray(parsed) ? parsed : [], defs);
    if (cleaned.length) {
      emit('update:columnFilters', cleaned);
      const pageSize = Number(props.pagination?.pageSize) || 25;
      emit('update:pagination', { ...(props.pagination || {}), pageIndex: 0, pageSize });
    }
  } catch {
    /* ignore */
  }
  columnFiltersServerHydratedFromStorage.value = true;
}

watch(
  () => props.storageKey,
  () => {
    columnFiltersClientLoadedFromStorage.value = false;
    columnFiltersServerHydratedFromStorage.value = false;
    sortingClientLoadedFromStorage.value = false;
    sortingServerHydratedFromStorage.value = false;
    loadVisibilityFromStorage();
    loadColumnOrderFromStorage();
    syncSortingFromStorage();
    syncColumnFiltersFromStorage();
  },
  { immediate: true },
);

watch(
  columnDefs,
  () => {
    syncSortingFromStorage();
    syncColumnFiltersFromStorage();
    if (!props.enableColumnReorder) return;
    const leafIds = getColumnIdsFromDefs(columnDefs.value);
    if (!leafIds.length) return;

    if (columnOrder.value.length) {
      const merged = mergeColumnOrderWithDef(columnOrder.value, leafIds);
      if (JSON.stringify(merged) !== JSON.stringify(columnOrder.value)) {
        columnOrder.value = merged;
      }
      return;
    }

    loadColumnOrderFromStorage();
  },
  { deep: true, immediate: true },
);

watch(
  columnOrder,
  (v) => {
    if (!props.storageKey || !props.enableColumnReorder) return;
    try {
      if (!v.length) {
        localStorage.removeItem(`datatable-column-order:${props.storageKey}`);
      } else {
        localStorage.setItem(`datatable-column-order:${props.storageKey}`, JSON.stringify(v));
      }
    } catch {
      /* ignore quota */
    }
  },
  { deep: true },
);

watch(
  sorting,
  (v) => {
    if (props.serverSide || !props.storageKey || !props.sortable) return;
    persistSortingToStorage(v);
  },
  { deep: true },
);

/** Persist server sorting when parent updates (e.g. reset); no immediate — avoids clearing cache before hydrate. */
watch(
  () => props.sorting,
  (v) => {
    if (!props.serverSide || !props.storageKey || !props.sortable) return;
    persistSortingToStorage(Array.isArray(v) ? v : []);
  },
  { deep: true },
);

watch(
  columnVisibility,
  (v) => {
    if (!props.storageKey) return;
    try {
      localStorage.setItem(`datatable-columns:${props.storageKey}`, JSON.stringify(v));
    } catch {
      /* ignore quota */
    }
  },
  { deep: true },
);

/** Persist column filters (no immediate — avoids clearing cache before server hydrate reads it). */
watch(
  () => (props.serverSide ? props.columnFilters : clientColumnFilters.value),
  (v) => {
    if (!props.filterable || !props.storageKey) return;
    const arr = Array.isArray(v) ? v : [];
    try {
      if (!arr.length) {
        localStorage.removeItem(`${FILTERS_STORAGE_PREFIX}${props.storageKey}`);
      } else {
        localStorage.setItem(`${FILTERS_STORAGE_PREFIX}${props.storageKey}`, JSON.stringify(arr));
      }
    } catch {
      /* ignore quota */
    }
  },
  { deep: true },
);

function applyVisibilityUpdate(updater) {
  const next = typeof updater === 'function' ? updater(columnVisibility.value) : updater;
  columnVisibility.value = next ?? {};
}

function applyColumnOrderUpdate(updater) {
  const next = typeof updater === 'function' ? updater(columnOrder.value) : updater;
  columnOrder.value = Array.isArray(next) ? next : [];
}

function resolveTableUpdater(updater, prev) {
  return typeof updater === 'function' ? updater(prev) : updater;
}

function serverColumnFilters() {
  return Array.isArray(props.columnFilters) ? props.columnFilters : [];
}

const table = useVueTable({
  data: toRef(props, 'data'),
  get columns() {
    return columnDefs.value;
  },
  get manualPagination() {
    return props.serverSide;
  },
  get manualSorting() {
    return props.serverSide;
  },
  get manualFiltering() {
    return props.serverSide;
  },
  get pageCount() {
    return props.serverSide ? Math.max(Number(props.pageCount) || 0, 1) : undefined;
  },
  state: {
    get columnFilters() {
      return props.serverSide ? serverColumnFilters() : clientColumnFilters.value;
    },
    get columnVisibility() {
      return columnVisibility.value;
    },
    get sorting() {
      return props.serverSide
        ? (Array.isArray(props.sorting) ? props.sorting : [])
        : sorting.value;
    },
    get columnOrder() {
      return columnOrder.value;
    },
    get pagination() {
      if (!props.serverSide) {
        return { pageIndex: 0, pageSize: Math.max(props.data?.length ?? 0, 1) };
      }
      const p = props.pagination || {};
      return {
        pageIndex: Number(p.pageIndex) || 0,
        pageSize: Number(p.pageSize) || 25,
      };
    },
  },
  onColumnFiltersChange: (updater) => {
    const prev = props.serverSide ? serverColumnFilters() : clientColumnFilters.value;
    const next = resolveTableUpdater(updater, prev);
    if (props.serverSide) {
      emit('update:columnFilters', next);
      const pageSize = Number(props.pagination?.pageSize) || 25;
      if ((props.pagination?.pageIndex ?? 0) !== 0) {
        emit('update:pagination', { ...(props.pagination || {}), pageIndex: 0, pageSize });
      }
    } else {
      clientColumnFilters.value = next;
    }
  },
  onColumnVisibilityChange: applyVisibilityUpdate,
  onColumnOrderChange: applyColumnOrderUpdate,
  onSortingChange: (updater) => {
    const prev = props.serverSide
      ? (Array.isArray(props.sorting) ? props.sorting : [])
      : sorting.value;
    const next = resolveTableUpdater(updater, prev);
    if (props.serverSide) {
      emit('update:sorting', next);
      persistSortingToStorage(next);
      const pageSize = Number(props.pagination?.pageSize) || 25;
      if ((props.pagination?.pageIndex ?? 0) !== 0) {
        emit('update:pagination', { ...(props.pagination || {}), pageIndex: 0, pageSize });
      }
    } else {
      sorting.value = next;
    }
  },
  onPaginationChange: (updater) => {
    if (!props.serverSide) return;
    const prev = {
      pageIndex: Number(props.pagination?.pageIndex) || 0,
      pageSize: Number(props.pagination?.pageSize) || 25,
    };
    const next = resolveTableUpdater(updater, prev);
    emit('update:pagination', next);
  },
  getCoreRowModel: getCoreRowModel(),
  ...(props.sortable
    ? {
        defaultColumn: {
          enableSorting: false,
        },
        ...(!props.serverSide ? { getSortedRowModel: getSortedRowModel() } : {}),
      }
    : {}),
  ...(props.filterable && !props.serverSide ? { getFilteredRowModel: getFilteredRowModel() } : {}),
});

/* ——— Row selection & bulk actions (reusable) ——— */
const selectedRowIds = ref([]);
const pendingBulkAction = ref('');
const headerSelectAllRef = ref(null);

const bulkActionsNormalized = computed(() => {
  const raw = props.bulkActions;
  if (!Array.isArray(raw)) return [];
  return raw
    .filter((a) => a && a.value != null && a.label != null)
    .map((a) => ({ value: String(a.value), label: String(a.label) }));
});

const showBulkActionBar = computed(
  () =>
    props.rowSelectionEnabled &&
    bulkActionsNormalized.value.length > 0 &&
    selectedRowIds.value.length > 0,
);

function selectedIdSet() {
  return new Set(selectedRowIds.value.map(String));
}

function getRowRecordId(row) {
  const raw = row?.original?.[props.rowIdKey];
  return raw != null ? String(raw) : '';
}

function isRowSelectable(row) {
  if (typeof props.isRowSelectable === 'function') {
    try {
      return props.isRowSelectable(row) !== false;
    } catch {
      return true;
    }
  }
  return true;
}

function isRowSelected(row) {
  const id = getRowRecordId(row);
  return Boolean(id && selectedIdSet().has(id));
}

function toggleRowSelection(row, checked) {
  const id = getRowRecordId(row);
  if (!id || !isRowSelectable(row)) return;
  const s = selectedIdSet();
  if (checked) s.add(id);
  else s.delete(id);
  selectedRowIds.value = [...s];
}

function clearRowSelection() {
  selectedRowIds.value = [];
  pendingBulkAction.value = '';
}

function getSelectableRowsOnPage() {
  try {
    return table.getRowModel().rows.filter((r) => isRowSelectable(r));
  } catch {
    return [];
  }
}

const headerSelectState = computed(() => {
  const rows = getSelectableRowsOnPage();
  if (!rows.length) return { checked: false, indeterminate: false };
  let n = 0;
  for (const r of rows) {
    if (isRowSelected(r)) n++;
  }
  return {
    checked: n > 0 && n === rows.length,
    indeterminate: n > 0 && n < rows.length,
  };
});

const selectAllCheckboxTitle = computed(() =>
  headerSelectState.value.checked ? 'Deselect all rows on this page' : 'Select all rows on this page',
);

function onHeaderSelectAllChange(ev) {
  const checked = Boolean(ev?.target?.checked);
  const rows = getSelectableRowsOnPage();
  const s = selectedIdSet();
  if (checked) {
    rows.forEach((r) => {
      const id = getRowRecordId(r);
      if (id) s.add(id);
    });
  } else {
    rows.forEach((r) => {
      const id = getRowRecordId(r);
      if (id) s.delete(id);
    });
  }
  selectedRowIds.value = [...s];
}

function applyBulkAction() {
  const action = pendingBulkAction.value;
  if (!action || !selectedRowIds.value.length) return;
  emit('bulk-action', {
    action,
    ids: [...selectedRowIds.value.map(String)],
  });
}

watchEffect(() => {
  const el = headerSelectAllRef.value;
  if (el && el instanceof HTMLInputElement) {
    el.indeterminate = headerSelectState.value.indeterminate;
  }
});

watch(
  () => [
    props.rowSelectionEnabled,
    props.serverSide ? Number(props.pagination?.pageIndex) || 0 : 0,
    props.serverSide ? Number(props.pagination?.pageSize) || 0 : 0,
  ],
  () => {
    clearRowSelection();
  },
);

watch(
  () => props.data,
  (rows) => {
    if (!props.rowSelectionEnabled || !Array.isArray(rows)) return;
    const onPage = new Set();
    for (const r of rows) {
      const v = r?.[props.rowIdKey];
      if (v != null) onPage.add(String(v));
    }
    const filtered = selectedRowIds.value.filter((id) => onPage.has(String(id)));
    if (filtered.length !== selectedRowIds.value.length) {
      selectedRowIds.value = filtered;
    }
  },
  { deep: true },
);

watch(
  () => props.rowSelectionEnabled,
  (on) => {
    if (!on) clearRowSelection();
  },
);

defineExpose({
  clearRowSelection,
});

watch(
  columnDefs,
  (cols) => {
    table.setOptions((prev) => ({
      ...prev,
      columns: cols,
    }));
  },
  { deep: true },
);

const hasFilterableColumns = computed(() =>
  columnDefs.value.some((col) => col.enableColumnFilter === true),
);

const filterRowHeaders = computed(() => {
  const groups = table.getHeaderGroups();
  return (groups[0] && groups[0].headers) || [];
});

const pickerLeafColumns = computed(() => table.getAllLeafColumns());

const showPicker = computed(
  () =>
    props.columnPicker &&
    pickerLeafColumns.value.some((c) => c.columnDef.header != null && c.getCanHide()),
);

/** Show Reset when enabled and the grid supports sorting and/or column filters */
const showReset = computed(
  () => props.showResetButton && (props.sortable || props.filterable),
);

const showToolbar = computed(() => showPicker.value || showReset.value);

const rangeFrom = computed(() => {
  if (!props.serverSide || !props.total) return 0;
  return props.pagination.pageIndex * props.pagination.pageSize + 1;
});

const rangeTo = computed(() => {
  if (!props.serverSide || !props.total) return 0;
  return Math.min(
    (props.pagination.pageIndex + 1) * props.pagination.pageSize,
    props.total,
  );
});

const canPrevPage = computed(() => props.serverSide && props.pagination.pageIndex > 0);

const canNextPage = computed(() => {
  if (!props.serverSide) return false;
  const lastIdx = Math.max(Number(props.pageCount) || 0, 1) - 1;
  return props.pagination.pageIndex < lastIdx;
});

const pageDisplayIndex = computed(() =>
  props.serverSide ? props.pagination.pageIndex + 1 : 1,
);

const pageDisplayCount = computed(() => Math.max(Number(props.pageCount) || 0, 1));

function goFirstPage() {
  if (!props.serverSide) return;
  emit('update:pagination', { ...props.pagination, pageIndex: 0 });
}

function goPrevPage() {
  if (!canPrevPage.value) return;
  emit('update:pagination', {
    ...props.pagination,
    pageIndex: props.pagination.pageIndex - 1,
  });
}

function goNextPage() {
  if (!canNextPage.value) return;
  emit('update:pagination', {
    ...props.pagination,
    pageIndex: props.pagination.pageIndex + 1,
  });
}

function goLastPage() {
  if (!props.serverSide) return;
  const last = Math.max(Number(props.pageCount) || 0, 1) - 1;
  emit('update:pagination', { ...props.pagination, pageIndex: last });
}

function onPageSizeSelect(ev) {
  if (!props.serverSide) return;
  const pageSize = Number(ev.target.value);
  emit('update:pagination', { pageIndex: 0, pageSize });
}

const columnMenuOpen = ref(false);
const pickerRootRef = ref(null);
const rootRef = ref(null);
const stickyControlsRef = ref(null);
const tableRef = ref(null);
let stickyControlsResizeObserver = null;
let theadResizeObserver = null;

/**
 * Toolbar + top pagination height, and measured header stack above the filter row —
 * drives thead sticky `top` against document scroll (wrapper must not be overflow:auto).
 */
function syncStickyTableOffsets() {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const root = rootRef.value;
      const stack = stickyControlsRef.value;
      const table = tableRef.value;
      if (!root) {
        return;
      }
      const controlsH = stack ? Math.round(stack.offsetHeight) : 0;
      root.style.setProperty('--data-table-controls-offset', `${controlsH}px`);

      const thead = table?.querySelector('thead');
      if (thead) {
        const filterRow = thead.querySelector('tr.data-table-filter-row');
        let headerStackPx = 0;
        if (filterRow) {
          let el = thead.firstElementChild;
          while (el && el !== filterRow) {
            if (el.tagName === 'TR') {
              headerStackPx += Math.round(el.getBoundingClientRect().height);
            }
            el = el.nextElementSibling;
          }
        }
        if (headerStackPx > 0) {
          root.style.setProperty('--data-table-header-row-1-height', `${headerStackPx}px`);
        }
      }
    });
  });
}
const dragSourceColumnId = ref(null);
const dragOverColumnId = ref(null);

function isLeafHeader(header) {
  return (
    !header.isPlaceholder &&
    header.colSpan === 1 &&
    (!header.subHeaders || header.subHeaders.length === 0)
  );
}

function onColumnDragStart(event, columnId) {
  dragSourceColumnId.value = columnId;
  dragOverColumnId.value = null;
  event.dataTransfer?.setData('text/plain', String(columnId));
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
  }
}

function onColumnDragOver(targetColumnId) {
  const src = dragSourceColumnId.value;
  if (!src || src === targetColumnId) return;
  dragOverColumnId.value = targetColumnId;
}

function onColumnHeaderDragOver(event, header) {
  if (!props.enableColumnReorder || !isLeafHeader(header)) return;
  event.preventDefault();
  onColumnDragOver(header.column.id);
}

function onColumnDrop(targetColumnId) {
  const source = dragSourceColumnId.value;
  dragOverColumnId.value = null;
  dragSourceColumnId.value = null;
  if (source == null || targetColumnId == null) return;
  const strSource = String(source);
  const strTarget = String(targetColumnId);
  if (strSource === strTarget) return;

  const leafIds = table.getAllLeafColumns().map((c) => String(c.id));
  let order = columnOrder.value.length ? columnOrder.value.map(String) : [...leafIds];
  order = mergeColumnOrderWithDef(order, leafIds);

  const fromIdx = order.indexOf(strSource);
  const toIdx = order.indexOf(strTarget);
  if (fromIdx === -1 || toIdx === -1) return;
  const next = [...order];
  next.splice(fromIdx, 1);
  next.splice(toIdx, 0, strSource);
  columnOrder.value = next;
}

function onColumnHeaderDrop(event, header) {
  if (!props.enableColumnReorder || !isLeafHeader(header)) return;
  event.preventDefault();
  onColumnDrop(header.column.id);
}

function onColumnDragEnd() {
  dragOverColumnId.value = null;
  dragSourceColumnId.value = null;
}

function columnHeaderLabel(col) {
  const h = col.columnDef.header;
  if (typeof h === 'string') return h;
  return col.id || 'Column';
}

function headerLabelText(header) {
  const h = header.column.columnDef.header;
  if (typeof h === 'string') return h;
  return header.column.id || 'Column';
}

function resetColumnVisibility() {
  columnVisibility.value = {};
  columnOrder.value = [];
  if (props.storageKey) {
    try {
      localStorage.removeItem(`datatable-columns:${props.storageKey}`);
      localStorage.removeItem(`datatable-column-order:${props.storageKey}`);
    } catch {
      /* ignore */
    }
  }
}

function resetFiltersAndSort() {
  columnMenuOpen.value = false;
  if (props.serverSide) {
    emit('server-reset');
    return;
  }
  if (props.filterable) {
    clientColumnFilters.value = [];
  }
  if (props.sortable) {
    sorting.value = cloneSortingSpec(props.initialSorting);
  }
}

function onDocClick(e) {
  if (!columnMenuOpen.value) return;
  const root = pickerRootRef.value;
  if (root && !root.contains(e.target)) {
    columnMenuOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick);
  nextTick(() => {
    syncStickyTableOffsets();
    if (typeof ResizeObserver !== 'undefined' && stickyControlsRef.value) {
      stickyControlsResizeObserver = new ResizeObserver(() => syncStickyTableOffsets());
      stickyControlsResizeObserver.observe(stickyControlsRef.value);
    }
    const thead = tableRef.value?.querySelector('thead');
    if (typeof ResizeObserver !== 'undefined' && thead) {
      theadResizeObserver = new ResizeObserver(() => syncStickyTableOffsets());
      theadResizeObserver.observe(thead);
    }
  });
});
onUnmounted(() => {
  document.removeEventListener('click', onDocClick);
  stickyControlsResizeObserver?.disconnect();
  stickyControlsResizeObserver = null;
  theadResizeObserver?.disconnect();
  theadResizeObserver = null;
});

function activeColumnFilters() {
  return props.serverSide ? serverColumnFilters() : clientColumnFilters.value;
}

function getColumnFilterValue(columnId) {
  const entry = activeColumnFilters().find((f) => f.id === columnId);
  const v = entry?.value;
  return v !== undefined && v !== null ? String(v) : '';
}

function getFilterOptions(columnDef) {
  const opts = columnDef.filterOptions;
  return Array.isArray(opts) ? opts : [];
}

function setColumnFilter(columnId, value) {
  const prev = activeColumnFilters().filter((f) => f.id !== columnId);
  const next =
    String(value).trim() !== ''
      ? [...prev, { id: columnId, value: String(value).trim() }]
      : prev;
  if (props.serverSide) {
    emit('update:columnFilters', next);
    const pageSize = Number(props.pagination?.pageSize) || 25;
    if ((props.pagination?.pageIndex ?? 0) !== 0) {
      emit('update:pagination', { ...(props.pagination || {}), pageIndex: 0, pageSize });
    }
  } else {
    clientColumnFilters.value = next;
  }
}

/** Default formatting for date/datetime columns (columnDef.meta.type === 'date' | 'datetime') */
function formatCellValue(cell, value) {
  const type = cell.column.columnDef.meta && cell.column.columnDef.meta.type;
  if (type === 'date') return formatDateDisplay(value);
  if (type === 'datetime') return formatDateTimeDisplay(value);
  return value;
}

watch(
  [
    showToolbar,
    () => props.serverSide,
    () => props.loading,
    () => showBulkActionBar.value,
    () => selectedRowIds.value.length,
  ],
  () => nextTick(() => syncStickyTableOffsets()),
);
</script>

<style scoped>
.data-table-root {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.data-table-sticky-controls {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  position: sticky;
  top: var(--app-sticky-top, 0px);
  z-index: 30;
  width: 100%;
  box-sizing: border-box;
  background: var(--color-bg-elevated);
  padding-bottom: var(--space-2);
  margin-bottom: var(--space-1);
  border-bottom: 1px solid var(--color-border-light);
}

.data-table-sticky-controls .data-table-pagination--top {
  padding-bottom: 0;
  margin-bottom: 0;
  border-bottom: none;
}

.data-table-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.data-table-pagination--top {
  padding-bottom: var(--space-3);
  margin-bottom: var(--space-2);
  border-bottom: 1px solid var(--color-border-light);
}

.data-table-pagination-info {
  font-size: var(--text-sm);
}

.data-table-pagination-info .muted {
  color: var(--color-text-muted);
}

.data-table-loading-text {
  color: var(--color-text-muted);
}

.data-table-pagination-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
}

.data-table-page-size {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.data-table-page-size select {
  min-width: 4.5rem;
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-light);
  background: var(--color-bg-elevated);
  color: var(--color-text);
  font-size: var(--text-sm);
}

.data-table-page-label {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  padding: 0 var(--space-1);
  white-space: nowrap;
}

.data-table-toolbar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.column-picker {
  position: relative;
}

.column-picker-panel {
  position: absolute;
  right: 0;
  top: calc(100% + var(--space-1));
  z-index: 40;
  min-width: 12rem;
  max-height: min(70vh, 22rem);
  overflow-y: auto;
  padding: var(--space-2);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
}

.column-picker-header {
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted);
  padding: var(--space-2) var(--space-2) var(--space-1);
}

.column-picker-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.column-picker-item {
  margin: 0;
}

.column-picker-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-2);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text);
}

.column-picker-label:hover:not(.is-locked) {
  background: var(--color-surface-hover);
}

.column-picker-label.is-locked {
  cursor: default;
  opacity: 0.85;
}

.column-picker-label .checkbox {
  flex-shrink: 0;
}

.column-picker-footer {
  margin-top: var(--space-2);
  padding-top: var(--space-2);
  border-top: 1px solid var(--color-border-light);
}

.column-picker-reset {
  width: 100%;
  justify-content: center;
}

.data-table-th-inner {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-width: 0;
}

.data-table-th-main {
  min-width: 0;
  flex: 1;
}

.data-table-drag-handle {
  flex-shrink: 0;
  cursor: grab;
  color: var(--color-text-subtle);
  font-size: 0.7rem;
  line-height: 1;
  letter-spacing: -0.12em;
  padding: var(--space-1);
  margin: calc(var(--space-1) * -1);
  border-radius: var(--radius-sm);
  user-select: none;
}

.data-table-drag-handle:hover {
  color: var(--color-text-muted);
  background: var(--color-surface-hover);
}

.data-table-drag-handle:active {
  cursor: grabbing;
}

.data-table-th--drop-target {
  box-shadow: inset 0 -3px 0 0 var(--color-primary);
}

.data-table-bulk-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3);
  background: var(--color-primary-muted);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border-light);
}

.data-table-bulk-actions-count {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
}

.data-table-bulk-actions-field {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

.data-table-bulk-actions-field-label {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.data-table-bulk-actions-select {
  min-width: 11rem;
  padding: var(--space-2) var(--space-3);
  font-size: var(--text-sm);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-input-border);
  background: var(--color-input-bg);
  color: var(--color-text);
}

.data-table-th-select,
.data-table-td-select {
  width: 2.75rem;
  text-align: center;
  vertical-align: middle;
  box-sizing: border-box;
}
</style>
