import { defineStore } from 'pinia';
import axios from 'axios';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
  }),
  actions: {
    async loadCart() {
      const { data } = await axios.get('/api/cart');
      this.items = data.items || [];
    },
    async addItem(skuId, quantity = 1) {
      await axios.post('/api/cart/items', { sku_id: skuId, quantity });
      await this.loadCart();
    },
    async updateItem(itemId, quantity) {
      await axios.patch(`/api/cart/items/${itemId}`, { quantity });
      await this.loadCart();
    },
    async removeItem(itemId) {
      await axios.delete(`/api/cart/items/${itemId}`);
      await this.loadCart();
    },
  },
});