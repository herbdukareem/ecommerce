<template>
  <MainLayout>
    <section class="max-w-6xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-primary mb-6">Checkout</h1>

      <div v-if="errorMessage" class="mb-4 text-danger">{{ errorMessage }}</div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card :elevation="2" class="p-5 lg:col-span-2">
          <h2 class="text-lg font-semibold text-primary mb-4">Delivery and Payment</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-secondary mb-1 block">City</label>
              <Select v-model="form.city_id" :options="cityOptions" @update:model-value="onCityChange" />
            </div>

            <div>
              <label class="text-sm text-secondary mb-1 block">Area</label>
              <Select v-model="form.area_id" :options="areaOptions" @update:model-value="refreshPaymentOptions" />
            </div>

            <div>
              <label class="text-sm text-secondary mb-1 block">Dispatch Time Slot</label>
              <Select v-model="form.dispatch_time_slot_id" :options="dispatchOptions" />
            </div>

            <div>
              <label class="text-sm text-secondary mb-1 block">Payment Method</label>
              <Select v-model="form.payment_mode" :options="paymentOptions" />
              <p v-if="codUnavailableReason" class="mt-1 text-xs text-secondary">{{ codUnavailableReason }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="text-sm text-secondary mb-1 block">Payment Reference (Optional)</label>
              <Input v-model="form.payment_reference" placeholder="Reference or transaction ID" />
            </div>

            <div class="md:col-span-2">
              <label class="text-sm text-secondary mb-1 block">Order Note (Optional)</label>
              <textarea
                v-model="form.order_note"
                rows="3"
                class="w-full border border-DEFAULT rounded-lg p-3"
                placeholder="Delivery or order note"
              />
            </div>
          </div>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Order Summary</h2>

          <div v-if="cartStore.loading" class="text-secondary text-sm">Loading cart...</div>

          <template v-else>
            <div class="space-y-3 max-h-72 overflow-y-auto mb-4">
              <div v-for="item in cartStore.items" :key="item.id" class="flex items-center gap-3">
                <img
                  :src="item.product_image || '/images/placeholders/product-placeholder.svg'"
                  :alt="item.product_title"
                  class="w-12 h-12 rounded object-cover border border-DEFAULT"
                  @error="onImageError"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm text-primary truncate">{{ item.product_title }}</p>
                  <p class="text-xs text-secondary">Qty {{ item.quantity }}</p>
                </div>
                <p class="text-sm font-semibold text-primary">{{ formatCurrency(item.subtotal) }}</p>
              </div>
            </div>

            <div class="space-y-2 text-sm border-t pt-3">
              <div class="flex justify-between"><span>Subtotal</span><span>{{ formatCurrency(cartStore.total) }}</span></div>
              <div class="flex justify-between"><span>Delivery</span><span>{{ formatCurrency(selectedAreaFee) }}</span></div>
              <div class="flex justify-between font-semibold text-base border-t pt-2"><span>Total</span><span>{{ formatCurrency(grandTotal) }}</span></div>
            </div>

            <Button class="w-full mt-4" :loading="placingOrder || processingPopup" @click="placeOrder">Place Order</Button>
          </template>
        </Card>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Input from '../components/ui/Input.vue';
import Select from '../components/ui/Select.vue';
import Button from '../components/ui/Button.vue';
import { useCheckoutStore } from '../stores/checkout';
import { useCartStore } from '../stores/cart';
import { useSettingsStore } from '../stores/settings';

const router = useRouter();
const toast = inject('toast');
const checkoutStore = useCheckoutStore();
const cartStore = useCartStore();
const settingsStore = useSettingsStore();

const errorMessage = ref('');
const placingOrder = ref(false);
const processingPopup = ref(false);

const form = reactive({
  city_id: '',
  area_id: '',
  dispatch_time_slot_id: '',
  payment_mode: '',
  payment_reference: '',
  order_note: '',
});

const formatCurrency = settingsStore.formatCurrency;

const cityOptions = computed(() => [
  { value: '', label: 'Select city' },
  ...checkoutStore.cities.map((city) => ({ value: city.id, label: city.name })),
]);

const areaOptions = computed(() => [
  { value: '', label: 'Select area' },
  ...checkoutStore.areas.map((area) => ({ value: area.id, label: area.name })),
]);

const dispatchOptions = computed(() => [
  { value: '', label: 'Select dispatch slot' },
  ...checkoutStore.dispatchSlots.map((slot) => ({ value: slot.id, label: `${slot.label} (${slot.display_time})` })),
]);

const paymentOptions = computed(() => {
  const dynamic = (checkoutStore.paymentOptions || [])
    .filter((option) => option.available !== false)
    .map((option) => ({ value: option.provider, label: option.display_name }));
  return [{ value: '', label: 'Select payment method' }, ...dynamic];
});

const selectedGatewayProvider = computed(() => {
  const providers = (checkoutStore.gateways || []).map((gateway) => gateway.provider);
  return providers.includes(form.payment_mode) ? form.payment_mode : null;
});

const isPayOnDelivery = computed(() => form.payment_mode === 'pay_on_delivery');

const codUnavailableReason = computed(() => {
  const option = (checkoutStore.paymentOptions || []).find((entry) => entry.provider === 'pay_on_delivery');
  if (!option || option.available !== false) {
    return '';
  }
  return option.unavailable_reason || 'Pay on delivery is not available for this cart.';
});

const selectedAreaFee = computed(() => {
  const area = checkoutStore.areas.find((entry) => Number(entry.id) === Number(form.area_id));
  return Number(area?.delivery_fee || 0);
});

const grandTotal = computed(() => Number(cartStore.total || 0) + selectedAreaFee.value);

const onCityChange = async () => {
  form.area_id = '';
  if (!form.city_id) {
    checkoutStore.areas = [];
    return;
  }

  const result = await checkoutStore.fetchAreas(form.city_id);
  if (!result.success) {
    toast?.error(result.error);
  }
  await refreshPaymentOptions();
};

const placeOrder = async () => {
  if (placingOrder.value || processingPopup.value) {
    return;
  }

  if (!form.city_id || !form.area_id || !form.dispatch_time_slot_id || !form.payment_mode) {
    toast?.error('City, area, dispatch slot, and payment method are required.');
    return;
  }

  if (!selectedGatewayProvider.value && !isPayOnDelivery.value) {
    toast?.error('Please select a valid payment method before placing your order.');
    return;
  }

  placingOrder.value = true;

  try {
    const result = await checkoutStore.placeOrder({
      city_id: Number(form.city_id),
      area_id: Number(form.area_id),
      dispatch_time_slot_id: Number(form.dispatch_time_slot_id),
      payment_mode: form.payment_mode,
      payment_reference: form.payment_reference || null,
      order_note: form.order_note || null,
    });

    if (!result.success) {
      toast?.error(result.error || 'Failed to place order.');
      return;
    }

    if (isPayOnDelivery.value) {
      toast?.success('Order placed successfully. Payment will be collected on delivery.');
      await cartStore.loadCart();
      router.push(`/orders/${result.order.id}`);
      return;
    }

    if (selectedGatewayProvider.value) {
      const initResult = await checkoutStore.initializeOrderPayment(result.order.id);
      if (!initResult.success) {
        toast?.error(initResult.error || 'Unable to initialize payment.');
        return;
      }

      const checkoutPayload = initResult.data?.checkout || {};

      if (checkoutPayload.provider === 'paystack') {
        // Release submit loading before opening popup; popup has its own processing state.
        placingOrder.value = false;
        await openPaystackPopup(result.order.id, initResult.data?.payment_id, checkoutPayload);
        return;
      }

      const checkoutUrl = checkoutPayload.checkout_url || checkoutPayload.authorization_url || null;
      if (checkoutUrl) {
        window.location.assign(checkoutUrl);
        return;
      }

      toast?.error('Gateway initialized but no checkout URL was returned.');
      return;
    }

    toast?.success('Order placed successfully.');
    await cartStore.loadCart();
    router.push(`/orders/${result.order.id}`);
  } catch (error) {
    toast?.error(error?.message || 'Unable to complete checkout.');
  } finally {
    if (!processingPopup.value) {
      placingOrder.value = false;
    }
  }
};

const loadPaystackScript = async () => {
  if (window.PaystackPop) {
    return true;
  }

  const existing = document.querySelector('script[data-paystack-inline="true"]');
  if (existing) {
    if (existing.dataset.loaded === 'true') {
      return !!window.PaystackPop;
    }

    if (existing.dataset.failed === 'true') {
      return false;
    }

    return await new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(!!window.PaystackPop), 10000);

      const onLoad = () => {
        clearTimeout(timeout);
        existing.dataset.loaded = 'true';
        resolve(!!window.PaystackPop);
      };

      const onError = () => {
        clearTimeout(timeout);
        existing.dataset.failed = 'true';
        resolve(false);
      };

      existing.addEventListener('load', onLoad, { once: true });
      existing.addEventListener('error', onError, { once: true });
    });
  }

  return await new Promise((resolve) => {
    const script = document.createElement('script');
    script.src = 'https://js.paystack.co/v1/inline.js';
    script.async = true;
    script.dataset.paystackInline = 'true';
    script.onload = () => {
      script.dataset.loaded = 'true';
      resolve(!!window.PaystackPop);
    };
    script.onerror = () => {
      script.dataset.failed = 'true';
      resolve(false);
    };
    document.head.appendChild(script);
  });
};

