<template>
  <MainLayout>
    <section class="max-w-4xl mx-auto px-4 py-12 space-y-4">
      <h1 class="text-3xl font-bold text-primary">{{ pageTitle }}</h1>
      <p class="text-secondary">{{ pageDescription }}</p>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';

const route = useRoute();

const pageContent = {
  about: {
    title: 'About Us',
    description: 'Learn more about our store, mission, and commitment to quality shopping experiences.',
  },
  contact: {
    title: 'Contact Us',
    description: 'Reach out to our support team for questions about orders, products, and partnerships.',
  },
  faq: {
    title: 'Frequently Asked Questions',
    description: 'Find quick answers to common questions about payments, orders, and account management.',
  },
  shipping: {
    title: 'Shipping Information',
    description: 'Review delivery timelines, zones, and shipping policies for your location.',
  },
  returns: {
    title: 'Returns and Refunds',
    description: 'Read our return eligibility, refund process, and timelines for completed orders.',
  },
  support: {
    title: 'Support Center',
    description: 'Get help with technical issues, account access, and order troubleshooting.',
  },
  privacy: {
    title: 'Privacy Policy',
    description: 'Understand how we collect, process, and protect your personal information.',
  },
  terms: {
    title: 'Terms of Service',
    description: 'Review the terms and conditions governing use of this platform and its services.',
  },
};

const key = computed(() => String(route.meta?.pageKey || route.name || '').toLowerCase());

const pageTitle = computed(() => pageContent[key.value]?.title || 'Information');

const pageDescription = computed(() => pageContent[key.value]?.description || 'This page contains helpful information.');
</script>
