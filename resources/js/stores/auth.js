import { defineStore } from 'pinia';
import axios from 'axios';
import { useCartStore } from './cart';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isVendor: (state) => state.user?.roles?.some(role => role.name === 'Vendor'),
    isAdmin: (state) => state.user?.roles?.some(role => role.name === 'Admin'),
  },

  actions: {
    async register(userData) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/auth/register', userData);
        this.user = data.user;
        this.token = data.token;
        localStorage.setItem('token', data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    async login(credentials) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/auth/login', credentials);
        this.user = data.user;
        this.token = data.token;
        localStorage.setItem('token', data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;

        // Merge guest cart with user cart
        const cartStore = useCartStore();
        await cartStore.mergeCart();

        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await axios.post('/api/auth/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.user = null;
        this.token = null;
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
      }
    },

    async fetchUser() {
      if (!this.token) return;

      try {
        const { data } = await axios.get('/api/me');
        this.user = data.user;
      } catch (error) {
        console.error('Failed to fetch user:', error);
        // Token might be invalid, clear auth state
        this.user = null;
        this.token = null;
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
      }
    },

    async updateProfile(profileData) {
      try {
        this.loading = true;
        const { data } = await axios.patch('/api/profile', profileData);
        this.user = data.user;
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    async changePassword(passwordData) {
      try {
        this.loading = true;
        await axios.post('/api/profile/change-password', passwordData);
        // Password changed, need to re-login
        await this.logout();
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    initializeAuth() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        this.fetchUser();
      }
    },
  },
});