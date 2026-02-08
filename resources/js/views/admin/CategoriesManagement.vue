<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Categories Management</h1>
          <p class="text-sm text-gray-600 mt-1">Organize products into categories and subcategories</p>
        </div>
        <button
          @click="openCreateModal()"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          <i class="mdi mdi-plus"></i>
          Add Category
        </button>
      </div>

      <!-- View Toggle -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex gap-2">
            <button
              @click="viewMode = 'tree'"
              :class="{
                'bg-orange-600 text-white': viewMode === 'tree',
                'bg-gray-100 text-gray-700': viewMode !== 'tree',
              }"
              class="px-4 py-2 rounded-lg transition-colors"
            >
              <i class="mdi mdi-file-tree"></i> Tree View
            </button>
            <button
              @click="viewMode = 'list'"
              :class="{
                'bg-orange-600 text-white': viewMode === 'list',
                'bg-gray-100 text-gray-700': viewMode !== 'list',
              }"
              class="px-4 py-2 rounded-lg transition-colors"
            >
              <i class="mdi mdi-view-list"></i> List View
            </button>
          </div>
          <div class="relative">
            <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search categories..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
          </div>
        </div>
      </div>

      <!-- Categories Content -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <i class="mdi mdi-loading mdi-spin text-4xl text-orange-500"></i>
          <p class="text-gray-600 mt-2">Loading categories...</p>
        </div>

        <div v-else-if="filteredCategories.length === 0" class="p-8 text-center">
          <i class="mdi mdi-folder-off text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-2">No categories found</p>
        </div>

        <!-- Tree View -->
        <div v-else-if="viewMode === 'tree'" class="p-6">
          <CategoryTreeItem
            v-for="category in categoryTree"
            :key="category.id"
            :category="category"
            @edit="openEditModal"
            @delete="deleteCategory"
            @add-child="openCreateModal"
          />
        </div>

        <!-- List View -->
        <table v-else class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Parent
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Products
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Order
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="category in filteredCategories" :key="category.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div v-if="category.image" class="w-10 h-10 rounded-lg overflow-hidden mr-3">
                    <img :src="category.image" :alt="category.name" class="w-full h-full object-cover" />
                  </div>
                  <div v-else class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="mdi mdi-folder text-gray-400"></i>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                    <div v-if="category.description" class="text-xs text-gray-500">{{ category.description }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ category.parent?.name || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ category.products_count || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-1">
                  <button
                    @click="moveCategory(category, 'up')"
                    class="text-gray-400 hover:text-gray-600"
                    title="Move Up"
                  >
                    <i class="mdi mdi-arrow-up"></i>
                  </button>
                  <span class="text-sm text-gray-600">{{ category.order }}</span>
                  <button
                    @click="moveCategory(category, 'down')"
                    class="text-gray-400 hover:text-gray-600"
                    title="Move Down"
                  >
                    <i class="mdi mdi-arrow-down"></i>
                  </button>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    @click="openCreateModal(category)"
                    class="text-blue-600 hover:text-blue-900"
                    title="Add Subcategory"
                  >
                    <i class="mdi mdi-plus-circle"></i>
                  </button>
                  <button
                    @click="openEditModal(category)"
                    class="text-orange-600 hover:text-orange-900"
                    title="Edit"
                  >
                    <i class="mdi mdi-pencil"></i>
                  </button>
                  <button
                    @click="deleteCategory(category)"
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
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-xl font-bold text-gray-900">
            {{ editingCategory ? 'Edit Category' : 'Create Category' }}
          </h2>
        </div>
        <form @submit.prevent="saveCategory" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="Enter category name"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="Enter category description"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
            <select
              v-model="form.parent_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option :value="null">None (Top Level)</option>
              <option
                v-for="category in availableParents"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
            <input
              v-model="form.image"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="https://example.com/image.jpg"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
            <input
              v-model.number="form.order"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50"
            >
              <i v-if="saving" class="mdi mdi-loading mdi-spin mr-2"></i>
              {{ saving ? 'Saving...' : 'Save Category' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import CategoryTreeItem from '../../components/admin/CategoryTreeItem.vue';

const categories = ref([]);
const loading = ref(false);
const saving = ref(false);
const viewMode = ref('tree');
const searchQuery = ref('');
const showModal = ref(false);
const editingCategory = ref(null);

const form = ref({
  name: '',
  description: '',
  parent_id: null,
  image: '',
  order: 0,
});

// Fetch categories
const fetchCategories = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/categories', {
      params: { tree: viewMode.value === 'tree' ? 'true' : 'false' },
    });
    categories.value = data;
  } catch (error) {
    console.error('Failed to fetch categories:', error);
  } finally {
    loading.value = false;
  }
};

