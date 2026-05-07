<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-primary">Analytics</h1>
          <p class="text-secondary">Performance signals for revenue, orders, products, payments, and dispatch.</p>
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
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <Card :elevation="2" class="p-5">
          <p class="text-sm text-secondary">Revenue</p>
          <p class="text-2xl font-bold text-primary mt-1">{{ formatCurrency(dashboard.total_revenue) }}</p>
          <p class="text-xs text-secondary mt-2">{{ dashboard.revenue_growth_percent || 0 }}% vs previous period</p>
        </Card>
        <Card :elevation="2" class="p-5">
          <p class="text-sm text-secondary">Gross Margin</p>
          <p class="text-2xl font-bold text-primary mt-1">{{ dashboard.gross_margin_percent || 0 }}%</p>
          <p class="text-xs text-secondary mt-2">{{ formatCurrency(dashboard.gross_profit) }} gross profit</p>
        </Card>
        <Card :elevation="2" class="p-5">
          <p class="text-sm text-secondary">Order Conversion Health</p>
          <p class="text-2xl font-bold text-primary mt-1">{{ paidOrderRate }}%</p>
          <p class="text-xs text-secondary mt-2">{{ dashboard.payment_success_count || 0 }} paid, {{ dashboard.payment_failed_count || 0 }} failed</p>
        </Card>
        <Card :elevation="2" class="p-5">
          <p class="text-sm text-secondary">Dispatch Risk</p>
          <p class="text-2xl font-bold text-primary mt-1">{{ dashboard.dispatch_pending || 0 }}</p>
          <p class="text-xs text-secondary mt-2">Unassigned or delivery issue orders</p>
        </Card>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <Card title="Revenue Trend" icon="chart-line" :elevation="2" class="xl:col-span-8">
          <template #actions>
            <Select v-model="chartGroupBy" :options="groupOptions" @change="loadSalesData" />
          </template>
          <RevenueChart :data="salesData" :groupBy="chartGroupBy" />
        </Card>

        <Card title="Order Status Mix" icon="chart-donut" :elevation="2" class="xl:col-span-4">
          <OrderStatusChart :data="orderStatusData" />
        </Card>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card title="Top Selling Products" icon="star" :elevation="2">
          <TopProductsTable :products="topProducts" />
        </Card>

        <Card title="Management Watchlist" icon="clipboard-alert-outline" :elevation="2">
          <div class="space-y-3">
            <WatchRow label="Pending Orders" :value="dashboard.pending_orders" note="Need processing attention" />
            <WatchRow label="Low Stock SKUs" :value="dashboard.low_stock_products" note="Possible sales interruption" />
            <WatchRow label="Cancelled Orders" :value="dashboard.cancelled_orders" note="Track reason patterns" />
            <WatchRow label="Payment Failures" :value="dashboard.payment_failed_count" note="Gateway or customer friction" />
          </div>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useAnalyticsStore } from '../stores/analytics';
import { useSettingsStore } from '../stores/settings';
import AdminLayout from '../components/admin/AdminLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Select from '../components/ui/Select.vue';
import RevenueChart from '../components/analytics/RevenueChart.vue';
import OrderStatusChart from '../components/analytics/OrderStatusChart.vue';
import TopProductsTable from '../components/analytics/TopProductsTable.vue';

const analyticsStore = useAnalyticsStore();
const settingsStore = useSettingsStore();
const { dashboard, salesData, topProducts } = storeToRefs(analyticsStore);
const formatCurrency = settingsStore.formatCurrency;

const period = ref(30);
const chartGroupBy = ref('day');

const periodOptions = [
  { label: '7 days', value: 7 },
  { label: '30 days', value: 30 },
  { label: '90 days', value: 90 },
  { label: '1 year', value: 365 },
];

const groupOptions = [
  { value: 'day', label: 'Daily' },
  { value: 'week', label: 'Weekly' },
  { value: 'month', label: 'Monthly' },
];

const paidOrderRate = computed(() => {
  const success = Number(dashboard.value.payment_success_count || 0);
  const failed = Number(dashboard.value.payment_failed_count || 0);
  const total = success + failed;
  return total > 0 ? Math.round((success / total) * 100) : 0;
});

const orderStatusData = computed(() => {
  const breakdown = dashboard.value.order_status_breakdown || {};
  return Object.entries(breakdown).map(([status, count]) => ({
    status: status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
    count,
  }));
});

const setPeriod = async (newPeriod) => {
  period.value = newPeriod;
  await analyticsStore.loadAll(period.value);
};

const loadSalesData = () => {
  analyticsStore.loadSalesData(period.value, chartGroupBy.value);
};

const WatchRow = (props) => h('div', { class: 'rounded-lg border border-DEFAULT p-3 flex items-center justify-between gap-3' }, [
  h('div', [
    h('p', { class: 'text-sm font-semibold text-primary' }, props.label),
    h('p', { class: 'text-xs text-secondary' }, props.note),
  ]),
  h('p', { class: 'text-xl font-bold text-primary' }, props.value || 0),
]);

onMounted(() => {
  analyticsStore.loadAll(period.value);
});
</script>
