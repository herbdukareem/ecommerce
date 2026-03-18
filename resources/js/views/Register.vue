<template>
  <MainLayout>
    <section class="max-w-md mx-auto px-4 py-12">
      <Card :elevation="2" class="p-6">
        <h1 class="text-2xl font-bold text-primary mb-2">Create Account</h1>
        <p class="text-sm text-secondary mb-6">Register to checkout faster and track your orders.</p>

        <form class="space-y-4" @submit.prevent="handleRegister">
          <div>
            <label class="block text-sm font-medium text-primary mb-1">Full name</label>
            <input v-model="form.name" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Password</label>
            <input v-model="form.password" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" minlength="8" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Confirm password</label>
            <input v-model="form.password_confirmation" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" minlength="8" required />
          </div>

          <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>

          <Button type="submit" variant="primary" class="w-full" :loading="loading">Create Account</Button>
        </form>

        <p class="mt-5 text-sm text-secondary">
          Already have an account?
          <router-link to="/login" class="text-primary hover:underline">Sign in</router-link>
        </p>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const handleRegister = async () => {
  errorMessage.value = '';
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Password confirmation does not match.';
    return;
  }

  loading.value = true;
  const result = await authStore.register(form);
  loading.value = false;

  if (!result.success) {
    errorMessage.value = result.error?.message || result.error?.email?.[0] || 'Unable to create account.';
    return;
  }

  router.push('/account');
};
</script>
