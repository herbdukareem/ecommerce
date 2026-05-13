<template>
  <MainLayout>
    <section class="max-w-6xl mx-auto px-4 py-10 space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-secondary">Account</p>
          <h1 class="mt-1 text-3xl font-bold text-primary">Welcome, {{ firstName }}</h1>
          <p class="text-sm text-secondary mt-1">Manage your profile, security, orders, addresses, and referrals.</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <router-link to="/orders">
            <Button variant="primary" icon="package-variant">Orders</Button>
          </router-link>
          <router-link to="/account/referrals">
            <Button variant="outline" icon="account-multiple-plus">Referrals</Button>
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card :elevation="1" class="p-5">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
              <i class="mdi mdi-account-outline text-2xl"></i>
            </div>
            <div>
              <p class="text-xs text-secondary">Profile</p>
              <p class="font-semibold text-primary">{{ authStore.user?.name || 'Customer' }}</p>
            </div>
          </div>
        </Card>

        <Card :elevation="1" class="p-5">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
              <i class="mdi mdi-email-outline text-2xl"></i>
            </div>
            <div class="min-w-0">
              <p class="text-xs text-secondary">Email</p>
              <p class="font-semibold text-primary truncate">{{ authStore.user?.email || 'Not set' }}</p>
            </div>
          </div>
        </Card>

        <Card :elevation="1" class="p-5">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
              <i class="mdi mdi-phone-outline text-2xl"></i>
            </div>
            <div>
              <p class="text-xs text-secondary">Phone</p>
              <p class="font-semibold text-primary">{{ authStore.user?.phone || 'Not set' }}</p>
            </div>
          </div>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card :elevation="2" class="p-5 lg:col-span-2">
          <div class="flex items-center justify-between gap-4 mb-4">
            <div>
              <h2 class="text-lg font-semibold text-primary">Profile Details</h2>
              <p class="text-sm text-secondary">Keep your contact information current.</p>
            </div>
            <i class="mdi mdi-card-account-details-outline text-2xl text-primary"></i>
          </div>

          <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="saveProfile">
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Name</label>
              <input v-model="profileForm.name" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Email</label>
              <input v-model="profileForm.email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Phone</label>
              <input v-model="profileForm.phone" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" />
            </div>

            <div class="md:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <p v-if="profileMessage" class="text-sm" :class="profileSuccess ? 'text-success' : 'text-danger'">
                {{ profileMessage }}
              </p>
              <span v-else class="text-sm text-secondary">Changes apply to your customer profile.</span>

              <Button type="submit" variant="primary" icon="content-save-outline" :loading="savingProfile">Save Profile</Button>
            </div>
          </form>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-3">Quick Actions</h2>
          <div class="space-y-2">
            <router-link v-for="action in quickActions" :key="action.to" :to="action.to" class="flex items-center justify-between rounded-lg border border-DEFAULT px-3 py-3 hover:border-primary/50 transition">
              <span class="flex items-center gap-3">
                <i :class="`mdi mdi-${action.icon} text-xl text-primary`"></i>
                <span class="font-medium text-primary">{{ action.label }}</span>
              </span>
              <i class="mdi mdi-chevron-right text-secondary"></i>
            </router-link>
          </div>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card :elevation="2" class="p-5">
          <div class="flex items-center justify-between gap-4 mb-4">
            <div>
              <h2 class="text-lg font-semibold text-primary">Security</h2>
              <p class="text-sm text-secondary">Update your password regularly.</p>
            </div>
            <i class="mdi mdi-shield-lock-outline text-2xl text-primary"></i>
          </div>

          <form class="space-y-3" @submit.prevent="savePassword">
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Current password</label>
              <input v-model="passwordForm.current_password" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">New password</label>
              <input v-model="passwordForm.password" type="password" minlength="8" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Confirm password</label>
              <input v-model="passwordForm.password_confirmation" type="password" minlength="8" class="w-full border border-DEFAULT rounded-lg px-3 py-2 focus:outline-none focus:border-primary" required />
            </div>

            <p v-if="passwordMessage" class="text-sm" :class="passwordSuccess ? 'text-success' : 'text-danger'">
              {{ passwordMessage }}
            </p>

            <Button type="submit" variant="outline" icon="lock-reset" :loading="savingPassword">Update Password</Button>
          </form>
        </Card>

        <Card :elevation="2" class="p-5 lg:col-span-2">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-primary">Session</h2>
              <p class="text-sm text-secondary">Sign out from this device when you are done shopping.</p>
            </div>
            <Button variant="danger" icon="logout" @click="logout">Logout</Button>
          </div>
        </Card>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const savingProfile = ref(false);
const savingPassword = ref(false);
const profileMessage = ref('');
const passwordMessage = ref('');
const profileSuccess = ref(false);
const passwordSuccess = ref(false);

const firstName = computed(() => {
  const name = authStore.user?.name || 'there';
  return name.split(' ')[0] || name;
});

const quickActions = [
  { to: '/orders', label: 'Order history', icon: 'package-variant' },
  { to: '/addresses', label: 'Saved addresses', icon: 'map-marker-outline' },
  { to: '/account/referrals', label: 'Invite and earn', icon: 'account-multiple-plus-outline' },
  { to: '/cart', label: 'Shopping cart', icon: 'cart-outline' },
];

const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const syncProfile = () => {
  profileForm.name = authStore.user?.name || '';
  profileForm.email = authStore.user?.email || '';
  profileForm.phone = authStore.user?.phone || '';
};

const saveProfile = async () => {
  savingProfile.value = true;
  profileMessage.value = '';

  const result = await authStore.updateProfile(profileForm);

  savingProfile.value = false;
  profileSuccess.value = !!result.success;
  profileMessage.value = result.success ? 'Profile updated successfully.' : (result.error?.message || 'Unable to update profile.');
};

const savePassword = async () => {
  passwordMessage.value = '';
  if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordSuccess.value = false;
    passwordMessage.value = 'Password confirmation does not match.';
    return;
  }

  savingPassword.value = true;
  const result = await authStore.changePassword(passwordForm);
  savingPassword.value = false;

  passwordSuccess.value = !!result.success;
  passwordMessage.value = result.success
    ? 'Password changed. Please login again.'
    : (result.error?.message || result.error?.current_password?.[0] || 'Unable to change password.');

  if (result.success) {
    router.push('/login');
  }
};

const logout = async () => {
  await authStore.logout();
  router.push('/');
};

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchUser();
  }
  syncProfile();
});
</script>
