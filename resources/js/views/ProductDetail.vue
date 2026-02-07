<template>
  <div v-if="loading" class="text-center py-8">Loading...</div>
  <div v-else-if="product" class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-2">{{ product.title }}</h2>
    <p class="text-gray-600 mb-4">{{ product.description }}</p>
    <div class="mb-4">
      <VariantSelector :product="product" @select="selectSku" />
    </div>
    <div class="mb-4">
      <strong>Price:</strong>
      <span>
        {{ selectedSku ? selectedSku.price : product.base_price }}
      </span>
    </div>
    <button
      class="bg-blue-600 text-white px-4 py-2 rounded"
      @click="addToCart"
    >Add to Cart</button>
  </div>
  <div v-else class="text-center py-8">Product not found.</div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import VariantSelector from '../components/VariantSelector.vue';
import { useCartStore } from '../stores/cart';

const route = useRoute();
const product = ref(null);
const loading = ref(true);
const selectedSku = ref(null);
const cart = useCartStore();

async function fetchProduct(slug) {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/products/${slug}`);
    product.value = data;
  } catch (error) {
    product.value = null;
  } finally {
    loading.value = false;
  }
}

function selectSku(sku) {
  selectedSku.value = sku;
}

async function addToCart() {
  if (!selectedSku.value) return;
  await cart.addItem(selectedSku.value.id, 1);
  alert('Added to cart');
}

onMounted(() => {
  fetchProduct(route.params.slug);
});

watch(() => route.params.slug, (slug) => {
  fetchProduct(slug);
});
</script>