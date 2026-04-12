<template>
  <header class="text-center space-y-3">
    <div class="mx-auto w-fit rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 flex items-center gap-2">
      <span class="h-6 w-6 rounded-lg bg-primary text-white inline-flex items-center justify-center">
        <i class="mdi mdi-store text-sm"></i>
      </span>
      <p class="text-sm font-semibold text-primary">{{ storeName }}</p>
    </div>

    <div>
      <p class="text-base font-semibold text-primary">Order Payment Receipt</p>
      <p class="text-xs text-secondary">Transaction confirmation</p>
    </div>

    <div class="pt-1">
      <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
        <i :class="`mdi ${iconClass} text-2xl`"></i>
      </div>
      <div class="mt-2 flex justify-center">
        <ReceiptStatusBadge
          :value="paymentStatus"
          category="payment"
          :label="statusLabel"
        />
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import ReceiptStatusBadge from './ReceiptStatusBadge.vue';

const props = defineProps({
  paymentStatus: {
    type: String,
    default: 'pending',
  },
  storeName: {
    type: String,
    default: 'Ashlab Commerce',
  },
  statusLabel: {
    type: String,
    default: 'Pending',
  },
});

const iconClass = computed(() => {
  if (props.paymentStatus === 'paid') return 'mdi-check-circle';
  if (props.paymentStatus === 'failed') return 'mdi-alert-circle';
  if (props.paymentStatus === 'refunded') return 'mdi-cash-refund';
  return 'mdi-clock-outline';
});
</script>
