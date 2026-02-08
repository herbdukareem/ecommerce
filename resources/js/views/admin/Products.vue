<template>
  <AdminLayout>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Products</h1>
        <p class="text-secondary">Manage your product catalog</p>
      </div>
      <div class="flex gap-3 mt-4 md:mt-0">
        <Button variant="outline" icon="upload">Import</Button>
        <Button variant="outline" icon="download">Export</Button>
        <Button variant="primary" icon="plus" @click="$router.push('/admin/products/add')">Add Product</Button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-1">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
            <i class="mdi mdi-package-variant text-2xl text-primary"></i>
          </div>
          <div>
            <p class="text-xs text-secondary">Total Products</p>
            <p class="text-2xl font-bold text-primary">2,543</p>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-2">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
            <i class="mdi mdi-check-circle text-2xl text-success"></i>
          </div>
          <div>
            <p class="text-xs text-secondary">Published</p>
            <p class="text-2xl font-bold text-success">2,401</p>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-3">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-warning/10 flex items-center justify-center">
            <i class="mdi mdi-clock-outline text-2xl text-warning"></i>
          </div>
          <div>
            <p class="text-xs text-secondary">Draft</p>
            <p class="text-2xl font-bold text-warning">98</p>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-danger/10 flex items-center justify-center">
            <i class="mdi mdi-alert-circle text-2xl text-danger"></i>
          </div>
          <div>
            <p class="text-xs text-secondary">Low Stock</p>
            <p class="text-2xl font-bold text-danger">44</p>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-5">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-info/10 flex items-center justify-center">
            <i class="mdi mdi-eye-off text-2xl text-info"></i>
          </div>
          <div>
            <p class="text-xs text-secondary">Out of Stock</p>
            <p class="text-2xl font-bold text-info">12</p>
          </div>
        </div>
      </Card>
    </div>

    <!-- Filters & Actions -->
    <Card :elevation="2" class="mb-6">
      <div class="flex flex-col lg:flex-row gap-4">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            v-model="filters.search"
            placeholder="Search products..."
            icon="magnify"
            clearable
          />
          <Select
            v-model="filters.category"
            :options="categoryOptions"
            placeholder="All Categories"
            icon="shape"
          />
          <Select
            v-model="filters.status"
            :options="statusOptions"
            placeholder="All Status"
            icon="filter"
          />
          <Select
            v-model="filters.vendor"
            :options="vendorOptions"
            placeholder="All Vendors"
            icon="store"
          />
        </div>
        <div class="flex gap-2">
          <Button variant="outline" icon="filter-remove" @click="clearFilters">Clear</Button>
          <Button variant="ghost" icon="view-grid" icon-only :class="viewMode === 'grid' ? 'bg-primary/10 text-primary' : ''" @click="viewMode = 'grid'" />
          <Button variant="ghost" icon="view-list" icon-only :class="viewMode === 'list' ? 'bg-primary/10 text-primary' : ''" @click="viewMode = 'list'" />
        </div>
      </div>
    </Card>

    <!-- Products Grid View -->
    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <Card
        v-for="(product, index) in products"
        :key="product.id"
        :elevation="2"
        hoverable
        animation="fade-in-up"
        :class="`stagger-${(index % 6) + 1}`"
      >
        <!-- Product Image -->
        <div class="relative mb-4">
          <div class="aspect-square bg-gradient-to-br from-primary/10 to-primary-dark/10 rounded-lg flex items-center justify-center">
            <i class="mdi mdi-image text-5xl text-primary/30"></i>
          </div>
          <Badge :variant="product.stock > 10 ? 'success' : product.stock > 0 ? 'warning' : 'danger'" class="absolute top-2 right-2">
            {{ product.stock > 0 ? `${product.stock} in stock` : 'Out of stock' }}
          </Badge>
        </div>

        <!-- Product Info -->
        <div class="mb-4">
          <h3 class="font-semibold text-primary mb-1 line-clamp-2">{{ product.name }}</h3>
          <p class="text-xs text-secondary mb-2">SKU: {{ product.sku }}</p>
          <div class="flex items-center gap-2 mb-2">
            <div class="flex items-center gap-1">
              <i class="mdi mdi-star text-warning text-sm"></i>
              <span class="text-sm font-medium text-primary">{{ product.rating }}</span>
            </div>
            <span class="text-xs text-secondary">({{ product.reviews }} reviews)</span>
          </div>
          <p class="text-lg font-bold text-primary">{{ formatCurrency(product.price) }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <Button variant="outline" size="sm" icon="eye" class="flex-1">View</Button>
          <Button variant="primary" size="sm" icon="pencil" class="flex-1">Edit</Button>
        </div>
      </Card>
    </div>

    <!-- Products List View -->
    <Card v-else :elevation="2">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-DEFAULT">
              <th class="text-left py-4 px-4">
                <input type="checkbox" class="w-4 h-4 rounded border-DEFAULT" />
              </th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Product</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">SKU</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Category</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Price</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Stock</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Status</th>
              <th class="text-right py-4 px-4 text-sm font-semibold text-primary">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="product in products"
              :key="product.id"
              class="border-b border-DEFAULT hover:bg-base transition-colors"
            >
              <td class="py-4 px-4">
                <input type="checkbox" class="w-4 h-4 rounded border-DEFAULT" />
              </td>
              <td class="py-4 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/10 to-primary-dark/10 flex items-center justify-center flex-shrink-0">
                    <i class="mdi mdi-image text-primary"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-primary truncate">{{ product.name }}</p>
                    <p class="text-xs text-secondary">{{ product.vendor }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm text-secondary font-mono">{{ product.sku }}</p>
              </td>
              <td class="py-4 px-4">
                <Badge variant="outline" size="sm">{{ product.category }}</Badge>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm font-semibold text-primary">{{ formatCurrency(product.price) }}</p>
              </td>
              <td class="py-4 px-4">
                <Badge :variant="product.stock > 10 ? 'success' : product.stock > 0 ? 'warning' : 'danger'">
                  {{ product.stock }}
                </Badge>
              </td>
              <td class="py-4 px-4">
                <Badge :variant="product.status === 'Published' ? 'success' : 'warning'">
                  {{ product.status }}
                </Badge>
              </td>
              <td class="py-4 px-4">
                <div class="flex items-center justify-end gap-2">
                  <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors">
                    <i class="mdi mdi-eye text-primary"></i>
                  </button>
                  <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors">
                    <i class="mdi mdi-pencil text-primary"></i>
                  </button>
                  <button class="p-2 hover:bg-danger/10 rounded-lg transition-colors">
                    <i class="mdi mdi-delete text-danger"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-6">
        <Pagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="totalProducts"
          :per-page="perPage"
          @page-change="handlePageChange"
        />
      </div>
    </Card>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';
import Badge from '../../components/ui/Badge.vue';
import Pagination from '../../components/ui/Pagination.vue';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();
const { formatCurrency } = settingsStore;

const viewMode = ref('grid');

const filters = ref({
  search: '',
  category: '',
  status: '',
  vendor: '',
});

const categoryOptions = [
  { value: '', label: 'All Categories' },
  { value: 'electronics', label: 'Electronics' },
  { value: 'fashion', label: 'Fashion' },
  { value: 'home', label: 'Home & Garden' },
];

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'published', label: 'Published' },
  { value: 'draft', label: 'Draft' },
];

