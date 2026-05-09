<template>
  <div class="sticky top-0 z-40">
    <!-- Flash Banner -->
    <FlashBanner />

    <!-- Main Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <router-link to="/" class="flex items-center">
          <img
            v-if="siteLogoUrl"
            :src="siteLogoUrl"
            :alt="`${siteName} logo`"
            class="h-12 w-auto max-w-[160px] object-contain"
          />
          <div v-else class="text-2xl font-bold text-primary">
            {{ siteName }}
          </div>
        </router-link>

        <!-- Search Bar (Desktop) -->
        <form class="hidden md:flex flex-1 max-w-2xl mx-8" @submit.prevent="submitSearch">
          <div class="relative w-full flex">
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search products, brands and categories"
              class="flex-1 px-4 py-2.5 border border-gray-300 rounded-l-md text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary"
            />
            <button
              type="submit"
              class="px-6 bg-primary text-white rounded-r-md hover:bg-primary-dark transition-colors"
              aria-label="Search products"
            >
              <i class="mdi mdi-magnify text-xl"></i>
            </button>
          </div>
        </form>

        <!-- Navigation -->
        <nav class="flex items-center gap-4">
          <!-- Help -->
          <button class="hidden lg:flex items-center gap-1 text-sm text-gray-700 hover:text-primary transition-colors">
            <i class="mdi mdi-help-circle-outline text-lg"></i>
            <span>Help</span>
          </button>

          <!-- Account -->
          <div class="relative" v-if="isAuthenticated">
            <button
              @click="toggleUserMenu"
              class="flex items-center gap-1 text-sm text-gray-700 hover:text-primary transition-colors"
            >
              <i class="mdi mdi-account-circle-outline text-lg"></i>
              <span class="hidden sm:inline">Account</span>
              <i class="mdi mdi-chevron-down text-sm"></i>
            </button>

            <transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md border border-gray-200 shadow-lg py-1"
              >
                <router-link
                  to="/account"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  <i class="mdi mdi-view-dashboard"></i>
                  <span>My Account</span>
                </router-link>
                <router-link
                  to="/orders"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  <i class="mdi mdi-package-variant"></i>
                  <span>My Orders</span>
                </router-link>
                <div class="border-t border-gray-200 my-1"></div>
                <button
                  @click="logout"
                  class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                >
                  <i class="mdi mdi-logout"></i>
                  <span>Logout</span>
                </button>
              </div>
            </transition>
          </div>

          <!-- Login Button (Not Authenticated) -->
          <router-link
            v-else
            to="/login"
            class="flex items-center gap-1 text-sm text-gray-700 hover:text-primary transition-colors"
          >
            <i class="mdi mdi-account-circle-outline text-lg"></i>
            <span>Account</span>
          </router-link>

          <!-- Cart -->
          <router-link
            to="/cart"
            class="relative flex items-center gap-1 text-sm text-gray-700 hover:text-primary transition-colors"
          >
            <i class="mdi mdi-cart-outline text-xl"></i>
            <span class="hidden sm:inline">Cart</span>
            <span
              v-if="cartCount > 0"
              class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 bg-primary text-white text-xs font-semibold rounded-full flex items-center justify-center"
            >
              {{ cartCount > 9 ? '9+' : cartCount }}
            </span>
          </router-link>
        </nav>
      </div>

      <!-- Categories (Desktop) -->
      <div class="hidden lg:flex items-center gap-8 py-3 border-t border-gray-200">
        <router-link
          v-for="category in categories"
          :key="category.id"
          :to="`/products?category_id=${category.id}`"
          class="text-sm text-gray-700 hover:text-primary transition-colors"
        >
          {{ category.name }}
        </router-link>
      </div>
    </div>
  </header>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FlashBanner from '../ui/FlashBanner.vue';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useCatalogStore } from '../../stores/catalog';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const route = useRoute();
const showUserMenu = ref(false);
const searchQuery = ref('');
const authStore = useAuthStore();
const cartStore = useCartStore();
const catalogStore = useCatalogStore();
const settingsStore = useSettingsStore();

const siteName = computed(() => settingsStore.siteName || 'Online Mart');
const siteLogoUrl = computed(() => settingsStore.siteLogoUrl || '');
const isAuthenticated = computed(() => authStore.isAuthenticated);
const cartCount = computed(() => cartStore.cartItemCount || 0);
const categories = computed(() => (catalogStore.categories || []).slice(0, 6));
const routeSearchQuery = computed(() => {
  const raw = route.query.q;
  return Array.isArray(raw) ? (raw[0] || '') : (raw || '');
});

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const logout = async () => {
  await authStore.logout();
  cartStore.$reset();
  showUserMenu.value = false;
  router.push('/');
};

const submitSearch = () => {
  const q = searchQuery.value.trim();
  router.push({
    name: 'Products',
    query: q ? { q } : {},
  });
};

watch(
  () => [route.name, routeSearchQuery.value],
  ([routeName, q]) => {
    if (routeName === 'Products') {
      searchQuery.value = q || '';
    }
  },
  { immediate: true }
);

onMounted(async () => {
  if (!catalogStore.categories.length) {
    await catalogStore.fetchCategories();
  }

  if (authStore.isAuthenticated) {
    await cartStore.loadCart();
  }
});
</script>

