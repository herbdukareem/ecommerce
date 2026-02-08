<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Vendors Management</h1>
          <p class="text-sm text-gray-600 mt-1">Manage vendor accounts and approvals</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          <i class="mdi mdi-plus"></i>
          Add Vendor
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Vendors</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-store text-blue-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Verified</p>
              <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.verified }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-check-circle text-green-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pending</p>
              <p class="text-2xl font-bold text-yellow-600 mt-1">{{ stats.pending }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-clock-outline text-yellow-600 text-2xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active Products</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.products }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="mdi mdi-package-variant text-purple-600 text-2xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <div class="relative">
              <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search vendors by name or email..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <select
            v-model="filters.verified"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            @change="fetchVendors"
          >
            <option value="">All Status</option>
            <option value="1">Verified</option>
            <option value="0">Pending Approval</option>
          </select>
        </div>
      </div>

      <!-- Vendors Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <i class="mdi mdi-loading mdi-spin text-4xl text-orange-500"></i>
          <p class="text-gray-600 mt-2">Loading vendors...</p>
        </div>

        <div v-else-if="vendors.length === 0" class="p-8 text-center">
          <i class="mdi mdi-store-off text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-2">No vendors found</p>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Vendor
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contact
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Products
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Joined
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="vendor in vendors" :key="vendor.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mr-3">
                    <span class="text-white font-bold text-sm">{{ vendor.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ vendor.name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ vendor.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ vendor.email }}</div>
                <div v-if="vendor.phone" class="text-xs text-gray-500">{{ vendor.phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ vendor.products_count || 0 }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="{
                    'bg-green-100 text-green-800': vendor.is_verified,
                    'bg-yellow-100 text-yellow-800': !vendor.is_verified,
                  }"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ vendor.is_verified ? 'Verified' : 'Pending' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(vendor.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    v-if="!vendor.is_verified"
                    @click="approveVendor(vendor)"
                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-xs"
                    title="Approve Vendor"
                  >
                    <i class="mdi mdi-check"></i> Approve
                  </button>
                  <button
                    @click="viewVendor(vendor)"
                    class="text-blue-600 hover:text-blue-900"
                    title="View Details"
                  >
                    <i class="mdi mdi-eye"></i>
                  </button>
                  <button
                    @click="editVendor(vendor)"
                    class="text-orange-600 hover:text-orange-900"
                    title="Edit"
                  >
                    <i class="mdi mdi-pencil"></i>
                  </button>
                  <button
                    @click="deleteVendor(vendor)"
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
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} vendors
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

      <!-- Create/Edit Vendor Modal -->
      <div
        v-if="showCreateModal || showEditModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">
              {{ showEditModal ? 'Edit Vendor' : 'Add New Vendor' }}
            </h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <i class="mdi mdi-close text-2xl"></i>
            </button>
          </div>

          <form @submit.prevent="saveVendor" class="p-6 space-y-6">
            <!-- Vendor Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Vendor Information</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                  <input
                    v-model="vendorForm.name"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter business name"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                  <input
                    v-model="vendorForm.email"
                    type="email"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="vendor@example.com"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                  <input
                    v-model="vendorForm.phone"
                    type="tel"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="+234 XXX XXX XXXX"
                  />
                </div>

                <div v-if="!showEditModal">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                  <input
                    v-model="vendorForm.password"
                    type="password"
                    :required="!showEditModal"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter password"
                  />
                </div>

                <div v-if="!showEditModal">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                  <input
                    v-model="vendorForm.password_confirmation"
                    type="password"
                    :required="!showEditModal"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Confirm password"
                  />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Business Address</label>
                  <textarea
                    v-model="vendorForm.address"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter business address"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                  <input
                    v-model="vendorForm.city"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter city"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                  <input
                    v-model="vendorForm.state"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Enter state"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                  <select
                    v-model="vendorForm.is_verified"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  >
                    <option :value="false">Pending</option>
                    <option :value="true">Verified</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select
                    v-model="vendorForm.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                  </select>
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
                {{ saving ? 'Saving...' : (showEditModal ? 'Update Vendor' : 'Create Vendor') }}
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

const router = useRouter();

