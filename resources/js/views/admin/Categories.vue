<template>
  <AdminLayout>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Categories</h1>
        <p class="text-secondary">Organize your product categories</p>
      </div>
      <div class="flex gap-3 mt-4 md:mt-0">
        <Button variant="outline" icon="sort">Reorder</Button>
        <Button variant="primary" icon="plus" @click="showAddCategory = true">Add Category</Button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Category Tree -->
      <div class="lg:col-span-2">
        <Card :elevation="2" title="Category Hierarchy" icon="file-tree">
          <div class="space-y-2">
            <div
              v-for="category in categories"
              :key="category.id"
              class="border border-DEFAULT rounded-lg overflow-hidden"
            >
              <!-- Parent Category -->
              <div
                @click="toggleCategory(category.id)"
                class="flex items-center justify-between p-4 hover:bg-base cursor-pointer transition-colors"
              >
                <div class="flex items-center gap-3 flex-1">
                  <button class="text-secondary hover:text-primary transition-colors">
                    <i :class="`mdi mdi-chevron-${expandedCategories.includes(category.id) ? 'down' : 'right'}`"></i>
                  </button>
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: category.color + '20' }">
                    <i :class="`mdi mdi-${category.icon} text-xl`" :style="{ color: category.color }"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="font-semibold text-primary">{{ category.name }}</h3>
                    <p class="text-xs text-secondary">{{ category.products }} products</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :variant="category.status === 'Active' ? 'success' : 'secondary'">
                    {{ category.status }}
                  </Badge>
                  <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors">
                    <i class="mdi mdi-pencil text-primary"></i>
                  </button>
                  <button class="p-2 hover:bg-danger/10 rounded-lg transition-colors">
                    <i class="mdi mdi-delete text-danger"></i>
                  </button>
                </div>
              </div>

              <!-- Subcategories -->
              <div v-if="expandedCategories.includes(category.id) && category.children" class="bg-base/50 border-t border-DEFAULT">
                <div
                  v-for="child in category.children"
                  :key="child.id"
                  class="flex items-center justify-between p-4 pl-16 hover:bg-base cursor-pointer transition-colors border-b border-DEFAULT last:border-b-0"
                >
                  <div class="flex items-center gap-3 flex-1">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                      <i :class="`mdi mdi-${child.icon} text-primary`"></i>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-medium text-primary">{{ child.name }}</h4>
                      <p class="text-xs text-secondary">{{ child.products }} products</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Badge variant="outline" size="sm">{{ child.status }}</Badge>
                    <button class="p-2 hover:bg-primary/10 rounded-lg transition-colors">
                      <i class="mdi mdi-pencil text-primary text-sm"></i>
                    </button>
                    <button class="p-2 hover:bg-danger/10 rounded-lg transition-colors">
                      <i class="mdi mdi-delete text-danger text-sm"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Category Stats & Quick Actions -->
      <div class="space-y-6">
        <!-- Stats -->
        <Card :elevation="2" title="Statistics" icon="chart-box">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-secondary">Total Categories</span>
              <span class="text-lg font-bold text-primary">24</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-secondary">Active</span>
              <span class="text-lg font-bold text-success">22</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-secondary">Inactive</span>
              <span class="text-lg font-bold text-secondary">2</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-secondary">Total Products</span>
              <span class="text-lg font-bold text-primary">2,543</span>
            </div>
          </div>
        </Card>

        <!-- Top Categories -->
        <Card :elevation="2" title="Top Categories" icon="trending-up">
          <div class="space-y-3">
            <div
              v-for="(cat, index) in topCategories"
              :key="cat.id"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-base transition-colors"
            >
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white text-sm font-bold">
                {{ index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-primary truncate">{{ cat.name }}</p>
                <p class="text-xs text-secondary">{{ cat.products }} products</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-success">{{ formatCurrency(cat.revenue) }}</p>
              </div>
            </div>
          </div>
        </Card>

        <!-- Quick Actions -->
        <Card :elevation="2" title="Quick Actions" icon="lightning-bolt">
          <div class="space-y-2">
            <Button variant="outline" icon="plus" class="w-full justify-start">
              Add New Category
            </Button>
            <Button variant="outline" icon="upload" class="w-full justify-start">
              Import Categories
            </Button>
            <Button variant="outline" icon="download" class="w-full justify-start">
              Export Categories
            </Button>
            <Button variant="outline" icon="sort" class="w-full justify-start">
              Bulk Reorder
            </Button>
          </div>
        </Card>
      </div>
    </div>

    <!-- Add Category Modal -->
    <Modal v-model="showAddCategory" title="Add New Category" icon="shape" size="lg">
      <div class="space-y-4">
        <Input
          v-model="newCategory.name"
          label="Category Name"
          placeholder="Enter category name"
          required
        />
        <Select
          v-model="newCategory.parent"
          label="Parent Category"
          :options="parentOptions"
          placeholder="Select parent (optional)"
        />
        <Input
          v-model="newCategory.slug"
          label="URL Slug"
          placeholder="category-slug"
          hint="Auto-generated from name"
        />
        <div class="grid grid-cols-2 gap-4">
          <Input
            v-model="newCategory.icon"
            label="Icon"
            placeholder="mdi-tag"
          />
          <Input
            v-model="newCategory.color"
            type="color"
            label="Color"
          />
        </div>
        <Select
          v-model="newCategory.status"
          label="Status"
          :options="[{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]"
        />
      </div>
      <template #actions>
        <Button variant="ghost" @click="showAddCategory = false">Cancel</Button>
        <Button variant="primary" icon="check">Create Category</Button>
      </template>
    </Modal>
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
import Modal from '../../components/ui/Modal.vue';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();
const { formatCurrency } = settingsStore;

const showAddCategory = ref(false);
const expandedCategories = ref([1, 2]);

const newCategory = ref({
  name: '',
  parent: '',
  slug: '',
  icon: 'tag',
  color: '#3B82F6',
  status: 'active',
});

const categories = ref([
  {
    id: 1,
    name: 'Electronics',
    icon: 'laptop',
    color: '#3B82F6',
    products: 856,
    status: 'Active',
    children: [
      { id: 11, name: 'Smartphones', icon: 'cellphone', products: 245, status: 'Active' },
      { id: 12, name: 'Laptops', icon: 'laptop', products: 189, status: 'Active' },
      { id: 13, name: 'Accessories', icon: 'headphones', products: 422, status: 'Active' },
    ],
  },
  {
    id: 2,
    name: 'Fashion',
    icon: 'tshirt-crew',
    color: '#8B5CF6',
    products: 1245,
    status: 'Active',
    children: [
      { id: 21, name: 'Men\'s Clothing', icon: 'human-male', products: 456, status: 'Active' },
      { id: 22, name: 'Women\'s Clothing', icon: 'human-female', products: 589, status: 'Active' },
      { id: 23, name: 'Shoes', icon: 'shoe-formal', products: 200, status: 'Active' },
    ],
  },
  {
    id: 3,
    name: 'Home & Garden',
    icon: 'home',
    color: '#10B981',
    products: 442,
    status: 'Active',
  },
]);

const topCategories = ref([
  { id: 1, name: 'Electronics', products: 856, revenue: 12500000 },
  { id: 2, name: 'Fashion', products: 1245, revenue: 8900000 },
  { id: 3, name: 'Home & Garden', products: 442, revenue: 3200000 },
]);

const parentOptions = [
  { value: '', label: 'None (Top Level)' },
  { value: '1', label: 'Electronics' },
  { value: '2', label: 'Fashion' },
  { value: '3', label: 'Home & Garden' },
];

const toggleCategory = (id) => {
  const index = expandedCategories.value.indexOf(id);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(id);
  }
};
</script>

