<template>
  <MainLayout>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div v-if="loading" class="py-16 text-center text-secondary">Loading product...</div>
      <div v-else-if="errorMessage" class="py-16 text-center text-danger">{{ errorMessage }}</div>
      <div v-else-if="!product" class="py-16 text-center text-secondary">Product not found.</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <Card :elevation="2" class="p-6">
          <div class="rounded-xl h-96 bg-base border border-DEFAULT overflow-hidden mb-3">
            <img
              :src="activeImage"
              :alt="product.title"
              class="w-full h-full object-cover"
              @error="onImageError"
            />
          </div>
          <div v-if="galleryImages.length > 1" class="grid grid-cols-4 sm:grid-cols-6 gap-2">
            <button
              v-for="(image, index) in galleryImages"
              :key="`${image}-${index}`"
              type="button"
              class="h-16 rounded-md overflow-hidden border transition"
              :class="image === activeImage ? 'border-primary' : 'border-DEFAULT hover:border-primary/50'"
              @click="activeImage = image"
            >
              <img
                :src="image"
                :alt="`${product.title} ${index + 1}`"
                class="w-full h-full object-cover"
                @error="onThumbnailError"
              />
            </button>
          </div>
        </Card>

        <div>
          <p class="text-sm text-secondary mb-2">{{ product.categories?.[0]?.name || 'General' }}</p>
          <h1 class="text-3xl font-bold text-primary mb-3">{{ product.title }}</h1>
          <p class="text-secondary mb-6">{{ product.description || 'No description available.' }}</p>

          <Card :elevation="2" class="p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
              <span class="text-secondary">Price</span>
              <span class="text-2xl font-bold text-primary">{{ formatCurrency(displayPrice) }}</span>
            </div>

            <div class="mb-4">
              <p class="text-sm font-semibold text-primary mb-2">Select Variant</p>
              <VariantSelector :product="product" @select="selectSku" />
            </div>

            <div class="mb-6 text-sm" :class="inStock ? 'text-success' : 'text-danger'">
              {{ inStock ? 'In stock' : 'Out of stock' }}
            </div>

            <Button
              variant="primary"
              icon="cart-plus"
              class="w-full"
              :disabled="!selectedSku || !inStock"
              @click="addToCart"
            >
              Add to Cart
            </Button>
          </Card>
        </div>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import VariantSelector from '../components/VariantSelector.vue';
import { useCartStore } from '../stores/cart';
import { useSettingsStore } from '../stores/settings';
import { normalizeProductMedia } from '../utils/productMedia';

const route = useRoute();
const product = ref(null);
const loading = ref(true);
const selectedSku = ref(null);
const errorMessage = ref('');
const activeImage = ref('/images/placeholders/product-placeholder.svg');
const cart = useCartStore();
const settingsStore = useSettingsStore();
const toast = inject('toast');

const displayPrice = computed(() => selectedSku.value?.price ?? product.value?.base_price ?? 0);
const inStock = computed(() => {
  if (!selectedSku.value) return false;
  if (Array.isArray(selectedSku.value.stocks) && selectedSku.value.stocks.length > 0) {
    return selectedSku.value.stocks.some(stock => (stock.on_hand - stock.reserved) > 0);
  }
  return true;
});
const galleryImages = computed(() => {
  const media = normalizeProductMedia(product.value);
  return media.gallery.length ? media.gallery : [media.placeholder];
});

const formatCurrency = settingsStore.formatCurrency;

async function fetchProduct(slug) {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/products/${slug}`);
    product.value = data;
    const media = normalizeProductMedia(data);
    activeImage.value = media.primaryImage || media.placeholder;
    selectedSku.value = data?.skus?.[0] || null;
    errorMessage.value = '';
  } catch (error) {
    product.value = null;
    errorMessage.value = error.response?.data?.message || 'Failed to load product.';
  } finally {
    loading.value = false;
  }
}

function onImageError(event) {
  if (!event?.target) return;
  const media = normalizeProductMedia(product.value);
  event.target.src = media.placeholder;
  activeImage.value = media.placeholder;
}

function onThumbnailError(event) {
  if (!event?.target) return;
  const media = normalizeProductMedia(product.value);
  event.target.src = media.placeholder;
}

function selectSku(sku) {
  selectedSku.value = sku;
}

async function addToCart() {
  if (!selectedSku.value || !inStock.value) return;
  const result = await cart.addItem(selectedSku.value.id, 1);
  if (result.success) {
    toast?.success('Added to cart');
  } else {
    toast?.error(result.error || 'Failed to add item to cart');
  }
}

onMounted(() => {
  fetchProduct(route.params.slug);
});

watch(() => route.params.slug, (slug) => {
  fetchProduct(slug);
});
</script>