<template>
  <div class="space-y-2">
    <button
      v-for="sku in options"
      :key="sku.id"
      type="button"
      class="w-full rounded-lg border px-3 py-2 text-left transition"
      :class="buttonClass(sku)"
      :disabled="isOutOfStock(sku) || sku.active === false"
      @click="selectOption(sku)"
    >
      <div class="flex items-start gap-3">
        <img
          v-if="skuImage(sku)"
          :src="skuImage(sku)"
          :alt="optionLabel(sku)"
          class="h-12 w-12 rounded-md border border-DEFAULT object-cover"
          @error="onImageError"
        />
        <div class="min-w-0 flex-1">
          <p class="font-semibold text-primary truncate">{{ optionLabel(sku) }}</p>
          <p class="text-sm text-secondary">{{ formatCurrency(sku.price || 0) }}</p>
          <p class="text-xs" :class="isOutOfStock(sku) ? 'text-danger' : 'text-success'">
            {{ isOutOfStock(sku) ? 'Out of stock' : 'In stock' }}
          </p>
        </div>
      </div>
    </button>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useSettingsStore } from '../stores/settings';

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['select', 'update:modelValue']);
const selectedId = ref(props.modelValue?.id || null);
const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;

const options = computed(() => {
  const skus = Array.isArray(props.product?.skus) ? props.product.skus : [];
  return skus
    .filter((sku) => sku && sku.active !== false)
    .sort((a, b) => Number(a.sort_order || 0) - Number(b.sort_order || 0));
});

const optionLabel = (sku) => sku?.display_label || sku?.option_label || sku?.attributes?.name || sku?.sku_code || 'Option';
const skuImage = (sku) => sku?.primary_image_url || sku?.image_path || sku?.images?.[0]?.image_url || null;

const getAvailableStock = (sku) => {
  if (Array.isArray(sku?.stocks) && sku.stocks.length > 0) {
    return sku.stocks.reduce((total, stock) => total + Number(stock.on_hand || 0) - Number(stock.reserved || 0), 0);
  }
  return Number(sku?.stock_quantity || 0);
};

const isOutOfStock = (sku) => getAvailableStock(sku) <= 0;

const buttonClass = (sku) => {
  const isSelected = Number(selectedId.value) === Number(sku.id);
  if (isOutOfStock(sku)) {
    return 'border-DEFAULT bg-base opacity-60 cursor-not-allowed';
  }
  return isSelected
    ? 'border-primary bg-primary/5'
    : 'border-DEFAULT hover:border-primary/40';
};

const selectOption = (sku) => {
  if (isOutOfStock(sku)) {
    return;
  }
  selectedId.value = sku.id;
  emit('select', sku);
  emit('update:modelValue', sku);
};

const onImageError = (event) => {
  if (event?.target) {
    event.target.style.display = 'none';
  }
};

watch(
  () => props.modelValue,
  (value) => {
    selectedId.value = value?.id || null;
  },
  { deep: true }
);
</script>