const openPaystackPopup = async (orderId, paymentId, checkoutPayload) => {
  const scriptReady = await loadPaystackScript();
  if (!scriptReady) {
    toast?.error('Unable to load Paystack popup script. Please try again.');
    return;
  }

  if (!checkoutPayload.public_key || !checkoutPayload.reference || !checkoutPayload.amount || !checkoutPayload.email) {
    toast?.error('Invalid Paystack checkout payload received.');
    return;
  }

  processingPopup.value = true;

  const allowedChannels = ['card', 'bank', 'ussd', 'qr', 'bank_transfer'];
  const channels = Array.isArray(checkoutPayload.channels)
    ? checkoutPayload.channels.filter((entry) => allowedChannels.includes(String(entry || '').trim()))
    : [];

  await new Promise((resolve) => {
    try {
      const setupConfig = {
        key: checkoutPayload.public_key,
        email: checkoutPayload.email,
        amount: checkoutPayload.amount,
        ref: checkoutPayload.reference,
        currency: checkoutPayload.currency || 'NGN',
        metadata: checkoutPayload.metadata || {},
        callback: function (response) {
          void handlePaystackSuccess(orderId, paymentId, response, resolve);
        },
        onClose: function () {
          processingPopup.value = false;
          toast?.error('Payment popup closed. Your order is still pending payment.');
          resolve();
        },
      };

      if (channels.length > 0) {
        setupConfig.channels = channels;
      }

      const handler = window.PaystackPop.setup(setupConfig);
      handler.openIframe();
    } catch (error) {
      processingPopup.value = false;

      // Last-resort fallback: open the hosted checkout URL if popup setup fails.
      const checkoutUrl = checkoutPayload.checkout_url || null;
      if (checkoutUrl) {
        window.location.assign(checkoutUrl);
        resolve();
        return;
      }

      toast?.error('Unable to open Paystack popup. Please try again.');
      resolve();
    }
  });
};