const vendorOptions = [
  { value: '', label: 'All Vendors' },
  { value: 'tech-store', label: 'Tech Store Nigeria' },
  { value: 'fashion-hub', label: 'Fashion Hub' },
];

const products = ref([
  { id: 1, name: 'Wireless Bluetooth Headphones', sku: 'WBH-001', category: 'Electronics', vendor: 'Tech Store Nigeria', price: 25000, stock: 45, rating: 4.5, reviews: 128, status: 'Published' },
  { id: 2, name: 'Smart Watch Series 5', sku: 'SW-005', category: 'Electronics', vendor: 'Tech Store Nigeria', price: 85000, stock: 12, rating: 4.8, reviews: 256, status: 'Published' },
  { id: 3, name: 'Cotton T-Shirt (Pack of 3)', sku: 'TS-003', category: 'Fashion', vendor: 'Fashion Hub', price: 8500, stock: 156, rating: 4.3, reviews: 89, status: 'Published' },
  { id: 4, name: 'Leather Wallet', sku: 'LW-012', category: 'Fashion', vendor: 'Fashion Hub', price: 12000, stock: 8, rating: 4.6, reviews: 145, status: 'Published' },
  { id: 5, name: 'Portable Power Bank 20000mAh', sku: 'PB-020', category: 'Electronics', vendor: 'Tech Store Nigeria', price: 15000, stock: 0, rating: 4.7, reviews: 203, status: 'Published' },
  { id: 6, name: 'Running Shoes', sku: 'RS-008', category: 'Fashion', vendor: 'Fashion Hub', price: 22000, stock: 34, rating: 4.4, reviews: 167, status: 'Draft' },
]);

const currentPage = ref(1);
const perPage = ref(12);
const totalProducts = ref(2543);
const totalPages = ref(Math.ceil(totalProducts.value / perPage.value));

const clearFilters = () => {
  filters.value = { search: '', category: '', status: '', vendor: '' };
};

const handlePageChange = (page) => {
  currentPage.value = page;
};
</script>

