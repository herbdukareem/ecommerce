import { defineStore } from 'pinia';
import axios from 'axios';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    total: 0,
    itemCount: 0,
    loading: false,
  }),

  getters: {
    cartTotal: (state) => state.total,
    cartItemCount: (state) => state.itemCount,
    isEmpty: (state) => state.items.length === 0,
  },

  actions: {
    getErrorMessage(error, fallbackMessage) {
      const status = error?.response?.status;
      const data = error?.response?.data;

      if (status === 401) {
        return 'Please login to manage your cart.';
      }

      if (status === 419) {
        return 'Your session expired. Refresh and try again.';
      }

      if (status === 422) {
        return data?.message || fallbackMessage;
      }

      return data?.message || fallbackMessage;
    },

    async loadCart() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/cart');
        this.items = data.items || [];
        this.total = data.total || 0;
        this.itemCount = data.item_count || 0;
      } catch (error) {
        console.error('Failed to load cart:', error);
      } finally {
        this.loading = false;
      }
    },

    async addItem(itemOrSkuId, quantity = 1) {
      try {
        this.loading = true;
        const payload = typeof itemOrSkuId === 'object' && itemOrSkuId !== null
          ? { ...itemOrSkuId, quantity }
          : { sku_id: itemOrSkuId, quantity };
        await axios.post('/api/cart/items', payload);
        await this.loadCart();
        return { success: true };
      } catch (error) {
        return { success: false, error: this.getErrorMessage(error, 'Unable to add item to cart.') };
      } finally {
        this.loading = false;
      }
    },

    async updateItem(itemId, quantity) {
      try {
        this.loading = true;
        await axios.put(`/api/cart/items/${itemId}`, { quantity });
        await this.loadCart();
        return { success: true };
      } catch (error) {
        return { success: false, error: this.getErrorMessage(error, 'Unable to update cart item.') };
      } finally {
        this.loading = false;
      }
    },

    async removeItem(itemId) {
      try {
        this.loading = true;
        await axios.delete(`/api/cart/items/${itemId}`);
        await this.loadCart();
        return { success: true };
      } catch (error) {
        return { success: false, error: this.getErrorMessage(error, 'Unable to remove item from cart.') };
      } finally {
        this.loading = false;
      }
    },

    async clearCart() {
      try {
        this.loading = true;
        await axios.delete('/api/cart');
        this.items = [];
        this.total = 0;
        this.itemCount = 0;
        return { success: true };
      } catch (error) {
        console.error('Failed to clear cart:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async mergeCart() {
      try {
        await axios.post('/api/cart/merge');
        await this.loadCart();
      } catch (error) {
        console.error('Failed to merge cart:', error);
      }
    },
  },
});