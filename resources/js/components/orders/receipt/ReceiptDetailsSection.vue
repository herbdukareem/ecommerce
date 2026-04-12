<template>
  <section class="space-y-3">
    <div class="flex items-center gap-2">
      <i :class="`mdi mdi-${icon} text-primary`"></i>
      <h3 class="text-sm font-semibold text-primary">{{ title }}</h3>
    </div>

    <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
      <div
        v-for="(row, index) in rows"
        :key="`${title}-${index}`"
        class="grid grid-cols-[1fr_auto] gap-3 px-4 py-3 text-sm"
      >
        <p class="text-secondary">{{ row.label }}</p>
        <p class="text-primary font-medium text-right">{{ row.value || 'N/A' }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    default: 'receipt-text-outline',
  },
  rows: {
    type: Array,
    default: () => [],
  },
});
</script>
