<template>
  <div class="overflow-x-auto">
    <table class="min-w-full">
      <thead>
        <tr class="border-b border-gray-200">
          <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700">Rank</th>
          <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700">Product</th>
          <th class="text-right py-3 px-2 text-sm font-semibold text-gray-700">Sold</th>
          <th class="text-right py-3 px-2 text-sm font-semibold text-gray-700">Revenue</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(product, index) in products"
          :key="product.id"
          class="border-b border-gray-100 hover:bg-gray-50"
        >
          <td class="py-3 px-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-800 font-bold text-sm">
              {{ index + 1 }}
            </span>
          </td>
          <td class="py-3 px-2 font-medium text-gray-900">{{ product.name }}</td>
          <td class="py-3 px-2 text-right text-gray-600">{{ product.total_sold }}</td>
          <td class="py-3 px-2 text-right font-semibold text-green-600">
            {{ formatCurrency(product.revenue) }}
          </td>
        </tr>
      </tbody>
    </table>
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

