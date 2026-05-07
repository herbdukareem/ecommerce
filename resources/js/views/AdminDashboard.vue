<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-primary">Business Dashboard</h1>
          <p class="text-secondary">Revenue, orders, inventory pressure, and dispatch signals for today’s decisions.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="option in periodOptions"
            :key="option.value"
            :variant="period === option.value ? 'primary' : 'outline'"
            size="sm"
            @click="setPeriod(option.value)"
          >
            {{ option.label }}
          </Button>
          <Button variant="ghost" size="sm" icon="refresh" :loading="loading" @click="loadDashboard">Refresh</Button>
        </div>
      </div>

      <div v-if="attentionItems.length" class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div
          v-for="item in attentionItems"
          :key="item.label"
          class="rounded-lg border p-4 flex items-center justify-between gap-3"
          :class="item.class"
        >
          <div>
            <p class="text-sm font-semibold">{{ item.label }}</p>
            <p class="text-xs opacity-80">{{ item.note }}</p>
          </div>
          <p class="text-2xl font-bold">{{ item.value }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <MetricCard title="Revenue" :value="formatCurrency(stats.total_revenue)" icon="cash-multiple" tone="success" :meta="growthText" />
        <MetricCard title="Gross Profit" :value="formatCurrency(stats.gross_profit)" icon="chart-areaspline" tone="primary" :meta="`${stats.gross_margin_percent || 0}% margin`" />
        <MetricCard title="Orders" :value="stats.total_orders" icon="package-variant" tone="info" :meta="`${stats.completed_orders || 0} completed`" />
        <MetricCard title="Average Order" :value="formatCurrency(stats.average_order_value)" icon="receipt-text-outline" tone="warning" :meta="`${stats.payment_success_count || 0} paid orders`" />
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <Card title="Sales Trend" icon="chart-line" :elevation="2" class="xl:col-span-8">
          <SalesChart :data="salesData" />
        </Card>

        <Card title="Operating Snapshot" icon="view-dashboard-outline" :elevation="2" class="xl:col-span-4">
          <div class="space-y-4">
            <SnapshotRow label="Pending Orders" :value="stats.pending_orders" icon="clock-alert-outline" />
            <SnapshotRow label="Processing Orders" :value="stats.processing_orders" icon="progress-clock" />
            <SnapshotRow label="Cancelled Orders" :value="stats.cancelled_orders" icon="cancel" />
            <SnapshotRow label="Payment Failures" :value="stats.payment_failed_count" icon="credit-card-remove-outline" />
            <SnapshotRow label="Low Stock SKUs" :value="stats.low_stock_products" icon="archive-alert-outline" />
            <SnapshotRow label="Dispatch Attention" :value="stats.dispatch_pending" icon="truck-alert-outline" />
          </div>
        </Card>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card title="Top Products" icon="star" :elevation="2">
          <TopProductsList :products="topProducts" />
        </Card>

        <Card title="Recent Orders" icon="receipt" :elevation="2">
          <RecentOrdersTable :orders="recentOrders" />
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../components/admin/AdminLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import SalesChart from '../components/admin/SalesChart.vue';
import TopProductsList from '../components/admin/TopProductsList.vue';
import RecentOrdersTable from '../components/admin/RecentOrdersTable.vue';
import { useSettingsStore } from '../stores/settings';

const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const loading = ref(false);
const period = ref(30);
const stats = ref({});
const salesData = ref([]);
const topProducts = ref([]);
const recentOrders = ref([]);

const periodOptions = [
  { label: '7 days', value: 7 },
  { label: '30 days', value: 30 },
  { label: '90 days', value: 90 },
  { label: '1 year', value: 365 },
];

const growthText = computed(() => {
  const growth = Number(stats.value.revenue_growth_percent || 0);
  return `${growth >= 0 ? '+' : ''}${growth}% vs previous period`;
});

const attentionItems = computed(() => [
  {
    label: 'Orders waiting',
    value: stats.value.pending_orders || 0,
    note: 'Move these into processing',
    class: 'border-amber-200 bg-amber-50 text-amber-800',
  },
  {
    label: 'Dispatch issues',
    value: stats.value.dispatch_pending || 0,
    note: 'Unassigned, rejected, or failed delivery',
    class: 'border-rose-200 bg-rose-50 text-rose-800',
  },
  {
    label: 'Low stock',
    value: stats.value.low_stock_products || 0,
    note: 'Restock before checkout friction',
    class: 'border-sky-200 bg-sky-50 text-sky-800',
  },
].filter((item) => Number(item.value) > 0));

const loadDashboard = async () => {
  loading.value = true;
  try {
    const [statsRes, salesRes, productsRes, ordersRes] = await Promise.all([
      axios.get('/api/admin/dashboard', { params: { period: period.value } }),
      axios.get('/api/admin/sales-data', { params: { period: Math.min(period.value, 30) } }),
      axios.get('/api/admin/top-products', { params: { period: period.value, limit: 8 } }),
      axios.get('/api/admin/recent-orders', { params: { limit: 8 } }),
    ]);

    stats.value = statsRes.data || {};
    salesData.value = salesRes.data || [];
    topProducts.value = productsRes.data || [];
    recentOrders.value = ordersRes.data || [];
  } finally {
    loading.value = false;
  }
};

const setPeriod = async (value) => {
  period.value = value;
  await loadDashboard();
};

const MetricCard = (props) => {
  const tones = {
    success: 'bg-emerald-50 text-emerald-700',
    info: 'bg-sky-50 text-sky-700',
    warning: 'bg-amber-50 text-amber-700',
    primary: 'bg-primary/10 text-primary',
  };

  return h(Card, { elevation: 2, class: 'p-5' }, () => h('div', { class: 'flex items-center justify-between gap-4' }, [
    h('div', [
      h('p', { class: 'text-sm text-secondary font-medium' }, props.title),
      h('p', { class: 'text-2xl font-bold text-primary mt-1' }, props.value),
      h('p', { class: 'text-xs text-secondary mt-2' }, props.meta),
    ]),
    h('div', { class: `h-12 w-12 rounded-lg flex items-center justify-center ${tones[props.tone]}` }, [
      h('i', { class: `mdi mdi-${props.icon} text-2xl` }),
    ]),
  ]));
};

const SnapshotRow = (props) => h('div', { class: 'flex items-center justify-between rounded-lg border border-DEFAULT p-3' }, [
  h('div', { class: 'flex items-center gap-2 text-primary' }, [
    h('i', { class: `mdi mdi-${props.icon}` }),
    h('span', { class: 'text-sm font-medium' }, props.label),
  ]),
  h('span', { class: 'text-lg font-bold text-primary' }, props.value || 0),
]);

onMounted(loadDashboard);
</script>
