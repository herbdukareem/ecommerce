<template>
  <MainLayout>
    <section class="max-w-6xl mx-auto px-4 py-10 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold text-primary">My Orders</h1>
          <p class="text-sm text-secondary mt-1">Track, view, and manage your purchases.</p>
        </div>
        <router-link to="/products">
          <Button variant="outline" icon="shopping">Continue Shopping</Button>
        </router-link>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <select v-model="filters.status" class="border border-DEFAULT rounded-lg px-3 py-2" @change="applyFilters">
            <option :value="null">All statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <select v-model="filters.payment_status" class="border border-DEFAULT rounded-lg px-3 py-2" @change="applyFilters">
            <option :value="null">All payment states</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
          <Button variant="ghost" icon="refresh" @click="loadOrders" :loading="ordersStore.loading">Refresh</Button>
        </div>
      </Card>

      <div v-if="ordersStore.loading" class="text-secondary py-8 text-center">Loading orders...</div>
      <div v-else-if="orders.length === 0" class="text-secondary py-8 text-center">You have no orders yet.</div>

      <div v-else class="space-y-3">
        <Card v-for="order in orders" :key="order.id" :elevation="2" class="p-5">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <p class="text-sm text-secondary">Order #{{ order.id }}</p>
              <p class="text-sm text-secondary">Placed {{ formatDate(order.placed_at || order.created_at) }}</p>
              <p class="text-base font-semibold text-primary mt-1">{{ formatCurrency(order.total) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-1 rounded text-xs bg-base text-primary">{{ order.status }}</span>
              <span class="px-2 py-1 rounded text-xs bg-base text-primary">{{ order.payment_status }}</span>
              <span class="px-2 py-1 rounded text-xs bg-base text-primary">{{ order.delivery_status || 'pending_assignment' }}</span>
              <router-link :to="`/orders/${order.id}`">
                <Button variant="primary" size="sm">View</Button>
              </router-link>
            </div>
          </div>
        </Card>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useOrdersStore } from '../stores/orders';
import { useSettingsStore } from '../stores/settings';

const ordersStore = useOrdersStore();
const settingsStore = useSettingsStore();

const filters = reactive({
  status: null,
  payment_status: null,
});

const orders = computed(() => ordersStore.orders || []);
const formatCurrency = settingsStore.formatCurrency;

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleString();
};

const applyFilters = async () => {
  ordersStore.filters.status = filters.status || null;
  ordersStore.filters.payment_status = filters.payment_status || null;
  ordersStore.filters.page = 1;
  await ordersStore.fetchOrders();
};

const loadOrders = async () => {
  await ordersStore.fetchOrders();
};

onMounted(loadOrders);
</script>
