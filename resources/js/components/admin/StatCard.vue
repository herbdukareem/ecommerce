<template>
  <div :class="cardClasses" class="rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-2xl font-bold mt-2">{{ value }}</p>
      </div>
      <div class="text-4xl">{{ icon }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  color: {
    type: String,
    default: 'blue',
  },
});

const cardClasses = computed(() => {
  const colors = {
    green: 'bg-green-50 border-l-4 border-green-500',
    blue: 'bg-blue-50 border-l-4 border-blue-500',
    yellow: 'bg-yellow-50 border-l-4 border-yellow-500',
    purple: 'bg-purple-50 border-l-4 border-purple-500',
  };
  return colors[props.color] || colors.blue;
});
</script>

