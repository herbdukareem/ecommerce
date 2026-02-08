<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Orders Management</h1>
          <p class="text-sm text-gray-600 mt-1">Track and manage customer orders</p>
        </div>
        <button
          @click="exportOrders"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          <i class="mdi mdi-download"></i>
          Export Orders
        </button>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Orders</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_orders }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-shopping text-blue-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pending</p>
              <p class="text-2xl font-bold text-yellow-600 mt-1">{{ stats.pending_orders }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-clock-outline text-yellow-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Processing</p>
              <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.processing_orders }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-package-variant text-blue-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Delivered</p>
              <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.delivered_orders }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-check-circle text-green-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Revenue</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ formatPrice(stats.total_revenue) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-currency-usd text-purple-600 text-2xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <div class="relative">
              <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by order ID or customer..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <select
            v-model="filters.status"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchOrders"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <!-- Payment Status Filter -->
          <select
            v-model="filters.payment_status"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchOrders"
          >
            <option value="">All Payments</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>

          <!-- Date Range -->
          <input
            v-model="filters.start_date"
            type="date"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchOrders"
          />
        </div>
      </div>

      <!-- Orders Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <i class="mdi mdi-loading mdi-spin text-4xl text-orange-500"></i>
          <p class="text-gray-600 mt-2">Loading orders...</p>
        </div>

        <div v-else-if="orders.length === 0" class="p-8 text-center">
          <i class="mdi mdi-cart-off text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-2">No orders found</p>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Order ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Items
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">#{{ order.id }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ order.user?.name }}</div>
                <div class="text-xs text-gray-500">{{ order.user?.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ order.items?.length || 0 }} items
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">₦{{ formatPrice(order.total) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="{
                    'bg-green-100 text-green-800': order.payment_status === 'paid',
                    'bg-yellow-100 text-yellow-800': order.payment_status === 'pending',
                    'bg-red-100 text-red-800': order.payment_status === 'failed',
                    'bg-gray-100 text-gray-800': order.payment_status === 'refunded',
                  }"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ order.payment_status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select
                  :value="order.status"
                  @change="updateOrderStatus(order, $event.target.value)"
                  :class="{
                    'bg-yellow-100 text-yellow-800 border-yellow-300': order.status === 'pending',
                    'bg-blue-100 text-blue-800 border-blue-300': order.status === 'processing',
                    'bg-purple-100 text-purple-800 border-purple-300': order.status === 'shipped',
                    'bg-green-100 text-green-800 border-green-300': order.status === 'delivered',
                    'bg-red-100 text-red-800 border-red-300': order.status === 'cancelled',
                  }"
                  class="px-2 py-1 text-xs font-medium rounded border focus:ring-2 focus:ring-orange-500"
                >
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="viewOrder(order)"
                  class="text-blue-600 hover:text-blue-900"
                  title="View Details"
                >
                  <i class="mdi mdi-eye"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} orders
        </div>
        <div class="flex gap-2">
          <button
            v-for="page in paginationPages"
            :key="page"
            @click="goToPage(page)"
            :class="{
              'bg-orange-600 text-white': page === pagination.current_page,
              'bg-white text-gray-700 hover:bg-gray-50': page !== pagination.current_page,
            }"
            class="px-3 py-1 border border-gray-300 rounded"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';

const router = useRouter();

const orders = ref([]);
const loading = ref(false);

const stats = ref({
  total_orders: 0,
  pending_orders: 0,
  processing_orders: 0,
  shipped_orders: 0,
  delivered_orders: 0,
  cancelled_orders: 0,
  total_revenue: 0,
});

const filters = ref({
  search: '',
  status: '',
  payment_status: '',
  start_date: '',
  end_date: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

// Fetch orders from API
const fetchOrders = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/orders', {
      params: {
        search: filters.value.search,
        status: filters.value.status,
        payment_status: filters.value.payment_status,
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      },
    });

    orders.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from,
      to: data.to,
    };
  } catch (error) {
    console.error('Failed to fetch orders:', error);
  } finally {
    loading.value = false;
  }
};

// Fetch statistics
const fetchStatistics = async () => {
  try {
    const { data } = await axios.get('/api/admin/orders/statistics');
    stats.value = data;
  } catch (error) {
    console.error('Failed to fetch statistics:', error);
  }
};

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    fetchOrders();
  }, 500);
};

// Update order status
const updateOrderStatus = async (order, newStatus) => {
  try {
    await axios.put(`/api/admin/orders/${order.id}/status`, {
      status: newStatus,
    });
    order.status = newStatus;
    fetchStatistics();
  } catch (error) {
    console.error('Failed to update order status:', error);
    alert('Failed to update order status. Please try again.');
  }
};

// View order details
const viewOrder = (order) => {
  router.push(`/admin/orders/${order.id}`);
};

// Export orders
const exportOrders = async () => {
  try {
    const { data } = await axios.get('/api/admin/orders/export', {
      params: filters.value,
    });

    // Create CSV and download
    const csv = convertToCSV(data.data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `orders-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
  } catch (error) {
    console.error('Failed to export orders:', error);
    alert('Failed to export orders. Please try again.');
  }
};

// Convert data to CSV
const convertToCSV = (data) => {
  const headers = ['Order ID', 'Customer', 'Email', 'Total', 'Status', 'Payment Status', 'Date'];
  const rows = data.map(order => [
    order.id,
    order.user?.name || '',
    order.user?.email || '',
    order.total,
    order.status,
    order.payment_status,
    new Date(order.created_at).toLocaleDateString(),
  ]);

  return [headers, ...rows].map(row => row.join(',')).join('\n');
};

// Pagination
const goToPage = (page) => {
  pagination.value.current_page = page;
  fetchOrders();
};

const paginationPages = computed(() => {
  const pages = [];
  const maxPages = 5;
  let start = Math.max(1, pagination.value.current_page - Math.floor(maxPages / 2));
  let end = Math.min(pagination.value.last_page, start + maxPages - 1);

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

// Format price
const formatPrice = (price) => {
  return new Intl.NumberFormat('en-NG').format(price);
};

// Format date
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Initialize
onMounted(() => {
  fetchOrders();
  fetchStatistics();
});
</script>


