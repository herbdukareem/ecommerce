import { defineStore } from 'pinia';
import axios from 'axios';

export const useCheckoutStore = defineStore('checkout', {
  state: () => ({
    cities: [],
    areas: [],
    dispatchSlots: [],
    gateways: [],
    paymentOptions: [],
    loading: false,
  }),

  actions: {
    async fetchCities() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/checkout/cities');
        this.cities = data.cities || [];
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data?.message || 'Failed to load cities.' };
      } finally {
        this.loading = false;
      }
    },

    async fetchAreas(cityId) {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/checkout/areas', { params: { city_id: cityId } });
        this.areas = data.areas || [];
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data?.message || 'Failed to load areas.' };
      } finally {
        this.loading = false;
      }
    },

    async fetchDispatchTimeSlots() {
      try {
        this.loading = true;
        const { data } = await axios.get('/api/checkout/dispatch-time-slots');
        this.dispatchSlots = data.dispatch_time_slots || [];
        return { success: true };
      } catch (error) {
        return { success: false, error: error.response?.data?.message || 'Failed to load dispatch slots.' };
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
        return { success: false, error: error.response?.data?.message || 'Failed to load payment gateways.' };
      }
    },

    async fetchPaymentOptions(params = {}) {
      try {
        const { data } = await axios.get('/api/checkout/payment-options', { params });
        this.paymentOptions = data.payment_options || [];
        this.gateways = this.paymentOptions.filter((option) => option.provider !== 'pay_on_delivery');
        return { success: true, paymentOptions: this.paymentOptions };
      } catch (error) {
        return { success: false, error: error.response?.data?.message || 'Failed to load payment options.' };
      }
    },

    async placeOrder(payload) {
      try {
        this.loading = true;
        const { data } = await axios.post('/api/checkout/place-order', payload);
        return { success: true, order: data.order };
      } catch (error) {
        const errors = error.response?.data?.errors;
        const validationMessage = errors ? Object.values(errors).flat().join(' ') : null;
        return { success: false, error: validationMessage || error.response?.data?.message || 'Unable to place order.' };
      } finally {
        this.loading = false;
      }
    },

    async initializeOrderPayment(orderId) {
      try {
        this.loading = true;
        const { data } = await axios.post(`/api/payments/orders/${orderId}/initialize`);
        return { success: true, data };
      } catch (error) {
        return { success: false, error: error.response?.data?.message || 'Unable to initialize payment.' };
      } finally {
        this.loading = false;
      }
    },

    async verifyPayment(paymentId, payload) {
      try {
        this.loading = true;
        const { data } = await axios.post(`/api/payments/${paymentId}/verify`, payload);
        return { success: true, data };
      } catch (error) {
        return {
          success: false,
          error: error.response?.data?.message || 'Unable to verify payment.',
        };
      } finally {
        this.loading = false;
      }
    },
  },
});
