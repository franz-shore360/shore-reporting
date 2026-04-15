<template>
  <div class="app-layout" :class="{ 'app-layout--guest': !authState.isAuthenticated }">
    <header v-if="authState.isAuthenticated" class="app-nav" :class="{ 'app-nav--open': mobileMenuOpen }" ref="navRef">
      <router-link :to="{ name: 'home' }" class="app-nav-brand" @click="mobileMenuOpen = false">
        <img src="/images/shore-reporting-logo.svg" alt="" class="app-nav-brand-logo" width="32" height="32" />
        <span class="app-nav-brand-text">Shore Reporting</span>
      </router-link>

      <button
        type="button"
        class="app-nav-toggle"
        aria-label="Toggle Menu"
        :aria-expanded="mobileMenuOpen"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        <span class="app-nav-toggle-bar"></span>
        <span class="app-nav-toggle-bar"></span>
        <span class="app-nav-toggle-bar"></span>
      </button>

      <nav class="app-nav-links">
        <router-link :to="{ name: 'home' }" @click="mobileMenuOpen = false">Dashboard</router-link>
        <router-link :to="{ name: 'user-manager' }" @click="mobileMenuOpen = false">Users</router-link>
        <router-link v-if="hasAdminRole" :to="{ name: 'roles' }" @click="mobileMenuOpen = false">Roles</router-link>
        <router-link v-if="hasDepartmentList" :to="{ name: 'departments' }" @click="mobileMenuOpen = false">Departments</router-link>
        <router-link v-if="hasGlAccountList" :to="{ name: 'gl-accounts' }" @click="mobileMenuOpen = false">GL Accounts</router-link>
        <router-link v-if="hasImportSection" :to="{ name: 'imports' }" @click="mobileMenuOpen = false">Imports</router-link>
        <div v-if="hasEmailLogList" class="account-dropdown" ref="logsDropdownRef">
          <button
            type="button"
            class="account-dropdown-trigger btn btn-ghost"
            :class="{ 'account-dropdown-trigger--active': logsOpen || isLogsSectionActive }"
            aria-haspopup="true"
            :aria-expanded="logsOpen"
            aria-label="Logs menu"
            @click="logsOpen = !logsOpen"
          >
            Logs
            <span class="account-dropdown-chevron">▼</span>
          </button>
          <div v-show="logsOpen" class="account-dropdown-menu" role="menu">
            <router-link
              :to="{ name: 'email-logs' }"
              class="account-dropdown-item"
              role="menuitem"
              @click="logsOpen = false; mobileMenuOpen = false"
            >
              Email Logs
            </router-link>
          </div>
        </div>
        <div class="account-dropdown" ref="accountDropdownRef">
          <button
            type="button"
            class="account-dropdown-trigger btn btn-ghost"
            :class="{ 'account-dropdown-trigger--active': accountOpen || isAccountSectionActive }"
            aria-haspopup="true"
            :aria-expanded="accountOpen"
            :aria-label="`Account menu, ${userDisplayName}`"
            @click="accountOpen = !accountOpen"
          >
            <span class="nav-user-avatar" aria-hidden="true">
              <img
                v-if="userPhotoUrl && !navAvatarFailed"
                :src="userPhotoUrl"
                :alt="''"
                class="nav-user-avatar-img"
                @error="navAvatarFailed = true"
              />
              <span v-else class="nav-user-avatar-placeholder">{{ userInitial }}</span>
            </span>
            <span class="nav-user-name">{{ userDisplayName }}</span>
            <span class="account-dropdown-chevron">▼</span>
          </button>
          <div v-show="accountOpen" class="account-dropdown-menu" role="menu">
            <router-link :to="{ name: 'profile' }" class="account-dropdown-item" role="menuitem" @click="accountOpen = false; mobileMenuOpen = false">
              Edit Profile
            </router-link>
            <button type="button" class="account-dropdown-item account-dropdown-item--logout" role="menuitem" :disabled="logoutLoading" @click="handleLogout">
              {{ logoutLoading ? 'Logging Out…' : 'Log Out' }}
            </button>
          </div>
        </div>
        <button
          type="button"
          class="theme-toggle btn btn-ghost btn-sm"
          :aria-label="theme === 'dark' ? 'Switch To Light Mode' : 'Switch To Dark Mode'"
          @click="toggleTheme"
        >
          <span v-if="theme === 'dark'" class="theme-toggle-icon" aria-hidden="true">☀️</span>
          <span v-else class="theme-toggle-icon" aria-hidden="true">🌙</span>
          <span class="theme-toggle-label">{{ theme === 'dark' ? 'Light Mode' : 'Dark Mode' }}</span>
        </button>
      </nav>

      <div class="app-nav-mobile" :aria-hidden="!mobileMenuOpen">
        <div class="app-nav-mobile-user">
          <span class="nav-user-avatar nav-user-avatar--mobile" aria-hidden="true">
            <img
              v-if="userPhotoUrl && !navAvatarFailed"
              :src="userPhotoUrl"
              :alt="''"
              class="nav-user-avatar-img"
              @error="navAvatarFailed = true"
            />
            <span v-else class="nav-user-avatar-placeholder">{{ userInitial }}</span>
          </span>
          <div class="app-nav-mobile-user-text">
            <span class="app-nav-mobile-user-name">{{ userDisplayName }}</span>
          </div>
        </div>
        <router-link :to="{ name: 'home' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Dashboard</router-link>
        <router-link :to="{ name: 'user-manager' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Users</router-link>
        <router-link v-if="hasAdminRole" :to="{ name: 'roles' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Roles</router-link>
        <router-link v-if="hasDepartmentList" :to="{ name: 'departments' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Departments</router-link>
        <router-link v-if="hasGlAccountList" :to="{ name: 'gl-accounts' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">GL Accounts</router-link>
        <router-link v-if="hasImportSection" :to="{ name: 'imports' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Imports</router-link>
        <router-link v-if="hasEmailLogList" :to="{ name: 'email-logs' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Email Logs</router-link>
        <router-link :to="{ name: 'profile' }" class="app-nav-mobile-link" @click="mobileMenuOpen = false">Edit Profile</router-link>
        <button type="button" class="app-nav-mobile-link app-nav-mobile-link--logout" :disabled="logoutLoading" @click="handleLogout">
          {{ logoutLoading ? 'Logging Out…' : 'Log Out' }}
        </button>
        <button
          type="button"
          class="app-nav-mobile-link"
          @click="toggleTheme(); mobileMenuOpen = false"
        >
          {{ theme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode' }}
        </button>
      </div>
    </header>
    <main class="app-main" :class="{ 'app-main--full': !authState.isAuthenticated }">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { authState } from './auth';
import { useTheme } from './composables/useTheme';
import { resetDashboardStats } from './composables/useDashboardStats';

const router = useRouter();
const route = useRoute();
const { theme, toggleTheme } = useTheme();

/** Child routes that keep the Logs parent highlighted (add names when new log pages ship). */
const LOGS_SECTION_ROUTE_NAMES = ['email-logs'];

const isLogsSectionActive = computed(() => LOGS_SECTION_ROUTE_NAMES.includes(route.name));
const isAccountSectionActive = computed(() => route.name === 'profile');

const accountOpen = ref(false);
const accountDropdownRef = ref(null);
const logsOpen = ref(false);
const logsDropdownRef = ref(null);
const navRef = ref(null);
const mobileMenuOpen = ref(false);
const logoutLoading = ref(false);

const hasAdminRole = computed(() => authState.user?.role_names?.includes('Admin') ?? false);
const hasDepartmentList = computed(() =>
  (authState.user?.permission_names ?? []).includes('department-list'),
);
const hasGlAccountList = computed(() =>
  (authState.user?.permission_names ?? []).includes('gl-account-list'),
);
const hasImportSection = computed(() => {
  const names = authState.user?.permission_names ?? [];
  return names.includes('import-list') || names.includes('import-create');
});
const hasEmailLogList = computed(() =>
  (authState.user?.permission_names ?? []).includes('email-log-list'),
);

const userDisplayName = computed(() => authState.user?.full_name?.trim() || 'User');
const userPhotoUrl = computed(() => authState.user?.profile_image_url || '');
const userInitial = computed(() => userDisplayName.value.charAt(0).toUpperCase() || '?');

const navAvatarFailed = ref(false);

watch(
  () => [authState.user?.id, authState.user?.profile_image_url],
  () => {
    navAvatarFailed.value = false;
  },
);

watch(
  () => authState.isAuthenticated,
  (authenticated) => {
    if (!authenticated) {
      accountOpen.value = false;
      logsOpen.value = false;
      mobileMenuOpen.value = false;
    }
  },
);

function handleClickOutside(event) {
  if (accountDropdownRef.value && !accountDropdownRef.value.contains(event.target)) {
    accountOpen.value = false;
  }
  if (logsDropdownRef.value && !logsDropdownRef.value.contains(event.target)) {
    logsOpen.value = false;
  }
  if (navRef.value && !navRef.value.contains(event.target)) {
    mobileMenuOpen.value = false;
  }
}

async function handleLogout() {
  logoutLoading.value = true;
  try {
    await axios.post('/logout');
  } finally {
    logoutLoading.value = false;
  }
  authState.clearUser();
  resetDashboardStats();
  router.push({ name: 'login' });
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.app-layout--guest .app-main--full {
  max-width: none;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}
.account-dropdown {
  position: relative;
}
.account-dropdown-trigger {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  max-width: 14rem;
  font-weight: var(--font-medium);
}
.nav-user-avatar {
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  overflow: hidden;
  background: var(--color-primary-muted);
  display: flex;
  align-items: center;
  justify-content: center;
}
.nav-user-avatar--mobile {
  width: 2.5rem;
  height: 2.5rem;
}
.nav-user-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.nav-user-avatar-placeholder {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-primary);
  line-height: 1;
}
.nav-user-name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}
.app-nav-mobile-user {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-4) var(--space-4) var(--space-3);
  margin-bottom: var(--space-1);
  border-bottom: 1px solid var(--color-border-light);
}
.app-nav-mobile-user-name {
  font-size: var(--text-base);
  font-weight: var(--font-semibold);
  color: var(--color-text);
  line-height: 1.3;
}
.app-nav-mobile-user-text {
  min-width: 0;
  flex: 1;
}
.account-dropdown-trigger--active {
  background: var(--color-primary-muted);
  color: var(--color-primary);
}
.account-dropdown-chevron {
  font-size: 0.625rem;
  opacity: 0.8;
}
.account-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: var(--space-1);
  min-width: 10rem;
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  padding: var(--space-1);
  z-index: 50;
}
.account-dropdown-item {
  display: block;
  width: 100%;
  padding: var(--space-2) var(--space-3);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text);
  text-align: left;
  text-decoration: none;
  border: none;
  background: none;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: background 0.15s;
}
.account-dropdown-item:hover {
  background: var(--color-surface-hover);
}
.account-dropdown-item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.account-dropdown-item--logout {
  color: var(--color-danger);
}
.theme-toggle {
  margin-left: var(--space-2);
}
.theme-toggle-icon {
  font-size: 1.125rem;
}
.theme-toggle-label {
  margin-left: var(--space-1);
}
</style>
