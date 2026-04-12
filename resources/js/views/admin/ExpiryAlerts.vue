<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Expiry Alerts</h1>
        <p class="text-secondary">Track expired and soon-to-expire batches.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Select v-model="bucket" :options="bucketOptions" label="Status Bucket" @update:model-value="loadAlerts" />
        </div>
      </Card>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Product</th>
              <th class="text-left p-3">SKU</th>
              <th class="text-left p-3">Batch</th>
              <th class="text-left p-3">Qty</th>
              <th class="text-left p-3">Expiry</th>
              <th class="text-left p-3">Days</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3">Update Expiry</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ row.product?.title }}</td>
              <td class="p-3">{{ row.sku?.sku_code }}</td>
              <td class="p-3">{{ row.batch_reference || `Batch #${row.id}` }}</td>
              <td class="p-3">{{ row.quantity_remaining }}</td>
              <td class="p-3">{{ row.expiry_date }}</td>
              <td class="p-3">{{ row.days_to_expiry }}</td>
              <td class="p-3">{{ row.status_bucket }}</td>
              <td class="p-3">
                <input type="date" v-model="expiryUpdates[row.id]" class="border border-DEFAULT rounded p-1" />
                <Button size="sm" class="ml-2" @click="updateExpiry(row.id)">Save</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Select from '../../components/ui/Select.vue';
import Button from '../../components/ui/Button.vue';

const toast = inject('toast');
const bucket = ref('all');
const rows = ref([]);
const expiryUpdates = ref({});

const bucketOptions = [
  { value: 'all', label: 'All' },
  { value: 'expired', label: 'Expired' },
  { value: '7_days', label: 'Expiring within 7 days' },
  { value: '14_days', label: 'Expiring within 14 days' },
  { value: '30_days', label: 'Expiring within 30 days' },
];

const loadAlerts = async () => {
  const { data } = await axios.get('/api/admin/inventory/expiry-alerts', { params: { bucket: bucket.value, per_page: 100 } });
  rows.value = data.data || [];
};

const updateExpiry = async (batchId) => {
  await axios.put(`/api/admin/inventory/batches/${batchId}/expiry`, {
    expiry_date: expiryUpdates.value[batchId] || null,
  });
  toast?.success('Expiry date updated.');
  await loadAlerts();
};

onMounted(loadAlerts);
</script>
