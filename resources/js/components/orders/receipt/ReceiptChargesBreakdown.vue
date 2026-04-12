<template>
  <section class="space-y-3">
    <div class="flex items-center gap-2">
      <i class="mdi mdi-receipt-text-outline text-primary"></i>
      <h3 class="text-sm font-semibold text-primary">Charges Breakdown</h3>
    </div>

    <div class="rounded-xl border border-slate-200 p-4 space-y-2 text-sm">
      <div class="flex items-center justify-between text-secondary">
        <span>Subtotal</span>
        <span class="text-primary font-medium">{{ formatCurrency(subtotal) }}</span>
      </div>
      <div class="flex items-center justify-between text-secondary">
        <span>Delivery Fee</span>
        <span class="text-primary font-medium">{{ formatCurrency(deliveryFee) }}</span>
      </div>
      <div class="flex items-center justify-between text-secondary">
        <span>Discount</span>
        <span class="text-primary font-medium">{{ formatCurrency(discount) }}</span>
      </div>
      <div class="flex items-center justify-between text-secondary">
        <span>Tax</span>
        <span class="text-primary font-medium">{{ formatCurrency(tax) }}</span>
      </div>
      <div class="border-t border-slate-200 pt-2 flex items-center justify-between">
        <span class="font-semibold text-primary">TOTAL</span>
        <span class="text-xl font-bold text-primary">{{ formatCurrency(total) }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
});

const num = (value) => Number(value || 0);

const subtotal = computed(() => {
  if (props.order.subtotal !== null && props.order.subtotal !== undefined) return num(props.order.subtotal);
  return (props.order.items || []).reduce((sum, item) => sum + (num(item.quantity) * num(item.price_snapshot)), 0);
});
const deliveryFee = computed(() => num(props.order.delivery_fee || props.order.shipping_cost));
const discount = computed(() => num(props.order.discount || props.order.discount_amount));
const tax = computed(() => num(props.order.tax));
const total = computed(() => {
  if (props.order.total !== null && props.order.total !== undefined) return num(props.order.total);
  return Math.max(subtotal.value + deliveryFee.value + tax.value - discount.value, 0);
});
</script>
