<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Inventory Ledger</h1>
        <p class="text-secondary">Auditable stock movement history.</p>
      </div>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Date</th>
              <th class="text-left p-3">Product</th>
              <th class="text-left p-3">Movement</th>
              <th class="text-left p-3">In</th>
              <th class="text-left p-3">Out</th>
              <th class="text-left p-3">Balance</th>
              <th class="text-left p-3">Note</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ formatDate(entry.created_at) }}</td>
              <td class="p-3">{{ entry.product?.title }}</td>
              <td class="p-3">{{ entry.movement_type }}</td>
              <td class="p-3">{{ entry.quantity_in }}</td>
              <td class="p-3">{{ entry.quantity_out }}</td>
              <td class="p-3">{{ entry.balance_after }}</td>
              <td class="p-3">{{ entry.note || 'N/A' }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';

const entries = ref([]);

const loadLedger = async () => {
  const { data } = await axios.get('/api/admin/inventory/ledger', { params: { per_page: 100 } });
  entries.value = data.data || [];
};

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleString();
};

onMounted(loadLedger);
</script>
