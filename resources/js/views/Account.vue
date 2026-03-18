<template>
  <MainLayout>
    <section class="max-w-4xl mx-auto px-4 py-10 space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">My Account</h1>
        <p class="text-sm text-secondary mt-1">Manage your profile and account security.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Profile</h2>

          <form class="space-y-3" @submit.prevent="saveProfile">
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Name</label>
              <input v-model="profileForm.name" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Email</label>
              <input v-model="profileForm.email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Phone</label>
              <input v-model="profileForm.phone" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2" />
            </div>

            <p v-if="profileMessage" class="text-sm" :class="profileSuccess ? 'text-success' : 'text-danger'">
              {{ profileMessage }}
            </p>

            <Button type="submit" variant="primary" :loading="savingProfile">Save Profile</Button>
          </form>
        </Card>

        <Card :elevation="2" class="p-5">
          <h2 class="text-lg font-semibold text-primary mb-4">Change Password</h2>

          <form class="space-y-3" @submit.prevent="savePassword">
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Current password</label>
              <input v-model="passwordForm.current_password" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">New password</label>
              <input v-model="passwordForm.password" type="password" minlength="8" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-primary mb-1">Confirm password</label>
              <input v-model="passwordForm.password_confirmation" type="password" minlength="8" class="w-full border border-DEFAULT rounded-lg px-3 py-2" required />
            </div>

            <p v-if="passwordMessage" class="text-sm" :class="passwordSuccess ? 'text-success' : 'text-danger'">
              {{ passwordMessage }}
            </p>

            <Button type="submit" variant="outline" :loading="savingPassword">Update Password</Button>
          </form>
        </Card>
      </div>

      <Card :elevation="2" class="p-5">
        <h2 class="text-lg font-semibold text-primary mb-3">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
          <router-link to="/addresses">
            <Button variant="outline" icon="map-marker">Saved Addresses</Button>
          </router-link>
          <router-link to="/orders">
            <Button variant="ghost" icon="package-variant">View Orders</Button>
          </router-link>
          <Button variant="danger" icon="logout" @click="logout">Logout</Button>
        </div>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
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
