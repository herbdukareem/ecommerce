<template>
  <header class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-primary">My Orders</h1>
        <p class="text-sm text-secondary mt-1">Track, view, and manage your purchases.</p>
      </div>

      <Button variant="outline" icon="shopping" @click="$emit('continue-shopping')">
        Continue Shopping
      </Button>
    </div>

    <div class="h-px bg-gradient-to-r from-DEFAULT via-slate-300/70 to-transparent"></div>
  </header>
</template>

<script setup>
import Button from '../../ui/Button.vue';

defineEmits(['continue-shopping']);
</script>
