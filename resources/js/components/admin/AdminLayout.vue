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
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
            <i class="mdi mdi-store text-white text-xl"></i>
          </div>
          <div>
            <h1 class="text-lg font-bold text-primary">Admin Panel</h1>
            <p class="text-xs text-secondary">E-commerce</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto p-4 space-y-1">
        <router-link
          v-for="item in navigation"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary hover:bg-primary/10 hover:text-primary transition-all group"
          active-class="bg-primary text-white hover:bg-primary hover:text-white"
        >
          <i :class="`mdi mdi-${item.icon} text-xl`"></i>
          <span class="font-medium">{{ item.label }}</span>
          <Badge v-if="item.badge" :variant="item.badgeVariant || 'primary'" size="sm" class="ml-auto">
            {{ item.badge }}
          </Badge>
        </router-link>
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
          <!-- Theme Selector -->
          <ThemeSelector />

          <!-- Notifications -->
          <button class="relative p-2 rounded-lg hover:bg-base transition-colors">
            <i class="mdi mdi-bell text-xl text-primary"></i>
            <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
          </button>

          <!-- Quick Actions -->
          <button class="hidden md:flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="mdi mdi-plus"></i>
            <span class="text-sm font-medium">New Product</span>
          </button>
        </div>
      </header>

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
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import Badge from '../ui/Badge.vue';
import ThemeSelector from '../ui/ThemeSelector.vue';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';

const router = useRouter();
const sidebarOpen = ref(false);
const authStore = useAuthStore();
const cartStore = useCartStore();

const displayName = computed(() => authStore.user?.name || 'Admin User');
const displayEmail = computed(() => authStore.user?.email || 'admin@example.com');
const userInitial = computed(() => (displayName.value || 'A').trim().charAt(0) || 'A');

const handleLogout = async () => {
  await authStore.logout();
  cartStore.$reset();
  router.push('/admin/login');
};

const navigation = [
  { path: '/admin/dashboard', icon: 'view-dashboard', label: 'Dashboard' },
  { path: '/admin/orders', icon: 'package-variant', label: 'Orders', badge: '12', badgeVariant: 'danger' },
  { path: '/admin/orders/create', icon: 'cart-plus', label: 'Create Order' },
  { path: '/admin/products', icon: 'tag-multiple', label: 'Products' },
  { path: '/admin/categories', icon: 'shape', label: 'Categories' },
  // { path: '/admin/vendors', icon: 'store', label: 'Vendors', badge: '3', badgeVariant: 'warning' },
  // { path: '/admin/zones', icon: 'map-marker-radius', label: 'Shipping Zones' },
  { path: '/admin/delivery-partners', icon: 'bike-fast', label: 'Delivery Partners' },
  { path: '/admin/dispatch-riders', icon: 'account-hard-hat', label: 'Dispatch Riders' },
  { path: '/admin/dispatch-time-slots', icon: 'clock-outline', label: 'Dispatch Slots' },
  { path: '/admin/operation-cities', icon: 'city', label: 'Operation Cities' },
  { path: '/admin/operation-areas', icon: 'map-marker', label: 'Operation Areas' },
  { path: '/admin/inventory', icon: 'archive-plus', label: 'Inventory' },
  { path: '/admin/inventory-ledger', icon: 'clipboard-list-outline', label: 'Inventory Ledger' },
  { path: '/admin/expiry-alerts', icon: 'calendar-alert', label: 'Expiry Alerts' },
  { path: '/admin/profit-margins', icon: 'chart-box-outline', label: 'Profit Margins' },
  { path: '/admin/analytics', icon: 'chart-line', label: 'Analytics' },
  { path: '/admin/payment-gateways', icon: 'credit-card-cog', label: 'Payment Gateways' },
  { path: '/admin/roles-permissions', icon: 'shield-account', label: 'Roles & Permissions' },
  { path: '/admin/settings', icon: 'cog-outline', label: 'Settings' },
];
</script>

