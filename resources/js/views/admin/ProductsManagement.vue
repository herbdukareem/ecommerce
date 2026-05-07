<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Products Management</h1>
          <p class="text-sm text-gray-600 mt-1">Manage all products in your store</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          <i class="mdi mdi-plus"></i>
          Add Product
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <div class="relative">
              <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search products..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <select
            v-model="filters.status"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchProducts"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
          </select>

          <!-- Category Filter -->
          <select
            v-model="filters.category_id"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchProducts"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Products Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <i class="mdi mdi-loading mdi-spin text-4xl text-orange-500"></i>
          <p class="text-gray-600 mt-2">Loading products...</p>
        </div>

        <div v-else-if="products.length === 0" class="p-8 text-center">
          <i class="mdi mdi-package-variant text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-2">No products found</p>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <input type="checkbox" class="rounded border-gray-300" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Product
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Vendor
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Price
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                SKUs
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Available Stock
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="rounded border-gray-300" />
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="mdi mdi-package-variant text-gray-400"></i>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ product.title }}</div>
                    <div class="text-xs text-gray-500">ID: {{ product.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ product.vendor?.name }}</div>
                <div class="text-xs text-gray-500">{{ product.vendor?.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(product.base_price) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
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
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ product.skus?.length || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold" :class="stockTextClass(product)">
                  {{ formatStock(product.available_stock) }}
                </div>
                <div v-if="Number(product.active_sku_count || 0) > 1" class="text-xs text-gray-500">
                  {{ product.active_sku_count }} active variants
                </div>
                <div v-else-if="Number(product.available_stock || 0) === 0" class="text-xs text-gray-500">
                  Out of stock
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    @click="viewProduct(product)"
                    class="text-blue-600 hover:text-blue-900"
                    title="View Details"
                  >
                    <i class="mdi mdi-eye"></i>
                  </button>
                  <button
                    @click="editProduct(product)"
                    class="text-orange-600 hover:text-orange-900"
                    title="Edit"
                  >
                    <i class="mdi mdi-pencil"></i>
                  </button>
                  <button
                    @click="deleteProduct(product)"
                    class="text-red-600 hover:text-red-900"
                    title="Delete"
                  >
                    <i class="mdi mdi-delete"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} products
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

      <!-- Create/Edit Product Modal -->
      <div
        v-if="showCreateModal || showEditModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">
              {{ showEditModal ? 'Edit Product' : 'Add New Product' }}
            </h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <i class="mdi mdi-close text-2xl"></i>
            </button>
          </div>

          <form @submit.prevent="saveProduct" class="p-6 space-y-6">
            <!-- Basic Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                  <input
                    v-model="productForm.name"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter product name"
                  />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                  <textarea
                    v-model="productForm.description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter product description"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                  <select
                    v-model="productForm.category_id"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  >
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select
                    v-model="productForm.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  >
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Base Price *</label>
                  <input
                    v-model.number="productForm.price"
                    type="number"
                    step="0.01"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="0.00"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                  <input
                    v-model="productForm.sku"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter SKU"
                  />
                </div>

                <div class="md:col-span-2">
                  <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input v-model="productForm.has_options" type="checkbox" class="rounded border-gray-300" />
                    Enable product options (variants)
                  </label>
                </div>
              </div>
            </div>

            <!-- Product Images -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Product Images</h3>

              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <div class="text-center">
                  <i class="mdi mdi-cloud-upload text-4xl text-gray-400"></i>
                  <div class="mt-2">
                    <label class="cursor-pointer">
                      <span class="text-orange-600 hover:text-orange-700 font-medium">Upload images</span>
                      <input
                        type="file"
                        multiple
                        accept="image/*"
                        class="hidden"
                        @change="handleImageUpload"
                      />
                    </label>
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB</p>
                  </div>
                </div>

                <!-- Image Preview -->
                <div v-if="productForm.images.length > 0" class="mt-4 grid grid-cols-4 gap-4">
                  <div
                    v-for="(image, index) in productForm.images"
                    :key="index"
                    class="relative group"
                  >
                    <img
                      :src="image.preview"
                      class="w-full h-24 object-cover rounded-lg border border-gray-200"
                    />
                    <button
                      type="button"
                      @click="removeImage(index)"
                      class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <i class="mdi mdi-close text-sm"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Product Variants -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Product Options</h3>
                <button
                  type="button"
                  @click="addVariant"
                  :disabled="!productForm.has_options"
                  class="text-sm text-orange-600 hover:text-orange-700 font-medium"
                >
                  <i class="mdi mdi-plus"></i> Add Variant
                </button>
              </div>

              <div v-if="!productForm.has_options" class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                This product will use simple add-to-cart flow. Enable product options to create selectable purchasable options.
              </div>

              <div v-else-if="productForm.variants.length === 0" class="text-center py-8 text-gray-500">
                No variants added. Click "Add Variant" to create product variations.
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(variant, index) in productForm.variants"
                  :key="index"
                  class="border border-gray-200 rounded-lg p-4"
                >
                  <div class="flex items-start justify-between mb-3">
                    <h4 class="font-medium text-gray-900">Variant {{ index + 1 }}</h4>
                    <button
                      type="button"
                      @click="removeVariant(index)"
                      class="text-red-600 hover:text-red-700"
                    >
                      <i class="mdi mdi-delete"></i>
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Option Label *</label>
                      <input
                        v-model="variant.label"
                        type="text"
                        placeholder="e.g., 2kg (mudu)"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">SKU</label>
                      <input
                        v-model="variant.sku"
                        type="text"
                        placeholder="Variant SKU"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Price</label>
                      <input
                        v-model.number="variant.price"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Weight (kg)</label>
                      <input
                        v-model.number="variant.weight"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Sort Order</label>
                      <input
                        v-model.number="variant.sort_order"
                        type="number"
                        placeholder="0"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                      />
                    </div>
                    <div class="flex items-center">
                      <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700">
                        <input v-model="variant.is_active" type="checkbox" class="rounded border-gray-300" />
                        Active
                      </label>
                    </div>
                  </div>

                  <div v-if="showEditModal && variant.id" class="mt-4 border-t border-gray-100 pt-4 space-y-3">
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Option Images</p>
                      <label class="text-xs text-orange-600 hover:text-orange-700 cursor-pointer font-medium">
                        Upload
                        <input
                          type="file"
                          multiple
                          accept="image/*"
                          class="hidden"
                          @change="handleVariantImageUpload(index, $event)"
                        />
                      </label>
                    </div>

                    <div v-if="variant.images?.length" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                      <div
                        v-for="(image, imageIndex) in variant.images"
                        :key="image.id"
                        class="relative rounded-lg overflow-hidden border border-gray-200"
                      >
                        <img
                          :src="image.image_url"
                          :alt="image.alt_text || variant.label || `Variant image ${imageIndex + 1}`"
                          class="h-20 w-full object-cover"
                        />
                        <div class="p-1 space-y-1 bg-white">
                          <button
                            type="button"
                            class="w-full text-[11px] px-2 py-1 rounded border border-gray-200 hover:bg-gray-50"
                            :class="image.is_primary ? 'bg-orange-50 border-orange-200 text-orange-700' : ''"
                            @click="setVariantPrimaryImage(index, image.id)"
                          >
                            {{ image.is_primary ? 'Primary' : 'Set primary' }}
                          </button>
                          <div class="flex gap-1">
                            <button
                              type="button"
                              class="flex-1 text-[11px] px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40"
                              :disabled="imageIndex === 0"
                              @click="moveVariantImage(index, imageIndex, -1)"
                            >
                              Up
                            </button>
                            <button
                              type="button"
                              class="flex-1 text-[11px] px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40"
                              :disabled="imageIndex === variant.images.length - 1"
                              @click="moveVariantImage(index, imageIndex, 1)"
                            >
                              Down
                            </button>
                          </div>
                          <button
                            type="button"
                            class="w-full text-[11px] px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50"
                            @click="deleteVariantImage(index, image.id)"
                          >
                            Remove
                          </button>
                        </div>
                      </div>
                    </div>
                    <p v-else class="text-xs text-gray-500">No images uploaded for this option yet.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50"
              >
                <i v-if="saving" class="mdi mdi-loading mdi-spin mr-2"></i>
                {{ saving ? 'Saving...' : (showEditModal ? 'Update Product' : 'Create Product') }}
              </button>
            </div>
          </form>
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
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const products = ref([]);
const categories = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const saving = ref(false);

const productForm = ref({
  id: null,
  name: '',
  description: '',
  category_id: '',
  status: 'active',
  has_options: false,
  price: 0,
  sku: '',
  images: [],
  variants: [],
});

const filters = ref({
  search: '',
  status: '',
  category_id: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

const sortVariantImages = (images = []) => {
  return [...images].sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0));
};

const syncVariantImages = (variantIndex, images) => {
  if (!productForm.value.variants[variantIndex]) {
    return;
  }

  productForm.value.variants[variantIndex].images = sortVariantImages(images);
};

// Fetch products from API
const fetchProducts = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/products', {
      params: {
        search: filters.value.search,
        status: filters.value.status,
        category_id: filters.value.category_id,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      },
    });

    products.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from,
      to: data.to,
    };
  } catch (error) {
    console.error('Failed to fetch products:', error);
  } finally {
    loading.value = false;
  }
};

