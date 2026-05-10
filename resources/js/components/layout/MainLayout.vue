<template>
  <div class="min-h-screen flex flex-col bg-base transition-colors duration-300">
    <Header />
    
    <main class="flex-1">
      <slot></slot>
    </main>
    
    <Footer />

    <a
      v-if="whatsappUrl"
      :href="whatsappUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="fixed bottom-5 right-5 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition-transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[#25D366]/30"
      aria-label="Chat on WhatsApp"
    >
      <i class="mdi mdi-whatsapp text-3xl"></i>
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Header from './Header.vue';
import Footer from './Footer.vue';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();

const whatsappUrl = computed(() => {
  const digits = String(settingsStore.siteWhatsappNumber || '').replace(/\D/g, '');
  return digits ? `https://wa.me/${digits}` : '';
});
</script>

