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

    async addItem(skuId, quantity = 1) {
      try {
        this.loading = true;
        await axios.post('/api/cart/items', { sku_id: skuId, quantity });
        await this.loadCart();
        return { success: true };
      } catch (error) {
        console.error('Failed to add item:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async updateItem(itemId, quantity) {
      try {
        this.loading = true;
        await axios.patch(`/api/cart/items/${itemId}`, { quantity });
        await this.loadCart();
        return { success: true };
      } catch (error) {
        console.error('Failed to update item:', error);
        return { success: false, error: error.response?.data?.message };
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
        console.error('Failed to remove item:', error);
        return { success: false, error: error.response?.data?.message };
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