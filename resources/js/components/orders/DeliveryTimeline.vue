<template>
  <section class="rounded-2xl border border-DEFAULT bg-white shadow-sm p-5 sm:p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg sm:text-xl font-bold text-primary">Delivery Timeline</h2>
      <Badge size="sm" :variant="overallVariant" icon="timeline-clock-outline">{{ overallLabel }}</Badge>
    </div>

    <ol class="space-y-4">
      <li v-for="(step, index) in timelineSteps" :key="step.key" class="relative pl-10">
        <span
          class="absolute left-0 top-0 h-7 w-7 rounded-full flex items-center justify-center border"
          :class="step.dotClass"
        >
          <i :class="`mdi mdi-${step.icon} text-sm`"></i>
        </span>

        <span
          v-if="index < timelineSteps.length - 1"
          class="absolute left-[13px] top-7 h-9 w-[2px]"
          :class="step.connectorClass"
        ></span>

        <div class="pb-2">
          <p class="text-sm font-semibold" :class="step.titleClass">{{ step.title }}</p>
          <p class="text-xs text-secondary mt-0.5">{{ step.description }}</p>
          <p class="text-xs mt-1" :class="step.timeClass">{{ step.timestamp }}</p>
        </div>
      </li>
    </ol>
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
  formatDate: {
    type: Function,
    required: true,
  },
});

const hasStatus = (statusList) => statusList.includes(props.order?.status);
const hasDeliveryStatus = (statusList) => statusList.includes(props.order?.delivery_status);

const timelineRaw = computed(() => {
  const delivered = hasStatus(['delivered']) || hasDeliveryStatus(['delivered']);
  const shipped = hasStatus(['shipped', 'delivered']) || hasDeliveryStatus(['shipped', 'in_transit', 'delivered']);
  const processing = hasStatus(['processing', 'shipped', 'delivered']) || hasDeliveryStatus(['assigned', 'packed', 'shipped', 'in_transit', 'delivered']);
  const paid = ['paid', 'refunded'].includes(props.order?.payment_status);

  return [
    {
      key: 'placed',
      title: 'Order Placed',
      description: 'Your order was created successfully.',
      icon: 'cart-check',
      done: true,
      timestamp: props.formatDate(props.order?.placed_at || props.order?.created_at),
    },
    {
      key: 'paid',
      title: 'Payment Confirmed',
      description: 'Payment has been authorized for processing.',
      icon: 'credit-card-check',
      done: paid,
      timestamp: paid ? props.formatDate(props.order?.payments?.[0]?.paid_at || props.order?.updated_at) : 'Awaiting payment confirmation',
    },
    {
      key: 'processing',
      title: 'Processing',
      description: 'Order is being prepared for dispatch.',
      icon: 'package-variant-closed-check',
      done: processing,
      timestamp: processing ? props.formatDate(props.order?.assigned_at || props.order?.updated_at) : 'Pending processing',
    },
    {
      key: 'shipped',
      title: 'Shipped',
      description: 'Package has left the fulfillment center.',
      icon: 'truck-fast',
      done: shipped,
      timestamp: shipped ? props.formatDate(props.order?.shipped_at || props.order?.updated_at) : 'Not yet shipped',
    },
    {
      key: 'delivered',
      title: 'Delivered',
      description: 'Package was delivered to destination.',
      icon: 'home-check-outline',
      done: delivered,
      timestamp: delivered ? props.formatDate(props.order?.delivered_at || props.order?.updated_at) : 'Not delivered yet',
    },
  ];
});

const timelineSteps = computed(() => {
  const firstPendingIndex = timelineRaw.value.findIndex((step) => !step.done);

  return timelineRaw.value.map((step, index) => {
    const isCurrent = firstPendingIndex !== -1 && index === firstPendingIndex;
    const dotClass = step.done
      ? 'bg-success/10 text-success border-success/40'
      : isCurrent
        ? 'bg-info/10 text-info border-info/40'
        : 'bg-base text-secondary border-DEFAULT';

    const connectorClass = step.done ? 'bg-success/30' : 'bg-DEFAULT';

    return {
      ...step,
      dotClass,
      connectorClass,
      titleClass: step.done || isCurrent ? 'text-primary' : 'text-secondary',
      timeClass: step.done ? 'text-success' : isCurrent ? 'text-info' : 'text-secondary',
    };
  });
});

const overallLabel = computed(() => {
  if (hasStatus(['cancelled']) || hasDeliveryStatus(['cancelled', 'delivery_failed', 'returned'])) {
    return 'Attention Needed';
  }

  const delivered = hasStatus(['delivered']) || hasDeliveryStatus(['delivered']);
  if (delivered) {
    return 'Completed';
  }

  return 'In Progress';
});

const overallVariant = computed(() => {
  if (overallLabel.value === 'Completed') return 'success';
  if (overallLabel.value === 'Attention Needed') return 'danger';
  return 'info';
});
</script>
