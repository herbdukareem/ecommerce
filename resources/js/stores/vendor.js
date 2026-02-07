import { defineStore } from 'pinia';
import axios from 'axios';

export const useVendorStore = defineStore('vendor', {
  state: () => ({
    products: [],
    currentProduct: null,
    orders: [],
    currentOrder: null,
    pagination: {},
    loading: false,
    filters: {
      status: null,
      q: '',
      page: 1,
    },
  }),

  actions: {
    // Product Management
    async fetchProducts() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/vendor/products', {
          params: this.filters,
        });
        this.products = data.data || [];
        this.pagination = data.meta || {};
      } catch (error) {
        console.error('Failed to fetch products:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchProduct(id) {
      try {
        this.loading = true;
        const { data } = await axios.get(`/api/vendor/products/${id}`);
        this.currentProduct = data;
        return { success: true, product: data };
      } catch (error) {
        console.error('Failed to fetch product:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async createProduct(productData) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/vendor/products', productData);
        await this.fetchProducts(); // Refresh list
        return { success: true, product: data.product };
      } catch (error) {
        console.error('Failed to create product:', error);
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    async updateProduct(id, productData) {
      try {
        this.loading = true;
        const { data } = await axios.patch(`/api/vendor/products/${id}`, productData);
        await this.fetchProducts(); // Refresh list
        return { success: true, product: data.product };
      } catch (error) {
        console.error('Failed to update product:', error);
        return { success: false, error: error.response?.data };
      } finally {
        this.loading = false;
      }
    },

    async deleteProduct(id) {
      try {
        this.loading = true;
        await axios.delete(`/api/vendor/products/${id}`);
        await this.fetchProducts(); // Refresh list
        return { success: true };
      } catch (error) {
        console.error('Failed to delete product:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    // Order Management
    async fetchOrders() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/vendor/orders', {
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

    async updateOrderStatus(orderId, status) {
      try {
        this.loading = true;
        const { data } = await axios.patch(`/api/vendor/orders/${orderId}/status`, { status });
        await this.fetchOrders(); // Refresh list
        return { success: true, order: data.order };
      } catch (error) {
        console.error('Failed to update order status:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async fulfillOrder(orderId, fulfillmentData) {
      try {
        this.loading = true;
        const { data } = await axios.post(`/api/vendor/orders/${orderId}/fulfill`, fulfillmentData);
        await this.fetchOrders(); // Refresh list
        return { success: true, fulfillment: data.fulfillment };
      } catch (error) {
        console.error('Failed to fulfill order:', error);
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
  },
});

