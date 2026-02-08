<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ value }}</p>
        <div v-if="trend !== undefined" class="mt-2 flex items-center">
          <span
            :class="[
              'text-sm font-medium',
              trend >= 0 ? 'text-green-600' : 'text-red-600'
            ]"
          >
            {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
          </span>
          <span class="text-xs text-gray-500 ml-2">vs previous period</span>
        </div>
      </div>
      <div class="text-5xl">{{ icon }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  trend: Number,
});
</script>

