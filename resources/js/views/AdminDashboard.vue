<template>
  <AdminLayout>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 animate-fade-in-down">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Dashboard</h1>
        <p class="text-secondary">Welcome back! Here's what's happening today.</p>
      </div>
    </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card
          :elevation="2"
          hoverable
          animation="fade-in-up"
          class="stagger-1"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Total Revenue</p>
              <p class="text-3xl font-bold text-primary">{{ formatCurrency(stats.total_revenue) }}</p>
              <p class="text-xs text-success mt-2 flex items-center gap-1">
                <i class="mdi mdi-trending-up"></i>
                <span>+12.5% from last month</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-success/10 flex items-center justify-center">
              <i class="mdi mdi-cash-multiple text-2xl text-success"></i>
            </div>
          </div>
        </Card>

        <Card
          :elevation="2"
          hoverable
          animation="fade-in-up"
          class="stagger-2"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Total Orders</p>
              <p class="text-3xl font-bold text-primary">{{ stats.total_orders }}</p>
              <p class="text-xs text-info mt-2 flex items-center gap-1">
                <i class="mdi mdi-trending-up"></i>
                <span>+8.2% from last month</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-info/10 flex items-center justify-center">
              <i class="mdi mdi-package-variant text-2xl text-info"></i>
            </div>
          </div>
        </Card>

        <Card
          :elevation="2"
          hoverable
          animation="fade-in-up"
          class="stagger-3"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Pending Orders</p>
              <p class="text-3xl font-bold text-primary">{{ stats.pending_orders }}</p>
              <p class="text-xs text-warning mt-2 flex items-center gap-1">
                <i class="mdi mdi-clock-outline"></i>
                <span>Requires attention</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-warning/10 flex items-center justify-center">
              <i class="mdi mdi-clock-alert-outline text-2xl text-warning"></i>
            </div>
          </div>
        </Card>

        <Card
          :elevation="2"
          hoverable
          animation="fade-in-up"
          class="stagger-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary font-medium mb-1">Total Customers</p>
              <p class="text-3xl font-bold text-primary">{{ stats.total_customers }}</p>
              <p class="text-xs text-primary mt-2 flex items-center gap-1">
                <i class="mdi mdi-trending-up"></i>
                <span>+15.3% from last month</span>
              </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
              <i class="mdi mdi-account-group text-2xl text-primary"></i>
            </div>
          </div>
        </Card>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <Card
          title="Sales Overview"
          icon="chart-line"
          :elevation="2"
          animation="fade-in-up"
          class="stagger-5"
        >
          <SalesChart :data="salesData" />
        </Card>

        <Card
          title="Top Products"
          icon="star"
          :elevation="2"
          animation="fade-in-up"
          class="stagger-6"
        >
          <TopProductsList :products="topProducts" />
        </Card>
      </div>

      <!-- Recent Orders -->
      <Card
        title="Recent Orders"
        icon="receipt"
        :elevation="2"
        animation="fade-in-up"
        class="stagger-7"
      >
        <template #actions>
          <Button variant="ghost" size="sm" icon="refresh">
            Refresh
          </Button>
        </template>
        <RecentOrdersTable :orders="recentOrders" />
      </Card>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AdminLayout from '../components/admin/AdminLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import SalesChart from '../components/admin/SalesChart.vue';
import TopProductsList from '../components/admin/TopProductsList.vue';
import RecentOrdersTable from '../components/admin/RecentOrdersTable.vue';

const stats = ref({
  total_revenue: 0,
  total_orders: 0,
  pending_orders: 0,
  total_customers: 0,
});

const salesData = ref([]);
const topProducts = ref([]);
const recentOrders = ref([]);

const loadDashboard = async () => {
  try {
    const [statsRes, salesRes, productsRes, ordersRes] = await Promise.all([
      axios.get('/api/admin/dashboard'),
      axios.get('/api/admin/sales-data'),
      axios.get('/api/admin/top-products'),
      axios.get('/api/admin/recent-orders'),
    ]);

    stats.value = statsRes.data;
    salesData.value = salesRes.data;
    topProducts.value = productsRes.data;
    recentOrders.value = ordersRes.data;
  } catch (error) {
    console.error('Failed to load dashboard:', error);
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value || 0);
};

onMounted(() => {
  loadDashboard();
});
</script>

