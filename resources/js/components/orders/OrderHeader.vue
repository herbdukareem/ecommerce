<template>
  <div class="rounded-2xl border border-DEFAULT bg-white shadow-sm p-5 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="space-y-2">
        <p class="text-xs uppercase tracking-[0.18em] text-secondary font-semibold">Order Details</p>
        <h1 class="text-2xl sm:text-3xl font-bold text-primary">Order #{{ orderId }}</h1>
        <p class="text-sm text-secondary">
          Placed on {{ placedText }}
        </p>
      </div>

      <div class="flex flex-wrap gap-2 sm:justify-end">
        <Badge :variant="statusMeta.variant" icon="package-variant" size="lg">
          {{ statusMeta.label }}
        </Badge>
        <Button variant="outline" icon="arrow-left" @click="$emit('back')">Back to Orders</Button>
      </div>
    </div>

    <div class="mt-4 pt-4 border-t border-DEFAULT flex flex-wrap gap-2">
      <Button variant="ghost" icon="download" :loading="downloadingInvoice" @click="$emit('download-invoice')">Download Invoice</Button>
      <Button variant="ghost" icon="cart-outline" @click="$emit('reorder')">Reorder</Button>
      <Button variant="ghost" icon="lifebuoy" @click="$emit('contact-support')">Contact Support</Button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '../ui/Badge.vue';
import Button from '../ui/Button.vue';

const props = defineProps({
  orderId: {
    type: [String, Number],
    required: true,
  },
  placedAt: {
    type: [String, Object, null],
    default: null,
  },
  status: {
    type: String,
    default: 'pending',
  },
  formatDate: {
    type: Function,
    required: true,
  },
  downloadingInvoice: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['back', 'download-invoice', 'reorder', 'contact-support']);

const statusMap = {
  pending: { label: 'Pending', variant: 'warning' },
  processing: { label: 'Processing', variant: 'info' },
  shipped: { label: 'Shipped', variant: 'info' },
  delivered: { label: 'Delivered', variant: 'success' },
  cancelled: { label: 'Cancelled', variant: 'danger' },
};

const statusMeta = computed(() => statusMap[props.status] || { label: props.status || 'Unknown', variant: 'secondary' });

const placedText = computed(() => props.formatDate(props.placedAt, true));
</script>
