<template>
  <MainLayout>
    <!-- Launching Soon Banner -->
    <LaunchingBanner />

    <!-- Hero Section -->
    <section
      v-if="hero.enabled"
      class="relative overflow-hidden"
      :class="heroSectionClass"
      :style="heroSectionStyle"
    >
      <div class="absolute inset-0 pointer-events-none" :class="hero.backgroundStyle === 'full_image' ? 'bg-black/35' : 'bg-transparent'"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div :class="heroGridClass">
          <div :class="heroTextClass">
            <p v-if="hero.eyebrow" class="mb-4 text-sm font-semibold uppercase tracking-[0.2em]" :class="heroTextOnDark ? 'text-white/75' : 'text-secondary'">
              {{ hero.eyebrow }}
            </p>
            <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight" :class="heroTextOnDark ? 'text-white' : 'text-primary'">
              {{ hero.headline }}
            </h1>
            <p class="text-xl mb-8 leading-8" :class="heroTextOnDark ? 'text-white/82' : 'text-secondary'">
              {{ hero.subheadline }}
            </p>
            <div class="flex flex-wrap gap-4" :class="hero.layout === 'centered' || hero.layout === 'full_bleed' ? 'justify-center' : ''">
              <Button
                v-if="hero.primaryButtonLabel && hero.primaryButtonUrl"
                variant="primary"
                size="lg"
                icon="shopping"
                @click="goTo(hero.primaryButtonUrl)"
              >
                {{ hero.primaryButtonLabel }}
              </Button>
              <Button
                v-if="hero.secondaryButtonLabel && hero.secondaryButtonUrl"
                variant="outline"
                size="lg"
                icon="tag"
                @click="goTo(hero.secondaryButtonUrl)"
              >
                {{ hero.secondaryButtonLabel }}
              </Button>
            </div>
          </div>

          <div v-if="hero.layout !== 'centered' && hero.layout !== 'full_bleed'" class="animate-fade-in-right" :class="hero.layout === 'image_left' ? 'lg:order-first' : ''">
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-primary-dark/20 rounded-3xl blur-3xl"></div>
              <div class="relative rounded-3xl shadow-material-5 w-full h-96 bg-gradient-to-br from-primary/10 to-primary-dark/10 flex items-center justify-center overflow-hidden">
                <img
                  v-if="hero.imageUrl"
                  :src="hero.imageUrl"
                  :alt="hero.headline"
                  class="h-full w-full object-cover"
                />
                <img
                  v-else
                  :src="launchingSoon"
                  :alt="hero.headline"
                  class="h-full w-full object-contain p-8"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8 animate-fade-in-down">
          <div>
            <h2 class="text-3xl font-bold text-primary mb-2">Featured Products</h2>
            <p class="text-secondary">Handpicked items just for you</p>
          </div>
          <Button variant="ghost" icon="arrow-right" icon-right @click="$router.push('/products')">
            View All
          </Button>
        </div>

        <div v-if="loading" class="py-10 text-center text-secondary">Loading featured products...</div>
        <div v-else-if="loadError" class="py-10 text-center text-danger">{{ loadError }}</div>
        <div v-else-if="featuredProducts.length === 0" class="py-10 text-center text-secondary">No featured products yet.</div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card
            v-for="(product, index) in featuredProducts"
            :key="product.id"
            :elevation="2"
            hoverable
            clickable
            allow-overflow
            animation="fade-in-up"
            :class="`stagger-${index + 1}`"
            @click="$router.push(`/products/${product.slug}`)"
          >
            <div class="relative mb-4">
              <div class="w-full h-48 bg-base rounded-lg overflow-hidden border border-DEFAULT">
                <img
                  :src="product.primary_image_url || product.image_placeholder"
                  :alt="product.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                  @error="onImageError($event, product.image_placeholder)"
                />
              </div>
              <Badge
                v-if="product.discount"
                variant="danger"
                class="absolute top-2 right-2"
              >
                -{{ product.discount }}%
              </Badge>
            </div>
            <h3 class="font-semibold text-primary mb-2">{{ product.title }}</h3>
            <span v-if="product.pay_on_delivery_enabled" class="mb-2 inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-1 text-xs font-semibold text-success">
              <i class="mdi mdi-cash-check"></i>
              Pay on delivery
            </span>
            <div class="flex items-center gap-2 mb-2">
              <div class="flex items-center gap-1 text-warning">
                <i class="mdi mdi-star text-sm"></i>
                <span class="text-sm font-medium">{{ product.average_rating ?? 'N/A' }}</span>
              </div>
              <span class="text-xs text-secondary">({{ product.review_count ?? 0 }} reviews)</span>
            </div>
            <div class="flex items-center justify-between">
              <div>
                <span class="text-lg font-bold text-primary">{{ product.has_options ? `From ${formatCurrency(product.display_price || product.base_price)}` : formatCurrency(product.base_price) }}</span>
                <span v-if="product.originalPrice" class="text-sm text-secondary line-through ml-2">
                  {{ formatCurrency(product.originalPrice) }}
                </span>
              </div>

              <div class="relative" data-option-menu>
                <Button
                  v-if="needsQuickOptionSelection(product)"
                  variant="outline"
                  size="sm"
                  icon="tune-variant"
                  @click.stop="toggleOptionMenu(product.id)"
                >
                  Select options
                </Button>
                <Button
                  v-else
                  variant="primary"
                  size="sm"
                  icon="cart-plus"
                  icon-only
                  :disabled="isProductOutOfStock(product)"
                  @click.stop="addToCart(product)"
                />

                <div
                  v-if="openOptionMenuId === product.id"
                  class="absolute right-0 mt-2 z-20 w-64 rounded-lg border border-DEFAULT bg-base p-3 shadow-material-3"
                  @click.stop
                >
                  <p class="text-xs font-semibold uppercase tracking-wide text-secondary mb-2">Choose option</p>
                  <div class="max-h-56 overflow-y-auto space-y-2">
                    <button
                      v-for="sku in productQuickOptions(product)"
                      :key="sku.id"
                      type="button"
                      class="w-full rounded-md border px-2 py-2 text-left transition"
                      :class="optionButtonClass(sku, selectedOptionByProduct[product.id])"
                      :disabled="!isSkuPurchasable(sku)"
                      @click="selectedOptionByProduct[product.id] = sku.id"
                    >
                      <p class="text-sm font-semibold text-primary truncate">{{ sku.display_label || sku.option_label || sku.label || sku.sku_code }}</p>
                      <p class="text-xs text-secondary">{{ formatCurrency(sku.price || 0) }}</p>
                    </button>
                  </div>
                  <Button
                    variant="primary"
                    size="sm"
                    class="w-full mt-3"
                    :disabled="!selectedOptionByProduct[product.id] || quickAddLoading[product.id]"
                    @click="quickAddSelectedOption(product)"
                  >
                    {{ quickAddLoading[product.id] ? 'Adding...' : 'Add to cart' }}
                  </Button>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="py-16 bg-surface">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 animate-fade-in-down">
          <h2 class="text-3xl font-bold text-primary mb-2">Shop by Category</h2>
          <p class="text-secondary">Explore our wide range of products</p>
        </div>

        <div v-if="loading" class="py-8 text-center text-secondary">Loading categories...</div>
        <div v-else-if="categories.length === 0" class="py-8 text-center text-secondary">No categories available.</div>
        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <Card
            v-for="(category, index) in categories"
            :key="category.id"
            :elevation="2"
            hoverable
            clickable
            animation="fade-in-up"
            :class="`stagger-${index + 1}`"
            @click="$router.push(`/products?category_id=${category.id}`)"
          >
            <div class="text-center">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-3">
                <i :class="`mdi mdi-${category.icon || 'shape'} text-2xl text-primary`"></i>
              </div>
              <h3 class="text-sm font-semibold text-primary">{{ category.name }}</h3>
            </div>
          </Card>
        </div>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Badge from '../components/ui/Badge.vue';
