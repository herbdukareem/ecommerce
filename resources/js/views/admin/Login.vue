<template>
  <div class="min-h-screen bg-base">
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[1.05fr_0.95fr]">
      <section class="relative hidden overflow-hidden bg-primary text-white lg:flex">
        <div class="absolute inset-0 opacity-20">
          <div class="absolute -left-20 top-16 h-64 w-64 rounded-full bg-white blur-3xl"></div>
          <div class="absolute bottom-10 right-10 h-80 w-80 rounded-full bg-accent blur-3xl"></div>
        </div>

        <div class="relative flex w-full flex-col justify-between p-12">
          <div class="flex items-center gap-4">
            <img
              v-if="siteLogoUrl"
              :src="siteLogoUrl"
              :alt="`${siteName} logo`"
              class="h-16 w-16 rounded-2xl bg-white object-contain p-1"
            />
            <div v-else class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold">
              {{ brandInitials }}
            </div>
            <div>
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/70">Admin workspace</p>
              <h1 class="text-3xl font-bold">{{ siteName }}</h1>
            </div>
          </div>

          <div class="max-w-xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/70">Secure operations</p>
            <h2 class="mt-4 text-5xl font-bold leading-tight">Manage orders, stock, dispatch, and revenue in one calm place.</h2>
            <p class="mt-5 text-lg leading-8 text-white/78">
              Sign in to review today’s orders, coordinate fulfillment, monitor inventory pressure, and keep customer updates moving.
            </p>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
              <p class="text-2xl font-bold">24/7</p>
              <p class="mt-1 text-xs text-white/70">Store control</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
              <p class="text-2xl font-bold">Live</p>
              <p class="mt-1 text-xs text-white/70">Order tracking</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
              <p class="text-2xl font-bold">Role</p>
              <p class="mt-1 text-xs text-white/70">Based access</p>
            </div>
          </div>
        </div>
      </section>

      <main class="flex items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
          <div class="mb-8 lg:hidden">
            <div class="flex items-center gap-3">
              <img
                v-if="siteLogoUrl"
                :src="siteLogoUrl"
                :alt="`${siteName} logo`"
                class="h-14 w-14 rounded-xl bg-white object-contain"
              />
              <div v-else class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-lg font-bold text-white">
                {{ brandInitials }}
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-secondary">Admin workspace</p>
                <h1 class="text-2xl font-bold text-primary">{{ siteName }}</h1>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-DEFAULT bg-surface p-6 shadow-lg sm:p-8">
            <div>
              <p class="text-sm font-semibold text-secondary">Welcome back</p>
              <h2 class="mt-1 text-3xl font-bold text-primary">Sign in to admin</h2>
              <p class="mt-2 text-sm leading-6 text-secondary">Use your authorized admin account to continue.</p>
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="handleLogin">
              <div>
                <label for="email" class="mb-2 block text-sm font-medium text-primary">Email address</label>
                <div class="relative">
                  <i class="mdi mdi-email-outline pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-secondary"></i>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                    class="form-input pl-10"
                    placeholder="admin@example.com"
                  />
                </div>
              </div>

              <div>
                <label for="password" class="mb-2 block text-sm font-medium text-primary">Password</label>
                <div class="relative">
                  <i class="mdi mdi-lock-outline pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-secondary"></i>
                  <input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    class="form-input px-10"
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary transition-colors hover:text-primary"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline'"></i>
                  </button>
                </div>
              </div>

              <label class="flex items-center gap-2 text-sm text-secondary">
                <input
                  v-model="form.remember"
                  type="checkbox"
                  class="h-4 w-4 rounded border-DEFAULT text-primary focus:ring-primary/40"
                />
                Remember this device
              </label>

              <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <i class="mdi mdi-alert-circle-outline mr-1"></i>
                {{ error }}
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
              >
                <i v-if="loading" class="mdi mdi-loading mdi-spin"></i>
                <i v-else class="mdi mdi-login"></i>
                {{ loading ? 'Signing in...' : 'Sign in' }}
              </button>
            </form>
          </div>

          <div class="mt-6 flex items-center justify-between text-sm">
            <router-link to="/" class="font-medium text-primary hover:underline">
              <i class="mdi mdi-arrow-left mr-1"></i>
              Back to store
            </router-link>
            <span class="text-secondary">{{ siteName }} Admin</span>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const authStore = useAuthStore();
const settingsStore = useSettingsStore();

const form = ref({
  email: '',
  password: '',
  remember: false,
});

const showPassword = ref(false);
const loading = ref(false);
const error = ref('');

const siteName = computed(() => settingsStore.siteName || 'Online Mart');
const siteLogoUrl = computed(() => settingsStore.siteLogoUrl || '');
const brandInitials = computed(() => settingsStore.brandInitials || 'OM');

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  const result = await authStore.login({
    email: form.value.email,
    password: form.value.password,
  });

  loading.value = false;

  if (result.success) {
    if (authStore.isAdmin) {
      router.push('/admin/dashboard');
      return;
    }

    error.value = 'Access denied. Admin privileges required.';
    await authStore.logout();
    return;
  }

  error.value = result.error?.message || 'Invalid email or password';
};
</script>

<style scoped>
.form-input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
  background: var(--color-background);
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  color: var(--color-text);
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
}
</style>