const vendors = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const saving = ref(false);

const vendorForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  address: '',
  city: '',
  state: '',
  is_verified: false,
  status: 'active',
});

const stats = ref({
  total: 0,
  verified: 0,
  pending: 0,
  products: 0,
});

const filters = ref({
  search: '',
  verified: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

// Fetch vendors from API
const fetchVendors = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/vendors', {
      params: {
        search: filters.value.search,
        verified: filters.value.verified,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      },
    });

    vendors.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from,
      to: data.to,
    };

    // Update stats
    updateStats();
  } catch (error) {
    console.error('Failed to fetch vendors:', error);
  } finally {
    loading.value = false;
  }
};

// Update statistics
const updateStats = () => {
  stats.value.total = pagination.value.total;
  stats.value.verified = vendors.value.filter(v => v.is_verified).length;
  stats.value.pending = vendors.value.filter(v => !v.is_verified).length;
  stats.value.products = vendors.value.reduce((sum, v) => sum + (v.products_count || 0), 0);
};

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    fetchVendors();
  }, 500);
};

// Modal functions
const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  resetForm();
};

const resetForm = () => {
  vendorForm.value = {
    id: null,
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    address: '',
    city: '',
    state: '',
    is_verified: false,
    status: 'active',
  };
};

// Save vendor
const saveVendor = async () => {
  // Validate passwords match
  if (!showEditModal.value && vendorForm.value.password !== vendorForm.value.password_confirmation) {
    alert('Passwords do not match!');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      name: vendorForm.value.name,
      email: vendorForm.value.email,
      phone: vendorForm.value.phone,
      address: vendorForm.value.address,
      city: vendorForm.value.city,
      state: vendorForm.value.state,
      is_verified: vendorForm.value.is_verified,
      status: vendorForm.value.status,
    };

    // Only include password for new vendors
    if (!showEditModal.value) {
      payload.password = vendorForm.value.password;
      payload.password_confirmation = vendorForm.value.password_confirmation;
    }

    if (showEditModal.value) {
      await axios.put(`/api/admin/vendors/${vendorForm.value.id}`, payload);
    } else {
      await axios.post('/api/admin/vendors', payload);
    }

    closeModal();
    fetchVendors();
    alert('Vendor saved successfully!');
  } catch (error) {
    console.error('Failed to save vendor:', error);
    const message = error.response?.data?.message || 'Failed to save vendor. Please try again.';
    alert(message);
  } finally {
    saving.value = false;
  }
};

// Approve vendor
const approveVendor = async (vendor) => {
  if (!confirm(`Approve vendor "${vendor.name}"?`)) {
    return;
  }

  try {
    await axios.post(`/api/admin/vendors/${vendor.id}/approve`);
    fetchVendors();
    alert('Vendor approved successfully!');
  } catch (error) {
    console.error('Failed to approve vendor:', error);
    alert('Failed to approve vendor. Please try again.');
  }
};

// View vendor details
const viewVendor = (vendor) => {
  router.push(`/admin/vendors/${vendor.id}`);
};

// Edit vendor
const editVendor = (vendor) => {
  vendorForm.value = {
    id: vendor.id,
    name: vendor.name,
    email: vendor.email,
    phone: vendor.phone || '',
    password: '',
    password_confirmation: '',
    address: vendor.address || '',
    city: vendor.city || '',
    state: vendor.state || '',
    is_verified: vendor.is_verified || false,
    status: vendor.status || 'active',
  };
  showEditModal.value = true;
};

// Delete vendor
const deleteVendor = async (vendor) => {
  if (!confirm(`Are you sure you want to delete vendor "${vendor.name}"? This will also delete all their products.`)) {
    return;
  }

  try {
    await axios.delete(`/api/admin/vendors/${vendor.id}`);
    fetchVendors();
    alert('Vendor deleted successfully!');
  } catch (error) {
    console.error('Failed to delete vendor:', error);
    alert('Failed to delete vendor. Please try again.');
  }
};

// Pagination
const goToPage = (page) => {
  pagination.value.current_page = page;
  fetchVendors();
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

// Format date
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Initialize
onMounted(() => {
  fetchVendors();
});
</script>


