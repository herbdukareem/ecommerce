<template>
  <AdminLayout>
    <div class="space-y-6 pb-8">
      <div class="rounded-xl border border-DEFAULT bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">Admin order workspace</p>
            <h1 class="mt-1 text-3xl font-bold text-primary">Order #{{ orderId }}</h1>
            <p class="mt-1 text-secondary">Review ordered items, payment, fulfillment, and dispatch activity in one place.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button v-if="order" icon="receipt-text-outline" @click="downloadReceipt">Download Receipt</Button>
            <router-link to="/admin/orders">
              <Button variant="outline" icon="arrow-left">Back to Orders</Button>
            </router-link>
          </div>
        </div>
      </div>

      <Card :elevation="2" class="p-5" v-if="loading">Loading order details...</Card>
      <Card :elevation="2" class="p-5 text-danger" v-else-if="errorMessage">{{ errorMessage }}</Card>

      <template v-else-if="order">
        <OrderStatusBadges :order="order" />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
          <div class="xl:col-span-8 space-y-6">
            <OrderItemsList :items="order.items || []" :format-currency="formatCurrency" />
            <DeliveryTimeline :order="order" :format-date="formatDate" />

            <Card v-if="order.dispatch_assignments?.length" :elevation="2" class="p-5">
              <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                  <h2 class="text-lg font-semibold text-primary">Dispatch Assignment History</h2>
                  <p class="text-sm text-secondary">Rider assignments, rejections, and delivery handoffs.</p>
                </div>
                <span class="rounded-full bg-base px-3 py-1 text-xs font-medium text-secondary">
                  {{ order.dispatch_assignments.length }} record{{ order.dispatch_assignments.length === 1 ? '' : 's' }}
                </span>
              </div>
              <div class="space-y-3">
                <div v-for="assignment in order.dispatch_assignments" :key="assignment.id" class="rounded-lg border border-DEFAULT p-4 text-sm">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p class="font-semibold text-primary">{{ assignment.rider?.user?.name || 'Rider' }}</p>
                      <p class="text-secondary">Assigned by {{ assignment.assigned_by?.name || 'N/A' }} at {{ formatDate(assignment.assigned_at) }}</p>
                    </div>
                    <span class="rounded-full bg-base px-3 py-1 text-xs font-semibold text-secondary">{{ humanize(assignment.status) }}</span>
                  </div>
                  <p v-if="assignment.rejection_reason" class="mt-2 rounded-lg bg-danger/5 px-3 py-2 text-danger">Reason: {{ assignment.rejection_reason }}</p>
                  <p v-if="assignment.issue_note" class="mt-2 rounded-lg bg-danger/5 px-3 py-2 text-danger">Issue: {{ assignment.issue_note }}</p>
                </div>
              </div>
            </Card>
          </div>

          <div class="xl:col-span-4 space-y-6">
            <OrderSummaryCard :order="order" :format-currency="formatCurrency" :format-date="formatDate" />

            <Card v-if="isPayOnDelivery" :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Pay on Delivery</h2>
              <p class="mt-1 text-sm text-secondary">
                {{ order.payment_status === 'paid' ? 'Payment has been collected for this delivery.' : 'Confirm collection after the customer pays on delivery.' }}
              </p>
              <Input v-if="order.payment_status !== 'paid'" v-model="collectionReference" class="mt-4" label="Collection Reference" />
              <Button
                v-if="order.payment_status !== 'paid'"
                class="mt-4 w-full"
                icon="cash-check"
                :loading="collectingPayment"
                @click="collectPayment"
              >
                Mark Payment Collected
              </Button>
            </Card>

            <Card :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Customer</h2>
              <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-start gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <i class="mdi mdi-account"></i>
                  </span>
                  <div class="min-w-0">
                    <p class="font-semibold text-primary truncate">{{ order.user?.name || 'Guest customer' }}</p>
                    <p class="text-secondary truncate">{{ order.user?.email || 'No email available' }}</p>
                    <p v-if="order.user?.phone || order.phone" class="text-secondary">{{ order.user?.phone || order.phone }}</p>
                  </div>
                </div>
                <div class="rounded-lg bg-base p-3 text-secondary">
                  <p class="font-medium text-primary">Delivery Area</p>
                  <p>{{ order.city_name || order.city?.name || 'N/A' }}<span v-if="order.area_name || order.area?.name">, {{ order.area_name || order.area?.name }}</span></p>
                </div>
              </div>
            </Card>

            <Card v-if="order.dispatch_rider" :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Assigned Rider</h2>
              <div class="mt-4 flex items-center gap-3">
                <img
                  :src="order.dispatch_rider.profile_photo_url || '/images/placeholders/product-placeholder.svg'"
                  :alt="order.dispatch_rider.user?.name || 'Dispatch rider'"
                  class="h-14 w-14 rounded-lg object-cover border border-DEFAULT"
                />
                <div class="min-w-0">
                  <p class="font-semibold text-primary truncate">{{ order.dispatch_rider.user?.name || 'Dispatch rider' }}</p>
                  <p class="text-sm text-secondary">{{ humanize(order.dispatch_rider.vehicle_type) }}</p>
                  <p v-if="order.dispatch_rider.phone" class="text-xs text-secondary">{{ order.dispatch_rider.phone }}</p>
                </div>
              </div>
              <img
                v-if="order.dispatch_rider.vehicle_image_url"
                :src="order.dispatch_rider.vehicle_image_url"
                alt="Assigned rider vehicle"
                class="mt-3 h-28 w-full rounded-lg object-cover border border-DEFAULT"
              />
              <p v-if="order.dispatch_rider.vehicle_plate_number" class="text-xs text-secondary mt-2">
                Plate: {{ order.dispatch_rider.vehicle_plate_number }}
              </p>
            </Card>

            <Card :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Assign Dispatch Rider</h2>
              <p class="mt-1 text-sm text-secondary">Send this order to an available rider for delivery acceptance.</p>

              <div class="mt-4 space-y-3">
                <Select
                  v-model="riderAssignment.dispatch_rider_id"
                  label="Dispatch Rider"
                  :options="riderOptions"
                />
                <Input v-model="riderAssignment.dispatch_note" label="Dispatch Note" />
              </div>

              <Button class="mt-4 w-full" :loading="assigningRider" @click="assignRider">Assign Rider</Button>
            </Card>

            <Card :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Delivery Partner</h2>
              <p class="mt-1 text-sm text-secondary">Use this for logistics partner tracking or manual handoff notes.</p>

              <div class="mt-4 space-y-3">
                <Select
                  v-model="assignment.delivery_partner_id"
                  label="Delivery Partner"
                  :options="partnerOptions"
                />
                <Input v-model="assignment.delivery_tracking_code" label="Tracking Code" />
                <Input v-model="assignment.dispatch_note" label="Dispatch Note" />
              </div>

              <Button class="mt-4 w-full" :loading="assigning" @click="assignPartner">Save Assignment</Button>
            </Card>

            <Card :elevation="2" class="p-5">
              <h2 class="text-lg font-semibold text-primary">Update Delivery Status</h2>
              <p class="mt-1 text-sm text-secondary">Move the delivery lifecycle forward after rider or logistics updates.</p>

              <div class="mt-4 space-y-3">
                <Select v-model="deliveryStatus" label="Delivery Status" :options="deliveryStatusOptions" />
                <Input v-model="assignment.delivery_tracking_code" label="Tracking Code" />
                <Input v-model="assignment.dispatch_note" label="Dispatch Note" />
              </div>

              <Button class="mt-4 w-full" :loading="updatingStatus" @click="updateDeliveryStatus">Update Delivery Status</Button>
            </Card>
          </div>
        </div>
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
import OrderStatusBadges from '../../components/orders/OrderStatusBadges.vue';
import OrderItemsList from '../../components/orders/OrderItemsList.vue';
import DeliveryTimeline from '../../components/orders/DeliveryTimeline.vue';
import OrderSummaryCard from '../../components/orders/OrderSummaryCard.vue';
import { useSettingsStore } from '../../stores/settings';

