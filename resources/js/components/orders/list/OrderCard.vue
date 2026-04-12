<template>
  <article class="rounded-xl border border-DEFAULT bg-white shadow-sm p-4 sm:p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div class="min-w-0">
        <p class="text-xs uppercase tracking-[0.12em] text-secondary font-semibold">Order #{{ order.id }}</p>
        <p class="text-sm text-secondary mt-1 inline-flex items-center gap-1.5">
          <i class="mdi mdi-calendar-month-outline"></i>
          Placed {{ formatDate(order.placed_at || order.created_at) }}
        </p>

        <p class="text-2xl font-bold text-primary mt-2">{{ formatCurrency(order.total || 0) }}</p>

        <div class="mt-3 flex items-center gap-2">
          <div v-for="(thumb, index) in thumbnails" :key="`${order.id}-thumb-${index}`" class="h-9 w-9 rounded-md border border-DEFAULT overflow-hidden bg-base">
            <img :src="thumb" alt="Order item" class="h-full w-full object-cover" @error="onImageError" />
          </div>
          <span class="text-xs text-secondary bg-base rounded-full px-2.5 py-1">{{ itemCountLabel }}</span>
        </div>
      </div>

      <div class="w-full lg:w-auto lg:min-w-[290px] space-y-3">
        <OrderStatusBadges :order="order" />

        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Button size="sm" variant="ghost" icon="cart-outline" @click="$emit('reorder', order)">Reorder</Button>
          <Button
            v-if="canTrack"
            size="sm"
            variant="outline"
            icon="map-marker-path"
            @click="$emit('track', order)"
          >
            Track Order
          </Button>
          <Button size="sm" variant="primary" icon="arrow-right" icon-right @click="$emit('view', order)">
            View Details
          </Button>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import Button from '../../ui/Button.vue';
import OrderStatusBadges from './OrderStatusBadges.vue';

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

defineEmits(['view', 'reorder', 'track']);

const placeholder = '/images/placeholders/product-placeholder.svg';

const thumbnails = computed(() => {
  const items = props.order.items || [];
  return items.slice(0, 3).map((item) => item?.sku?.product?.image || placeholder);
});

const itemCountLabel = computed(() => {
  const count = (props.order.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  return `${count || (props.order.items || []).length || 0} item${count === 1 ? '' : 's'}`;
});

const canTrack = computed(() => {
  const status = props.order.delivery_status || '';
  return ['assigned', 'packed', 'shipped', 'in_transit', 'delivered'].includes(status) || !!props.order.delivery_tracking_code;
});

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = placeholder;
  }
};
</script>