// Fetch categories for filter
const fetchCategories = async () => {
  try {
    const { data } = await axios.get('/api/categories');
    categories.value = data;
  } catch (error) {
    console.error('Failed to fetch categories:', error);
  }
};

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    fetchProducts();
  }, 500);
};

// Modal functions
const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  resetForm();
};

const resetForm = () => {
  productForm.value = {
    id: null,
    name: '',
    description: '',
    category_id: '',
    status: 'active',
    has_options: false,
    price: 0,
    sku: '',
    images: [],
    variants: [],
  };
};

// Image upload
const handleImageUpload = (event) => {
  const files = Array.from(event.target.files);
  files.forEach(file => {
    if (file.size > 10 * 1024 * 1024) {
      alert(`File ${file.name} is too large. Maximum size is 10MB.`);
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      productForm.value.images.push({
        file: file,
        preview: e.target.result,
      });
    };
    reader.readAsDataURL(file);
  });
  event.target.value = '';
};

const removeImage = (index) => {
  productForm.value.images.splice(index, 1);
};

// Variant management
const addVariant = () => {
  productForm.value.variants.push({
    label: '',
    sku: '',
    price: productForm.value.price,
    weight: 0,
    sort_order: productForm.value.variants.length,
    is_active: true,
    images: [],
  });
};

