<template>
  <MainLayout>
    <section class="max-w-md mx-auto px-4 py-12">
      <Card :elevation="2" class="p-6">
        <h1 class="text-2xl font-bold text-primary mb-2">Forgot Password</h1>
        <p class="text-sm text-secondary mb-6">Enter your account email and we will send a reset link.</p>

        <form class="space-y-4" @submit.prevent="submitRequest">
          <div>
            <label class="block text-sm font-medium text-primary mb-1">Email</label>
            <input v-model="email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
          </div>

          <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>
          <p v-if="successMessage" class="text-sm text-success">{{ successMessage }}</p>

          <Button type="submit" variant="primary" class="w-full" :loading="loading">Send Reset Link</Button>
        </form>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';

const email = ref('');
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const submitRequest = async () => {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const { data } = await axios.post('/api/auth/forgot-password', { email: email.value });
    successMessage.value = data.message || 'Reset link sent successfully.';
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.response?.data?.email?.[0] || 'Unable to send reset link.';
  } finally {
    loading.value = false;
  }
};
</script>
