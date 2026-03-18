<template>
  <RouterView />
  <Toast ref="toastRef" />
</template>

<script setup>
import { provide, ref } from 'vue';
import Toast from './components/ui/Toast.vue';

const toastRef = ref(null);

provide('toast', {
  success: (message, title, duration) => toastRef.value?.success(message, title, duration),
  error: (message, title, duration) => toastRef.value?.error(message, title, duration),
  warning: (message, title, duration) => toastRef.value?.warning(message, title, duration),
  info: (message, title, duration) => toastRef.value?.info(message, title, duration),
});
</script>