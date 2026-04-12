<template>
  <div class="flex flex-wrap items-center gap-1.5">
    <Badge size="sm" :variant="orderMeta.variant" :icon="orderMeta.icon">
      {{ humanize(order.status || 'pending') }}
    </Badge>
    <Badge size="sm" :variant="paymentMeta.variant" :icon="paymentMeta.icon">
      {{ humanize(order.payment_status || 'pending') }}
    </Badge>
    <Badge size="sm" :variant="deliveryMeta.variant" :icon="deliveryMeta.icon">
      {{ humanize(order.delivery_status || 'pending_assignment') }}
    </Badge>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '../../ui/Badge.vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
});

const humanize = (value) => {
  if (!value) return 'N/A';
  return String(value).replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const statusMap = {
  order: {
    pending: { variant: 'warning', icon: 'clock-outline' },
    processing: { variant: 'info', icon: 'cog' },
    shipped: { variant: 'info', icon: 'truck-fast' },
    delivered: { variant: 'success', icon: 'check-circle' },
    cancelled: { variant: 'danger', icon: 'close-circle' },
  },
  payment: {
    pending: { variant: 'warning', icon: 'cash-clock' },
    paid: { variant: 'success', icon: 'check-decagram' },
    failed: { variant: 'danger', icon: 'alert-circle' },
    refunded: { variant: 'danger', icon: 'cash-refund' },
  },
  delivery: {
    pending_assignment: { variant: 'warning', icon: 'account-clock' },
    assigned: { variant: 'info', icon: 'account-check' },
    packed: { variant: 'info', icon: 'package-variant-closed' },
    shipped: { variant: 'info', icon: 'truck-fast' },
    in_transit: { variant: 'info', icon: 'map-marker-path' },
    delivered: { variant: 'success', icon: 'home-check' },
    delivery_failed: { variant: 'danger', icon: 'alert-outline' },
    returned: { variant: 'danger', icon: 'backup-restore' },
    cancelled: { variant: 'danger', icon: 'close-circle' },
  },
};

const orderMeta = computed(() => statusMap.order[props.order.status] || { variant: 'secondary', icon: 'help-circle-outline' });
const paymentMeta = computed(() => statusMap.payment[props.order.payment_status] || { variant: 'secondary', icon: 'help-circle-outline' });
const deliveryMeta = computed(() => statusMap.delivery[props.order.delivery_status || 'pending_assignment'] || { variant: 'secondary', icon: 'help-circle-outline' });
</script>
