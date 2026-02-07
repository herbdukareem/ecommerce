<template>
  <router-link :to="`/products/${product.slug}`" class="block border rounded p-4 hover:shadow">
    <div class="h-40 bg-gray-100 mb-2 flex items-center justify-center">
      <span class="text-gray-400">Image</span>
    </div>
    <h3 class="font-semibold mb-1 truncate">{{ product.title }}</h3>
    <p class="text-gray-600">Starting at ₦{{ product.base_price }}</p>
  </router-link>
</template>

<script setup>
const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
});
</script>