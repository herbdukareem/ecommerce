import { defineStore } from 'pinia';
import axios from 'axios';

export const useOrdersStore = defineStore('orders', {
  state: () => ({
    orders: [],
    currentOrder: null,
    pagination: {},
    loading: false,
    filters: {
      status: null,
      payment_status: null,
      page: 1,
    },
  }),

  actions: {
    async fetchOrders() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/orders', {
          params: this.filters,
        });
        this.orders = data.data || [];
        this.pagination = data.meta || {};
      } catch (error) {
        console.error('Failed to fetch orders:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchOrder(id) {
      try {
        this.loading = true;
        const { data } = await axios.get(`/api/orders/${id}`);
        this.currentOrder = data;
        return { success: true, order: data };
      } catch (error) {
        console.error('Failed to fetch order:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async cancelOrder(id) {
      try {
        this.loading = true;
        const { data } = await axios.post(`/api/orders/${id}/cancel`);
        await this.fetchOrders(); // Refresh list
        return { success: true, message: data.message };
      } catch (error) {
        console.error('Failed to cancel order:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    setFilter(name, value) {
      this.filters[name] = value;
      this.filters.page = 1;
    },

    setPage(page) {
      this.filters.page = page;
    },

    clearFilters() {
      this.filters = {
        status: null,
        payment_status: null,
        page: 1,
      };
    },
  },
});

