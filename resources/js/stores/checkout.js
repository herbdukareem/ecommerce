import { defineStore } from 'pinia';
import axios from 'axios';

export const useCheckoutStore = defineStore('checkout', {
  state: () => ({
    quotes: [],
    selectedShipping: null,
  }),
  actions: {
    async quoteShipping(destination) {
      const { data } = await axios.post('/api/checkout/quote-shipping', { destination });
      this.quotes = data.quotes;
    },
    async placeOrder(addressId, paymentMethod) {
      const { data } = await axios.post('/api/checkout/place-order', {
        address_id: addressId,
        payment_method: paymentMethod,
      });
      return data;
    },
  },
});