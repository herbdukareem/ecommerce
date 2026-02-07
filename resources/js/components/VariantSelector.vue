<template>
  <div v-if="product.attributes && product.attributes.length">
    <div v-for="attr in product.attributes" :key="attr.id" class="mb-2">
      <label class="block font-semibold mb-1">{{ attr.name }}</label>
      <select
        v-model="selected[attr.id]"
        class="border p-1 w-full"
        @change="onChange"
      >
        <option value="" disabled>Select {{ attr.name }}</option>
        <option
          v-for="val in attr.values"
          :key="val.id"
          :value="val.id"
        >
          {{ val.value }}
        </option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
});
const emit = defineEmits(['select']);

const selected = reactive({});

watch(
  () => selected,
  () => {
    // When selections change, attempt to find matching SKU
    const sku = findMatchingSku();
    emit('select', sku);
  },
  { deep: true }
);

function findMatchingSku() {
    if (!props.product.skus) return null;
    return props.product.skus.find((sku) => {
      // Each sku has attributeValues array
      const attrIds = sku.attribute_values?.map((av) => av.pivot?.attribute_value_id || av.id);
      // Compare sets
      const selectedVals = Object.values(selected).map(Number);
      if (selectedVals.length === 0 || selectedVals.includes(undefined) || selectedVals.includes(null) || selectedVals.includes(NaN)) {
        return false;
      }
      return selectedVals.every((id) => attrIds?.includes(id));
    });
}

function onChange() {
  // Called on select change to trigger watcher
}
</script>