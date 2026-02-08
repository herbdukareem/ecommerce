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
            <h1 class="text-2xl font-bold text-gray-900">Product Details</h1>
            <p class="text-sm text-gray-600 mt-1">View product information</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="editProduct"
            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
          >
            <i class="mdi mdi-pencil mr-2"></i>
            Edit Product
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>

      <!-- Product Details -->
      <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Images -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Product Images</h2>
            
            <!-- Main Image -->
            <div v-if="product.images && product.images.length > 0" class="mb-4">
              <img
                :src="selectedImage || product.images[0].image_url"
                :alt="product.name"
                class="w-full h-64 object-cover rounded-lg border border-gray-200"
              />
            </div>
            <div v-else class="mb-4">
              <div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-image-off text-6xl text-gray-400"></i>
              </div>
            </div>

            <!-- Thumbnail Gallery -->
            <div v-if="product.images && product.images.length > 1" class="grid grid-cols-4 gap-2">
              <div
                v-for="image in product.images"
                :key="image.id"
                @click="selectedImage = image.image_url"
                :class="{
                  'ring-2 ring-orange-500': selectedImage === image.image_url,
                  'ring-1 ring-gray-200': selectedImage !== image.image_url,
                }"
                class="cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-orange-300 transition-all"
              >
                <img
                  :src="image.image_url"
                  :alt="`${product.name} - ${image.order + 1}`"
                  class="w-full h-16 object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-600">Product Name</label>
                <p class="text-gray-900 mt-1">{{ product.name || product.title }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">SKU</label>
                <p class="text-gray-900 mt-1">{{ product.skus?.[0]?.sku_code || 'N/A' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Price</label>
                <p class="text-gray-900 mt-1 font-semibold">₦{{ formatPrice(product.price || product.base_price) }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <span
                  :class="{
                    'bg-green-100 text-green-800': product.status === 'active',
                    'bg-yellow-100 text-yellow-800': product.status === 'draft',
                    'bg-gray-100 text-gray-800': product.status === 'archived',
                  }"
                  class="inline-block px-2 py-1 text-xs font-medium rounded-full mt-1"
                >
                  {{ product.status }}
                </span>
              </div>
              <div class="col-span-2">
                <label class="text-sm font-medium text-gray-600">Category</label>
                <p class="text-gray-900 mt-1">
                  {{ product.categories?.map(c => c.name).join(', ') || 'N/A' }}
                </p>
              </div>
              <div class="col-span-2">
                <label class="text-sm font-medium text-gray-600">Description</label>
                <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ product.description || 'No description' }}</p>
              </div>
            </div>
          </div>

          <!-- Variants/SKUs -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Variants</h2>
            <div v-if="product.skus && product.skus.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="sku in product.skus" :key="sku.id">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ sku.sku_code }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ sku.attributes?.name || 'Default' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">₦{{ formatPrice(sku.price) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ sku.stock_quantity || 0 }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ sku.weight || 0 }}kg</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-gray-500 text-sm">No variants available</p>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">Failed to load product details.</p>
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

const product = ref(null);
const loading = ref(false);
const selectedImage = ref(null);

const fetchProduct = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/admin/products/${route.params.id}`);
    product.value = data;
    if (data.images && data.images.length > 0) {
      selectedImage.value = data.images[0].image_url;
    }
  } catch (error) {
    console.error('Failed to fetch product:', error);
  } finally {
    loading.value = false;
  }
};

const editProduct = () => {
  router.push(`/admin/products?edit=${route.params.id}`);
};

const formatPrice = (price) => {
  return parseFloat(price || 0).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

onMounted(() => {
  fetchProduct();
});
</script>