const route = useRoute();
const toast = inject('toast');

const loading = ref(false);
const errorMessage = ref('');
const assigning = ref(false);
const assigningRider = ref(false);
const updatingStatus = ref(false);
const collectingPayment = ref(false);
const collectionReference = ref('');

const order = ref(null);
const partners = ref([]);
const riders = ref([]);

const assignment = ref({
  delivery_partner_id: '',
  delivery_tracking_code: '',
  dispatch_note: '',
});

const deliveryStatus = ref('pending_assignment');
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const riderAssignment = ref({
  dispatch_rider_id: '',
  dispatch_note: '',
});

const orderId = computed(() => route.params.id);
const isPayOnDelivery = computed(() => order.value?.payment_mode === 'pay_on_delivery' || order.value?.payments?.some((payment) => payment.method === 'pay_on_delivery'));

const deliveryStatusOptions = [
  { value: 'pending_assignment', label: 'Pending Assignment' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'packed', label: 'Packed' },
  { value: 'ready_for_dispatch', label: 'Ready for Dispatch' },
  { value: 'picked_up', label: 'Picked Up' },
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

const riderOptions = computed(() => [
  { value: '', label: 'Select rider' },
  ...riders.value.map((rider) => ({
    value: rider.id,
    label: `${rider.user?.name || 'Rider'} (${humanize(rider.availability_status)} / ${rider.status})`,
  })),
]);

const humanize = (value) => String(value || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const formatDate = (value) => {
  if (!value) return 'N/A';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 'N/A' : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

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
    riderAssignment.value = {
      dispatch_rider_id: data.dispatch_rider_id || '',
      dispatch_note: data.dispatch_note || '',
    };
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Unable to load order details';
  } finally {
    loading.value = false;
  }
};

const loadRiders = async () => {
  const { data } = await axios.get('/api/admin/dispatch-riders', {
    params: { status: 'active', per_page: 100 },
  });
  riders.value = data.data || [];
};

const loadPartners = async () => {
  const { data } = await axios.get('/api/admin/delivery-partners', {
    params: { status: 'active', per_page: 100 },
  });
  partners.value = data.data || [];
};

const assignRider = async () => {
  if (!riderAssignment.value.dispatch_rider_id) {
    toast?.error('Select a dispatch rider.');
    return;
  }

  assigningRider.value = true;
  try {
    await axios.put(`/api/admin/orders/${orderId.value}/dispatch-rider`, {
      dispatch_rider_id: riderAssignment.value.dispatch_rider_id,
      dispatch_note: riderAssignment.value.dispatch_note || null,
    });
    toast?.success('Dispatch rider assigned');
    await loadOrder();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to assign dispatch rider');
  } finally {
    assigningRider.value = false;
  }
};

const downloadReceipt = async () => {
  const { data } = await axios.get(`/api/admin/orders/${orderId.value}/terminal-receipt`, {
    responseType: 'blob',
    headers: { Accept: 'text/html' },
  });
  const url = URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.download = `order-${orderId.value}-terminal-receipt.html`;
  link.click();
  URL.revokeObjectURL(url);
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

const collectPayment = async () => {
  collectingPayment.value = true;
  try {
    await axios.post(`/api/admin/orders/${orderId.value}/collect-payment`, {
      payment_reference: collectionReference.value || null,
    });
    toast?.success('Payment collected');
    collectionReference.value = '';
    await loadOrder();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to mark payment collected');
  } finally {
    collectingPayment.value = false;
  }
};

onMounted(async () => {
  await Promise.all([loadPartners(), loadRiders(), loadOrder()]);
});
</script>
