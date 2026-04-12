<template>
  <div class="rounded-xl border border-DEFAULT bg-white shadow-sm p-4 sm:p-5">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 flex-1">
        <Select
          :model-value="status"
          :options="statusOptions"
          label="Order Status"
          icon="package-variant"
          @update:model-value="$emit('update:status', normalizeNull($event))"
        />

        <Select
          :model-value="paymentStatus"
          :options="paymentOptions"
          label="Payment"
          icon="credit-card-outline"
          @update:model-value="$emit('update:payment-status', normalizeNull($event))"
        />

        <Input
          :model-value="dateFrom"
          label="From"
          type="date"
          icon="calendar-start"
          @update:model-value="$emit('update:date-from', $event)"
        />

        <Input
          :model-value="dateTo"
          label="To"
          type="date"
          icon="calendar-end"
          @update:model-value="$emit('update:date-to', $event)"
        />

        <Input
          :model-value="search"
          label="Search"
          placeholder="Order ID"
          icon="magnify"
          @update:model-value="$emit('update:search', $event)"
        />
      </div>

      <div class="flex gap-2 xl:pb-[2px]">
        <Button variant="outline" icon="filter-check" @click="$emit('apply')">Apply</Button>
        <Button variant="ghost" icon="refresh" :loading="loading" @click="$emit('refresh')">Refresh</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Button from '../../ui/Button.vue';
import Input from '../../ui/Input.vue';
import Select from '../../ui/Select.vue';

defineProps({
  status: { type: String, default: null },
  paymentStatus: { type: String, default: null },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  search: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

defineEmits([
  'update:status',
  'update:payment-status',
  'update:date-from',
  'update:date-to',
  'update:search',
  'apply',
  'refresh',
]);

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
];

const paymentOptions = [
  { value: '', label: 'All payment states' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
  { value: 'refunded', label: 'Refunded' },
];

const normalizeNull = (value) => {
  return value === '' ? null : value;
};
</script>
