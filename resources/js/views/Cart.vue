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
          >
            <div class="flex gap-4">
              <!-- Product Image -->
              <div class="w-24 h-24 flex-shrink-0 bg-gradient-to-br from-primary/10 to-primary-dark/10 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-image text-3xl text-primary/30"></i>
              </div>

              <!-- Product Info -->
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-primary mb-1 truncate">{{ item.product_name }}</h3>
                <p class="text-sm text-secondary mb-2">SKU: {{ item.sku_code }}</p>

                <!-- Attributes -->
                <div v-if="item.attributes" class="flex flex-wrap gap-2 mb-2">
                  <Badge
                    v-for="attr in item.attributes"
                    :key="attr.name"
                    variant="outline"
                    size="sm"
                  >
                    {{ attr.name }}: {{ attr.value }}
                  </Badge>
                </div>

                <!-- Price -->
                <p class="text-lg font-bold text-primary">
                  {{ formatCurrency(item.price) }}
                </p>
              </div>

              <!-- Quantity Controls -->
              <div class="flex flex-col items-end justify-between">
                <button
                  @click="removeItem(item.id)"
                  class="text-danger hover:bg-danger/10 p-2 rounded-lg transition-colors"
                >
                  <i class="mdi mdi-delete text-xl"></i>
                </button>

                <div class="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    icon="minus"
                    icon-only
                    @click="updateQuantity(item.id, item.quantity - 1)"
                    :disabled="item.quantity <= 1"
                  />
                  <span class="text-lg font-semibold text-primary w-12 text-center">
                    {{ item.quantity }}
                  </span>
                  <Button
                    variant="outline"
                    size="sm"
                    icon="plus"
                    icon-only
                    @click="updateQuantity(item.id, item.quantity + 1)"
                  />
                </div>

                <p class="text-sm text-secondary mt-2">
                  Subtotal: {{ formatCurrency(item.price * item.quantity) }}
                </p>
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
                    {{ shipping === 0 ? 'Free' : formatCurrency(shipping) }}
                  </span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-secondary">Tax</span>
                  <span class="font-medium text-primary">{{ formatCurrency(tax) }}</span>
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
                :loading="loading"
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
import { ref, computed, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Input from '../components/ui/Input.vue';
import Badge from '../components/ui/Badge.vue';
import { useSettingsStore } from '../stores/settings';

const router = useRouter();
const toast = inject('toast');
const settingsStore = useSettingsStore();
const { formatCurrency } = settingsStore;

const cartItems = ref([]);
const loading = ref(false);
const couponCode = ref('');
const couponError = ref('');
const couponSuccess = ref('');
const applyingCoupon = ref(false);
const appliedCoupon = ref(null);

// Computed values
const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const discount = computed(() => {
  if (!appliedCoupon.value) return 0;
  // Calculate discount based on coupon type
  return 0; // Implement discount logic
});

const shipping = computed(() => {
  return subtotal.value > 50 ? 0 : 10; // Free shipping over 50
});

const tax = computed(() => {
  return (subtotal.value - discount.value) * 0.075; // 7.5% VAT
});

const total = computed(() => {
  return subtotal.value - discount.value + shipping.value + tax.value;
});

// Methods
const fetchCart = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/cart');
    cartItems.value = response.data.items || [];
  } catch (error) {
    console.error('Error fetching cart:', error);
    toast?.error('Failed to load cart');
  } finally {
    loading.value = false;
  }
};

const updateQuantity = async (itemId, newQuantity) => {
  if (newQuantity < 1) return;

  try {
    await axios.put(`/api/cart/${itemId}`, { quantity: newQuantity });
    const item = cartItems.value.find(i => i.id === itemId);
    if (item) {
      item.quantity = newQuantity;
    }
    toast?.success('Cart updated');
  } catch (error) {
    console.error('Error updating quantity:', error);
    toast?.error('Failed to update quantity');
  }
};

const removeItem = async (itemId) => {
  try {
    await axios.delete(`/api/cart/${itemId}`);
    cartItems.value = cartItems.value.filter(i => i.id !== itemId);
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
    couponSuccess.value = 'Coupon applied successfully!';
    toast?.success('Coupon applied!');
  } catch (error) {
    couponError.value = error.response?.data?.message || 'Invalid coupon code';
  } finally {
    applyingCoupon.value = false;
  }
};

const proceedToCheckout = () => {
  router.push('/checkout');
};

onMounted(() => {
  fetchCart();
});
</script>