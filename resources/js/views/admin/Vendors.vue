<template>
  <AdminLayout>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Vendors</h1>
        <p class="text-secondary">Manage your marketplace vendors</p>
      </div>
      <div class="flex gap-3 mt-4 md:mt-0">
        <Button variant="outline" icon="download">Export</Button>
        <Button variant="primary" icon="plus" @click="showAddVendor = true">Add Vendor</Button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-1">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-secondary mb-1">Total Vendors</p>
            <p class="text-3xl font-bold text-primary">{{ stats.total }}</p>
            <p class="text-xs text-success mt-1">
              <i class="mdi mdi-arrow-up"></i> 12% from last month
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <i class="mdi mdi-store text-2xl text-primary"></i>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-2">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-secondary mb-1">Active</p>
            <p class="text-3xl font-bold text-success">{{ stats.active }}</p>
            <p class="text-xs text-secondary mt-1">Currently selling</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
            <i class="mdi mdi-check-circle text-2xl text-success"></i>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-secondary mb-1">Pending Approval</p>
            <p class="text-3xl font-bold text-warning">{{ stats.pending }}</p>
            <p class="text-xs text-secondary mt-1">Awaiting review</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-warning/10 flex items-center justify-center">
            <i class="mdi mdi-clock-outline text-2xl text-warning"></i>
          </div>
        </div>
      </Card>

      <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-secondary mb-1">Suspended</p>
            <p class="text-3xl font-bold text-danger">{{ stats.suspended }}</p>
            <p class="text-xs text-secondary mt-1">Temporarily blocked</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center">
            <i class="mdi mdi-cancel text-2xl text-danger"></i>
          </div>
        </div>
      </Card>
    </div>

    <!-- Filters & Search -->
    <Card :elevation="2" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Input
          v-model="filters.search"
          placeholder="Search vendors..."
          icon="magnify"
          clearable
        />
        <Select
          v-model="filters.status"
          :options="statusOptions"
          placeholder="All Status"
          icon="filter"
        />
        <Select
          v-model="filters.country"
          :options="countryOptions"
          placeholder="All Countries"
          icon="map-marker"
        />
        <Button variant="outline" icon="filter-remove" @click="clearFilters">Clear Filters</Button>
      </div>
    </Card>

    <!-- Vendors Table -->
    <Card :elevation="2">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-DEFAULT">
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">
                <input type="checkbox" class="w-4 h-4 rounded border-DEFAULT" />
              </th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Vendor</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Contact</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Products</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Revenue</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Status</th>
              <th class="text-left py-4 px-4 text-sm font-semibold text-primary">Joined</th>
              <th class="text-right py-4 px-4 text-sm font-semibold text-primary">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="vendor in vendors"
              :key="vendor.id"
              class="border-b border-DEFAULT hover:bg-base transition-colors"
            >
              <td class="py-4 px-4">
                <input type="checkbox" class="w-4 h-4 rounded border-DEFAULT" />
              </td>
              <td class="py-4 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary/20 to-primary-dark/20 flex items-center justify-center">
                    <i class="mdi mdi-store text-primary"></i>
                  </div>
                  <div>
                    <p class="font-semibold text-primary">{{ vendor.name }}</p>
                    <p class="text-xs text-secondary">{{ vendor.location }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm text-primary">{{ vendor.email }}</p>
                <p class="text-xs text-secondary">{{ vendor.phone }}</p>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm font-semibold text-primary">{{ vendor.products }}</p>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm font-semibold text-primary">{{ formatCurrency(vendor.revenue) }}</p>
              </td>
              <td class="py-4 px-4">
                <Badge :variant="getStatusVariant(vendor.status)">
                  {{ vendor.status }}
                </Badge>
              </td>
              <td class="py-4 px-4">
                <p class="text-sm text-secondary">{{ vendor.joined }}</p>
              </td>
              <td class="py-4 px-4">
                <div class="flex items-center justify-end gap-2">
                  <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors" title="View">
                    <i class="mdi mdi-eye text-primary"></i>
                  </button>
                  <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors" title="Edit">
                    <i class="mdi mdi-pencil text-primary"></i>
                  </button>
                  <button class="p-2 hover:bg-danger/10 rounded-lg transition-colors" title="Delete">
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
          :total="totalVendors"
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

const showAddVendor = ref(false);

const stats = ref({
  total: 156,
  active: 142,
  pending: 8,
  suspended: 6,
});

const filters = ref({
  search: '',
  status: '',
  country: '',
});

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'pending', label: 'Pending' },
  { value: 'suspended', label: 'Suspended' },
];

const countryOptions = [
  { value: '', label: 'All Countries' },
  { value: 'ng', label: 'Nigeria' },
  { value: 'us', label: 'United States' },
  { value: 'uk', label: 'United Kingdom' },
];

const vendors = ref([
  { id: 1, name: 'Tech Store Nigeria', location: 'Lagos, Nigeria', email: 'contact@techstore.ng', phone: '+234 801 234 5678', products: 245, revenue: 1250000, status: 'Active', joined: '2024-01-15' },
  { id: 2, name: 'Fashion Hub', location: 'Abuja, Nigeria', email: 'info@fashionhub.ng', phone: '+234 802 345 6789', products: 189, revenue: 890000, status: 'Active', joined: '2024-02-01' },
  { id: 3, name: 'Home Essentials', location: 'Port Harcourt, Nigeria', email: 'sales@homeessentials.ng', phone: '+234 803 456 7890', products: 156, revenue: 650000, status: 'Pending', joined: '2024-02-05' },
]);

const currentPage = ref(1);
const perPage = ref(10);
const totalVendors = ref(156);
const totalPages = ref(Math.ceil(totalVendors.value / perPage.value));

const getStatusVariant = (status) => {
  const variants = {
    'Active': 'success',
    'Pending': 'warning',
    'Suspended': 'danger',
  };
  return variants[status] || 'secondary';
};

const clearFilters = () => {
  filters.value = { search: '', status: '', country: '' };
};

const handlePageChange = (page) => {
  currentPage.value = page;
};
</script>

