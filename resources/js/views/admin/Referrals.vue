<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Referrals</h1>
        <p class="text-sm text-gray-600 mt-1">Manage referral settings, relationships, and rewards.</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <form class="bg-white rounded-lg border border-gray-200 p-5 space-y-4" @submit.prevent="saveSettings">
          <h2 class="font-semibold text-gray-900">Settings</h2>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="settings.referral_enabled" type="checkbox" class="rounded border-gray-300" />
            Enable referrals
          </label>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Reward basis</label>
            <select v-model="settings.referral_reward_basis" class="w-full rounded-lg border border-gray-300 px-3 py-2">
              <option value="fixed">Fixed</option>
              <option value="percentage">Percentage</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Reward value</label>
            <input v-model.number="settings.referral_reward_value" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Trigger</label>
            <select v-model="settings.referral_trigger" class="w-full rounded-lg border border-gray-300 px-3 py-2">
              <option value="paid_order">Paid order</option>
              <option value="delivered_order">Delivered order</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Approval</label>
            <select v-model="settings.referral_approval_mode" class="w-full rounded-lg border border-gray-300 px-3 py-2">
              <option value="manual">Manual</option>
              <option value="automatic">Automatic</option>
            </select>
          </div>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="settings.referral_wallet_redemption_enabled" type="checkbox" class="rounded border-gray-300" />
            Allow wallet redemption
          </label>
          <button class="rounded-lg bg-primary px-4 py-2 text-white" type="submit">Save Settings</button>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-5 py-4 font-semibold text-gray-900">Rewards</div>
          <div v-if="loading" class="p-5 text-gray-500">Loading...</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th class="px-4 py-3">Referrer</th>
                <th class="px-4 py-3">Referred</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reward in rewards" :key="reward.id" class="border-t border-gray-100">
                <td class="px-4 py-3">{{ reward.referral?.referrer?.name || reward.referrer_id }}</td>
                <td class="px-4 py-3">{{ reward.referral?.referred_user?.name || reward.referred_user_id }}</td>
                <td class="px-4 py-3">{{ formatCurrency(reward.amount) }}</td>
                <td class="px-4 py-3">{{ reward.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();
const formatCurrency = settingsStore.formatCurrency;
const loading = ref(false);
const rewards = ref([]);
const settings = reactive({
  referral_enabled: false,
  referral_reward_basis: 'fixed',
  referral_reward_value: 0,
  referral_trigger: 'paid_order',
  referral_approval_mode: 'manual',
  referral_wallet_redemption_enabled: false,
});

const load = async () => {
  loading.value = true;
  try {
    const [settingsRes, rewardsRes] = await Promise.all([
      axios.get('/api/admin/referral-settings'),
      axios.get('/api/admin/referral-rewards'),
    ]);
    Object.assign(settings, settingsRes.data || {});
    rewards.value = rewardsRes.data.data || [];
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  const { data } = await axios.put('/api/admin/referral-settings', settings);
  Object.assign(settings, data || {});
};

onMounted(load);
</script>
