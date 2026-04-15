import { createRouter, createWebHistory } from 'vue-router';
import { authState } from '../auth';
import LoginPage from '../pages/LoginPage.vue';
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue';
import ResetPasswordPage from '../pages/ResetPasswordPage.vue';
import HomePage from '../pages/HomePage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import UsersPage from '../pages/UsersPage.vue';
import DepartmentsPage from '../pages/DepartmentsPage.vue';
import RolesPage from '../pages/RolesPage.vue';
import EmailLogsPage from '../pages/EmailLogsPage.vue';
import GlAccountsPage from '../pages/GlAccountsPage.vue';

/** Browser tab title suffix — matches Laravel APP_NAME (see spa.blade.php meta application-name). */
function appDocumentTitle() {
    const el = document.querySelector('meta[name="application-name"]');
    return el?.getAttribute('content')?.trim() || 'Shore Reporting';
}

function applyDocumentTitle(route) {
    const suffix = appDocumentTitle();
    const page = typeof route.meta?.title === 'string' ? route.meta.title.trim() : '';
    document.title = page ? `${page} / ${suffix}` : suffix;
}

const routes = [
    {
        path: '/',
        redirect: () => (authState.isAuthenticated ? '/home' : '/login'),
    },
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { guest: true, title: 'Sign In' },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordPage,
        meta: { guest: true, title: 'Forgot Password' },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: ResetPasswordPage,
        meta: { guest: true, title: 'Reset Password' },
    },
    {
        path: '/home',
        name: 'home',
        component: HomePage,
        meta: { requiresAuth: true, title: 'Dashboard' },
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: { requiresAuth: true, title: 'Edit Profile' },
    },
    {
        path: '/users',
        name: 'user-manager',
        component: UsersPage,
        meta: { requiresAuth: true, title: 'Users' },
    },
    {
        path: '/departments',
        name: 'departments',
        component: DepartmentsPage,
        meta: { requiresAuth: true, title: 'Departments' },
    },
    {
        path: '/gl-accounts',
        name: 'gl-accounts',
        component: GlAccountsPage,
        meta: { requiresAuth: true, title: 'GL Accounts' },
    },
    {
        path: '/roles',
        name: 'roles',
        component: RolesPage,
        meta: { requiresAuth: true, title: 'Roles' },
    },
    {
        path: '/logs/email',
        name: 'email-logs',
        component: EmailLogsPage,
        meta: { requiresAuth: true, title: 'Email Logs' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    if (to.meta.requiresAuth && !authState.isAuthenticated) {
        return { name: 'login' };
    }
    if (to.meta.requiresAuth && authState.isAuthenticated && authState.user?.is_active === false) {
        authState.clearUser();
        return { name: 'login', query: { inactive: '1' } };
    }
    if (to.meta.guest && authState.isAuthenticated) {
        return { name: 'home' };
    }
    return true;
});

router.afterEach((to) => {
    applyDocumentTitle(to);
});

export default router;
