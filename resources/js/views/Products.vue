<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8 animate-fade-in-down">
        <h1 class="text-4xl font-bold text-primary mb-2">Products</h1>
        <p class="text-secondary">Discover our latest catalog</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
          <Card :elevation="2" title="Filters" icon="filter-variant" class="sticky top-20">
            <div class="mb-6">
              <Input v-model="localFilters.q" placeholder="Search products..." icon="magnify" />
            </div>

            <div class="mb-6">
              <h3 class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Category</h3>
              <Select
                v-model="localFilters.category_id"
                :options="categoryOptions"
                placeholder="All categories"
              />
            </div>

            <div class="mb-6">
              <h3 class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Price Range</h3>
              <div class="space-y-2">
                <Input v-model="localFilters.price_min" type="number" placeholder="Min" />
                <Input v-model="localFilters.price_max" type="number" placeholder="Max" />
              </div>
            </div>

            <div class="mb-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="localFilters.in_stock" type="checkbox" />
                <span class="text-sm text-secondary">In stock only</span>
              </label>
            </div>

            <div class="flex gap-2">
              <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
              <Button variant="outline" class="w-full" @click="clearFilters">Reset</Button>
            </div>
          </Card>
        </aside>

        <div class="lg:col-span-3">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <p class="text-sm text-secondary">
              Showing <span class="font-medium text-primary">{{ products.length }}</span>
              of <span class="font-medium text-primary">{{ pagination.total || 0 }}</span>
            </p>

            <Select v-model="localFilters.sort" :options="sortOptions" class="w-56" @update:model-value="applyFilters" />
          </div>

          <div v-if="loading" class="py-10 text-center text-secondary">Loading products...</div>
          <div v-else-if="errorMessage" class="py-10 text-center text-danger">{{ errorMessage }}</div>
          <div v-else-if="products.length === 0" class="py-10 text-center text-secondary">No products found for the selected filters.</div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <Card
              v-for="product in products"
              :key="product.id"
              :elevation="2"
              hoverable
              clickable
              @click="$router.push(`/products/${product.slug}`)"
            >
              <div class="h-44 rounded-lg bg-gradient-to-br from-primary/10 to-primary-dark/10 flex items-center justify-center mb-4">
                <img
                  :src="product.primary_image_url || product.image_placeholder"
                  :alt="product.title"
                  class="w-full h-full object-cover rounded-lg"
                  loading="lazy"
                  @error="onImageError($event, product.image_placeholder)"
                />
              </div>

              <h3 class="font-semibold text-primary mb-2 line-clamp-2">{{ product.title }}</h3>
              <p class="text-sm text-secondary mb-4 line-clamp-2">{{ product.description || 'No description available.' }}</p>

              <div class="flex items-center justify-between gap-3">
                <span class="text-lg font-bold text-primary">{{ formatCurrency(product.base_price) }}</span>
                <Button
                  variant="primary"
                  size="sm"
                  icon="cart-plus"
                  :disabled="isProductOutOfStock(product)"
                  @click.stop="addToCart(product)"
                >
                  {{ cartButtonLabel(product) }}
                </Button>
              </div>
            </Card>
          </div>

          <div class="mt-8">
            <Pagination
              :current-page="pagination.current_page || 1"
              :total-pages="pagination.last_page || 1"
              :total="pagination.total || 0"
              :per-page="pagination.per_page || 24"
              @page-change="changePage"
            />
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Input from '../components/ui/Input.vue';
import Select from '../components/ui/Select.vue';
import Pagination from '../components/ui/Pagination.vue';
import { useCatalogStore } from '../stores/catalog';
import { useCartStore } from '../stores/cart';
import { useSettingsStore } from '../stores/settings';
import { useAuthStore } from '../stores/auth';
import { resolveProductPurchase } from '../utils/productPurchase';

const route = useRoute();
const router = useRouter();
const catalogStore = useCatalogStore();
const cartStore = useCartStore();
const settingsStore = useSettingsStore();
const authStore = useAuthStore();
const toast = inject('toast');

const loading = computed(() => catalogStore.loading);
const products = computed(() => catalogStore.products);
const pagination = computed(() => catalogStore.pagination);
const categories = computed(() => catalogStore.categories || []);
const errorMessage = ref('');

const localFilters = reactive({
  q: '',
  category_id: null,
  price_min: '',
  price_max: '',
  sort: 'newest',
  in_stock: false,
});

const sortOptions = [
  { value: 'newest', label: 'Newest' },
  { value: 'oldest', label: 'Oldest' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
  { value: 'name_asc', label: 'Name: A-Z' },
  { value: 'name_desc', label: 'Name: Z-A' },
];

const categoryOptions = computed(() => {
  const options = [{ value: null, label: 'All categories' }];
  for (const category of categories.value) {
    options.push({ value: category.id, label: category.name });
  }
  return options;
});

const formatCurrency = settingsStore.formatCurrency;

const isProductOutOfStock = (product) => resolveProductPurchase(product).outOfStock;

const cartButtonLabel = (product) => {
  const purchase = resolveProductPurchase(product);
  if (purchase.outOfStock) return 'Out of stock';
  if (purchase.requiresSelection) return 'Select options';
  return 'Add';
};

const syncFiltersToStore = () => {
  catalogStore.filters.q = localFilters.q || '';
  catalogStore.filters.category_id = localFilters.category_id || null;
  catalogStore.filters.price_min = localFilters.price_min || null;
  catalogStore.filters.price_max = localFilters.price_max || null;
  catalogStore.filters.sort = localFilters.sort || 'newest';
  catalogStore.filters.in_stock = !!localFilters.in_stock;
};

const applyFilters = async () => {
  errorMessage.value = '';
  catalogStore.filters.page = 1;
  syncFiltersToStore();

  try {
    await catalogStore.fetchProducts();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to fetch products.';
  }
};

const clearFilters = async () => {
  localFilters.q = '';
  localFilters.category_id = null;
  localFilters.price_min = '';
  localFilters.price_max = '';
  localFilters.sort = 'newest';
  localFilters.in_stock = false;
  await applyFilters();
};

const changePage = async (page) => {
  catalogStore.filters.page = page;
  syncFiltersToStore();
  await catalogStore.fetchProducts();
};

const addToCart = async (product) => {
  if (!authStore.isAuthenticated) {
    toast?.warning('Please login to add items to your cart.');
    router.push({ name: 'Login', query: { redirect: route.fullPath } });
    return;
  }

  const purchase = resolveProductPurchase(product);

  if (purchase.requiresSelection) {
    toast?.info('Select a variant on the product details page.');
    router.push(`/products/${product.slug}`);
    return;
  }

  if (purchase.outOfStock || !purchase.selectedSkuId) {
    toast?.warning('This product is currently out of stock.');
    return;
  }

  const result = await cartStore.addItem(purchase.selectedSkuId, 1);
  if (result.success) {
    toast?.success('Added to cart');
  } else {
    toast?.error(result.error || 'Failed to add item to cart');
  }
};

const onImageError = (event, fallback) => {
  if (!event?.target) return;
  event.target.src = fallback;
};

onMounted(async () => {
  if (route.query.category_id) {
    localFilters.category_id = Number(route.query.category_id) || null;
  }
  await Promise.all([catalogStore.fetchCategories(), catalogStore.fetchAttributes()]);
  await applyFilters();
});
</script>
