<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Profit Margins</h1>
        <p class="text-secondary">Revenue, COGS, and gross profit analytics.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <Input v-model="filters.date" label="Single Date" type="date" />
          <Input v-model="filters.start_date" label="Start Date" type="date" />
          <Input v-model="filters.end_date" label="End Date" type="date" />
          <Button class="self-end" :loading="loading" @click="loadReport">Apply</Button>
        </div>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card :elevation="2" class="p-4"><p class="text-secondary text-sm">Revenue</p><p class="text-2xl font-bold text-primary">{{ currency(summary.revenue) }}</p></Card>
        <Card :elevation="2" class="p-4"><p class="text-secondary text-sm">Total Cost</p><p class="text-2xl font-bold text-primary">{{ currency(summary.total_cost) }}</p></Card>
        <Card :elevation="2" class="p-4"><p class="text-secondary text-sm">Gross Profit</p><p class="text-2xl font-bold text-primary">{{ currency(summary.gross_profit) }}</p></Card>
      </div>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <h3 class="text-lg font-semibold text-primary p-4 border-b border-DEFAULT">Order Breakdown</h3>
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Order</th>
              <th class="text-left p-3">Date</th>
              <th class="text-left p-3">Customer</th>
              <th class="text-left p-3">Revenue</th>
              <th class="text-left p-3">Cost</th>
              <th class="text-left p-3">Profit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in orders" :key="row.order_id" class="border-t border-DEFAULT">
              <td class="p-3">#{{ row.order_id }}</td>
              <td class="p-3">{{ row.date }}</td>
              <td class="p-3">{{ row.customer }}</td>
              <td class="p-3">{{ currency(row.revenue) }}</td>
              <td class="p-3">{{ currency(row.cost) }}</td>
              <td class="p-3">{{ currency(row.profit) }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Input from '../../components/ui/Input.vue';
import Button from '../../components/ui/Button.vue';

const loading = ref(false);
const summary = reactive({
  revenue: 0,
  total_cost: 0,
  gross_profit: 0,
  gross_margin_percent: 0,
  orders_count: 0,
  items_sold: 0,
});
const orders = ref([]);

const filters = reactive({
  date: '',
  start_date: '',
  end_date: '',
});

const currency = (value) => new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
  minimumFractionDigits: 2,
}).format(Number(value || 0));

const loadReport = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/reports/profit-margins', {
      params: {
        date: filters.date || null,
        start_date: filters.start_date || null,
        end_date: filters.end_date || null,
      },
    });

    Object.assign(summary, data.summary || {});
    orders.value = data.orders || [];
  } finally {
    loading.value = false;
  }
};

onMounted(loadReport);
</script>
