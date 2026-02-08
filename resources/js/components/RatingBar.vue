<template>
  <div class="flex items-center space-x-2">
    <span class="text-sm text-gray-600 w-12">{{ stars }} star</span>
    <div class="flex-1 bg-gray-200 rounded-full h-2">
      <div
        class="bg-yellow-400 h-2 rounded-full"
        :style="{ width: percentage + '%' }"
      ></div>
    </div>
    <span class="text-sm text-gray-600 w-12 text-right">{{ count }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stars: {
    type: Number,
    required: true,
  },
  count: {
    type: Number,
    default: 0,
  },
  total: {
    type: Number,
    default: 0,
  },
});

const percentage = computed(() => {
  if (props.total === 0) return 0;
  return (props.count / props.total) * 100;
});
</script>