const handlePaystackSuccess = async (orderId, paymentId, response, done) => {
  try {
    const verifyResult = await checkoutStore.verifyPayment(paymentId, {
      provider: 'paystack',
      reference: response.reference,
    });

    processingPopup.value = false;

    if (!verifyResult.success) {
      toast?.error(verifyResult.error || 'Payment verification failed.');
      done();
      return;
    }

    const status = verifyResult.data?.result?.status;
    if (status !== 'paid') {
      toast?.error('Payment was not completed successfully.');
      done();
      return;
    }

    toast?.success('Payment successful.');
    await cartStore.loadCart();
    await router.push(`/orders/${orderId}`);
    done();
  } catch (error) {
    processingPopup.value = false;
    toast?.error('Payment verification failed.');
    done();
  }
};

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = '/images/placeholders/product-placeholder.svg';
  }
};

const refreshPaymentOptions = async () => {
  const result = await checkoutStore.fetchPaymentOptions({
    city_id: form.city_id || undefined,
    area_id: form.area_id || undefined,
  });

  if (!result.success) {
    toast?.error(result.error);
    return;
  }

  const available = new Set((checkoutStore.paymentOptions || [])
    .filter((option) => option.available !== false)
    .map((option) => option.provider));

  if (form.payment_mode && !available.has(form.payment_mode)) {
    form.payment_mode = '';
  }
};

onMounted(async () => {
  errorMessage.value = '';

  await Promise.all([
    cartStore.loadCart(),
    checkoutStore.fetchCities(),
    checkoutStore.fetchDispatchTimeSlots(),
    checkoutStore.fetchPaymentOptions(),
  ]);

  if (!form.city_id && checkoutStore.cities.length) {
    form.city_id = checkoutStore.cities[0].id;
    await onCityChange();
  }

  await refreshPaymentOptions();

  const availableOptions = (checkoutStore.paymentOptions || []).filter((option) => option.available !== false);
  if (!form.payment_mode && availableOptions.length === 1) {
    form.payment_mode = availableOptions[0].provider;
  }

  if (!cartStore.items.length) {
    errorMessage.value = 'Your cart is empty.';
  }
});
</script>
