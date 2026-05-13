<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
      class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transition-transform duration-300 ease-in-out"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center justify-between px-6 border-b border-DEFAULT">
        <div class="flex items-center gap-3">
          <img
            v-if="siteLogoUrl"
            :src="siteLogoUrl"
            :alt="`${siteName} logo`"
            class="h-11 w-11 rounded-lg object-contain bg-white"
          />
          <div v-else class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
            <i class="mdi mdi-store text-white text-xl"></i>
          </div>
          <div>
            <h1 class="text-lg font-bold text-primary">{{ siteName }}</h1>
            <p class="text-xs text-secondary">Admin Panel</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto p-4 space-y-2">
        <div v-for="group in navigation" :key="group.key || group.path">
          <router-link
            v-if="!group.children"
            :to="group.path"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-secondary transition-all hover:bg-primary/10 hover:text-primary"
            :class="isNavItemActive(group) ? 'bg-primary text-white hover:bg-primary hover:text-white' : ''"
            @click="sidebarOpen = false"
          >
            <i :class="`mdi mdi-${group.icon} text-xl`"></i>
            <span class="font-medium">{{ group.label }}</span>
            <Badge v-if="group.badge" :variant="group.badgeVariant || 'primary'" size="sm" class="ml-auto">
              {{ group.badge }}
            </Badge>
          </router-link>

          <div v-else>
            <button
              type="button"
              class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-secondary transition-all hover:bg-primary/10 hover:text-primary"
              :class="isGroupActive(group) ? 'bg-primary/10 text-primary' : ''"
              @click="toggleGroup(group.key)"
            >
              <i :class="`mdi mdi-${group.icon} text-xl`"></i>
              <span class="flex-1 font-medium">{{ group.label }}</span>
              <i
                class="mdi mdi-chevron-down text-lg transition-transform"
                :class="{ 'rotate-180': openGroups[group.key] }"
              ></i>
            </button>

            <div v-show="openGroups[group.key]" class="mt-1 space-y-1 pl-3">
              <router-link
                v-for="item in group.children"
                :key="item.path"
                :to="item.path"
                class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm text-secondary transition-all hover:bg-primary/10 hover:text-primary"
                :class="isNavItemActive(item) ? 'bg-primary text-white hover:bg-primary hover:text-white' : ''"
                @click="sidebarOpen = false"
              >
                <i :class="`mdi mdi-${item.icon} text-lg`"></i>
                <span class="font-medium">{{ item.label }}</span>
                <Badge v-if="item.badge" :variant="item.badgeVariant || 'primary'" size="sm" class="ml-auto">
                  {{ item.badge }}
                </Badge>
              </router-link>
            </div>
          </div>
        </div>
      </nav>

      <!-- User Profile -->
      <div class="p-4 border-t border-DEFAULT">
        <div class="flex items-center gap-3 p-3 rounded-lg bg-base transition-colors">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-semibold uppercase">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-primary truncate">{{ displayName }}</p>
            <p class="text-xs text-secondary truncate">{{ displayEmail }}</p>
          </div>
          <button
            @click="handleLogout"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-danger hover:bg-danger/10 transition-colors"
            title="Logout"
          >
            <i class="mdi mdi-logout"></i>
            <span>Logout</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Bar -->
      <header class="h-16 bg-surface border-b border-DEFAULT flex items-center justify-between px-6 sticky top-0 z-40">
        <div class="flex items-center gap-4">
          <!-- Mobile Menu Toggle -->
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden p-2 rounded-lg hover:bg-base transition-colors"
          >
            <i class="mdi mdi-menu text-xl text-primary"></i>
          </button>

          <!-- Search -->
          <div class="hidden md:flex items-center gap-2 bg-base rounded-lg px-4 py-2 w-96">
            <i class="mdi mdi-magnify text-secondary"></i>
            <input
              type="text"
              placeholder="Search products, orders, customers..."
              class="bg-transparent border-none outline-none text-sm text-primary placeholder-secondary flex-1"
            />
            <kbd class="hidden lg:inline-block px-2 py-1 text-xs bg-surface rounded border border-DEFAULT text-secondary">
              Ctrl K
            </kbd>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Notifications -->
          <div class="relative">
          <button class="relative p-2 rounded-lg hover:bg-base transition-colors" @click="showStockAlerts = !showStockAlerts">
            <i class="mdi mdi-bell text-xl text-primary"></i>
            <span
              v-if="totalStockAlerts > 0"
              class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-danger px-1 text-center text-xs font-semibold text-white"
            >
              {{ totalStockAlerts > 9 ? '9+' : totalStockAlerts }}
            </span>
          </button>

          <div
            v-if="showStockAlerts"
            class="absolute right-0 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-lg"
          >
            <div class="flex items-center justify-between">
              <p class="font-semibold text-gray-900">Stock alerts</p>
              <router-link to="/admin/inventory" class="text-xs font-medium text-primary" @click="showStockAlerts = false">
                Restock
              </router-link>
            </div>
            <div v-if="stockAlertItems.length === 0" class="mt-4 text-sm text-gray-500">No out-of-stock products.</div>
            <div v-else class="mt-4 max-h-72 space-y-3 overflow-y-auto">
              <div v-for="item in stockAlertItems" :key="item.sku_id" class="rounded-md border border-gray-100 p-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ item.product_title }}</p>
                    <p class="truncate text-xs text-gray-500">{{ item.option_label || item.sku_code }}</p>
                  </div>
                  <span
                    class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="item.status === 'out_of_stock' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                  >
                    {{ item.status === 'out_of_stock' ? 'Out' : 'Low' }}
                  </span>
                </div>
                <p class="mt-2 text-xs text-gray-600">{{ item.available_stock }} available</p>
              </div>
            </div>
          </div>
          </div>

          <!-- Quick Actions -->
          <button class="hidden md:flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="mdi mdi-plus"></i>
            <span class="text-sm font-medium">New Product</span>
          </button>
        </div>
      </header>

      <div v-if="stockAlerts.out_of_stock_count > 0" class="border-b border-red-200 bg-red-50 px-6 py-3">
        <div class="flex flex-col gap-2 text-sm text-red-800 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-2">
            <i class="mdi mdi-alert-circle-outline text-lg"></i>
            <span>
              {{ stockAlerts.out_of_stock_count }} product option{{ stockAlerts.out_of_stock_count === 1 ? '' : 's' }} out of stock. Restock now to keep storefront sales moving.
            </span>
          </div>
          <router-link to="/admin/inventory" class="font-semibold text-red-900 hover:underline">
            Open Inventory
          </router-link>
        </div>
      </div>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
    ></div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import Badge from '../ui/Badge.vue';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const route = useRoute();
