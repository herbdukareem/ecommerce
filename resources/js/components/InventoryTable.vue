<template>
  <div>
    <h3 class="font-semibold mb-2">Inventory</h3>
    <table class="w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="py-2">SKU</th>
          <th class="py-2">On Hand</th>
          <th class="py-2">Reserved</th>
          <th class="py-2">Available</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="stock in stocks" :key="stock.id" class="border-b">
          <td class="py-2">{{ stock.sku.sku_code }}</td>
          <td class="py-2">{{ stock.on_hand }}</td>
          <td class="py-2">{{ stock.reserved }}</td>
          <td class="py-2">{{ stock.on_hand - stock.reserved }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  stocks: {
    type: Array,
    default: () => [],
  },
});
</script>