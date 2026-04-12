<template>
  <section class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-center">
    <p class="text-xs uppercase tracking-[0.12em] text-secondary">Total Amount</p>
    <p class="mt-1 text-3xl sm:text-4xl font-bold text-primary">{{ formatCurrency(amount) }}</p>
    <p class="mt-2 text-sm" :class="supportClass">{{ supportText }}</p>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  amount: {
    type: Number,
    default: 0,
  },
  paymentStatus: {
    type: String,
    default: 'pending',
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
});

const supportText = computed(() => {
  if (props.paymentStatus === 'paid') return 'Payment successful. Thank you for your purchase.';
  if (props.paymentStatus === 'failed') return 'Payment failed. Please retry or contact support.';
  if (props.paymentStatus === 'refunded') return 'Payment has been refunded.';
  return 'Payment is being processed.';
});

const supportClass = computed(() => {
  if (props.paymentStatus === 'paid') return 'text-emerald-700';
  if (props.paymentStatus === 'failed') return 'text-rose-700';
  if (props.paymentStatus === 'refunded') return 'text-sky-700';
  return 'text-amber-700';
});
</script>