const removeVariant = (index) => {
  productForm.value.variants.splice(index, 1);
};

const handleVariantImageUpload = async (variantIndex, event) => {
  const variant = productForm.value.variants[variantIndex];
  const files = Array.from(event?.target?.files || []);
  event.target.value = '';

  if (!showEditModal.value || !productForm.value.id || !variant?.id || !files.length) {
    return;
  }

  const formData = new FormData();
  files.forEach((file, index) => {
    formData.append(`images[${index}]`, file);
  });

  try {
    const { data } = await axios.post(
      `/api/admin/products/${productForm.value.id}/skus/${variant.id}/images`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );
    syncVariantImages(variantIndex, data.images || []);
  } catch (error) {
    console.error('Failed to upload variant images:', error);
    alert(error.response?.data?.message || 'Failed to upload option images.');
  }
};

const setVariantPrimaryImage = async (variantIndex, imageId) => {
  const variant = productForm.value.variants[variantIndex];
  if (!showEditModal.value || !productForm.value.id || !variant?.id || !imageId) {
    return;
  }

  try {
    const { data } = await axios.put(`/api/admin/products/${productForm.value.id}/skus/${variant.id}/images/${imageId}/primary`);
    syncVariantImages(variantIndex, data.images || []);
  } catch (error) {
    console.error('Failed to set primary option image:', error);
    alert(error.response?.data?.message || 'Failed to set primary option image.');
  }
};

const deleteVariantImage = async (variantIndex, imageId) => {
  const variant = productForm.value.variants[variantIndex];
  if (!showEditModal.value || !productForm.value.id || !variant?.id || !imageId) {
    return;
  }

  try {
    const { data } = await axios.delete(`/api/admin/products/${productForm.value.id}/skus/${variant.id}/images/${imageId}`);
    syncVariantImages(variantIndex, data.images || []);
  } catch (error) {
    console.error('Failed to delete option image:', error);
    alert(error.response?.data?.message || 'Failed to delete option image.');
  }
};

