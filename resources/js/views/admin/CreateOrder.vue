<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Create Order for Customer</h1>
        <p class="text-secondary">Place an order on behalf of a customer with inventory validation.</p>
      </div>

      <Card :elevation="2" class="p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <Select v-model="form.customer_id" :options="customerOptions" label="Customer" />
          <Select v-model="form.payment_mode" :options="paymentOptions" label="Payment Method" />
          <Select v-model="form.city_id" :options="cityOptions" label="City" @update:model-value="loadAreas" />
          <Select v-model="form.area_id" :options="areaOptions" label="Area" />
          <Select v-model="form.dispatch_time_slot_id" :options="slotOptions" label="Dispatch Time" />
          <Input v-model="form.payment_reference" label="Payment Reference" />
          <Input v-model="form.order_note" label="Order Note" class="md:col-span-2" />
          <Input v-model="form.internal_note" label="Internal Note" class="md:col-span-2" />
        </div>
      </Card>

      <Card :elevation="2" class="p-5">
        <h2 class="text-lg font-semibold text-primary mb-3">Products</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <Select v-model="productLine.sku_id" :options="productOptions" label="Product SKU" />
          <Input v-model="productLine.quantity" label="Quantity" type="number" />
          <Button class="self-end" @click="addLine">Add Line</Button>
        </div>

        <div class="space-y-2">
          <div v-for="(line, index) in form.items" :key="`${line.sku_id}-${index}`" class="flex items-center justify-between border border-DEFAULT rounded p-3 text-sm">
            <div>
              <p class="font-semibold text-primary">{{ line.product_title }} ({{ line.sku_code }})</p>
              <p class="text-secondary">Qty: {{ line.quantity }} | Unit: {{ formatCurrency(line.price) }}</p>
            </div>
            <Button size="sm" variant="danger" @click="removeLine(index)">Remove</Button>
          </div>
        </div>
      </Card>

      <Card :elevation="2" class="p-5">
        <div class="flex justify-between text-sm mb-2"><span>Subtotal</span><span>{{ formatCurrency(subtotal) }}</span></div>
        <div class="flex justify-between text-sm mb-2"><span>Delivery</span><span>{{ formatCurrency(selectedAreaFee) }}</span></div>
        <div class="flex justify-between text-base font-semibold border-t pt-2"><span>Total</span><span>{{ formatCurrency(total) }}</span></div>
        <Button class="mt-4 w-full" :loading="saving" @click="submit">Create Order</Button>
      </Card>

      <Card v-if="createdOrder" :elevation="2" class="p-5 border border-emerald-200 bg-emerald-50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-primary">Order #{{ createdOrder.id }} created</h2>
            <p class="text-sm text-secondary">Download the terminal receipt now or continue to the order details page.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button icon="receipt-text-outline" @click="downloadReceipt(createdOrder.id)">Download Receipt</Button>
            <Button variant="outline" icon="open-in-new" @click="goToOrder(createdOrder.id)">Open Order</Button>
          </div>
        </div>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Select from '../../components/ui/Select.vue';
import Input from '../../components/ui/Input.vue';
import Button from '../../components/ui/Button.vue';
import { useSettingsStore } from '../../stores/settings';

const toast = inject('toast');
const saving = ref(false);
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const customers = ref([]);
const products = ref([]);
const cities = ref([]);
const areas = ref([]);
const slots = ref([]);
const createdOrder = ref(null);

const form = reactive({
  customer_id: '',
  payment_mode: 'cash',
  city_id: '',
  area_id: '',
  dispatch_time_slot_id: '',
  payment_reference: '',
  order_note: '',
  internal_note: '',
  items: [],
});

const productLine = reactive({
  sku_id: '',
  quantity: 1,
});

const paymentOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'bank_deposit', label: 'Bank Deposit' },
  { value: 'pos', label: 'POS' },
  { value: 'online_payment', label: 'Online Payment' },
  { value: 'other', label: 'Other' },
];

const customerOptions = computed(() => [
  { value: '', label: 'Select customer' },
  ...customers.value.map((customer) => ({ value: customer.id, label: `${customer.name} (${customer.email})` })),
]);

const cityOptions = computed(() => [
  { value: '', label: 'Select city' },
  ...cities.value.map((city) => ({ value: city.id, label: city.name })),
]);

