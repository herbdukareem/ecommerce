<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Operations command center</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Orders Management</h1>
            <p class="text-sm text-gray-600 mt-1">Track customer demand, ordered products, payment health, and fulfillment readiness.</p>
          </div>
          <button
            @click="exportOrders"
            class="flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
          >
            <i class="mdi mdi-download"></i>
            Export Orders
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
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
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
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
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
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
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
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
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Revenue</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(stats.total_revenue) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-currency-usd text-purple-600 text-2xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
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

          <Select
            v-model="filters.status"
            placeholder="All order statuses"
            :options="statusOptions"
            @change="fetchOrders"
          />

          <Select
            v-model="filters.payment_status"
            placeholder="All payment statuses"
            :options="paymentStatusOptions"
            @change="fetchOrders"
          />

          <Select
            v-model="filters.dispatch_status"
            placeholder="All dispatch statuses"
            :options="dispatchStatusOptions"
            @change="fetchOrders"
          />

          <input
            v-model="filters.start_date"
            type="date"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchOrders"
          />

          <input
            v-model="filters.end_date"
            type="date"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchOrders"
          />
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 text-sm">
          <p class="text-gray-500">Open an order to process fulfillment with full item visibility.</p>
          <button class="font-medium text-orange-600 hover:text-orange-700" @click="clearFilters">Clear filters</button>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div v-if="loading" class="p-8 text-center">
          <i class="mdi mdi-loading mdi-spin text-4xl text-orange-500"></i>
          <p class="text-gray-600 mt-2">Loading orders...</p>
        </div>

        <div v-else-if="orders.length === 0" class="p-8 text-center">
          <i class="mdi mdi-cart-off text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-2">No orders found</p>
        </div>

        <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[1120px]">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Order
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ordered Items
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fulfillment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Dispatch
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">#{{ order.id }}</div>
                <div class="mt-1 text-xs text-gray-500">{{ formatDate(order.placed_at || order.created_at) }}</div>
                <div v-if="order.created_by_admin" class="mt-1 text-xs text-gray-500">By {{ order.created_by_admin.name }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900">{{ order.user?.name || 'Guest customer' }}</div>
                <div class="text-xs text-gray-500">{{ order.user?.email || 'No email' }}</div>
                <div class="text-xs text-gray-500">{{ order.city_name || order.city?.name || 'N/A' }}<span v-if="order.area_name || order.area?.name">, {{ order.area_name || order.area?.name }}</span></div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div v-for="item in visibleOrderItems(order)" :key="item.id || `${order.id}-${itemProductName(item)}`" class="flex items-center gap-3">
                    <img
                      :src="itemImage(item)"
                      :alt="itemProductName(item)"
                      class="h-10 w-10 rounded-lg border border-gray-200 object-cover bg-gray-50"
                      @error="onImageError"
                    />
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-gray-900">{{ itemProductName(item) }}</p>
                      <p class="text-xs text-gray-500">
                        {{ itemOptionLabel(item) }} · Qty {{ Number(item.quantity || 0) }}
                      </p>
                    </div>
                  </div>
                  <p v-if="remainingItemCount(order) > 0" class="text-xs font-medium text-orange-600">
                    +{{ remainingItemCount(order) }} more product{{ remainingItemCount(order) === 1 ? '' : 's' }}
                  </p>
                  <p v-if="!(order.items || []).length" class="text-sm text-gray-500">No order items loaded</p>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(order.total) }}</div>
                <div class="text-xs text-gray-500">{{ totalQuantity(order) }} unit{{ totalQuantity(order) === 1 ? '' : 's' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="paymentClass(order.payment_status)" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                  {{ humanize(order.payment_status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap min-w-[190px]">
                <select
                  :value="order.status"
                  @change="updateOrderStatus(order, $event.target.value)"
                  :class="{
                    'bg-yellow-100 text-yellow-800 border-yellow-300': order.status === 'pending',
                    'bg-blue-100 text-blue-800 border-blue-300': order.status === 'processing',
                    'bg-indigo-100 text-indigo-800 border-indigo-300': ['packed', 'ready_for_dispatch'].includes(order.status),
                    'bg-purple-100 text-purple-800 border-purple-300': order.status === 'shipped',
                    'bg-green-100 text-green-800 border-green-300': order.status === 'delivered',
                    'bg-red-100 text-red-800 border-red-300': order.status === 'cancelled',
                  }"
                  class="px-2 py-1 text-xs font-medium rounded border focus:ring-2 focus:ring-orange-500"
                >
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="packed">Packed</option>
                  <option value="ready_for_dispatch">Ready for Dispatch</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex flex-col gap-1">
                  <span :class="dispatchClass(order.delivery_status)" class="w-fit px-2.5 py-1 text-xs font-semibold rounded-full">
                    {{ humanize(order.delivery_status || 'pending_assignment') }}
                  </span>
                  <span class="text-xs text-gray-500">{{ order.dispatch_rider?.user?.name || order.delivery_partner?.name || 'Unassigned' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="viewOrder(order)"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700 hover:bg-blue-100"
                  title="View order workspace"
                >
                  <i class="mdi mdi-eye"></i>
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
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
import Select from '../../components/ui/Select.vue';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

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
  dispatch_status: '',
  start_date: '',
  end_date: '',
});

const statusOptions = [
  { value: '', label: 'All order statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'packed', label: 'Packed' },
  { value: 'ready_for_dispatch', label: 'Ready for Dispatch' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
];

const paymentStatusOptions = [
  { value: '', label: 'All payment statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
  { value: 'refunded', label: 'Refunded' },
];

const dispatchStatusOptions = [
  { value: '', label: 'All dispatch statuses' },
  { value: 'pending_assignment', label: 'Pending Assignment' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'picked_up', label: 'Picked Up' },
  { value: 'in_transit', label: 'In Transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'delivery_failed', label: 'Delivery Failed' },
  { value: 'cancelled', label: 'Cancelled' },
];

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
        dispatch_status: filters.value.dispatch_status,
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

const clearFilters = () => {
  filters.value = {
    search: '',
    status: '',
    payment_status: '',
    dispatch_status: '',
    start_date: '',
    end_date: '',
  };
  pagination.value.current_page = 1;
  fetchOrders();
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

const placeholderImage = '/images/placeholders/product-placeholder.svg';

const humanize = (value) => String(value || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const visibleOrderItems = (order) => (order.items || []).slice(0, 2);

const remainingItemCount = (order) => Math.max((order.items || []).length - visibleOrderItems(order).length, 0);

const totalQuantity = (order) => (order.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0);

const itemProductName = (item) => item?.product_name_snapshot || item?.sku?.product?.title || 'Product';

const itemOptionLabel = (item) => item?.option_label_snapshot || item?.product_option?.display_label || item?.sku?.display_label || item?.sku?.code || 'Standard';

const itemImage = (item) => item?.image_snapshot || item?.sku?.product?.image || item?.sku?.product?.images?.[0]?.image_url || placeholderImage;

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = placeholderImage;
  }
};

const paymentClass = (status) => ({
  paid: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  failed: 'bg-red-100 text-red-800',
  refunded: 'bg-gray-100 text-gray-800',
}[status] || 'bg-gray-100 text-gray-800');

const dispatchClass = (status) => ({
  delivered: 'bg-green-100 text-green-800',
  in_transit: 'bg-blue-100 text-blue-800',
  picked_up: 'bg-indigo-100 text-indigo-800',
  accepted: 'bg-cyan-100 text-cyan-800',
  assigned: 'bg-purple-100 text-purple-800',
  pending_assignment: 'bg-yellow-100 text-yellow-800',
  delivery_failed: 'bg-red-100 text-red-800',
  cancelled: 'bg-red-100 text-red-800',
}[status] || 'bg-gray-100 text-gray-800');

// Format date
const formatDate = (date) => {
  if (!date) return 'N/A';
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


