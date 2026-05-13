<template>
  <MainLayout>
    <section class="max-w-5xl mx-auto px-4 py-10 space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Referrals</h1>
        <p class="text-sm text-secondary mt-1">Invite customers and track your rewards.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div v-if="store.loading" class="py-8 text-secondary">Loading referrals...</div>
        <div v-else class="space-y-5">
          <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-DEFAULT p-4">
              <p class="text-xs text-secondary">Code</p>
              <p class="mt-1 text-xl font-bold text-primary">{{ summary?.code?.code || 'N/A' }}</p>
            </div>
            <div class="rounded-lg border border-DEFAULT p-4">
              <p class="text-xs text-secondary">Invites</p>
              <p class="mt-1 text-xl font-bold text-primary">{{ summary?.stats?.referrals || 0 }}</p>
            </div>
            <div class="rounded-lg border border-DEFAULT p-4">
              <p class="text-xs text-secondary">Pending</p>
              <p class="mt-1 text-xl font-bold text-primary">{{ formatCurrency(summary?.stats?.pending_rewards || 0) }}</p>
            </div>
            <div class="rounded-lg border border-DEFAULT p-4">
              <p class="text-xs text-secondary">Wallet</p>
              <p class="mt-1 text-xl font-bold text-primary">{{ formatCurrency(summary?.stats?.wallet_balance || 0) }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-primary mb-1">Invite link</label>
            <div class="flex flex-col gap-3 md:flex-row">
              <input :value="inviteLink" readonly class="w-full rounded-lg border border-DEFAULT px-3 py-2 text-sm focus:outline-none" />
              <div class="flex gap-2">
                <Button variant="primary" icon="content-copy" :disabled="!inviteLink" @click="copyInviteLink">
                  Copy
                </Button>
                <Button variant="outline" icon="share-variant" :disabled="!inviteLink" @click="shareInviteLink">
                  Share
                </Button>
              </div>
            </div>
            <p v-if="actionMessage" class="mt-2 text-sm" :class="actionSuccess ? 'text-success' : 'text-danger'">
              {{ actionMessage }}
            </p>
          </div>
        </div>
      </Card>

      <Card :elevation="2" class="p-5">
        <div class="flex items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-semibold text-primary">Reward History</h2>
            <p class="text-sm text-secondary">Track pending, approved, and paid referral rewards.</p>
          </div>
          <Button variant="ghost" icon="refresh" size="sm" @click="loadRewards">Refresh</Button>
        </div>

        <div v-if="store.rewards.length === 0" class="rounded-lg border border-DEFAULT p-4 text-sm text-secondary">
          No referral rewards yet.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-DEFAULT text-left text-secondary">
                <th class="py-2 pr-4 font-medium">Order</th>
                <th class="py-2 pr-4 font-medium">Amount</th>
                <th class="py-2 pr-4 font-medium">Status</th>
                <th class="py-2 pr-4 font-medium">Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reward in store.rewards" :key="reward.id" class="border-b border-DEFAULT last:border-0">
                <td class="py-3 pr-4 text-primary">{{ reward.order?.order_number || `#${reward.order_id}` }}</td>
                <td class="py-3 pr-4 font-semibold text-primary">{{ formatCurrency(reward.amount || 0) }}</td>
                <td class="py-3 pr-4">
                  <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="rewardStatusClass(reward.status)">
                    {{ reward.status || 'pending' }}
                  </span>
                </td>
                <td class="py-3 pr-4 text-secondary">{{ formatDate(reward.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import { useReferralStore } from '../stores/referrals';
import { useSettingsStore } from '../stores/settings';

const store = useReferralStore();
const settingsStore = useSettingsStore();
const toast = inject('toast');
const summary = computed(() => store.summary);
const formatCurrency = settingsStore.formatCurrency;
const actionMessage = ref('');
const actionSuccess = ref(false);

const inviteLink = computed(() => {
  if (summary.value?.invite_url) {
    return summary.value.invite_url;
  }

  const code = summary.value?.code?.code;
  return code ? `${window.location.origin}/register?ref=${encodeURIComponent(code)}` : '';
});

const writeToClipboard = async (text) => {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text);
    return;
  }

  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.setAttribute('readonly', '');
  textarea.style.position = 'fixed';
  textarea.style.left = '-9999px';
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  document.body.removeChild(textarea);
};

const copyInviteLink = async () => {
  actionMessage.value = '';

  try {
    await writeToClipboard(inviteLink.value);
    actionSuccess.value = true;
    actionMessage.value = 'Referral link copied to clipboard.';
    toast?.success(actionMessage.value);
  } catch (error) {
    actionSuccess.value = false;
    actionMessage.value = 'Unable to copy referral link.';
    toast?.error(actionMessage.value);
  }
};

const shareInviteLink = async () => {
  actionMessage.value = '';
  const text = 'Join me here and shop with my referral link.';

  try {
    if (navigator.share) {
      await navigator.share({
        title: 'My referral link',
        text,
        url: inviteLink.value,
      });
      actionSuccess.value = true;
      actionMessage.value = 'Referral link shared.';
      return;
    }

    await writeToClipboard(inviteLink.value);
    actionSuccess.value = true;
    actionMessage.value = 'Sharing is not available on this browser, so the link was copied instead.';
    toast?.success('Referral link copied.');
  } catch (error) {
    if (error?.name === 'AbortError') {
      return;
    }

    actionSuccess.value = false;
    actionMessage.value = 'Unable to share referral link.';
    toast?.error(actionMessage.value);
  }
};

const rewardStatusClass = (status) => {
  const classes = {
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    redeemed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    reversed: 'bg-red-100 text-red-700',
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Intl.DateTimeFormat('en-NG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date));
};

const loadRewards = () => {
  store.loadRewards();
};

onMounted(() => {
  store.loadSummary();
  store.loadRewards();
});
</script>
