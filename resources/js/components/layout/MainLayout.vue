<template>
  <div class="min-h-screen flex flex-col bg-base transition-colors duration-300">
    <Header />
    
    <main class="flex-1">
      <slot></slot>
    </main>
    
    <Footer />
  </div>
</template>

<script setup>
import Header from './Header.vue';
import Footer from './Footer.vue';
</script>

