<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-primary">Profit Margins</h1>
          <p class="text-secondary">Understand revenue quality, cost of goods sold, and product contribution.</p>
        </div>
        <Button icon="refresh" variant="outline" :loading="loading" @click="loadReport">Refresh</Button>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <Input v-model="filters.date" label="Single Date" type="date" />
          <Input v-model="filters.start_date" label="Start Date" type="date" />
          <Input v-model="filters.end_date" label="End Date" type="date" />
          <Button class="self-end" :loading="loading" @click="loadReport">Apply Filters</Button>
        </div>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <MetricCard title="Revenue" :value="formatCurrency(summary.revenue)" icon="cash-register" />
        <MetricCard title="COGS" :value="formatCurrency(summary.total_cost)" icon="archive-outline" />
        <MetricCard title="Gross Profit" :value="formatCurrency(summary.gross_profit)" icon="chart-areaspline" />
        <MetricCard title="Margin" :value="`${summary.gross_margin_percent || 0}%`" icon="percent-outline" />
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <Card title="Product Contribution" icon="package-variant" :elevation="2" class="xl:col-span-7 p-0 overflow-hidden">
          <div v-if="loading" class="p-5 text-secondary">Loading product margins...</div>
          <div v-else-if="products.length === 0" class="p-5 text-secondary">No product margin data for this period.</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-base text-secondary">
              <tr>
                <th class="text-left p-3">Product</th>
                <th class="text-left p-3">Qty</th>
                <th class="text-left p-3">Revenue</th>
                <th class="text-left p-3">Cost</th>
                <th class="text-left p-3">Profit</th>
                <th class="text-left p-3">Margin</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in products" :key="row.product_id" class="border-t border-DEFAULT">
                <td class="p-3 font-medium text-primary">{{ row.product || 'N/A' }}</td>
                <td class="p-3">{{ row.qty_sold }}</td>
                <td class="p-3">{{ formatCurrency(row.revenue) }}</td>
                <td class="p-3">{{ formatCurrency(row.cost) }}</td>
                <td class="p-3 font-semibold" :class="Number(row.profit || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700'">{{ formatCurrency(row.profit) }}</td>
                <td class="p-3">{{ row.margin_percent }}%</td>
              </tr>
            </tbody>
          </table>
        </Card>

        <Card title="Margin Notes" icon="lightbulb-outline" :elevation="2" class="xl:col-span-5">
          <div class="space-y-3 text-sm">
            <InsightRow label="Orders analyzed" :value="summary.orders_count" />
            <InsightRow label="Items sold" :value="summary.items_sold" />
            <InsightRow label="Average profit/order" :value="formatCurrency(averageProfitPerOrder)" />
            <InsightRow label="Best contributor" :value="bestProductLabel" />
          </div>
        </Card>
      </div>

      <Card title="Order Breakdown" icon="receipt-text-outline" :elevation="2" class="p-0 overflow-hidden">
        <div v-if="loading" class="p-5 text-secondary">Loading orders...</div>
        <div v-else-if="orders.length === 0" class="p-5 text-secondary">No orders with cost data found for this period.</div>
        <table v-else class="w-full text-sm">
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
              <td class="p-3">{{ formatDate(row.date) }}</td>
              <td class="p-3">{{ row.customer || 'N/A' }}</td>
              <td class="p-3">{{ formatCurrency(row.revenue) }}</td>
              <td class="p-3">{{ formatCurrency(row.cost) }}</td>
              <td class="p-3 font-semibold" :class="Number(row.profit || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700'">{{ formatCurrency(row.profit) }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Input from '../../components/ui/Input.vue';
import Button from '../../components/ui/Button.vue';
import { useSettingsStore } from '../../stores/settings';

const loading = ref(false);
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const summary = reactive({
  revenue: 0,
  total_cost: 0,
  gross_profit: 0,
  gross_margin_percent: 0,
  orders_count: 0,
  items_sold: 0,
});
const orders = ref([]);
const products = ref([]);

const filters = reactive({
  date: '',
  start_date: '',
  end_date: '',
});

const averageProfitPerOrder = computed(() => {
  const count = Number(summary.orders_count || 0);
  return count > 0 ? Number(summary.gross_profit || 0) / count : 0;
});

const bestProductLabel = computed(() => {
  const best = [...products.value].sort((a, b) => Number(b.profit || 0) - Number(a.profit || 0))[0];
  return best ? `${best.product} (${formatCurrency(best.profit)})` : 'N/A';
});

const formatDate = (value) => {
  if (!value) return 'N/A';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 'N/A' : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
};

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
    products.value = data.products || [];
  } finally {
    loading.value = false;
  }
};

const MetricCard = (props) => h(Card, { elevation: 2, class: 'p-5' }, () => h('div', { class: 'flex items-center justify-between gap-4' }, [
  h('div', [
    h('p', { class: 'text-sm text-secondary' }, props.title),
    h('p', { class: 'text-2xl font-bold text-primary mt-1' }, props.value),
  ]),
  h('div', { class: 'h-11 w-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center' }, [
    h('i', { class: `mdi mdi-${props.icon} text-2xl` }),
  ]),
]));

const InsightRow = (props) => h('div', { class: 'rounded-lg border border-DEFAULT p-3 flex items-center justify-between gap-3' }, [
  h('span', { class: 'text-secondary' }, props.label),
  h('span', { class: 'font-semibold text-primary text-right' }, props.value || 0),
]);

onMounted(loadReport);
</script>
