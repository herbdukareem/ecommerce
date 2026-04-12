<template>
  <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <article
      v-for="entry in statusCards"
      :key="entry.key"
      class="rounded-xl border border-DEFAULT bg-white p-4 shadow-sm"
    >
      <div class="flex items-center justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-secondary">{{ entry.title }}</p>
          <p class="text-sm text-primary mt-1 font-medium">{{ entry.description }}</p>
        </div>
        <span class="h-10 w-10 rounded-xl flex items-center justify-center" :class="entry.iconBgClass">
          <i :class="`mdi mdi-${entry.icon} text-xl`"></i>
        </span>
      </div>

      <div class="mt-3">
        <Badge :variant="entry.variant" :icon="entry.badgeIcon">{{ entry.label }}</Badge>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '../ui/Badge.vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
});

const humanize = (value) => {
  if (!value) return 'Unknown';
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

const orderStatusMeta = (status) => {
  const map = {
    pending: { variant: 'warning', icon: 'clock-outline' },
    processing: { variant: 'info', icon: 'cog' },
    shipped: { variant: 'info', icon: 'truck-fast' },
    delivered: { variant: 'success', icon: 'check-circle' },
    cancelled: { variant: 'danger', icon: 'close-circle' },
  };
  return map[status] || { variant: 'secondary', icon: 'help-circle-outline' };
};

const paymentStatusMeta = (status) => {
  const map = {
    pending: { variant: 'warning', icon: 'cash-clock' },
    paid: { variant: 'success', icon: 'check-decagram' },
    refunded: { variant: 'info', icon: 'cash-refund' },
    failed: { variant: 'danger', icon: 'alert-circle' },
  };
  return map[status] || { variant: 'secondary', icon: 'help-circle-outline' };
};

const deliveryStatusMeta = (status) => {
  const map = {
    pending_assignment: { variant: 'warning', icon: 'account-clock' },
    assigned: { variant: 'info', icon: 'account-check' },
    packed: { variant: 'info', icon: 'package-variant-closed' },
    shipped: { variant: 'info', icon: 'truck-fast' },
    in_transit: { variant: 'info', icon: 'map-marker-path' },
    delivered: { variant: 'success', icon: 'home-check' },
    delivery_failed: { variant: 'danger', icon: 'alert-outline' },
    returned: { variant: 'danger', icon: 'backup-restore' },
    cancelled: { variant: 'danger', icon: 'close-circle' },
  };
  return map[status] || { variant: 'secondary', icon: 'help-circle-outline' };
};

const iconBackgroundClass = (variant) => {
  const map = {
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-warning',
    danger: 'bg-danger/10 text-danger',
    info: 'bg-info/10 text-info',
    secondary: 'bg-secondary/10 text-secondary',
    primary: 'bg-primary/10 text-primary',
  };
  return map[variant] || map.secondary;
};

const statusCards = computed(() => {
  const orderStatus = props.order?.status || 'pending';
  const paymentStatus = props.order?.payment_status || 'pending';
  const deliveryStatus = props.order?.delivery_status || 'pending_assignment';

  const orderMeta = orderStatusMeta(orderStatus);
  const paymentMeta = paymentStatusMeta(paymentStatus);
  const deliveryMeta = deliveryStatusMeta(deliveryStatus);

  return [
    {
      key: 'order',
      title: 'Order Status',
      description: 'Current order processing state',
      label: humanize(orderStatus),
      variant: orderMeta.variant,
      icon: 'package-variant',
      badgeIcon: orderMeta.icon,
      iconBgClass: iconBackgroundClass(orderMeta.variant),
    },
    {
      key: 'payment',
      title: 'Payment Status',
      description: 'Billing and transaction state',
      label: humanize(paymentStatus),
      variant: paymentMeta.variant,
      icon: 'credit-card-check-outline',
      badgeIcon: paymentMeta.icon,
      iconBgClass: iconBackgroundClass(paymentMeta.variant),
    },
    {
      key: 'delivery',
      title: 'Delivery Status',
      description: 'Logistics and fulfillment progress',
      label: humanize(deliveryStatus),
      variant: deliveryMeta.variant,
      icon: 'truck-delivery-outline',
      badgeIcon: deliveryMeta.icon,
      iconBgClass: iconBackgroundClass(deliveryMeta.variant),
    },
  ];
});
</script>
