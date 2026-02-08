<template>
  <div class="category-tree-item">
    <div
      class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg group"
      :class="{ 'bg-gray-50': expanded }"
    >
      <div class="flex items-center gap-3 flex-1">
        <!-- Expand/Collapse Button -->
        <button
          v-if="category.children && category.children.length > 0"
          @click="expanded = !expanded"
          class="text-gray-400 hover:text-gray-600"
        >
          <i :class="expanded ? 'mdi mdi-chevron-down' : 'mdi mdi-chevron-right'"></i>
        </button>
        <div v-else class="w-6"></div>

        <!-- Category Icon/Image -->
        <div v-if="category.image" class="w-8 h-8 rounded overflow-hidden">
          <img :src="category.image" :alt="category.name" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center">
          <i class="mdi mdi-folder text-gray-400"></i>
        </div>

        <!-- Category Info -->
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <span class="font-medium text-gray-900">{{ category.name }}</span>
            <span class="text-xs text-gray-500">({{ category.products_count || 0 }} products)</span>
          </div>
          <p v-if="category.description" class="text-xs text-gray-500 mt-0.5">
            {{ category.description }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <button
          @click="$emit('add-child', category)"
          class="p-1 text-blue-600 hover:bg-blue-50 rounded"
          title="Add Subcategory"
        >
          <i class="mdi mdi-plus-circle"></i>
        </button>
        <button
          @click="$emit('edit', category)"
          class="p-1 text-orange-600 hover:bg-orange-50 rounded"
          title="Edit"
        >
          <i class="mdi mdi-pencil"></i>
        </button>
        <button
          @click="$emit('delete', category)"
          class="p-1 text-red-600 hover:bg-red-50 rounded"
          title="Delete"
        >
          <i class="mdi mdi-delete"></i>
        </button>
      </div>
    </div>

    <!-- Children -->
    <div v-if="expanded && category.children && category.children.length > 0" class="ml-8 mt-1">
      <CategoryTreeItem
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @add-child="$emit('add-child', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  category: {
    type: Object,
    required: true,
  },
});

defineEmits(['edit', 'delete', 'add-child']);

const expanded = ref(true);
</script>

