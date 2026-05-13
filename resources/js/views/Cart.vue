<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8 animate-fade-in-down">
        <h1 class="text-4xl font-bold text-primary mb-2">Shopping Cart</h1>
        <p class="text-secondary">{{ cartItems.length }} items in your cart</p>
      </div>

      <div v-if="cartItems.length === 0" class="text-center py-16">
        <Card :elevation="2" class="max-w-md mx-auto">
          <div class="flex flex-col items-center py-8">
            <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center mb-6">
              <i class="mdi mdi-cart-outline text-5xl text-primary/50"></i>
            </div>
            <h2 class="text-2xl font-bold text-primary mb-2">Your cart is empty</h2>
            <p class="text-secondary mb-6">Add some products to get started!</p>
            <Button variant="primary" size="lg" icon="shopping" @click="$router.push('/products')">
              Continue Shopping
            </Button>
          </div>
        </Card>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-4">
          <Card
            v-for="(item, index) in cartItems"
            :key="item.id"
            :elevation="2"
            animation="fade-in-up"
            :class="`stagger-${index + 1}`"
            no-padding
          >
            <div class="p-4 sm:p-6">
              <div class="flex gap-3 sm:gap-4">
                <!-- Product Image -->
                <div class="h-24 w-24 flex-shrink-0 bg-gradient-to-br from-primary/10 to-primary-dark/10 rounded-lg flex items-center justify-center overflow-hidden sm:h-28 sm:w-28">
                  <img
                    :src="item.product_image || '/images/placeholders/product-placeholder.svg'"
                    :alt="item.product_title"
                    class="w-full h-full object-cover"
                    @error="onImageError"
                  />
                </div>

                <!-- Product Info -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-3">
                    <h3 class="min-w-0 font-semibold text-primary line-clamp-2 text-base sm:text-lg">{{ item.product_title }}</h3>
                    <button
                      @click="removeItem(item.id)"
                      class="flex-shrink-0 text-danger hover:bg-danger/10 p-2 rounded-lg transition-colors"
                      aria-label="Remove item"
                    >
                      <i class="mdi mdi-delete text-xl"></i>
                    </button>
                  </div>

                  <div class="mt-2 space-y-1 text-xs text-secondary sm:text-sm">
                    <p class="break-words"><span class="font-medium">SKU:</span> {{ item.sku_code }}</p>
                    <p v-if="item.option_label" class="break-words"><span class="font-medium">Option:</span> {{ item.option_label }}</p>
                    <p v-if="item.product_type === 'basket'" class="inline-flex items-center gap-1 rounded bg-orange-50 px-2 py-1 text-orange-700">
                      <i class="mdi mdi-basket-outline"></i>
                      Basket
                    </p>
                  </div>

                  <!-- Attributes -->
                  <div v-if="item.attributes" class="mt-2 flex flex-wrap gap-2">
                    <Badge
                      v-for="attr in item.attributes"
                      :key="attr.name"
                      variant="outline"
                      size="sm"
                    >
                      {{ attr.name }}: {{ attr.value }}
                    </Badge>
                  </div>
                </div>
              </div>

              <div v-if="item.product_type === 'basket' && item.basket_components?.length" class="mt-4 rounded-lg border border-orange-100 bg-orange-50/50 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-orange-800">Included items</p>
                <div class="grid gap-2 sm:grid-cols-2">
                  <div
                    v-for="component in item.basket_components"
                    :key="component.id || component.component_sku_id"
                    class="text-xs text-secondary"
                  >
                    <span class="font-medium text-primary">{{ component.product_title }}</span>
                    <span> - {{ component.total_quantity }} {{ component.unit_name || '' }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 flex items-center justify-between rounded-lg border border-DEFAULT bg-base px-3 py-2">
                <span class="text-sm font-medium text-secondary">Quantity</span>
                <div class="flex items-center gap-3">
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-md border border-DEFAULT text-primary transition disabled:opacity-40"
                    :disabled="item.quantity <= 1"
                    aria-label="Decrease quantity"
                    @click="updateQuantity(item.id, item.quantity - 1)"
                  >
                    <i class="mdi mdi-minus"></i>
                  </button>
                  <span class="w-8 text-center text-lg font-semibold text-primary">{{ item.quantity }}</span>
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-md border border-DEFAULT text-primary transition"
                    aria-label="Increase quantity"
                    @click="updateQuantity(item.id, item.quantity + 1)"
                  >
                    <i class="mdi mdi-plus"></i>
                  </button>
                </div>
              </div>

              <div class="mt-3 flex items-end justify-between gap-4 border-t border-DEFAULT pt-3">
                <div>
                  <p class="text-xs text-secondary">Unit price</p>
                  <p class="text-lg font-bold text-primary">{{ formatCurrency(item.price) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-secondary">Subtotal</p>
                  <p class="text-sm font-semibold text-primary sm:text-base">{{ formatCurrency(item.price * item.quantity) }}</p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <Card :elevation="2" title="Order Summary" icon="receipt" class="sticky top-20">
            <div class="space-y-4">
              <!-- Coupon Code -->
              <div>
                <Input
                  v-model="couponCode"
                  placeholder="Enter coupon code"
                  icon="ticket-percent"
                >
                  <template #append>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="applyCoupon"
                      :loading="applyingCoupon"
                    >
                      Apply
                    </Button>
                  </template>
                </Input>
                <p v-if="couponError" class="text-xs text-danger mt-1">{{ couponError }}</p>
                <p v-if="couponSuccess" class="text-xs text-success mt-1">{{ couponSuccess }}</p>
              </div>

              <div class="border-t border-DEFAULT pt-4 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-secondary">Subtotal</span>
                  <span class="font-medium text-primary">{{ formatCurrency(subtotal) }}</span>
                </div>

                <div v-if="discount > 0" class="flex justify-between text-sm">
                  <span class="text-secondary">Discount</span>
                  <span class="font-medium text-success">-{{ formatCurrency(discount) }}</span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-secondary">Shipping</span>
                  <span class="font-medium text-primary">
                    Included at checkout
                  </span>
                </div>

                <div class="border-t border-DEFAULT pt-3 flex justify-between">
                  <span class="text-lg font-semibold text-primary">Total</span>
                  <span class="text-2xl font-bold text-primary">{{ formatCurrency(total) }}</span>
                </div>
              </div>

              <Button
                variant="primary"
                size="lg"
                icon="lock"
                class="w-full"
                @click="proceedToCheckout"
                :loading="cartStore.loading"
              >
                Proceed to Checkout
              </Button>

              <Button
                variant="ghost"
                size="md"
                icon="arrow-left"
                class="w-full"
                @click="$router.push('/products')"
              >
                Continue Shopping
              </Button>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Input from '../components/ui/Input.vue';
import Badge from '../components/ui/Badge.vue';
import { useSettingsStore } from '../stores/settings';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const toast = inject('toast');
const settingsStore = useSettingsStore();
const cartStore = useCartStore();
const { formatCurrency } = settingsStore;

const cartItems = computed(() => cartStore.items || []);
const couponCode = ref('');
const couponError = ref('');
const couponSuccess = ref('');
const applyingCoupon = ref(false);
const appliedCoupon = ref(null);
const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
});
const discount = computed(() => Math.max(0, subtotal.value - Number(cartStore.total || 0)));
const total = computed(() => Number(cartStore.total || 0));

// Methods
const fetchCart = async () => {
  try {
    await cartStore.loadCart();
  } catch (error) {
    console.error('Error fetching cart:', error);
    toast?.error('Failed to load cart');
  }
};

const updateQuantity = async (itemId, newQuantity) => {
  if (newQuantity < 1) return;

  try {
    const result = await cartStore.updateItem(itemId, newQuantity);
    if (!result.success) {
      toast?.error(result.error || 'Failed to update quantity');
      return;
    }
    toast?.success('Cart updated');
  } catch (error) {
    console.error('Error updating quantity:', error);
    toast?.error('Failed to update quantity');
  }
};

const removeItem = async (itemId) => {
  try {
    const result = await cartStore.removeItem(itemId);
    if (!result.success) {
      toast?.error(result.error || 'Failed to remove item');
      return;
    }
    toast?.success('Item removed from cart');
  } catch (error) {
    console.error('Error removing item:', error);
    toast?.error('Failed to remove item');
  }
};

const applyCoupon = async () => {
  if (!couponCode.value) return;

  try {
    applyingCoupon.value = true;
    couponError.value = '';
    couponSuccess.value = '';

    const response = await axios.post('/api/cart/apply-coupon', {
      code: couponCode.value
    });

    appliedCoupon.value = response.data.coupon;
    await fetchCart();
    couponSuccess.value = 'Coupon applied successfully!';
    toast?.success('Coupon applied!');
  } catch (error) {
    couponError.value = error.response?.data?.message || 'Invalid coupon code';
  } finally {
    applyingCoupon.value = false;
  }
};

const proceedToCheckout = () => {
  if (cartItems.value.length === 0) {
    toast?.error('Your cart is empty');
    return;
  }
  router.push('/checkout');
};

onMounted(() => {
  fetchCart();
});

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = '/images/placeholders/product-placeholder.svg';
  }
};
</script>
