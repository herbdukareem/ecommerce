import { defineStore } from 'pinia';
import axios from 'axios';

export const useCheckoutStore = defineStore('checkout', {
  state: () => ({
    quotes: [],
    gateways: [],
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
    async quoteShipping(payload = {}) {
      try {
        this.loading = true;
        const body = payload.address_id
          ? { address_id: payload.address_id }
          : { destination: payload.destination || {} };

        const { data } = await axios.post('/api/checkout/quote-shipping', body);
        this.quotes = data.quotes || [];
        return { success: true };
      } catch (error) {
        console.error('Failed to get shipping quotes:', error);
        const serverErrors = error.response?.data?.errors;
        const validationMessage = serverErrors
          ? Object.values(serverErrors).flat().join(' ')
          : null;
        return { success: false, error: validationMessage || error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async placeOrder(addressId, paymentMethod, shippingMethod) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/checkout/place-order', {
          address_id: addressId,
          payment_provider: paymentMethod,
          payment_method: 'card',
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

    async fetchPaymentGateways() {
      try {
        const { data } = await axios.get('/api/payments/gateways');
        this.gateways = data.gateways || [];
        return { success: true, gateways: this.gateways };
      } catch (error) {
        console.error('Failed to load payment gateways:', error);
        return { success: false, error: error.response?.data?.message };
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
      this.gateways = [];
      this.selectedShipping = null;
      this.currentStep = 1;
    },
  },
});