import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import axios from 'axios';
import './index.css';
import '@mdi/font/css/materialdesignicons.css';
import './styles/animations.css';

// Configure axios defaults
axios.defaults.baseURL = import.meta.env.VITE_API_URL || '';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Add response interceptor for error handling
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Unauthorized - clear auth. Only redirect when user is in admin area.
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
      const currentPath = router.currentRoute.value.path || '';
      if (currentPath.startsWith('/admin') && currentPath !== '/admin/login') {
        router.push('/admin/login');
      }
    }
    return Promise.reject(error);
  }
);

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);

// Initialize auth from localStorage
import { useAuthStore } from './stores/auth';
const authStore = useAuthStore();
authStore.initializeAuth();

// Initialize theme
import { useThemeStore } from './stores/theme';
const themeStore = useThemeStore();
themeStore.initTheme();

// Initialize settings (currency, etc.)
import { useSettingsStore } from './stores/settings';
const settingsStore = useSettingsStore();
settingsStore.initSettings();

app.mount('#app');