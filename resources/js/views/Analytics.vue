<template>
  <div class="min-h-screen bg-base transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 animate-fade-in-down">
        <div>
          <h1 class="text-4xl font-bold text-primary mb-2">Analytics & Reports</h1>
          <p class="text-secondary">Track your business performance and insights</p>
        </div>

        <!-- Period Selector -->
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="option in periodOptions"
            :key="option.value"
            @click="setPeriod(option.value)"
            :variant="period === option.value ? 'primary' : 'ghost'"
            size="sm"
          >
            {{ option.label }}
          </Button>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-1">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Total Revenue</p>
              <p class="text-3xl font-bold text-primary">{{ formattedRevenue }}</p>
              <p class="text-xs mt-2 flex items-center gap-1" :class="revenueGrowth >= 0 ? 'text-success' : 'text-danger'">
                <i :class="`mdi mdi-trending-${revenueGrowth >= 0 ? 'up' : 'down'}`"></i>
                <span>{{ revenueGrowth }}% from last period</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-success/10 flex items-center justify-center">
              <i class="mdi mdi-cash-multiple text-2xl text-success"></i>
            </div>
          </div>
        </Card>

        <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-2">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Total Orders</p>
              <p class="text-3xl font-bold text-primary">{{ dashboard.total_orders }}</p>
              <p class="text-xs text-info mt-2 flex items-center gap-1">
                <i class="mdi mdi-package-variant"></i>
                <span>All time</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-info/10 flex items-center justify-center">
              <i class="mdi mdi-package-variant text-2xl text-info"></i>
            </div>
          </div>
        </Card>

        <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Avg Order Value</p>
              <p class="text-3xl font-bold text-primary">{{ formattedAverageOrderValue }}</p>
              <p class="text-xs text-primary mt-2 flex items-center gap-1">
                <i class="mdi mdi-credit-card"></i>
                <span>Per order</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
              <i class="mdi mdi-credit-card text-2xl text-primary"></i>
            </div>
          </div>
        </Card>

        <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Customers</p>
              <p class="text-3xl font-bold text-primary">{{ dashboard.total_customers }}</p>
              <p class="text-xs text-secondary mt-2 flex items-center gap-1">
                <i class="mdi mdi-account-group"></i>
                <span>Total registered</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-secondary/10 flex items-center justify-center">
              <i class="mdi mdi-account-group text-2xl text-secondary"></i>
            </div>
          </div>
        </Card>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Chart -->
        <Card
          title="Revenue Trend"
          icon="chart-line"
          :elevation="2"
          animation="fade-in-up"
          class="lg:col-span-2 stagger-5"
        >
          <template #actions>
            <select
              v-model="chartGroupBy"
              @change="loadSalesData"
              class="border border-DEFAULT rounded-lg px-3 py-2 text-sm bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
            >
              <option value="day">Daily</option>
              <option value="week">Weekly</option>
              <option value="month">Monthly</option>
            </select>
          </template>
          <RevenueChart :data="salesData" :groupBy="chartGroupBy" />
        </Card>

        <!-- Order Status Distribution -->
        <Card
          title="Order Status"
          icon="chart-donut"
          :elevation="2"
          animation="fade-in-up"
          class="stagger-6"
        >
          <OrderStatusChart :data="orderStatusData" />
        </Card>
      </div>

      <!-- Top Products & Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <Card
          title="Top Selling Products"
          icon="star"
          :elevation="2"
          animation="fade-in-up"
          class="stagger-7"
        >
          <TopProductsTable :products="topProducts" />
        </Card>

        <Card
          title="Performance Metrics"
          icon="speedometer"
          :elevation="2"
          animation="fade-in-up"
          class="stagger-8"
        >
          <div class="space-y-4">
            <PerformanceMetric
              label="Low Stock Products"
              :value="dashboard.low_stock_products"
              :alert="dashboard.low_stock_products > 0"
            />
            <PerformanceMetric
              label="Pending Reviews"
              :value="dashboard.pending_reviews"
            />
            <PerformanceMetric
              label="Pending Orders"
              :value="dashboard.pending_orders"
              :alert="dashboard.pending_orders > 10"
            />
          </div>
        </Card>
      </div>

      <!-- Export Options -->
      <Card
        title="Export Reports"
        icon="download"
        :elevation="2"
        animation="fade-in-up"
        class="stagger-9"
      >
        <div class="flex flex-wrap gap-4">
          <Button
            @click="exportReport('csv')"
            variant="success"
            icon="file-delimited"
          >
            Export as CSV
          </Button>
          <Button
            @click="exportReport('pdf')"
            variant="danger"
            icon="file-pdf-box"
          >
            Export as PDF
          </Button>
          <Button
            @click="exportReport('excel')"
            variant="info"
            icon="file-excel"
          >
            Export as Excel
          </Button>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAnalyticsStore } from '../stores/analytics';
import { storeToRefs } from 'pinia';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import RevenueChart from '../components/analytics/RevenueChart.vue';
import OrderStatusChart from '../components/analytics/OrderStatusChart.vue';
import TopProductsTable from '../components/analytics/TopProductsTable.vue';
import PerformanceMetric from '../components/analytics/PerformanceMetric.vue';

const analyticsStore = useAnalyticsStore();
const { dashboard, salesData, topProducts, loading } = storeToRefs(analyticsStore);
const { formattedRevenue, formattedAverageOrderValue, revenueGrowth } = storeToRefs(analyticsStore);

const period = ref(30);
const chartGroupBy = ref('day');

const periodOptions = [
  { label: '7 Days', value: 7 },
  { label: '30 Days', value: 30 },
  { label: '90 Days', value: 90 },
  { label: '1 Year', value: 365 },
];

const orderStatusData = computed(() => {
  // This would come from an API endpoint in a real implementation
  return [
    { status: 'Pending', count: dashboard.value.pending_orders || 0 },
    { status: 'Processing', count: 0 },
    { status: 'Shipped', count: 0 },
    { status: 'Delivered', count: 0 },
  ];
});

const setPeriod = (newPeriod) => {
  period.value = newPeriod;
  analyticsStore.setPeriod(newPeriod);
};

const loadSalesData = () => {
  analyticsStore.loadSalesData(period.value, chartGroupBy.value);
};

const exportReport = (format) => {
  // In a real implementation, this would call an API endpoint to generate the report
  alert(`Exporting report as ${format.toUpperCase()}...`);
  // Example: window.location.href = `/api/admin/export-report?format=${format}&period=${period.value}`;
};

onMounted(() => {
  analyticsStore.loadAll(period.value);
});
</script>
