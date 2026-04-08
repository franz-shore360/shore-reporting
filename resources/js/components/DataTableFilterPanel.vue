<template>
  <div
    class="dt-filter-panel-root"
    :class="{ 'dt-filter-panel-root--separated': expanded || appliedFilters.length > 0 }"
  >
    <!-- Collapsible: form only. Close hides this block, not active filter chips. -->
    <section
      v-show="expanded"
      :id="panelBodyId"
      class="dt-filter-panel dt-filter-panel-search"
      :aria-labelledby="panelTitleId"
    >
      <h2 :id="panelTitleId" class="dt-filter-panel-title">Search Filters</h2>
      <div class="dt-filter-panel-body">
        <div class="dt-filter-panel-fields">
          <div v-for="f in fields" :key="f.id" class="dt-filter-panel-field">
            <label :for="inputId(f.id)">{{ f.label }}</label>
            <select
              v-if="f.select"
              :id="inputId(f.id)"
              v-model="draft[f.id]"
              :disabled="disabled"
            >
              <option value="">Any</option>
              <option v-for="opt in f.options" :key="String(opt.value)" :value="String(opt.value)">
                {{ opt.label }}
              </option>
            </select>
            <input
              v-else
              :id="inputId(f.id)"
              v-model="draft[f.id]"
              type="text"
              autocomplete="off"
              :disabled="disabled"
              :placeholder="'Filter by ' + f.label"
            />
          </div>
        </div>
        <div class="dt-filter-panel-actions">
          <button type="button" class="btn btn-sm btn-primary" :disabled="disabled" @click="applyFilters">
            Apply Filters
          </button>
          <button type="button" class="btn btn-sm btn-secondary" :disabled="disabled" @click="clearForm">
            Clear
          </button>
          <button
            type="button"
            class="btn btn-sm dt-filter-panel-close"
            aria-label="Close search filters"
            @click="emit('close')"
          >
            Close
          </button>
        </div>
      </div>
    </section>

    <section
      v-if="appliedFilters.length > 0"
      class="dt-filter-panel-active"
      :class="{ 'dt-filter-panel-active--below-search': expanded }"
      aria-label="Active filters"
    >
      <span class="dt-filter-panel-active-title">Active Filters</span>
      <ul class="dt-filter-panel-active-list" role="list">
        <li v-for="fl in appliedFilters" :key="fl.id" class="dt-filter-panel-active-item">
          <span class="dt-filter-panel-chip">
            <span class="dt-filter-panel-chip-text">{{ chipLabel(fl) }}</span>
            <button
              type="button"
              class="dt-filter-panel-chip-remove"
              :aria-label="'Remove ' + chipLabel(fl) + ' filter'"
              :disabled="disabled"
              @click="removeFilterChip(fl.id)"
            >
              ×
            </button>
          </span>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue';

