<template>
  <div class="min-h-screen flex flex-col bg-base transition-colors duration-300">
    <Header />
    
    <main class="flex-1">
      <slot></slot>
    </main>
    
    <Footer />
    
    <!-- Toast Notifications -->
    <Toast ref="toastRef" />
  </div>
</template>

<script setup>
import { ref, provide } from 'vue';
import Header from './Header.vue';
import Footer from './Footer.vue';
import Toast from '../ui/Toast.vue';

const toastRef = ref(null);

// Provide toast methods to child components
provide('toast', {
  success: (message, title, duration) => toastRef.value?.success(message, title, duration),
  error: (message, title, duration) => toastRef.value?.error(message, title, duration),
  warning: (message, title, duration) => toastRef.value?.warning(message, title, duration),
  info: (message, title, duration) => toastRef.value?.info(message, title, duration),
});
</script>

