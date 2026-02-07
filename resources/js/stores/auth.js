import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),
  actions: {
    async login(credentials) {
      // Fetch CSRF cookie for Sanctum SPA login
      await axios.get('/sanctum/csrf-cookie');
      const { data } = await axios.post('/api/auth/login', credentials);
      this.user = data.user;
      this.token = data.token;
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
    },
    async logout() {
      await axios.post('/api/auth/logout');
      this.user = null;
      this.token = null;
      delete axios.defaults.headers.common['Authorization'];
    },
    async fetchUser() {
      const { data } = await axios.get('/api/me');
      this.user = data;
    },
  },
});