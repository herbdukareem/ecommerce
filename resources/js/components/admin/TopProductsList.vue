<template>
  <div class="space-y-4">
    <div
      v-for="(product, index) in products"
      :key="product.id"
      class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
    >
      <div class="flex items-center space-x-3">
        <span class="text-lg font-bold text-gray-400">#{{ index + 1 }}</span>
        <div>
          <p class="font-medium text-gray-900">{{ product.name }}</p>
          <p class="text-sm text-gray-500">{{ product.total_sold }} sold</p>
        </div>
      </div>
      <div class="text-right">
        <p class="font-semibold text-green-600">{{ formatCurrency(product.revenue) }}</p>
      </div>
    </div>
    <div v-if="!products.length" class="text-center text-gray-500 py-8">
      No data available
    </div>
  </div>
</template>

<script setup>
defineProps({
  products: {
    type: Array,
    default: () => [],
  },
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value || 0);
};
</script>

