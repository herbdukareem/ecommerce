<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Inventory Management</h1>
        <p class="text-secondary">Add stock batches with cost, selling price, and expiry date.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Select v-model="form.sku_id" :options="skuOptions" label="SKU" />
          <Input v-model="form.quantity" label="Quantity" type="number" />
          <Input v-model="form.cost_price" label="Cost Price" type="number" />
          <Input v-model="form.selling_price" label="Selling Price" type="number" />
          <Input v-model="form.expiry_date" label="Expiry Date" type="date" />
          <Input v-model="form.batch_reference" label="Batch Reference" />
          <Input v-model="form.source_type" label="Supplier/Source" />
          <Input v-model="form.note" label="Note" class="md:col-span-2" />
        </div>
        <Button class="mt-4" :loading="saving" @click="addStock">Add Stock</Button>
      </Card>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Product</th>
              <th class="text-left p-3">SKU</th>
              <th class="text-left p-3">Qty Added</th>
              <th class="text-left p-3">Cost</th>
              <th class="text-left p-3">Selling</th>
              <th class="text-left p-3">Expiry</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="batch in batches" :key="batch.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ batch.product?.title }}</td>
              <td class="p-3">{{ batch.sku?.sku_code }}</td>
              <td class="p-3">{{ formatQuantity(batch.quantity_received) }}</td>
              <td class="p-3">{{ batch.cost_price }}</td>
              <td class="p-3">{{ batch.selling_price }}</td>
              <td class="p-3">{{ formatDate(batch.expiry_date) }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';
import Button from '../../components/ui/Button.vue';

const toast = inject('toast');
const saving = ref(false);
const skus = ref([]);
const batches = ref([]);

const form = reactive({
  sku_id: '',
  quantity: 1,
  cost_price: 0,
  selling_price: 0,
  expiry_date: '',
  batch_reference: '',
  source_type: '',
  note: '',
});

const skuOptions = computed(() => [
  { value: '', label: 'Select SKU' },
  ...skus.value.map((sku) => ({ value: sku.sku_id, label: `${sku.product_title} - ${sku.sku_code} (Avail ${sku.available_stock})` })),
]);

const selectedSku = computed(() => skus.value.find((sku) => Number(sku.sku_id) === Number(form.sku_id)));

const applySelectedSkuPrices = () => {
  const sku = selectedSku.value;
  if (!sku) {
    return;
  }

  form.cost_price = Number(sku.last_cost_price ?? sku.cost_price ?? sku.cost ?? 0);
  form.selling_price = Number(sku.last_selling_price ?? sku.price ?? 0);
};

const loadSkus = async () => {
  const { data } = await axios.get('/api/admin/inventory/skus');
  skus.value = data.skus || [];
  applySelectedSkuPrices();
};

const loadBatches = async () => {
  const { data } = await axios.get('/api/admin/inventory/batches', { params: { per_page: 100 } });
  batches.value = data.data || [];
};

const formatQuantity = (value) => new Intl.NumberFormat('en-NG').format(Math.max(0, Number(value || 0)));

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleDateString('en-NG');
};

const addStock = async () => {
  saving.value = true;
  try {
    await axios.post('/api/admin/inventory/add-stock', {
      sku_id: Number(form.sku_id),
      quantity: Number(form.quantity),
      cost_price: Number(form.cost_price),
      selling_price: Number(form.selling_price),
      expiry_date: form.expiry_date || null,
      batch_reference: form.batch_reference || null,
      source_type: form.source_type || null,
      note: form.note || null,
    });

    toast?.success('Stock added successfully.');
    form.quantity = 1;
    form.cost_price = 0;
    form.selling_price = 0;
    form.expiry_date = '';
    form.batch_reference = '';
    form.source_type = '';
    form.note = '';

    await Promise.all([loadSkus(), loadBatches()]);
    applySelectedSkuPrices();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to add stock.');
  } finally {
    saving.value = false;
  }
};

watch(() => form.sku_id, applySelectedSkuPrices);

onMounted(async () => {
  await Promise.all([loadSkus(), loadBatches()]);
});
</script>
