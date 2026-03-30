import { ref } from 'vue';
import axios from 'axios';

const usersCount = ref(null);
const departmentsCount = ref(null);
const loading = ref(true);
let loadPromise = null;

/** Call on logout so the next session refetches counts. */
export function resetDashboardStats() {
  loadPromise = null;
  usersCount.value = null;
  departmentsCount.value = null;
  loading.value = true;
}

/**
 * Shared dashboard stats (single request for /api/dashboard/stats).
 * Safe to call ensureLoaded() from multiple widgets — only one request runs.
 */
export function useDashboardStats() {
  function ensureLoaded() {
    if (loadPromise) {
      return loadPromise;
    }
    loadPromise = (async () => {
      loading.value = true;
      try {
        const { data } = await axios.get('/api/dashboard/stats');
        usersCount.value =
          typeof data.users_count === 'number' ? data.users_count : null;
        departmentsCount.value =
          typeof data.departments_count === 'number' ? data.departments_count : null;
      } catch {
        usersCount.value = null;
        departmentsCount.value = null;
      } finally {
        loading.value = false;
        // Allow a new fetch when the user returns to the dashboard (widgets remount).
        // Concurrent calls on the same visit still share this promise until it settles.
        loadPromise = null;
      }
    })();
    return loadPromise;
  }

  return {
    usersCount,
    departmentsCount,
    loading,
    ensureLoaded,
  };
}

export function formatStatCount(value) {
  if (value === null || value === undefined) return '—';
  return new Intl.NumberFormat('en-AU').format(value);
}
