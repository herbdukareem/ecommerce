<template>
  <MainLayout>
    <section class="max-w-md mx-auto px-4 py-12">
      <Card :elevation="2" class="p-6 sm:p-7">
        <h1 class="text-2xl font-bold text-primary mb-2">{{ verificationStep ? 'Verify Your Email' : 'Create Account' }}</h1>
        <p class="text-sm text-secondary">
          {{ verificationStep ? `Enter the six-digit code sent to ${pendingRegistrationEmail || form.email}.` : 'Register to checkout faster and track your orders.' }}
        </p>

        <div
          v-if="statusMessage"
          class="mt-5 rounded-xl border p-4 text-sm"
          :class="statusTone === 'success' ? 'border-green-200 bg-green-50 text-green-800' : statusTone === 'danger' ? 'border-red-200 bg-red-50 text-red-800' : 'border-orange-200 bg-orange-50 text-orange-800'"
          role="status"
          aria-live="polite"
        >
          <div class="flex gap-3">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/80">
              <i v-if="loading || resending" class="mdi mdi-loading mdi-spin"></i>
              <i v-else-if="statusTone === 'success'" class="mdi mdi-check"></i>
              <i v-else-if="statusTone === 'danger'" class="mdi mdi-alert-circle"></i>
              <i v-else class="mdi mdi-email-fast-outline"></i>
            </span>
            <div>
              <p class="font-semibold">{{ statusTitle }}</p>
              <p class="mt-1 leading-6">{{ statusMessage }}</p>
            </div>
          </div>
        </div>

        <form v-if="!verificationStep" class="mt-6 space-y-4" @submit.prevent="handleRegister">
          <div>
            <label class="block text-sm font-medium text-primary mb-1">Full name</label>
            <input v-model="form.name" type="text" class="w-full border border-DEFAULT rounded-lg px-3 py-2" :disabled="loading" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full border border-DEFAULT rounded-lg px-3 py-2" :disabled="loading" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Password</label>
            <input v-model="form.password" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" :disabled="loading" minlength="8" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Confirm password</label>
            <input v-model="form.password_confirmation" type="password" class="w-full border border-DEFAULT rounded-lg px-3 py-2" :disabled="loading" minlength="8" required />
          </div>

          <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>

          <Button type="submit" variant="primary" class="w-full" :loading="loading">
            {{ loading ? 'Creating account...' : 'Create Account' }}
          </Button>
        </form>

        <form v-else class="mt-6 space-y-4" @submit.prevent="handleVerify">
          <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900" role="status" aria-live="polite">
            <div class="flex gap-3">
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-green-700">
                <i class="mdi mdi-check"></i>
              </span>
              <div>
                <p class="font-semibold">Verification code sent</p>
                <p class="mt-1 text-sm leading-6">
                  Enter the six-digit code sent to {{ pendingRegistrationEmail || form.email }} to complete your registration and continue.
                </p>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
            <label class="block text-sm font-medium text-primary mb-2">Verification code</label>
            <input
              ref="codeInput"
              v-model="verificationCode"
              type="text"
              inputmode="numeric"
              autocomplete="one-time-code"
              maxlength="6"
              placeholder="000000"
              class="w-full border border-orange-200 rounded-lg bg-white px-3 py-3 text-center text-2xl font-bold tracking-[0.3em] focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
              :disabled="loading"
              required
            />
            <p class="mt-2 text-xs text-secondary">Check your email inbox for the code. It expires after 15 minutes.</p>
          </div>

          <p v-if="successMessage" class="text-sm text-success">{{ successMessage }}</p>
          <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>

          <Button type="submit" variant="primary" class="w-full" :loading="loading">
            {{ loading ? 'Verifying code...' : 'Verify and Continue' }}
          </Button>
          <Button type="button" variant="outline" class="w-full" :loading="resending" @click="resendCode">Resend Code</Button>
          <button type="button" class="w-full text-sm text-secondary hover:text-primary" @click="editDetails">
            Change registration details
          </button>
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
import { computed, nextTick, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const resending = ref(false);
const verificationStep = ref(false);
const verificationCode = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const pendingRegistrationEmail = ref('');
const codeInput = ref(null);
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const statusTone = computed(() => {
  if (errorMessage.value) return 'danger';
  if (successMessage.value || verificationStep.value) return 'success';
  return 'info';
});

const statusTitle = computed(() => {
  if (errorMessage.value) return 'Something needs attention';
  if (loading.value && !verificationStep.value) return 'Creating your account';
  if (loading.value && verificationStep.value) return 'Checking your code';
  if (resending.value) return 'Sending a new code';
  if (verificationStep.value) return 'Verification code sent';
  return '';
});

const statusMessage = computed(() => {
  if (errorMessage.value) return errorMessage.value;
  if (successMessage.value) return successMessage.value;
  if (loading.value && !verificationStep.value) return 'Please wait while we send a verification code to your email.';
  if (loading.value && verificationStep.value) return 'Please wait while we verify your email code.';
  if (resending.value) return 'Please wait while we send another verification code.';
  if (verificationStep.value) return 'Enter the six-digit code from your email to finish creating your account.';
  return '';
});

const handleRegister = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Password confirmation does not match.';
    return;
  }

  loading.value = true;
  const result = await authStore.register(form);
  loading.value = false;

  if (!result.success) {
    errorMessage.value = result.error?.message || result.error?.email?.[0] || result.error?.password?.[0] || 'Unable to create account.';
    return;
  }

  if (result.verificationRequired) {
    pendingRegistrationEmail.value = result.email || form.email;
    verificationStep.value = true;
    successMessage.value = 'Verification code sent. Enter the six-digit code below to complete your registration.';
    await nextTick();
    codeInput.value?.focus();
    return;
  }

  router.push('/account');
};

const handleVerify = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (!/^\d{6}$/.test(verificationCode.value)) {
    errorMessage.value = 'Enter the 6-digit verification code sent to your email.';
    return;
  }

  loading.value = true;
  const result = await authStore.verifyRegistration({
    email: pendingRegistrationEmail.value || form.email,
    code: verificationCode.value,
  });
  loading.value = false;

  if (!result.success) {
    errorMessage.value = result.error?.message || result.error?.code?.[0] || result.error?.email?.[0] || 'Unable to verify code.';
    return;
  }

  router.push('/account');
};

const resendCode = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  resending.value = true;
  const result = await authStore.resendRegistrationCode(pendingRegistrationEmail.value || form.email);
  resending.value = false;

  if (!result.success) {
    errorMessage.value = result.error?.message || result.error?.email?.[0] || 'Unable to resend code.';
    return;
  }

  successMessage.value = result.message || 'A new verification code has been sent.';
};

const editDetails = () => {
  verificationStep.value = false;
  verificationCode.value = '';
  pendingRegistrationEmail.value = '';
  errorMessage.value = '';
  successMessage.value = '';
};
</script>
