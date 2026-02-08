<template>
  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
    <span class="text-sm font-medium text-gray-700">{{ label }}</span>
    <div class="flex items-center space-x-2">
      <span
        :class="[
          'text-lg font-bold',
          alert ? 'text-red-600' : 'text-gray-900'
        ]"
      >
        {{ value }}
      </span>
      <span v-if="alert" class="text-red-500">⚠️</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  alert: {
    type: Boolean,
    default: false,
  },
});
</script>

