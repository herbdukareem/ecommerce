<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8 animate-fade-in-down">
        <h1 class="text-4xl font-bold text-primary mb-2">Products</h1>
        <p class="text-secondary">Discover our amazing collection</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Filters Sidebar -->
        <aside class="lg:col-span-1">
          <Card :elevation="2" title="Filters" icon="filter-variant" class="sticky top-20">
            <!-- Search -->
            <div class="mb-6">
              <Input
                v-model="filters.search"
                placeholder="Search products..."
                icon="magnify"
                clearable
              />
            </div>

            <!-- Categories -->
            <div class="mb-6">
              <h3 class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Categories</h3>
              <div class="space-y-2">
                <label
                  v-for="category in categories"
                  :key="category.id"
                  class="flex items-center gap-2 cursor-pointer group"
                >
                  <input
                    type="checkbox"
                    :value="category.id"
                    v-model="filters.categories"
                    class="w-4 h-4 rounded border-DEFAULT text-primary focus:ring-2 focus:ring-primary/50"
                  />
                  <span class="text-sm text-secondary group-hover:text-primary transition-colors">
                    {{ category.name }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Price Range -->
            <div class="mb-6">
              <h3 class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Price Range</h3>
              <div class="space-y-3">
                <Input
                  v-model="filters.minPrice"
                  type="number"
                  placeholder="Min"
                  icon="currency-usd"
                  size="sm"
                />
                <Input
                  v-model="filters.maxPrice"
                  type="number"
                  placeholder="Max"
                  icon="currency-usd"
                  size="sm"
                />
              </div>
            </div>

            <!-- Rating -->
            <div class="mb-6">
              <h3 class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Rating</h3>
              <div class="space-y-2">
                <label
                  v-for="rating in [5, 4, 3, 2, 1]"
                  :key="rating"
                  class="flex items-center gap-2 cursor-pointer group"
                >
                  <input
                    type="radio"
                    :value="rating"
                    v-model="filters.rating"
                    class="w-4 h-4 border-DEFAULT text-primary focus:ring-2 focus:ring-primary/50"
                  />
                  <div class="flex items-center gap-1">
                    <i
                      v-for="i in 5"
                      :key="i"
                      :class="i <= rating ? 'mdi mdi-star text-warning' : 'mdi mdi-star-outline text-secondary'"
                      class="text-sm"
                    ></i>
                    <span class="text-sm text-secondary ml-1">& up</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- Clear Filters -->
            <Button variant="outline" class="w-full" icon="filter-remove" @click="clearFilters">
              Clear Filters
            </Button>
          </Card>
        </aside>

        <!-- Products Grid -->
        <div class="lg:col-span-3">
          <!-- Toolbar -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <p class="text-sm text-secondary">
              Showing <span class="font-medium text-primary">{{ products.length }}</span> products
            </p>

            <div class="flex items-center gap-3">
              <!-- View Toggle -->
              <div class="flex items-center gap-1 bg-surface rounded-lg p-1 border border-DEFAULT">
                <button
                  @click="viewMode = 'grid'"
                  :class="viewMode === 'grid' ? 'bg-primary text-white' : 'text-secondary hover:text-primary'"
                  class="p-2 rounded transition-all"
                >
                  <i class="mdi mdi-view-grid"></i>
                </button>
                <button
                  @click="viewMode = 'list'"
                  :class="viewMode === 'list' ? 'bg-primary text-white' : 'text-secondary hover:text-primary'"
                  class="p-2 rounded transition-all"
                >
                  <i class="mdi mdi-view-list"></i>
                </button>
              </div>

              <!-- Sort -->
              <Select
                v-model="sortBy"
                :options="sortOptions"
                icon="sort"
                class="w-48"
              />
            </div>
          </div>

          <!-- Products -->
          <div
            :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6' : 'space-y-4'"
          >
            <Card
              v-for="(product, index) in products"
              :key="product.id"
              :elevation="2"
              hoverable
              clickable
              animation="fade-in-up"
              :class="`stagger-${(index % 6) + 1}`"
              @click="$router.push(`/products/${product.id}`)"
            >
              <div :class="viewMode === 'list' ? 'flex gap-4' : ''">
                <div :class="viewMode === 'list' ? 'w-32 flex-shrink-0' : 'relative mb-4'">
                  <div :class="viewMode === 'list' ? 'h-32' : 'h-48'" class="w-full bg-gradient-to-br from-primary/10 to-primary-dark/10 rounded-lg flex items-center justify-center">
                    <i class="mdi mdi-image text-5xl text-primary/30"></i>
                  </div>
                  <Badge
                    v-if="product.discount"
                    variant="danger"
                    class="absolute top-2 right-2"
                  >
                    -{{ product.discount }}%
                  </Badge>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-primary mb-2">{{ product.name }}</h3>
                  <p v-if="viewMode === 'list'" class="text-sm text-secondary mb-3 line-clamp-2">
                    {{ product.description }}
                  </p>
                  <div class="flex items-center gap-2 mb-2">
                    <div class="flex items-center gap-1 text-warning">
                      <i class="mdi mdi-star text-sm"></i>
                      <span class="text-sm font-medium">{{ product.rating }}</span>
                    </div>
                    <span class="text-xs text-secondary">({{ product.reviews }} reviews)</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-lg font-bold text-primary">${{ product.price }}</span>
                      <span v-if="product.originalPrice" class="text-sm text-secondary line-through ml-2">
                        ${{ product.originalPrice }}
                      </span>
                    </div>
                    <Button variant="primary" size="sm" icon="cart-plus" :icon-only="viewMode === 'grid'" @click.stop="addToCart(product)">
                      <span v-if="viewMode === 'list'">Add to Cart</span>
                    </Button>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Pagination -->
          <div class="mt-8">
            <Pagination
              :current-page="currentPage"
              :total-pages="totalPages"
              :total="totalProducts"
              :per-page="perPage"
              @page-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, inject } from 'vue';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Input from '../components/ui/Input.vue';
