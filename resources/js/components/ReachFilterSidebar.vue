<template>
  <div class="space-y-4">
    <!-- Price Range -->
    <div>
      <h4 class="font-semibold mb-2">Price</h4>
      <input
        type="number"
        placeholder="Min"
        v-model.number="localFilters.price_min"
        class="border p-1 w-full mb-1"
      />
      <input
        type="number"
        placeholder="Max"
        v-model.number="localFilters.price_max"
        class="border p-1 w-full"
      />
    </div>
    <!-- In Stock -->
    <div>
      <label class="flex items-center">
        <input type="checkbox" v-model="localFilters.in_stock" class="mr-2" />
        In Stock Only
      </label>
    </div>
    <!-- Sort -->
    <div>
      <label class="block mb-1 font-semibold">Sort By</label>
      <select v-model="localFilters.sort" class="border p-1 w-full">
        <option value="newest">Newest</option>
        <option value="price_asc">Price: Low → High</option>
        <option value="price_desc">Price: High → Low</option>
      </select>
    </div>
    <div>
      <button class="bg-blue-600 text-white px-3 py-1 rounded" @click="applyFilters">Apply</button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
  filters: Object,
  facets: Object,
});
const emit = defineEmits(['update-filter']);

const localFilters = reactive({ ...props.filters });

// Watchers could sync filters on change with debounce for instant search
const emitDebounced = debounce(() => {
  emit('update-filter', { name: 'price_min', value: localFilters.price_min });
  emit('update-filter', { name: 'price_max', value: localFilters.price_max });
  emit('update-filter', { name: 'in_stock', value: localFilters.in_stock });
  emit('update-filter', { name: 'sort', value: localFilters.sort });
}, 300);

watch(localFilters, emitDebounced, { deep: true });

function applyFilters() {
  // Immediately apply filters when the apply button is clicked
  emit('update-filter', { name: 'price_min', value: localFilters.price_min });
  emit('update-filter', { name: 'price_max', value: localFilters.price_max });
  emit('update-filter', { name: 'in_stock', value: localFilters.in_stock });
  emit('update-filter', { name: 'sort', value: localFilters.sort });
}
</script>