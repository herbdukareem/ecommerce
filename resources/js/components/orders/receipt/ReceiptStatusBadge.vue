<template>
  <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="badgeClass">
    <i :class="`mdi mdi-${meta.icon}`"></i>
    <span>{{ label }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: String,
    default: '',
  },
  category: {
    type: String,
    default: 'order',
  },
  label: {
    type: String,
    default: '',
  },
});

const maps = {
  order: {
    pending: { cls: 'bg-amber-100 text-amber-800', icon: 'clock-outline' },
    processing: { cls: 'bg-sky-100 text-sky-800', icon: 'cog' },
    shipped: { cls: 'bg-indigo-100 text-indigo-800', icon: 'truck-fast' },
    delivered: { cls: 'bg-emerald-100 text-emerald-800', icon: 'check-circle' },
    cancelled: { cls: 'bg-rose-100 text-rose-800', icon: 'close-circle' },
  },
  payment: {
    pending: { cls: 'bg-amber-100 text-amber-800', icon: 'cash-clock' },
    paid: { cls: 'bg-emerald-100 text-emerald-800', icon: 'check-decagram' },
    refunded: { cls: 'bg-sky-100 text-sky-800', icon: 'cash-refund' },
    failed: { cls: 'bg-rose-100 text-rose-800', icon: 'alert-circle' },
  },
  delivery: {
    pending_assignment: { cls: 'bg-amber-100 text-amber-800', icon: 'account-clock' },
    assigned: { cls: 'bg-sky-100 text-sky-800', icon: 'account-check' },
    packed: { cls: 'bg-sky-100 text-sky-800', icon: 'package-variant-closed' },
    shipped: { cls: 'bg-indigo-100 text-indigo-800', icon: 'truck-fast' },
    in_transit: { cls: 'bg-indigo-100 text-indigo-800', icon: 'map-marker-path' },
    delivered: { cls: 'bg-emerald-100 text-emerald-800', icon: 'home-check' },
    delivery_failed: { cls: 'bg-rose-100 text-rose-800', icon: 'alert-outline' },
    returned: { cls: 'bg-rose-100 text-rose-800', icon: 'backup-restore' },
    cancelled: { cls: 'bg-rose-100 text-rose-800', icon: 'close-circle' },
  },
};

const meta = computed(() => {
  const categoryMap = maps[props.category] || {};
  return categoryMap[props.value] || { cls: 'bg-slate-100 text-slate-700', icon: 'help-circle-outline' };
});

const badgeClass = computed(() => meta.value.cls);
</script>
