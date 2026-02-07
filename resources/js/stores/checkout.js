import { defineStore } from 'pinia';
import axios from 'axios';

export const useCheckoutStore = defineStore('checkout', {
  state: () => ({
    quotes: [],
    selectedShipping: null,
    loading: false,
    currentStep: 1, // 1: Address, 2: Shipping, 3: Payment, 4: Review
  }),

  getters: {
    selectedQuote: (state) => {
      if (!state.selectedShipping) return null;
      return state.quotes.find(q => q.method === state.selectedShipping);
    },
  },

  actions: {
    async quoteShipping(destination) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/checkout/quote-shipping', { destination });
        this.quotes = data.quotes || [];
        return { success: true };
      } catch (error) {
        console.error('Failed to get shipping quotes:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async placeOrder(addressId, paymentMethod, shippingMethod) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/checkout/place-order', {
          address_id: addressId,
          payment_method: paymentMethod,
          shipping_method: shippingMethod,
        });
        return { success: true, order: data.order };
      } catch (error) {
        console.error('Failed to place order:', error);
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    selectShipping(method) {
      this.selectedShipping = method;
    },

    setStep(step) {
      this.currentStep = step;
    },

    nextStep() {
      if (this.currentStep < 4) {
        this.currentStep++;
      }
    },

    previousStep() {
      if (this.currentStep > 1) {
        this.currentStep--;
      }
    },

    reset() {
      this.quotes = [];
      this.selectedShipping = null;
      this.currentStep = 1;
    },
  },
});