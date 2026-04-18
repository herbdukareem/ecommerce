<template>
  <article class="rounded-xl border border-DEFAULT bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex items-start gap-4">
      <img
        :src="imageSrc"
        :alt="title"
        class="h-20 w-20 rounded-lg border border-DEFAULT object-cover bg-base"
        @error="onImageError"
      />

      <div class="min-w-0 flex-1">
        <p class="text-base font-semibold text-primary leading-snug">{{ title }}</p>
        <p v-if="optionLabel" class="mt-1 text-xs text-secondary">Option: {{ optionLabel }}</p>
        <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
          <p class="text-secondary">Quantity</p>
          <p class="text-right font-medium text-primary">{{ quantity }}</p>

          <p class="text-secondary">Unit Price</p>
          <p class="text-right font-medium text-primary">{{ formatCurrency(unitPrice) }}</p>

          <p class="text-secondary">Subtotal</p>
          <p class="text-right font-semibold text-primary">{{ formatCurrency(subtotal) }}</p>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
});

const placeholderImage = '/images/placeholders/product-placeholder.svg';

const title = computed(() => props.item?.product_name_snapshot || props.item?.sku?.product?.title || 'Product');
const imageSrc = computed(() => props.item?.image_snapshot || props.item?.sku?.product?.image || placeholderImage);
const quantity = computed(() => Number(props.item?.quantity || 0));
const unitPrice = computed(() => Number(props.item?.price_snapshot || 0));
const subtotal = computed(() => Number(props.item?.subtotal || quantity.value * unitPrice.value));
const optionLabel = computed(() => props.item?.option_label_snapshot || props.item?.product_option?.display_label || props.item?.sku?.display_label || null);

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = placeholderImage;
  }
};
</script>
