<template>
  <aside class="rounded-2xl border border-DEFAULT bg-white shadow-sm p-5 sm:p-6 space-y-5">
    <div>
      <h2 class="text-lg sm:text-xl font-bold text-primary">Order Summary</h2>
      <p class="text-sm text-secondary mt-1">Charges, delivery info, and tracking details.</p>
    </div>

    <section class="space-y-2">
      <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">Delivery Information</h3>
      <SummaryRow icon="city" label="City" :value="cityName" />
      <SummaryRow icon="map-marker" label="Area" :value="areaName" />
      <SummaryRow icon="clock-outline" label="Dispatch Slot" :value="dispatchSlot" />
      <SummaryRow icon="truck-delivery-outline" label="Delivery Method" :value="deliveryMethod" />
    </section>

    <section class="space-y-2 border-t border-DEFAULT pt-4">
      <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">Payment Information</h3>
      <SummaryRow icon="cash-check" label="Payment Status" :value="humanize(order.payment_status || 'pending')" />
      <SummaryRow icon="credit-card-outline" label="Payment Method" :value="humanize(paymentMethod)" />
      <SummaryRow icon="identifier" label="Reference" :value="order.payment_reference || 'N/A'" />
    </section>

    <section class="space-y-2 border-t border-DEFAULT pt-4">
      <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">Charges Breakdown</h3>
      <SummaryRow icon="receipt-text-outline" label="Subtotal" :value="formatCurrency(subtotal)" />
      <SummaryRow icon="truck-outline" label="Delivery Fee" :value="formatCurrency(deliveryFee)" />
      <SummaryRow icon="sale" label="Discount" :value="formatCurrency(discount)" />
      <div class="rounded-xl bg-primary/5 border border-primary/20 px-3 py-2 flex items-center justify-between">
        <p class="text-sm font-semibold text-primary">TOTAL</p>
        <p class="text-lg font-bold text-primary">{{ formatCurrency(total) }}</p>
      </div>
    </section>

    <section class="space-y-2 border-t border-DEFAULT pt-4">
      <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">Tracking Info</h3>
      <SummaryRow icon="barcode" label="Tracking Code" :value="order.delivery_tracking_code || 'N/A'" />
      <SummaryRow icon="calendar-check" label="Placed" :value="formatDate(order.placed_at || order.created_at)" />
      <SummaryRow icon="map-marker-path" label="Delivery Status" :value="humanize(order.delivery_status || 'pending_assignment')" />
    </section>
  </aside>
</template>

<script setup>
import { computed, h } from 'vue';

const SummaryRow = {
  props: {
    icon: { type: String, default: 'circle-small' },
    label: { type: String, required: true },
    value: { type: [String, Number], default: 'N/A' },
  },
  setup(props) {
    const displayValue = () => {
      if (props.value === null || props.value === undefined || props.value === '') {
        return 'N/A';
      }
      return String(props.value);
    };

    return () => h('div', { class: 'grid grid-cols-[1fr_auto] gap-3 items-center text-sm' }, [
      h('p', { class: 'text-secondary inline-flex items-center gap-1.5 min-w-0' }, [
        h('i', { class: `mdi mdi-${props.icon} text-base` }),
        h('span', { class: 'truncate' }, props.label),
      ]),
      h('p', { class: 'text-primary font-medium text-right' }, displayValue()),
    ]);
  },
};

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
  formatDate: {
    type: Function,
    required: true,
  },
});

const humanize = (value) => {
  if (!value) return 'N/A';
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

const toNumber = (value) => Number(value || 0);

const subtotal = computed(() => {
  if (props.order.subtotal !== null && props.order.subtotal !== undefined) {
    return toNumber(props.order.subtotal);
  }

  return (props.order.items || []).reduce((sum, item) => {
    const itemSubtotal = item?.subtotal !== undefined && item?.subtotal !== null
      ? toNumber(item.subtotal)
      : toNumber(item?.price_snapshot) * toNumber(item?.quantity);
    return sum + itemSubtotal;
  }, 0);
});

const deliveryFee = computed(() => toNumber(props.order.delivery_fee || props.order.shipping_cost));
const discount = computed(() => toNumber(props.order.discount || props.order.discount_amount));
const total = computed(() => {
  if (props.order.total !== null && props.order.total !== undefined) {
    return toNumber(props.order.total);
  }
  return Math.max(subtotal.value + deliveryFee.value - discount.value, 0);
});

const cityName = computed(() => props.order.city_name || props.order.city?.name || 'N/A');
const areaName = computed(() => props.order.area_name || props.order.area?.name || 'N/A');
const dispatchSlot = computed(() => props.order.dispatch_time_label || props.order.dispatchTimeSlot?.label || 'N/A');
const deliveryMethod = computed(() => props.order.delivery_snapshot?.method_name || props.order.shippingMethod?.name || 'N/A');
const paymentMethod = computed(() => props.order.payment_mode || props.order.payments?.[0]?.provider || 'N/A');
</script>
