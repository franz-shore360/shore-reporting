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
      <div class="modal modal--wide email-log-detail-modal">
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
          <div v-if="detail.body" class="email-log-body-block">
            <h3 class="email-log-body-heading">Body</h3>
            <pre class="email-log-pre email-log-pre--body">{{ detail.body }}</pre>
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
import { authState } from '../auth';
import { formatDateTimeDisplay } from '../utils/date';
import EmailLogsTable from '../components/EmailLogsTable.vue';

const canView = computed(() => (authState.user?.permission_names ?? []).includes('email-log-list'));

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
.email-log-detail-modal .modal-header {
  margin-bottom: var(--space-3);
}
.email-log-detail-body {
  max-height: min(70vh, 40rem);
  overflow: auto;
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
.email-log-pre {
  margin: 0;
  padding: var(--space-3);
  background: var(--color-surface-hover);
  border-radius: var(--radius-md);
  font-size: var(--text-xs);
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 16rem;
  overflow: auto;
}
.email-log-pre--body {
  max-height: 24rem;
}
</style>
