<template>
  <MainLayout>
    <section class="max-w-7xl mx-auto px-4 py-8 sm:py-10 space-y-6 bg-gray-50/60 min-h-[70vh]">
      <OrdersHeader @continue-shopping="goShopping" />

      <OrdersFilterBar
        :status="filters.status"
        :payment-status="filters.payment_status"
        :date-from="filters.date_from"
        :date-to="filters.date_to"
        :search="filters.search"
        :loading="ordersStore.loading"
        @update:status="filters.status = $event"
        @update:payment-status="filters.payment_status = $event"
        @update:date-from="filters.date_from = $event"
        @update:date-to="filters.date_to = $event"
        @update:search="filters.search = $event"
        @apply="applyFilters"
        @refresh="loadOrders"
      />

      <Card :elevation="1" class="p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
          <p class="text-secondary">
            Showing <span class="font-semibold text-primary">{{ displayedCount }}</span> of
            <span class="font-semibold text-primary">{{ totalOrders }}</span> orders
          </p>

          <Button v-if="hasActiveClientFilters" variant="ghost" size="sm" icon="close" @click="clearClientFilters">
            Clear Search/Date
          </Button>
        </div>
      </Card>

      <div v-if="ordersStore.loading" class="space-y-3">
        <Card v-for="index in 4" :key="`skeleton-${index}`" :elevation="1" class="p-5 animate-pulse">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-2 w-full lg:w-1/2">
              <div class="h-3 w-24 rounded bg-base"></div>
              <div class="h-3 w-56 rounded bg-base"></div>
              <div class="h-7 w-40 rounded bg-base"></div>
            </div>
            <div class="space-y-2 w-full lg:w-1/3">
              <div class="h-6 w-full rounded bg-base"></div>
              <div class="h-8 w-full rounded bg-base"></div>
            </div>
          </div>
        </Card>
      </div>

      <div v-else-if="displayedOrders.length === 0">
        <EmptyOrdersState @start-shopping="goShopping" />
      </div>

      <div v-else class="space-y-3">
        <OrderCard
          v-for="order in displayedOrders"
          :key="order.id"
          :order="order"
          :format-currency="formatCurrency"
          :format-date="formatDate"
          @view="openOrder"
          @reorder="reorder"
          @track="trackOrder"
        />
      </div>

      <Card v-if="!ordersStore.loading && totalPages > 1" :elevation="1" class="p-4">
        <Pagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="totalOrders"
          :per-page="perPage"
          @page-change="changePage"
        />
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Pagination from '../components/ui/Pagination.vue';
import OrdersHeader from '../components/orders/list/OrdersHeader.vue';
import OrdersFilterBar from '../components/orders/list/OrdersFilterBar.vue';
import OrderCard from '../components/orders/list/OrderCard.vue';
import EmptyOrdersState from '../components/orders/list/EmptyOrdersState.vue';
import { useOrdersStore } from '../stores/orders';
import { useSettingsStore } from '../stores/settings';

const router = useRouter();
const ordersStore = useOrdersStore();
const settingsStore = useSettingsStore();

const filters = reactive({
  status: ordersStore.filters.status,
  payment_status: ordersStore.filters.payment_status,
  search: '',
  date_from: '',
  date_to: '',
});

const orders = computed(() => ordersStore.orders || []);
const formatCurrency = settingsStore.formatCurrency;

const currentPage = computed(() => Number(ordersStore.pagination?.current_page || ordersStore.filters.page || 1));
const totalPages = computed(() => Number(ordersStore.pagination?.last_page || 1));
const perPage = computed(() => Number(ordersStore.pagination?.per_page || 20));
const totalOrders = computed(() => Number(ordersStore.pagination?.total || orders.value.length || 0));

const formatDate = (value) => {
  if (!value) return 'N/A';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return 'N/A';
  }

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
};

const filteredByClient = computed(() => {
  return orders.value.filter((order) => {
    const search = String(filters.search || '').trim();
    if (search && !String(order.id).includes(search)) {
      return false;
    }

    const created = new Date(order.placed_at || order.created_at || '');
    if (filters.date_from && !Number.isNaN(created.getTime())) {
      const from = new Date(filters.date_from);
      from.setHours(0, 0, 0, 0);
      if (created < from) {
        return false;
      }
    }

    if (filters.date_to && !Number.isNaN(created.getTime())) {
      const to = new Date(filters.date_to);
      to.setHours(23, 59, 59, 999);
      if (created > to) {
        return false;
      }
    }

    return true;
  });
});

const displayedOrders = computed(() => filteredByClient.value);
const displayedCount = computed(() => displayedOrders.value.length);
const hasActiveClientFilters = computed(() => !!(filters.search || filters.date_from || filters.date_to));

const applyFilters = async () => {
  ordersStore.setFilter('status', filters.status || null);
  ordersStore.setFilter('payment_status', filters.payment_status || null);
  await ordersStore.fetchOrders();
};

const loadOrders = async () => {
  await ordersStore.fetchOrders();
};

const changePage = async (page) => {
  ordersStore.setPage(page);
  await ordersStore.fetchOrders();
};

const clearClientFilters = () => {
  filters.search = '';
  filters.date_from = '';
  filters.date_to = '';
};

const goShopping = () => {
  router.push('/products');
};

const openOrder = (order) => {
  router.push(`/orders/${order.id}`);
};

const reorder = () => {
  router.push('/products');
};

const trackOrder = (order) => {
  router.push(`/orders/${order.id}`);
};

onMounted(loadOrders);
</script>
