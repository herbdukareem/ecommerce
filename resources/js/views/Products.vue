<template>
  <div class="flex">
    <!-- Reach Filter Sidebar -->
    <div class="w-1/4 hidden md:block pr-4">
      <ReachFilterSidebar :filters="catalog.filters" @update-filter="updateFilter" :facets="catalog.facets" />
    </div>
    <!-- Product Grid -->
    <div class="flex-1">
      <ProductGrid :products="catalog.products" />
      <div class="mt-4 flex justify-center">
        <!-- Simple pagination controls -->
        <button
          class="px-3 py-1 border mr-2"
          :disabled="catalog.filters.page <= 1"
          @click="changePage(catalog.filters.page - 1)"
        >Prev</button>
        <span>Page {{ catalog.filters.page }}</span>
        <button
          class="px-3 py-1 border ml-2"
          @click="changePage(catalog.filters.page + 1)"
        >Next</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useCatalogStore } from '../stores/catalog';
import ReachFilterSidebar from '../components/ReachFilterSidebar.vue';
import ProductGrid from '../components/ProductGrid.vue';

const catalog = useCatalogStore();

onMounted(() => {
  catalog.fetchProducts();
});

function updateFilter({ name, value }) {
  catalog.setFilter(name, value);
  catalog.fetchProducts();
}

function changePage(page) {
  catalog.setFilter('page', page);
  catalog.fetchProducts();
}
</script>