import LaunchingBanner from '../components/ui/LaunchingBanner.vue';
import launchingSoon from '@/assets/images/cart-bag.png';
import { useCartStore } from '../stores/cart';
import { useSettingsStore } from '../stores/settings';
import { useAuthStore } from '../stores/auth';
import { resolveProductPurchase } from '../utils/productPurchase';
import { normalizeProductMedia } from '../utils/productMedia';

const cartStore = useCartStore();
const settingsStore = useSettingsStore();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const toast = inject('toast');
const featuredProducts = ref([]);
const categories = ref([]);
const loading = ref(false);
const loadError = ref('');
const openOptionMenuId = ref(null);
const selectedOptionByProduct = ref({});
const quickAddLoading = ref({});

const formatCurrency = settingsStore.formatCurrency;
const hero = computed(() => settingsStore.homepageHero);
const heroTextOnDark = computed(() => ['solid', 'full_image'].includes(hero.value.backgroundStyle));
const heroSectionClass = computed(() => {
  if (hero.value.backgroundStyle === 'solid') return 'bg-primary';
  if (hero.value.backgroundStyle === 'light') return 'bg-surface';
  return 'bg-gradient-to-br from-primary/10 via-primary/5 to-transparent';
});
const heroSectionStyle = computed(() => {
  if (!hero.value.imageUrl || !['full_bleed', 'full_image'].includes(hero.value.backgroundStyle)) {
    return {};
  }

  return {
    backgroundImage: `url("${hero.value.imageUrl}")`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
  };
});
const heroGridClass = computed(() => {
  if (hero.value.layout === 'centered' || hero.value.layout === 'full_bleed') {
    return 'max-w-4xl mx-auto text-center';
  }

  return 'grid grid-cols-1 lg:grid-cols-2 gap-12 items-center';
});
const heroTextClass = computed(() => {
  const centered = hero.value.layout === 'centered' || hero.value.layout === 'full_bleed';
  return centered ? 'animate-fade-in-up' : 'animate-fade-in-left';
});
const isAuthenticated = computed(() => authStore.isAuthenticated);
const isProductOutOfStock = (product) => resolveProductPurchase(product).outOfStock;
const needsQuickOptionSelection = (product) => {
  const purchase = resolveProductPurchase(product);
  return purchase.requiresSelection && !purchase.outOfStock;
};