// Build category tree
const categoryTree = computed(() => {
  if (viewMode.value !== 'tree') return [];

  const buildTree = (parentId = null) => {
    return categories.value
      .filter(cat => cat.parent_id === parentId)
      .map(cat => ({
        ...cat,
        children: buildTree(cat.id),
      }));
  };

  return buildTree();
});

// Filtered categories for search
const filteredCategories = computed(() => {
  if (!searchQuery.value) return categories.value;

  const query = searchQuery.value.toLowerCase();
  return categories.value.filter(cat =>
    cat.name.toLowerCase().includes(query) ||
    cat.description?.toLowerCase().includes(query)
  );
});

// Available parent categories (exclude self and descendants)
const availableParents = computed(() => {
  if (!editingCategory.value) return categories.value;

  const excludeIds = new Set([editingCategory.value.id]);
  const addDescendants = (parentId) => {
    categories.value
      .filter(cat => cat.parent_id === parentId)
      .forEach(cat => {
        excludeIds.add(cat.id);
        addDescendants(cat.id);
      });
  };
  addDescendants(editingCategory.value.id);

  return categories.value.filter(cat => !excludeIds.has(cat.id));
});

// Open create modal
const openCreateModal = (parent = null) => {
  editingCategory.value = null;
  form.value = {
    name: '',
    description: '',
    parent_id: parent?.id || null,
    image: '',
    order: 0,
  };
  showModal.value = true;
};

// Open edit modal
const openEditModal = (category) => {
  editingCategory.value = category;
  form.value = {
    name: category.name,
    description: category.description || '',
    parent_id: category.parent_id,
    image: category.image || '',
    order: category.order || 0,
  };
  showModal.value = true;
};

// Close modal
const closeModal = () => {
  showModal.value = false;
  editingCategory.value = null;
};

// Save category
const saveCategory = async () => {
  saving.value = true;
  try {
    if (editingCategory.value) {
      await axios.put(`/api/admin/categories/${editingCategory.value.id}`, form.value);
    } else {
      await axios.post('/api/admin/categories', form.value);
    }
    closeModal();
    fetchCategories();
  } catch (error) {
    console.error('Failed to save category:', error);
    alert('Failed to save category. Please try again.');
  } finally {
    saving.value = false;
  }
};

// Delete category
const deleteCategory = async (category) => {
  if (!confirm(`Delete "${category.name}"? This will also delete all subcategories.`)) {
    return;
  }

  try {
    await axios.delete(`/api/admin/categories/${category.id}`);
    fetchCategories();
  } catch (error) {
    console.error('Failed to delete category:', error);
    alert('Failed to delete category. Please try again.');
  }
};

// Move category up/down
const moveCategory = async (category, direction) => {
  const siblings = categories.value.filter(c => c.parent_id === category.parent_id);
  const currentIndex = siblings.findIndex(c => c.id === category.id);

  if (direction === 'up' && currentIndex === 0) return;
  if (direction === 'down' && currentIndex === siblings.length - 1) return;

  const newOrder = direction === 'up' ? category.order - 1 : category.order + 1;

  try {
    await axios.put(`/api/admin/categories/${category.id}`, { order: newOrder });
    fetchCategories();
  } catch (error) {
    console.error('Failed to reorder category:', error);
  }
};

// Initialize
onMounted(() => {
  fetchCategories();
});
</script>