import Select from '../components/ui/Select.vue';
import Badge from '../components/ui/Badge.vue';
import Pagination from '../components/ui/Pagination.vue';

const toast = inject('toast');

// Filters
const filters = ref({
  search: '',
  categories: [],
  minPrice: '',
  maxPrice: '',
  rating: null,
});

// View and Sort
const viewMode = ref('grid');
const sortBy = ref('featured');

const sortOptions = [
  { value: 'featured', label: 'Featured' },
  { value: 'price-asc', label: 'Price: Low to High' },
  { value: 'price-desc', label: 'Price: High to Low' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'newest', label: 'Newest' },
];

// Categories
const categories = ref([
  { id: 1, name: 'Electronics' },
  { id: 2, name: 'Fashion' },
  { id: 3, name: 'Home & Garden' },
  { id: 4, name: 'Sports' },
  { id: 5, name: 'Books' },
]);

// Mock Products Data
const products = ref([
  { id: 1, name: 'Wireless Headphones', price: 79.99, originalPrice: 99.99, discount: 20, rating: 4.5, reviews: 128, description: 'High-quality wireless headphones with noise cancellation' },
  { id: 2, name: 'Smart Watch', price: 199.99, rating: 4.8, reviews: 256, description: 'Feature-rich smartwatch with health tracking' },
  { id: 3, name: 'Laptop Stand', price: 49.99, originalPrice: 69.99, discount: 29, rating: 4.3, reviews: 89, description: 'Ergonomic laptop stand for better posture' },
  { id: 4, name: 'USB-C Hub', price: 39.99, rating: 4.6, reviews: 145, description: 'Multi-port USB-C hub with fast data transfer' },
  { id: 5, name: 'Mechanical Keyboard', price: 129.99, rating: 4.7, reviews: 203, description: 'RGB mechanical keyboard with custom switches' },
  { id: 6, name: 'Wireless Mouse', price: 59.99, originalPrice: 79.99, discount: 25, rating: 4.4, reviews: 167, description: 'Ergonomic wireless mouse with precision tracking' },
]);

// Pagination
const currentPage = ref(1);
const perPage = ref(12);
const totalProducts = ref(48);
const totalPages = ref(Math.ceil(totalProducts.value / perPage.value));

const clearFilters = () => {
  filters.value = {
    search: '',
    categories: [],
    minPrice: '',
    maxPrice: '',
    rating: null,
  };
};

const handlePageChange = (page) => {
  currentPage.value = page;
  // Fetch products for the new page
};

const addToCart = (product) => {
  toast?.success(`${product.name} added to cart!`);
};
</script>