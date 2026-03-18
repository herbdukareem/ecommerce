<template>
  <MainLayout>
    <section class="max-w-6xl mx-auto px-4 py-10 space-y-6">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold text-primary">Order #{{ orderId }}</h1>
          <p class="text-sm text-secondary mt-1">Order details and fulfillment status.</p>
        </div>
        <router-link to="/orders">
          <Button variant="outline" icon="arrow-left">Back to Orders</Button>
        </router-link>
      </div>

      <div v-if="ordersStore.loading" class="text-secondary py-8 text-center">Loading order details...</div>
      <div v-else-if="errorMessage" class="text-danger py-8 text-center">{{ errorMessage }}</div>
      <div v-else-if="!order" class="text-secondary py-8 text-center">Order not found.</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card :elevation="2" class="p-5 lg:col-span-2">
          <h2 class="text-lg font-semibold text-primary mb-4">Items</h2>
          <div class="space-y-3">
            <div v-for="item in order.items || []" :key="item.id" class="border border-DEFAULT rounded-lg p-3">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="font-medium text-primary">{{ item.sku?.product?.title || 'Product' }}</p>
                  <p class="text-sm text-secondary">Qty: {{ item.quantity }}</p>
                </div>
                <p class="font-semibold text-primary">{{ formatCurrency(item.price_snapshot || 0) }}</p>
              </div>
            </div>
          </div>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Summary</h2>
          <div class="space-y-2 text-sm">
            <p><strong>Status:</strong> {{ order.status }}</p>
            <p><strong>Payment:</strong> {{ order.payment_status }}</p>
            <p><strong>Delivery:</strong> {{ order.delivery_status || 'pending_assignment' }}</p>
            <p><strong>Delivery Method:</strong> {{ order.delivery_snapshot?.method_name || order.shippingMethod?.name || 'N/A' }}</p>
            <p><strong>Delivery Fee:</strong> {{ formatCurrency(order.delivery_fee || order.shipping_cost || 0) }}</p>
            <p><strong>Tracking Code:</strong> {{ order.delivery_tracking_code || 'N/A' }}</p>
            <p><strong>Placed:</strong> {{ formatDate(order.placed_at || order.created_at) }}</p>
            <p><strong>Total:</strong> {{ formatCurrency(order.total || 0) }}</p>
          </div>

          <div class="mt-4 border-t pt-3">
            <p class="text-sm font-semibold text-primary mb-2">Delivery Timeline</p>
            <ul class="space-y-1 text-xs text-secondary">
              <li>Assigned: {{ formatDate(order.assigned_at) }}</li>
              <li>Shipped: {{ formatDate(order.shipped_at) }}</li>
              <li>Delivered: {{ formatDate(order.delivered_at) }}</li>
            </ul>
          </div>

          <Button
            v-if="canCancel"
            variant="danger"
            class="w-full mt-5"
            :loading="cancelling"
            @click="cancelOrder"
          >
            Cancel Order
          </Button>
        </Card>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useOrdersStore } from '../stores/orders';
import { useSettingsStore } from '../stores/settings';

const route = useRoute();
const router = useRouter();
const toast = inject('toast');
const ordersStore = useOrdersStore();
const settingsStore = useSettingsStore();

const errorMessage = ref('');
const cancelling = ref(false);

const orderId = computed(() => route.params.id);
const order = computed(() => ordersStore.currentOrder);
const formatCurrency = settingsStore.formatCurrency;

const canCancel = computed(() => {
  const status = order.value?.status;
  return status === 'pending' || status === 'processing';
});

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleString();
};

const loadOrder = async () => {
  errorMessage.value = '';
  const result = await ordersStore.fetchOrder(orderId.value);
  if (!result.success) {
    errorMessage.value = result.error || 'Unable to load order.';
  }
};

const cancelOrder = async () => {
  cancelling.value = true;
  const result = await ordersStore.cancelOrder(orderId.value);
  cancelling.value = false;

  if (!result.success) {
    toast?.error(result.error || 'Unable to cancel order');
    return;
  }

  toast?.success(result.message || 'Order cancelled');
  await loadOrder();
};

onMounted(loadOrder);
</script>
