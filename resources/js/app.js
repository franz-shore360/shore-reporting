import './bootstrap';
import axios from 'axios';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { authState } from './auth';
import { formatDate } from './utils/date';

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 403 && error.response?.data?.code === 'ACCOUNT_INACTIVE') {
            authState.clearUser();
            if (router.currentRoute.value.name !== 'login') {
                router.push({ name: 'login', query: { inactive: '1' } }).catch(() => {});
            }
        }
        return Promise.reject(error);
    },
);

const app = createApp(App);
app.config.globalProperties.formatDate = formatDate;
app.use(router);
app.mount('#app');
