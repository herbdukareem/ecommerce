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
          <div class="text-2xl font-bold">
            <span class="text-orange-500">ShopHub</span>
          </div>
        </router-link>

        <!-- Search Bar (Desktop) -->
        <div class="hidden md:flex flex-1 max-w-2xl mx-8">
          <div class="relative w-full flex">
            <input
              type="search"
              placeholder="Search products, brands and categories"
              class="flex-1 px-4 py-2.5 border border-gray-300 rounded-l-md text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-orange-500"
            />
            <button class="px-6 bg-orange-500 text-white rounded-r-md hover:bg-orange-600 transition-colors">
              <i class="mdi mdi-magnify text-xl"></i>
            </button>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex items-center gap-4">
          <!-- Help -->
          <button class="hidden lg:flex items-center gap-1 text-sm text-gray-700 hover:text-orange-500 transition-colors">
            <i class="mdi mdi-help-circle-outline text-lg"></i>
            <span>Help</span>
          </button>

          <!-- Account -->
          <div class="relative" v-if="isAuthenticated">
            <button
              @click="toggleUserMenu"
              class="flex items-center gap-1 text-sm text-gray-700 hover:text-orange-500 transition-colors"
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
                  to="/dashboard"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  <i class="mdi mdi-view-dashboard"></i>
                  <span>Dashboard</span>
                </router-link>
                <router-link
                  to="/orders"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  <i class="mdi mdi-package-variant"></i>
                  <span>My Orders</span>
                </router-link>
                <router-link
                  to="/wishlist"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  <i class="mdi mdi-heart-outline"></i>
                  <span>Wishlist</span>
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
            class="flex items-center gap-1 text-sm text-gray-700 hover:text-orange-500 transition-colors"
          >
            <i class="mdi mdi-account-circle-outline text-lg"></i>
            <span>Account</span>
          </router-link>

          <!-- Cart -->
          <router-link
            to="/cart"
            class="relative flex items-center gap-1 text-sm text-gray-700 hover:text-orange-500 transition-colors"
          >
            <i class="mdi mdi-cart-outline text-xl"></i>
            <span class="hidden sm:inline">Cart</span>
            <span
              v-if="cartCount > 0"
              class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 bg-orange-500 text-white text-xs font-semibold rounded-full flex items-center justify-center"
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
          :to="`/products?category=${category.slug}`"
          class="text-sm text-gray-700 hover:text-orange-500 transition-colors"
        >
          {{ category.name }}
        </router-link>
      </div>
    </div>
  </header>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import FlashBanner from '../ui/FlashBanner.vue';

const router = useRouter();
const showUserMenu = ref(false);

// Mock data - replace with actual store/API calls
const isAuthenticated = ref(true);
const cartCount = ref(3);
const categories = ref([
  { id: 1, name: 'Electronics', slug: 'electronics' },
  { id: 2, name: 'Fashion', slug: 'fashion' },
  { id: 3, name: 'Home & Garden', slug: 'home-garden' },
  { id: 4, name: 'Sports', slug: 'sports' },
  { id: 5, name: 'Books', slug: 'books' },
]);

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const logout = () => {
  // Implement logout logic
  showUserMenu.value = false;
  router.push('/login');
};
</script>

