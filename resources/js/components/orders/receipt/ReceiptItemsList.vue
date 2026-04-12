<template>
  <section class="space-y-3">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <i class="mdi mdi-package-variant text-primary"></i>
        <h3 class="text-sm font-semibold text-primary">Item Summary</h3>
      </div>
      <p class="text-xs text-secondary">{{ totalItems }} item{{ totalItems === 1 ? '' : 's' }}</p>
    </div>

    <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
      <div v-for="item in items" :key="item.id" class="p-3 sm:p-4">
        <div class="flex items-center gap-3">
          <img
            :src="item.sku?.product?.image || placeholder"
            :alt="item.sku?.product?.title || 'Product'"
            class="h-12 w-12 rounded-lg border border-slate-200 object-cover"
            @error="onImageError"
          />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-primary truncate">{{ item.sku?.product?.title || 'Product' }}</p>
            <p class="text-xs text-secondary">{{ item.quantity }} x {{ formatCurrency(item.price_snapshot || 0) }}</p>
          </div>
          <p class="text-sm font-semibold text-primary">{{ formatCurrency(itemSubtotal(item)) }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

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

const placeholder = '/images/placeholders/product-placeholder.svg';

const itemSubtotal = (item) => {
  if (item?.subtotal !== null && item?.subtotal !== undefined) {
    return Number(item.subtotal || 0);
  }
  return Number(item?.quantity || 0) * Number(item?.price_snapshot || 0);
};

const totalItems = computed(() => props.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0));

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = placeholder;
  }
};
</script>
