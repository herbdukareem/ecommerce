<template>
  <section class="rounded-2xl border border-DEFAULT bg-white shadow-sm p-5 sm:p-6">
    <div class="flex items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-lg sm:text-xl font-bold text-primary">Order Items</h2>
        <p class="text-sm text-secondary">{{ itemCountText }}</p>
      </div>
      <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full bg-base px-3 py-1 text-secondary">
        <i class="mdi mdi-package-variant"></i>
        {{ items.length }} item{{ items.length === 1 ? '' : 's' }}
      </span>
    </div>

    <div v-if="!items.length" class="rounded-xl border border-dashed border-DEFAULT bg-base p-6 text-center text-secondary text-sm">
      No items found for this order.
    </div>

    <div v-else class="space-y-3">
      <OrderItemCard
        v-for="item in items"
        :key="item.id"
        :item="item"
        :format-currency="formatCurrency"
      />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import OrderItemCard from './OrderItemCard.vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
});

const itemCountText = computed(() => {
  if (!props.items.length) {
    return 'No products in this order yet';
  }
  return 'Products purchased in this order';
});
</script>
