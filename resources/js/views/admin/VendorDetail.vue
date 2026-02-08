<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="router.back()"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <i class="mdi mdi-arrow-left text-xl"></i>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Vendor Details</h1>
            <p class="text-sm text-gray-600 mt-1">View vendor information and products</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            v-if="vendor && !vendor.is_verified"
            @click="approveVendor"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            <i class="mdi mdi-check-circle mr-2"></i>
            Approve Vendor
          </button>
          <button
            @click="editVendor"
            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
          >
            <i class="mdi mdi-pencil mr-2"></i>
            Edit Vendor
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>

      <!-- Vendor Details -->
      <div v-else-if="vendor" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
              <label class="text-sm font-medium text-gray-600">Business Name</label>
              <p class="text-gray-900 mt-1 font-medium">{{ vendor.name }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Email</label>
              <p class="text-gray-900 mt-1">{{ vendor.email }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Phone</label>
              <p class="text-gray-900 mt-1">{{ vendor.phone || 'N/A' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Verification Status</label>
              <span
                :class="{
                  'bg-green-100 text-green-800': vendor.is_verified,
                  'bg-yellow-100 text-yellow-800': !vendor.is_verified,
                }"
                class="inline-block px-2 py-1 text-xs font-medium rounded-full mt-1"
              >
                {{ vendor.is_verified ? 'Verified' : 'Pending' }}
              </span>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Account Status</label>
              <span
                :class="{
                  'bg-green-100 text-green-800': vendor.status === 'active',
                  'bg-red-100 text-red-800': vendor.status === 'suspended',
                  'bg-gray-100 text-gray-800': vendor.status === 'inactive',
                }"
                class="inline-block px-2 py-1 text-xs font-medium rounded-full mt-1"
              >
                {{ vendor.status }}
              </span>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Member Since</label>
              <p class="text-gray-900 mt-1">{{ formatDate(vendor.created_at) }}</p>
            </div>
            <div class="col-span-full">
              <label class="text-sm font-medium text-gray-600">Address</label>
              <p class="text-gray-900 mt-1">
                {{ [vendor.address, vendor.city, vendor.state].filter(Boolean).join(', ') || 'N/A' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Total Products</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_products }}</p>
              </div>
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-package-variant text-blue-600 text-2xl"></i>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Active Products</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active_products }}</p>
              </div>
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-check-circle text-green-600 text-2xl"></i>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Total Orders</p>
                <p class="text-2xl font-bold text-orange-600 mt-1">{{ stats.total_orders }}</p>
              </div>
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-cart text-orange-600 text-2xl"></i>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Total Revenue</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">₦{{ formatPrice(stats.total_revenue) }}</p>
              </div>
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-currency-ngn text-purple-600 text-2xl"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Products</h2>
            <button
              @click="loadProducts"
              class="text-orange-600 hover:text-orange-700 text-sm font-medium"
            >
              <i class="mdi mdi-refresh mr-1"></i>
              Refresh
            </button>
          </div>

          <div v-if="loadingProducts" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600"></div>
          </div>

          <div v-else-if="products.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKUs</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <div class="flex items-center">
                      <img
                        v-if="product.image"
                        :src="product.image"
                        :alt="product.name"
                        class="w-10 h-10 rounded object-cover mr-3"
                      />
                      <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center mr-3" v-else>
                        <i class="mdi mdi-image-off text-gray-400"></i>
                      </div>
                      <span class="text-sm font-medium text-gray-900">{{ product.name || product.title }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">
                    {{ product.categories?.map(c => c.name).join(', ') || 'N/A' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">₦{{ formatPrice(product.price || product.base_price) }}</td>
                  <td class="px-4 py-3">
                    <span
                      :class="{
                        'bg-green-100 text-green-800': product.status === 'active',
                        'bg-yellow-100 text-yellow-800': product.status === 'draft',
                        'bg-gray-100 text-gray-800': product.status === 'archived',
                      }"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ product.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ product.skus?.length || 0 }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-else class="text-gray-500 text-sm text-center py-8">No products found</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">Failed to load vendor details.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';

const router = useRouter();
const route = useRoute();

const vendor = ref(null);
const products = ref([]);
const loading = ref(false);
const loadingProducts = ref(false);

const stats = ref({
  total_products: 0,
  active_products: 0,
  total_orders: 0,
  total_revenue: 0,
});

const fetchVendor = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/admin/vendors/${route.params.id}`);
    vendor.value = data;
  } catch (error) {
    console.error('Failed to fetch vendor:', error);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  loadingProducts.value = true;
  try {
    const { data } = await axios.get(`/api/admin/vendors/${route.params.id}/products`);
    products.value = data.data || data;
    
    // Update stats
    stats.value.total_products = products.value.length;
    stats.value.active_products = products.value.filter(p => p.status === 'active').length;
  } catch (error) {
    console.error('Failed to fetch products:', error);
  } finally {
    loadingProducts.value = false;
  }
};

const approveVendor = async () => {
  if (!confirm(`Approve vendor "${vendor.value.name}"?`)) {
    return;
  }

  try {
    await axios.post(`/api/admin/vendors/${vendor.value.id}/approve`);
    alert('Vendor approved successfully!');
    await fetchVendor(); // Reload vendor data
  } catch (error) {
    console.error('Failed to approve vendor:', error);
    alert('Failed to approve vendor. Please try again.');
  }
};

const editVendor = () => {
  router.push(`/admin/vendors?edit=${route.params.id}`);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const formatPrice = (price) => {
  return parseFloat(price || 0).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

onMounted(() => {
  fetchVendor();
  loadProducts();
});
</script>

