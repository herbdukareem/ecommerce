<template>
  <div class="rounded-xl border border-dashed border-DEFAULT bg-white shadow-sm p-10 text-center">
    <div class="mx-auto h-14 w-14 rounded-2xl bg-base text-primary flex items-center justify-center">
      <i class="mdi mdi-package-variant-closed text-3xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-primary mt-4">You have no orders yet</h3>
    <p class="text-sm text-secondary mt-1">Once you place an order, it will appear here for tracking and updates.</p>
    <Button class="mt-5" variant="primary" icon="shopping" @click="$emit('start-shopping')">Start Shopping</Button>
  </div>
</template>

<script setup>
import Button from '../../ui/Button.vue';

defineEmits(['start-shopping']);
</script>
