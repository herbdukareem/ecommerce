<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primary">Order #{{ orderId }}</h1>
          <p class="text-secondary">Delivery assignment and order lifecycle management.</p>
        </div>
        <router-link to="/admin/orders">
          <Button variant="outline">Back to Orders</Button>
        </router-link>
      </div>

      <Card :elevation="2" class="p-5" v-if="loading">Loading order details...</Card>
      <Card :elevation="2" class="p-5 text-danger" v-else-if="errorMessage">{{ errorMessage }}</Card>

      <template v-else-if="order">
        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Order Summary</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <p><strong>Customer:</strong> {{ order.user?.name }} ({{ order.user?.email }})</p>
            <p><strong>Status:</strong> {{ order.status }}</p>
            <p><strong>Payment:</strong> {{ order.payment_status }}</p>
            <p><strong>Delivery Status:</strong> {{ order.delivery_status || 'pending_assignment' }}</p>
            <p><strong>City:</strong> {{ order.city_name || order.city?.name || 'N/A' }}</p>
            <p><strong>Area:</strong> {{ order.area_name || order.area?.name || 'N/A' }}</p>
            <p><strong>Dispatch Slot:</strong> {{ order.dispatch_time_label || order.dispatchTimeSlot?.label || 'N/A' }}</p>
            <p><strong>Delivery Fee:</strong> {{ formatCurrency(order.delivery_fee || order.shipping_cost || 0) }}</p>
            <p><strong>Tracking:</strong> {{ order.delivery_tracking_code || 'N/A' }}</p>
          </div>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Assign Delivery Partner</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <Select
              v-model="assignment.delivery_partner_id"
              label="Delivery Partner"
              :options="partnerOptions"
            />
            <Input v-model="assignment.delivery_tracking_code" label="Tracking Code" />
            <Input v-model="assignment.dispatch_note" label="Dispatch Note" />
          </div>

          <Button class="mt-4" :loading="assigning" @click="assignPartner">Save Assignment</Button>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Update Delivery Status</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <Select v-model="deliveryStatus" label="Delivery Status" :options="deliveryStatusOptions" />
            <Input v-model="assignment.delivery_tracking_code" label="Tracking Code" />
            <Input v-model="assignment.dispatch_note" label="Dispatch Note" />
          </div>

          <Button class="mt-4" :loading="updatingStatus" @click="updateDeliveryStatus">Update Delivery Status</Button>
        </Card>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Select from '../../components/ui/Select.vue';
import Input from '../../components/ui/Input.vue';

const route = useRoute();
const toast = inject('toast');

const loading = ref(false);
const errorMessage = ref('');
const assigning = ref(false);
const updatingStatus = ref(false);

const order = ref(null);
const partners = ref([]);

const assignment = ref({
  delivery_partner_id: '',
  delivery_tracking_code: '',
  dispatch_note: '',
});

const deliveryStatus = ref('pending_assignment');

const orderId = computed(() => route.params.id);

const deliveryStatusOptions = [
  { value: 'pending_assignment', label: 'Pending Assignment' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'packed', label: 'Packed' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'in_transit', label: 'In Transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'delivery_failed', label: 'Delivery Failed' },
  { value: 'returned', label: 'Returned' },
  { value: 'cancelled', label: 'Cancelled' },
];

const partnerOptions = computed(() => [
  { value: '', label: 'Unassigned' },
  ...partners.value.map((partner) => ({ value: partner.id, label: `${partner.name} (${partner.status})` })),
]);

const formatCurrency = (value) => new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
  minimumFractionDigits: 2,
}).format(Number(value || 0));

const loadOrder = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const { data } = await axios.get(`/api/admin/orders/${orderId.value}`);
    order.value = data;
    deliveryStatus.value = data.delivery_status || 'pending_assignment';
    assignment.value = {
      delivery_partner_id: data.delivery_partner_id || '',
      delivery_tracking_code: data.delivery_tracking_code || '',
      dispatch_note: data.dispatch_note || '',
    };
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Unable to load order details';
  } finally {
    loading.value = false;
  }
};

const loadPartners = async () => {
  const { data } = await axios.get('/api/admin/delivery-partners', {
    params: { status: 'active', per_page: 100 },
  });
  partners.value = data.data || [];
};

const assignPartner = async () => {
  assigning.value = true;
  try {
    await axios.put(`/api/admin/orders/${orderId.value}/delivery-assignment`, {
      delivery_partner_id: assignment.value.delivery_partner_id || null,
      delivery_tracking_code: assignment.value.delivery_tracking_code || null,
      dispatch_note: assignment.value.dispatch_note || null,
    });
    toast?.success('Delivery assignment updated');
    await loadOrder();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to update delivery assignment');
  } finally {
    assigning.value = false;
  }
};

const updateDeliveryStatus = async () => {
  updatingStatus.value = true;
  try {
    await axios.put(`/api/admin/orders/${orderId.value}/delivery-status`, {
      delivery_status: deliveryStatus.value,
      delivery_tracking_code: assignment.value.delivery_tracking_code || null,
      dispatch_note: assignment.value.dispatch_note || null,
    });
    toast?.success('Delivery status updated');
    await loadOrder();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to update delivery status');
  } finally {
    updatingStatus.value = false;
  }
};

onMounted(async () => {
  await Promise.all([loadPartners(), loadOrder()]);
});
</script>
