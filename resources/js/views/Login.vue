<template>
  <MainLayout>
    <section class="max-w-md mx-auto px-4 py-12">
      <Card :elevation="2" class="p-6">
        <h1 class="text-2xl font-bold text-primary mb-2">Customer Login</h1>
        <p class="text-sm text-secondary mb-6">Sign in to manage your account and orders.</p>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <div>
            <label class="block text-sm font-medium text-primary mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Password</label>
            <input v-model="form.password" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
          </div>

          <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>

          <Button type="submit" variant="primary" class="w-full" :loading="loading">Login</Button>
        </form>

        <div class="mt-5 text-sm text-secondary space-y-1">
          <p>
            No account?
            <router-link to="/register" class="text-primary hover:underline">Create one</router-link>
          </p>
          <p>
            <router-link to="/forgot-password" class="text-primary hover:underline">Forgot your password?</router-link>
          </p>
        </div>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const loading = ref(false);
const errorMessage = ref('');
const form = reactive({
  email: '',
  password: '',
});

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  const result = await authStore.login({
    email: form.email,
    password: form.password,
  });

  loading.value = false;

  if (!result.success) {
    errorMessage.value = result.error?.message || result.error?.email?.[0] || 'Unable to login with provided credentials.';
    return;
  }

  const redirectPath = typeof route.query.redirect === 'string' ? route.query.redirect : '/account';
  router.push(redirectPath);
};
</script>
