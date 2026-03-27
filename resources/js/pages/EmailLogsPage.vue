<template>
  <div class="email-logs-page">
    <div class="card">
      <div class="page-header">
        <h1 class="page-title">Email Logs</h1>
        <div class="header-actions">
          <router-link :to="{ name: 'home' }" class="btn btn-secondary">Back To Home</router-link>
        </div>
      </div>
    </div>

    <div v-if="!canView" class="card">
      <p class="permission-message">You do not have permission to view email logs.</p>
    </div>

    <div v-else class="card table-card">
      <EmailLogsTable :can-fetch="canView" @view="openDetail" />
    </div>

    <div v-if="detailOpen" class="modal-backdrop" @click.self="closeDetail">
      <div class="modal email-log-detail-modal">
        <div class="modal-header">
          <h2 class="modal-title">Email Details</h2>
          <button type="button" class="modal-close" aria-label="Close" @click="closeDetail">&times;</button>
        </div>
        <div v-if="detailLoading" class="email-logs-loading muted">Loading…</div>
        <div v-else-if="detailError" class="form-error">{{ detailError }}</div>
        <div v-else-if="detail" class="email-log-detail-body">
          <dl class="email-log-meta">
            <div><dt>Sent</dt><dd>{{ formatDateTimeDisplay(detail.sent_at) }}</dd></div>
            <div><dt>To</dt><dd class="email-logs-cell-wrap">{{ detail.to_addresses || '—' }}</dd></div>
            <div><dt>From</dt><dd class="email-logs-cell-wrap">{{ detail.from_address || '—' }}</dd></div>
            <div v-if="detail.cc_addresses"><dt>Cc</dt><dd class="email-logs-cell-wrap">{{ detail.cc_addresses }}</dd></div>
            <div v-if="detail.bcc_addresses"><dt>Bcc</dt><dd class="email-logs-cell-wrap">{{ detail.bcc_addresses }}</dd></div>
            <div><dt>Subject</dt><dd class="email-logs-cell-wrap">{{ detail.subject || '—' }}</dd></div>
          </dl>
          <div v-if="detailBodyHtml" class="email-log-body-block">
            <h3 class="email-log-body-heading">Body</h3>
            <div class="email-log-html-body" v-html="detailBodyHtml" />
          </div>
          <p v-else class="muted">No body stored for this message.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { authState } from '../auth';
import { formatDateTimeDisplay } from '../utils/date';
import EmailLogsTable from '../components/EmailLogsTable.vue';

const canView = computed(() => (authState.user?.permission_names ?? []).includes('email-log-list'));

const detailBodyHtml = computed(() => {
  const raw = detail.value?.body;
  if (raw == null || typeof raw !== 'string' || raw.trim() === '') {
    return '';
  }
  return DOMPurify.sanitize(raw, { USE_PROFILES: { html: true } });
});

const detailOpen = ref(false);
const detailId = ref(null);
const detail = ref(null);
const detailLoading = ref(false);
const detailError = ref('');

async function openDetail(id) {
  detailId.value = id;
  detail.value = null;
  detailError.value = '';
  detailOpen.value = true;
  detailLoading.value = true;
  try {
    const { data } = await axios.get(`/api/email-logs/${id}`);
    detail.value = data;
  } catch (e) {
    detailError.value = e.response?.data?.message || 'Could not load this email.';
  } finally {
    detailLoading.value = false;
  }
}

function closeDetail() {
  detailOpen.value = false;
  detailId.value = null;
  detail.value = null;
  detailError.value = '';
}
</script>

<style scoped>
.email-logs-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.email-logs-page .card:first-child {
  padding: var(--space-6);
}
.permission-message {
  padding: var(--space-6);
  margin: 0;
  color: var(--color-text-muted);
}
.email-logs-loading {
  padding: var(--space-4);
}
.email-logs-cell-wrap {
  max-width: 16rem;
  white-space: normal;
  word-break: break-word;
}
.email-log-detail-modal {
  max-width: min(56rem, 100%);
}
.email-log-detail-modal .modal-header {
  margin-bottom: var(--space-3);
}
.email-log-detail-modal .email-logs-cell-wrap {
  max-width: none;
}
.email-log-detail-body {
  max-height: min(78vh, 48rem);
  overflow: auto;
  padding: 0 var(--space-6) var(--space-6);
}
.email-log-detail-modal > .email-logs-loading {
  padding: var(--space-4) var(--space-6) var(--space-6);
}
.email-log-detail-modal > .form-error {
  margin: 0 var(--space-6) var(--space-6);
}
.email-log-meta {
  margin: 0 0 var(--space-4);
}
.email-log-meta > div {
  display: grid;
  grid-template-columns: 6rem 1fr;
  gap: var(--space-2);
  margin-bottom: var(--space-2);
  font-size: var(--text-sm);
}
.email-log-meta dt {
  margin: 0;
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
}
.email-log-meta dd {
  margin: 0;
}
.email-log-body-heading {
  margin: 0 0 var(--space-2);
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
}
.email-log-html-body {
  margin: 0;
  padding: var(--space-3);
  background: var(--color-surface-hover);
  color: var(--color-text);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.5;
  max-height: min(50vh, 32rem);
  overflow: auto;
  word-break: break-word;
}
/* HTML email almost always targets a light page; dark app theme would hide that contrast */
[data-theme='dark'] .email-log-html-body {
  background: #f1f5f9;
  color: #0f172a;
  border: 1px solid var(--color-border);
}
[data-theme='dark'] .email-log-html-body :deep(a) {
  color: #0369a1;
}
[data-theme='dark'] .email-log-html-body :deep(a:visited) {
  color: #6d28d9;
}
.email-log-html-body :deep(img) {
  max-width: 100%;
  height: auto;
}
.email-log-html-body :deep(table) {
  max-width: 100%;
}
</style>
