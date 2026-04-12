<template>
  <section class="space-y-3">
    <div class="flex items-center gap-2">
      <i class="mdi mdi-shield-check-outline text-primary"></i>
      <h3 class="text-sm font-semibold text-primary">Reference & Support</h3>
    </div>

    <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-sm">
      <div class="px-4 py-3 grid grid-cols-[1fr_auto] gap-3">
        <p class="text-secondary">Transaction Reference</p>
        <div class="flex items-center gap-2">
          <p class="text-primary font-medium text-right">{{ transactionReference }}</p>
          <button type="button" class="text-secondary hover:text-primary" @click="$emit('copy-reference', transactionReference)">
            <i class="mdi mdi-content-copy"></i>
          </button>
        </div>
      </div>
      <div class="px-4 py-3 grid grid-cols-[1fr_auto] gap-3">
        <p class="text-secondary">Payment Reference</p>
        <p class="text-primary font-medium text-right">{{ order.payment_reference || 'N/A' }}</p>
      </div>
      <div class="px-4 py-3 grid grid-cols-[1fr_auto] gap-3">
        <p class="text-secondary">Generated At</p>
        <p class="text-primary font-medium text-right">{{ formatDate(new Date().toISOString(), true) }}</p>
      </div>
      <div class="px-4 py-3 grid grid-cols-[1fr_auto] gap-3">
        <p class="text-secondary">Support</p>
        <p class="text-primary font-medium text-right">support@ashlabtech.com</p>
      </div>
    </div>

    <p class="text-xs text-secondary">This is a computer-generated receipt. Keep it for your records.</p>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  formatDate: {
    type: Function,
    required: true,
  },
});

defineEmits(['copy-reference']);

const transactionReference = computed(() => {
  return props.order?.payments?.[0]?.transaction_id || `TXN-${props.order?.id || 'N/A'}`;
});
</script>
