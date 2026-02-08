import { defineStore } from 'pinia';
import axios from 'axios';

export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    dashboard: {
      total_revenue: 0,
      total_orders: 0,
      pending_orders: 0,
      total_customers: 0,
      total_products: 0,
      low_stock_products: 0,
      pending_reviews: 0,
      average_order_value: 0,
    },
    salesData: [],
    topProducts: [],
    recentOrders: [],
    loading: false,
    period: 30, // days
  }),

  getters: {
    formattedRevenue: (state) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(state.dashboard.total_revenue || 0);
    },
    
    formattedAverageOrderValue: (state) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(state.dashboard.average_order_value || 0);
    },

    revenueGrowth: (state) => {
      if (state.salesData.length < 2) return 0;
      const current = state.salesData[state.salesData.length - 1]?.revenue || 0;
      const previous = state.salesData[state.salesData.length - 2]?.revenue || 0;
      if (previous === 0) return 0;
      return ((current - previous) / previous * 100).toFixed(1);
    },
  },

  actions: {
    async loadDashboard(period = 30) {
      this.loading = true;
      this.period = period;
      
      try {
        const { data } = await axios.get('/api/admin/dashboard', {
          params: { period },
        });
        this.dashboard = data;
      } catch (error) {
        console.error('Failed to load dashboard:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async loadSalesData(period = 30, groupBy = 'day') {
      try {
        const { data } = await axios.get('/api/admin/sales-data', {
          params: { period, group_by: groupBy },
        });
        this.salesData = data;
      } catch (error) {
        console.error('Failed to load sales data:', error);
        throw error;
      }
    },

    async loadTopProducts(limit = 10, period = 30) {
      try {
        const { data } = await axios.get('/api/admin/top-products', {
          params: { limit, period },
        });
        this.topProducts = data;
      } catch (error) {
        console.error('Failed to load top products:', error);
        throw error;
      }
    },

    async loadRecentOrders(limit = 20) {
      try {
        const { data } = await axios.get('/api/admin/recent-orders', {
          params: { limit },
        });
        this.recentOrders = data;
      } catch (error) {
        console.error('Failed to load recent orders:', error);
        throw error;
      }
    },

    async loadAll(period = 30) {
      await Promise.all([
        this.loadDashboard(period),
        this.loadSalesData(period),
        this.loadTopProducts(10, period),
        this.loadRecentOrders(20),
      ]);
    },

    setPeriod(period) {
      this.period = period;
      this.loadAll(period);
    },
  },
});