const moveVariantImage = async (variantIndex, imageIndex, direction) => {
  const variant = productForm.value.variants[variantIndex];
  if (!showEditModal.value || !productForm.value.id || !variant?.id) {
    return;
  }

  const targetIndex = imageIndex + direction;
  if (targetIndex < 0 || targetIndex >= (variant.images?.length || 0)) {
    return;
  }

  const reordered = [...variant.images];
  const [moved] = reordered.splice(imageIndex, 1);
  reordered.splice(targetIndex, 0, moved);
  syncVariantImages(variantIndex, reordered);

  try {
    const { data } = await axios.put(`/api/admin/products/${productForm.value.id}/skus/${variant.id}/images/order`, {
      image_ids: reordered.map((image) => image.id),
    });
    syncVariantImages(variantIndex, data.images || reordered);
  } catch (error) {
    console.error('Failed to reorder option images:', error);
    alert(error.response?.data?.message || 'Failed to reorder option images.');
  }
};

// Save product
const saveProduct = async () => {
  saving.value = true;
  try {
    const formData = new FormData();

    // Add basic fields
    formData.append('name', productForm.value.name);
    formData.append('description', productForm.value.description || '');
    formData.append('category_id', productForm.value.category_id);
    formData.append('status', productForm.value.status);
    formData.append('has_options', productForm.value.has_options ? '1' : '0');
    formData.append('price', productForm.value.price);
    formData.append('sku', productForm.value.sku || '');

    // Handle images
    if (showEditModal.value) {
      // For edit mode: track existing images to keep and new images to add
      const existingImageIds = productForm.value.images
        .filter(img => img.existing && img.id)
        .map(img => img.id);

      if (existingImageIds.length > 0) {
        formData.append('keep_image_ids', JSON.stringify(existingImageIds));
      }

      // Add new image files
      let newImageIndex = 0;
      productForm.value.images.forEach((image) => {
        if (image.file && !image.existing) {
          formData.append(`images[${newImageIndex}]`, image.file);
          newImageIndex++;
        }
      });
    } else {
      // For create mode: just add all images
      productForm.value.images.forEach((image, index) => {
        if (image.file) {
          formData.append(`images[${index}]`, image.file);
        }
      });
    }

    // Add variants
    if (productForm.value.has_options && productForm.value.variants.length > 0) {
      formData.append('variants', JSON.stringify(productForm.value.variants));
    }

    if (showEditModal.value) {
      await axios.post(`/api/admin/products/${productForm.value.id}?_method=PUT`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      await axios.post('/api/admin/products', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    closeModal();
    fetchProducts();
    alert('Product saved successfully!');
  } catch (error) {
    console.error('Failed to save product:', error);
    alert('Failed to save product. Please try again.');
  } finally {
    saving.value = false;
  }
};

// View product details
const viewProduct = (product) => {
  router.push(`/admin/products/${product.id}`);
};

// Edit product
const editProduct = (product) => {
  productForm.value = {
    id: product.id,
    name: product.name || product.title,
    description: product.description || '',
    category_id: product.categories?.[0]?.id || product.category_id || '',
    status: product.status,
    has_options: !!product.has_options,
    price: product.price || product.base_price,
    sku: product.skus?.[0]?.sku_code || product.sku || '',
    images: product.images ? product.images.map(img => ({
      preview: img.image_url,
      id: img.id,
      existing: true
    })) : [],
    variants: product.skus ? product.skus.map(sku => ({
      id: sku.id,
      label: sku.option_label || sku.display_label || sku.attributes?.name || 'Default',
      sku: sku.sku_code,
      price: sku.price,
      weight: sku.weight || 0,
      sort_order: sku.sort_order || 0,
      is_active: sku.active !== false,
      images: sortVariantImages(sku.images || []),
      existing: true
    })) : [],
  };
  showEditModal.value = true;
};

// Delete product
const deleteProduct = async (product) => {
  if (!confirm(`Are you sure you want to delete "${product.name}"?`)) {
    return;
  }

  try {
    await axios.delete(`/api/admin/products/${product.id}`);
    fetchProducts();
    alert('Product deleted successfully!');
  } catch (error) {
    console.error('Failed to delete product:', error);
    alert('Failed to delete product. Please try again.');
  }
};

// Pagination
const goToPage = (page) => {
  pagination.value.current_page = page;
  fetchProducts();
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
const formatStock = (stock) => {
  return new Intl.NumberFormat('en-NG').format(Math.max(0, Number(stock || 0)));
};

const stockTextClass = (product) => {
  const available = Number(product?.available_stock || 0);
  if (available <= 0) {
    return 'text-red-600';
  }
  if (available <= 5) {
    return 'text-amber-600';
  }
  return 'text-green-700';
};

// Initialize
onMounted(() => {
  fetchProducts();
  fetchCategories();
});
</script>