const props = defineProps({
  /** Controlled by DataTable toolbar Filter button. */
  expanded: {
    type: Boolean,
    default: false,
  },
  columns: {
    type: Array,
    required: true,
  },
  appliedFilters: {
    type: Array,
    default: () => [],
  },
  /** Stable prefix for input ids (e.g. storageKey). */
  idPrefix: {
    type: String,
    default: 'datatable',
  },
  /** Disable fields (e.g. loading or export in progress). */
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['apply', 'remove-filter', 'close']);

const idSafe = computed(() => props.idPrefix.replace(/\s+/g, '-'));

const panelTitleId = computed(() => `dt-search-filters-title-${idSafe.value}`);

const panelBodyId = computed(() => `dt-filter-panel-body-${idSafe.value}`);

function columnIdFromDef(def) {
  if (def?.id != null) return String(def.id);
  if (def?.accessorKey != null) return String(def.accessorKey);
  return '';
}

const fields = computed(() => {
  const cols = Array.isArray(props.columns) ? props.columns : [];
  const out = [];
  for (const def of cols) {
    const id = columnIdFromDef(def);
    if (!id || def.enableColumnFilter !== true) continue;
    const label = typeof def.header === 'string' ? def.header : id;
    const raw = Array.isArray(def.filterOptions) ? def.filterOptions : [];
    const options = raw.filter((o) => o && String(o.value).trim() !== '');
    out.push({
      id,
      label,
      select: options.length > 0,
      options,
    });
  }
  return out;
});

const draft = reactive({});

/** Track prior applied ids so we clear draft when a filter is dropped without going through the chip. */
let prevAppliedFilterIds = new Set();

function appliedFiltersMap() {
  const raw = Array.isArray(props.appliedFilters) ? props.appliedFilters : [];
  return new Map(raw.map((f) => [String(f.id), String(f.value ?? '')]));
}

watch(
  fields,
  (list) => {
    const keep = new Set(list.map((x) => x.id));
    for (const key of Object.keys(draft)) {
      if (!keep.has(key)) {
        delete draft[key];
      }
    }
    const appliedById = appliedFiltersMap();
    for (const f of list) {
      if (draft[f.id] === undefined) {
        draft[f.id] = appliedById.has(f.id) ? appliedById.get(f.id) : '';
      }
    }
  },
  { immediate: true, deep: true },
);

watch(
  () => props.appliedFilters,
  () => {
    const appliedById = appliedFiltersMap();
    const nextIds = new Set(appliedById.keys());

    for (const f of fields.value) {
      if (appliedById.has(f.id)) {
        draft[f.id] = appliedById.get(f.id);
      }
    }
    for (const id of prevAppliedFilterIds) {
      if (!nextIds.has(id) && fields.value.some((x) => x.id === id)) {
        draft[id] = '';
      }
    }
    prevAppliedFilterIds = nextIds;
  },
  { deep: true, immediate: true },
);

function inputId(columnId) {
  return `dt-filter-panel-${idSafe.value}-${columnId}`.replace(/[^a-zA-Z0-9_-]/g, '-');
}

function chipLabel(f) {
  const meta = fields.value.find((x) => x.id === f.id);
  const title = meta?.label ?? f.id;
  return `${title}: ${f.value}`;
}

function removeFilterChip(columnId) {
  const id = String(columnId);
  draft[id] = '';
  emit('remove-filter', id);
}

function applyFilters() {
  const next = [];
  for (const f of fields.value) {
    const v = String(draft[f.id] ?? '').trim();
    if (v) next.push({ id: f.id, value: v });
  }
  emit('apply', next);
}

function clearForm() {
  for (const f of fields.value) {
    draft[f.id] = '';
  }
}

defineExpose({
  clearForm,
});
</script>

<style scoped>
.dt-filter-panel-root--separated {
  margin-bottom: var(--space-4);
  padding-bottom: var(--space-4);
  border-bottom: 1px solid var(--color-border-light);
}

.dt-filter-panel-search {
  padding-bottom: var(--space-3);
}

.dt-filter-panel-title {
  margin: 0 0 var(--space-3);
  font-size: var(--text-sm);
  font-weight: 400;
  line-height: inherit;
  color: var(--color-text);
}

.dt-filter-panel-body {
  padding-bottom: 0;
}

.dt-filter-panel-fields {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(12rem, 12rem));
  gap: var(--space-4);
  margin-bottom: var(--space-4);
}

.dt-filter-panel-field {
  min-width: 0;
}

.dt-filter-panel-field label {
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
  margin-bottom: var(--space-1);
}

.dt-filter-panel-field input,
.dt-filter-panel-field select {
  width: 100%;
  max-width: 100%;
  font-size: var(--text-sm);
}

.dt-filter-panel-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

/* Same metrics as .btn-sm + .btn-secondary; distinct neutral fill so Close ≠ Clear */
.dt-filter-panel-close {
  background: var(--color-surface-hover);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.dt-filter-panel-close:hover:not(:disabled) {
  background: var(--color-border-light);
  color: var(--color-text);
}

/* Divider only when Search Filters block is visible (expanded); hidden search = chips align under toolbar */
.dt-filter-panel-active--below-search {
  margin-top: var(--space-4);
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border-light);
}

.dt-filter-panel-active-title {
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
  margin-bottom: var(--space-2);
}

.dt-filter-panel-active-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.dt-filter-panel-active-item {
  margin: 0;
}

.dt-filter-panel-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-1) var(--space-2);
  background: var(--color-surface-hover, rgba(0, 0, 0, 0.06));
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-md, 6px);
  font-size: var(--text-sm);
  color: var(--color-text);
}

.dt-filter-panel-chip-text {
  max-width: 20rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dt-filter-panel-chip-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: none;
  border-radius: var(--radius-sm, 4px);
  background: transparent;
  color: var(--color-text-muted);
  font-size: 1.125rem;
  line-height: 1;
  cursor: pointer;
}

.dt-filter-panel-chip-remove:hover:not(:disabled) {
  background: var(--color-border-light);
  color: var(--color-text);
}

.dt-filter-panel-chip-remove:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