const sidebarOpen = ref(false);
const showStockAlerts = ref(false);
const stockAlerts = ref({
  out_of_stock_count: 0,
  low_stock_count: 0,
  items: [],
});
const openGroups = reactive({});
const authStore = useAuthStore();
const cartStore = useCartStore();
const settingsStore = useSettingsStore();

const siteName = computed(() => settingsStore.siteName || 'Online Mart');
const siteLogoUrl = computed(() => settingsStore.siteLogoUrl || '');
const displayName = computed(() => authStore.user?.name || 'Admin User');
const displayEmail = computed(() => authStore.user?.email || 'admin@example.com');
const userInitial = computed(() => (displayName.value || 'A').trim().charAt(0) || 'A');
const stockAlertItems = computed(() => stockAlerts.value.items || []);
const totalStockAlerts = computed(() => Number(stockAlerts.value.out_of_stock_count || 0) + Number(stockAlerts.value.low_stock_count || 0));

const handleLogout = async () => {
  await authStore.logout();
  cartStore.$reset();
  router.push('/admin/login');
};

const loadStockAlerts = async () => {
  if (!authStore.isAdmin) {
    return;
  }

  try {
    const { data } = await axios.get('/api/admin/stock-alerts');
    stockAlerts.value = {
      out_of_stock_count: Number(data.out_of_stock_count || 0),
      low_stock_count: Number(data.low_stock_count || 0),
      items: data.items || [],
    };
  } catch (error) {
    stockAlerts.value = {
      out_of_stock_count: 0,
      low_stock_count: 0,
      items: [],
    };
  }
};

