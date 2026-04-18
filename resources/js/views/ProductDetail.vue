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

            <div class="mb-4" v-if="product.has_options || (product.skus?.length || 0) > 1">
              <p class="text-sm font-semibold text-primary mb-2">Select Option</p>
              <VariantSelector :product="product" :model-value="selectedSku" @select="selectSku" />
            </div>

            <div class="mb-4">
              <p class="text-sm font-semibold text-primary mb-2">Quantity</p>
              <div class="inline-flex w-full max-w-xs items-center rounded-lg border border-DEFAULT bg-base overflow-hidden">
                <button
                  type="button"
                  class="h-11 w-12 flex items-center justify-center text-primary transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary/5"
                  :disabled="quantity <= 1"
                  aria-label="Decrease quantity"
                  @click="decrementQuantity"
                >
                  <i class="mdi mdi-minus text-lg"></i>
                </button>

                <input
                  v-model="quantityInput"
                  type="number"
                  min="1"
                  inputmode="numeric"
                  class="h-11 w-full text-center bg-transparent text-primary font-semibold outline-none border-x border-DEFAULT"
                  aria-label="Quantity"
                  @blur="sanitizeQuantity"
                  @keydown.enter.prevent="sanitizeQuantity"
                />

                <button
                  type="button"
                  class="h-11 w-12 flex items-center justify-center text-primary transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary/5"
                  :disabled="!canIncrement"
                  aria-label="Increase quantity"
                  @click="incrementQuantity"
                >
                  <i class="mdi mdi-plus text-lg"></i>
                </button>
              </div>
            </div>

            <div class="mb-6 text-sm" :class="inStock ? 'text-success' : 'text-danger'">
              {{ inStock ? 'In stock' : 'Out of stock' }}
            </div>

            <Button
              variant="primary"
              icon="cart-plus"
              class="w-full"
              :disabled="!canAddToCart || cart.loading"
              @click="addToCart"
            >
              Add to Cart
            </Button>
            <p v-if="requiresOptionSelection && !selectedSku" class="mt-2 text-xs text-danger">
              Please choose an option before adding to cart.
            </p>
            <p v-else-if="quantityAdjustedMessage" class="mt-2 text-xs text-warning">
              {{ quantityAdjustedMessage }}
            </p>
            <p v-else-if="stockHintText" class="mt-2 text-xs text-secondary">
              {{ stockHintText }}
            </p>
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
const quantity = ref(1);
const quantityInput = ref('1');
const errorMessage = ref('');
const quantityAdjustedMessage = ref('');
const activeImage = ref('/images/placeholders/product-placeholder.svg');
const cart = useCartStore();
const settingsStore = useSettingsStore();
const toast = inject('toast');

const requiresOptionSelection = computed(() => {
  if (!product.value) return false;
  return Boolean(product.value.has_options || (product.value.skus?.length || 0) > 1);
});

const displayPrice = computed(() => selectedSku.value?.price ?? product.value?.base_price ?? 0);
const getSkuAvailableStock = (sku) => {
  if (!sku) {
    return null;
  }

  if (Array.isArray(sku.stocks) && sku.stocks.length > 0) {
    return sku.stocks.reduce((sum, stock) => sum + Number(stock.on_hand || 0) - Number(stock.reserved || 0), 0);
  }

  if (Number.isFinite(Number(sku.stock_quantity))) {
    return Number(sku.stock_quantity);
  }

  return null;
};

const fallbackSimpleSku = computed(() => {
  if (!product.value?.skus?.length) {
    return null;
  }

  return product.value.skus.find((sku) => sku?.active !== false) || product.value.skus[0] || null;
});

const effectiveSku = computed(() => {
  if (selectedSku.value) {
    return selectedSku.value;
  }

  if (!requiresOptionSelection.value) {
    return fallbackSimpleSku.value;
  }

  return null;
});

const maxAvailableStock = computed(() => {
  const stock = getSkuAvailableStock(effectiveSku.value);
  if (stock === null || Number.isNaN(stock)) {
    return null;
  }
  return Math.max(0, Math.floor(stock));
});

const inStock = computed(() => {
  if (!effectiveSku.value) {
    return false;
  }

  const stock = maxAvailableStock.value;
  return stock === null ? true : stock > 0;
});

const quantityIsValid = computed(() => {
  const value = Number(quantity.value);
  if (!Number.isInteger(value) || value < 1) {
    return false;
  }

  if (maxAvailableStock.value !== null && value > maxAvailableStock.value) {
    return false;
  }

  return true;
});

const canIncrement = computed(() => {
  if (maxAvailableStock.value === null) {
    return true;
  }
  return quantity.value < maxAvailableStock.value;
});

const canAddToCart = computed(() => {
  if (requiresOptionSelection.value && !selectedSku.value) {
    return false;
  }
  return !!effectiveSku.value && inStock.value && quantityIsValid.value;
});

const stockHintText = computed(() => {
  if (maxAvailableStock.value === null) {
    return null;
  }

  if (maxAvailableStock.value <= 0) {
    return null;
  }

  if (maxAvailableStock.value <= 5) {
    return `Only ${maxAvailableStock.value} left in stock.`;
  }

  return `${maxAvailableStock.value} available.`;
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
    selectedSku.value = requiresOptionSelection.value ? null : (data?.skus?.[0] || null);
    quantity.value = 1;
    quantityInput.value = '1';
    quantityAdjustedMessage.value = '';
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
  sanitizeQuantity();
}

function sanitizeQuantity() {
  quantityAdjustedMessage.value = '';

  const parsed = Number.parseInt(String(quantityInput.value), 10);
  let normalized = Number.isInteger(parsed) ? parsed : 1;
  normalized = Math.max(1, normalized);

  if (maxAvailableStock.value !== null && maxAvailableStock.value > 0 && normalized > maxAvailableStock.value) {
    normalized = maxAvailableStock.value;
    quantityAdjustedMessage.value = `Quantity adjusted to available stock (${maxAvailableStock.value}).`;
  }

  quantity.value = normalized;
  quantityInput.value = String(normalized);
}

function incrementQuantity() {
  quantityInput.value = String(Number(quantity.value || 1) + 1);
  sanitizeQuantity();
}

function decrementQuantity() {
  quantityInput.value = String(Math.max(1, Number(quantity.value || 1) - 1));
  sanitizeQuantity();
}

async function addToCart() {
  sanitizeQuantity();

  if (requiresOptionSelection.value && !selectedSku.value) {
    toast?.warning('Select an option before adding to cart.');
    return;
  }

  if (!effectiveSku.value || !inStock.value || !quantityIsValid.value) {
    return;
  }

  const payload = requiresOptionSelection.value
    ? { product_id: product.value.id, sku_id: selectedSku.value.id }
    : { product_id: product.value.id };

  const result = await cart.addItem(payload, quantity.value);
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

watch(maxAvailableStock, () => {
  sanitizeQuantity();
});
</script>