const skuAvailableStock = (sku) => {
  if (Number.isFinite(Number(sku?.available_stock))) {
    return Number(sku.available_stock);
  }

  if (Array.isArray(sku?.stocks) && sku.stocks.length > 0) {
    return sku.stocks.reduce((sum, stock) => sum + Number(stock?.on_hand || 0) - Number(stock?.reserved || 0), 0);
  }

  return Number(sku?.stock_quantity || 0);
};

const isSkuActive = (sku) => Boolean(sku) && sku.active !== false && sku.is_active !== false;
const isSkuPurchasable = (sku) => isSkuActive(sku) && skuAvailableStock(sku) > 0;
const productQuickOptions = (product) => (product?.skus || [])
  .filter((sku) => isSkuActive(sku))
  .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0));

const optionButtonClass = (sku, selectedId) => {
  if (!isSkuPurchasable(sku)) {
    return 'border-DEFAULT bg-surface opacity-50 cursor-not-allowed';
  }

  if (Number(selectedId) === Number(sku.id)) {
    return 'border-primary bg-primary/5';
  }

  return 'border-DEFAULT hover:border-primary/40';
};

const setDefaultQuickOption = (product) => {
  if (!product || selectedOptionByProduct.value[product.id]) {
    return;
  }

  const firstInStock = productQuickOptions(product).find(isSkuPurchasable);
  if (firstInStock) {
    selectedOptionByProduct.value[product.id] = firstInStock.id;
  }
};

const toggleOptionMenu = (productId) => {
  openOptionMenuId.value = openOptionMenuId.value === productId ? null : productId;
  const product = featuredProducts.value.find((item) => item.id === productId);
  setDefaultQuickOption(product);
};

const closeOptionMenu = () => {
  openOptionMenuId.value = null;
};

const handleDocumentClick = (event) => {
  const target = event?.target;
  if (!target) {
    return;
  }

  const menu = target.closest('[data-option-menu]');
  if (!menu) {
    closeOptionMenu();
  }
};

const normalizeFeaturedProduct = (product) => {
  const media = normalizeProductMedia(product);
  return {
    ...product,
    primary_image_url: media.primaryImage,
    image_placeholder: media.placeholder,
  };
};

const loadHomeData = async () => {
  loading.value = true;
  loadError.value = '';
  try {
    const [productsRes, categoriesRes] = await Promise.all([
      axios.get('/api/products', { params: { per_page: 4, sort: 'newest' } }),
      axios.get('/api/categories'),
    ]);
    featuredProducts.value = (productsRes.data?.data || []).map(normalizeFeaturedProduct);
    categories.value = categoriesRes.data || [];
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Failed to load homepage data.';
  } finally {
    loading.value = false;
  }
};

const addToCart = async (product) => {
  if (!isAuthenticated.value) {
    toast?.warning('Please login to add items to your cart.');
    router.push({ name: 'Login', query: { redirect: route.fullPath } });
    return;
  }

  const purchase = resolveProductPurchase(product);

  if (purchase.requiresSelection) {
    toast?.info('Select a variant on the product details page.');
    router.push(`/products/${product.slug}`);
    return;
  }

  if (purchase.outOfStock || !purchase.selectedSkuId) {
    toast?.warning('This product is currently out of stock.');
    return;
  }

  const result = await cartStore.addItem(purchase.selectedSkuId, 1);
  if (result.success) {
    toast?.success('Added to cart');
  } else {
    toast?.error(result.error || 'Failed to add item to cart');
  }
};

const quickAddSelectedOption = async (product) => {
  if (!product) {
    return;
  }

  if (!isAuthenticated.value) {
    toast?.warning('Please login to add items to your cart.');
    router.push({ name: 'Login', query: { redirect: route.fullPath } });
    return;
  }

  const selectedSkuId = selectedOptionByProduct.value[product.id];
  if (!selectedSkuId) {
    toast?.warning('Please select an option first.');
    return;
  }

  quickAddLoading.value = {
    ...quickAddLoading.value,
    [product.id]: true,
  };

  const result = await cartStore.addItem({ product_id: product.id, sku_id: selectedSkuId }, 1);
  quickAddLoading.value = {
    ...quickAddLoading.value,
    [product.id]: false,
  };

  if (result.success) {
    toast?.success('Added to cart');
    closeOptionMenu();
  } else {
    toast?.error(result.error || 'Failed to add item to cart');
  }
};

const onImageError = (event, fallback) => {
  if (!event?.target) return;
  if (event.target.src !== fallback) {
    event.target.src = fallback;
  }
};

const goTo = (url) => {
  if (!url) return;
  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.location.href = url;
    return;
  }
  router.push(url);
};

onMounted(async () => {
  await loadHomeData();
  document.addEventListener('click', handleDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>
