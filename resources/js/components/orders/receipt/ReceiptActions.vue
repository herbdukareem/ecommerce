<template>
  <section class="no-print space-y-3 pt-2">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
      <Button icon="download" :loading="downloading" @click="$emit('download-pdf')">Download PDF</Button>
      <Button variant="outline" icon="printer" @click="$emit('print')">Print Receipt</Button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
      <Button variant="ghost" icon="arrow-left" @click="$emit('back')">Back to Orders</Button>
      <Button variant="ghost" icon="cart-outline" @click="$emit('reorder')">Reorder</Button>
      <Button variant="ghost" icon="map-marker-path" @click="$emit('track')">Track Order</Button>
    </div>

    <Button
      v-if="cancellationEnabled && canCancel"
      variant="danger"
      icon="close-circle"
      class="w-full"
      :loading="cancelling"
      @click="$emit('cancel')"
    >
      Cancel Order
    </Button>
  </section>
</template>

<script setup>
import Button from '../../ui/Button.vue';

defineProps({
  canCancel: {
    type: Boolean,
    default: false,
  },
  cancelling: {
    type: Boolean,
    default: false,
  },
  downloading: {
    type: Boolean,
    default: false,
  },
  cancellationEnabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['download-pdf', 'print', 'back', 'reorder', 'track', 'cancel']);
</script>