const navigation = [
  { path: '/admin/dashboard', icon: 'view-dashboard', label: 'Dashboard' },
  {
    key: 'sales',
    icon: 'receipt-text-outline',
    label: 'Sales',
    children: [
      { path: '/admin/orders', icon: 'package-variant', label: 'Orders', badge: '12', badgeVariant: 'danger', exact: true },
      { path: '/admin/orders/create', icon: 'cart-plus', label: 'Create Order' },
    ],
  },
  {
    key: 'catalog',
    icon: 'storefront-outline',
    label: 'Catalog',
    children: [
      { path: '/admin/products', icon: 'tag-multiple', label: 'Products' },
      { path: '/admin/categories', icon: 'shape', label: 'Categories' },
      { path: '/admin/inventory', icon: 'archive-plus', label: 'Inventory', exact: true },
      { path: '/admin/inventory-ledger', icon: 'clipboard-list-outline', label: 'Inventory Ledger' },
      { path: '/admin/expiry-alerts', icon: 'calendar-alert', label: 'Expiry Alerts' },
    ],
  },
  {
    key: 'logistics',
    icon: 'truck-delivery-outline',
    label: 'Logistics',
    children: [
      { path: '/admin/delivery-partners', icon: 'bike-fast', label: 'Delivery Partners' },
      { path: '/admin/dispatch-riders', icon: 'account-hard-hat', label: 'Dispatch Riders' },
      { path: '/admin/dispatch-time-slots', icon: 'clock-outline', label: 'Dispatch Slots' },
      { path: '/admin/operation-cities', icon: 'city', label: 'Operation Cities' },
      { path: '/admin/operation-areas', icon: 'map-marker', label: 'Operation Areas' },
    ],
  },
  {
    key: 'marketing',
    icon: 'bullhorn-outline',
    label: 'Marketing',
    children: [
      { path: '/admin/newsletter-subscribers', icon: 'email-newsletter', label: 'Newsletter' },
      { path: '/admin/referrals', icon: 'account-multiple-plus', label: 'Referrals' },
    ],
  },
  {
    key: 'reports',
    icon: 'chart-line',
    label: 'Reports',
    children: [
      { path: '/admin/analytics', icon: 'chart-line', label: 'Analytics' },
      { path: '/admin/profit-margins', icon: 'chart-box-outline', label: 'Profit Margins' },
    ],
  },
  {
    key: 'administration',
    icon: 'cog-outline',
    label: 'Administration',
    children: [
      { path: '/admin/payment-gateways', icon: 'credit-card-cog', label: 'Payment Gateways' },
      { path: '/admin/roles-permissions', icon: 'shield-account', label: 'Roles & Permissions' },
      { path: '/admin/settings', icon: 'cog-outline', label: 'Settings' },
    ],
  },
];

const isNavItemActive = (item) => {
  if (route.path === item.path) {
    return true;
  }

  return !item.exact && route.path.startsWith(`${item.path}/`);
};

const isGroupActive = (group) => group.children?.some(isNavItemActive);

const toggleGroup = (key) => {
  openGroups[key] = !openGroups[key];
};

watch(
  () => route.path,
  () => {
    navigation.forEach((group) => {
      if (group.children && isGroupActive(group)) {
        openGroups[group.key] = true;
      }
    });
  },
  { immediate: true }
);

onMounted(loadStockAlerts);
</script>