const areaOptions = computed(() => [
  { value: '', label: 'Select area' },
  ...areas.value.map((area) => ({ value: area.id, label: `${area.name} (${formatCurrency(area.delivery_fee || 0)})` })),
]);

const slotOptions = computed(() => [
  { value: '', label: 'Select slot' },
  ...slots.value.map((slot) => ({ value: slot.id, label: `${slot.label} (${slot.display_time})` })),
]);

const productOptions = computed(() => [
  { value: '', label: 'Select product SKU' },
  ...products.value.map((product) => ({ value: product.sku_id, label: `${product.product_title} - ${product.sku_code} (Stock ${product.available_stock})` })),
]);

const selectedAreaFee = computed(() => {
  const area = areas.value.find((entry) => Number(entry.id) === Number(form.area_id));
  return Number(area?.delivery_fee || 0);
});

const subtotal = computed(() => form.items.reduce((sum, line) => sum + Number(line.price || 0) * Number(line.quantity || 0), 0));
const total = computed(() => subtotal.value + selectedAreaFee.value);

const loadInitial = async () => {
  const [customersRes, productsRes, citiesRes, slotsRes] = await Promise.all([
    axios.get('/api/admin/orders/customers'),
    axios.get('/api/admin/orders/products'),
    axios.get('/api/checkout/cities'),
    axios.get('/api/checkout/dispatch-time-slots'),
  ]);

  customers.value = customersRes.data.customers || [];
  products.value = productsRes.data.products || [];
  cities.value = citiesRes.data.cities || [];
  slots.value = slotsRes.data.dispatch_time_slots || [];
};

const loadAreas = async () => {
  form.area_id = '';
  if (!form.city_id) {
    areas.value = [];
    return;
  }

  const { data } = await axios.get('/api/checkout/areas', { params: { city_id: form.city_id } });
  areas.value = data.areas || [];
};

const addLine = () => {
  const sku = products.value.find((entry) => Number(entry.sku_id) === Number(productLine.sku_id));
  if (!sku) {
    toast?.error('Select a valid product SKU.');
    return;
  }

  const qty = Number(productLine.quantity || 0);
  if (qty < 1) {
    toast?.error('Quantity must be at least 1.');
    return;
  }

  const existing = form.items.find((line) => Number(line.sku_id) === Number(sku.sku_id));
  if (existing) {
    existing.quantity += qty;
  } else {
    form.items.push({
      sku_id: sku.sku_id,
      sku_code: sku.sku_code,
      product_title: sku.product_title,
      price: sku.price,
      quantity: qty,
    });
  }

  productLine.sku_id = '';
  productLine.quantity = 1;
};

const removeLine = (index) => {
  form.items.splice(index, 1);
};

const submit = async () => {
  if (!form.customer_id || !form.city_id || !form.area_id || !form.dispatch_time_slot_id || !form.payment_mode || !form.items.length) {
    toast?.error('Customer, products, city, area, dispatch slot and payment method are required.');
    return;
  }

  saving.value = true;

  try {
    const payload = {
      customer_id: Number(form.customer_id),
      city_id: Number(form.city_id),
      area_id: Number(form.area_id),
      dispatch_time_slot_id: Number(form.dispatch_time_slot_id),
      payment_mode: form.payment_mode,
      payment_reference: form.payment_reference || null,
      order_note: form.order_note || null,
      internal_note: form.internal_note || null,
      items: form.items.map((line) => ({ sku_id: Number(line.sku_id), quantity: Number(line.quantity) })),
    };

    const { data } = await axios.post('/api/admin/orders', payload);
    toast?.success('Order created successfully.');
    createdOrder.value = data.order || null;

    form.customer_id = '';
    form.city_id = '';
    form.area_id = '';
    form.dispatch_time_slot_id = '';
    form.payment_mode = 'cash';
    form.payment_reference = '';
    form.order_note = '';
    form.internal_note = '';
    form.items = [];

  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to create order.');
  } finally {
    saving.value = false;
  }
};

const downloadReceipt = async (orderId) => {
  const { data } = await axios.get(`/api/admin/orders/${orderId}/terminal-receipt`, {
    responseType: 'blob',
    headers: { Accept: 'text/html' },
  });
  const url = URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.download = `order-${orderId}-terminal-receipt.html`;
  link.click();
  URL.revokeObjectURL(url);
};

const goToOrder = (orderId) => {
  window.location.assign(`/admin/orders/${orderId}`);
};

onMounted(loadInitial);
</script>
