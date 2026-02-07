import { defineStore } from 'pinia';
import axios from 'axios';

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    orders: [],
    inventory: [],
    zones: [],
    methods: [],
  }),
  actions: {
    async loadOrders() {
      const { data } = await axios.get('/api/orders');
      this.orders = data.orders || [];
    },
    async loadInventory() {
      // TODO: call endpoint for vendor inventory
      this.inventory = [];
    },
    async loadZones() {
      const { data } = await axios.get('/api/shipping/zones');
      this.zones = data.zones;
    },
    async loadMethods() {
      const { data } = await axios.get('/api/shipping/methods');
      this.methods = data.methods;
    },
  },
});