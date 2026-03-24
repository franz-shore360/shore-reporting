import { reactive } from 'vue';

/**
 * Simple reactive auth state shared across the app.
 * Initial user is injected from Laravel via window.Laravel.user
 */
export const authState = reactive({
    user: window.Laravel?.user ?? null,

    setUser(user) {
        this.user = user;
    },

    clearUser() {
        this.user = null;
    },

    get isAuthenticated() {
        return this.user !== null;
    },
});
