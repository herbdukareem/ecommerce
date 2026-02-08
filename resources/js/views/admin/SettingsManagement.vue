<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-600 mt-1">Manage your store settings and configuration</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="{
              'border-orange-600 text-orange-600': activeTab === tab.id,
              'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== tab.id,
            }"
            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
          >
            <i :class="tab.icon" class="mr-2"></i>
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- General Settings -->
      <div v-if="activeTab === 'general'" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h2>
        <form @submit.prevent="saveSettings" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
            <input
              v-model="settings.site_name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
            <textarea
              v-model="settings.site_description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
            <input
              v-model="settings.site_email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
            <input
              v-model.number="settings.tax_rate"
              type="number"
              step="0.01"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
          </div>

          <div class="flex justify-end pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50"
            >
              <i v-if="saving" class="mdi mdi-loading mdi-spin mr-2"></i>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Currency Settings -->
      <div v-if="activeTab === 'currency'" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Currency Settings</h2>
        <form @submit.prevent="saveSettings" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
            <select
              v-model="settings.currency"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option value="NGN">Nigerian Naira (NGN)</option>
              <option value="USD">US Dollar (USD)</option>
              <option value="EUR">Euro (EUR)</option>
              <option value="GBP">British Pound (GBP)</option>
              <option value="ZAR">South African Rand (ZAR)</option>
              <option value="KES">Kenyan Shilling (KES)</option>
              <option value="GHS">Ghanaian Cedi (GHS)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
            <input
              v-model="settings.currency_symbol"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
            <p class="text-xs text-gray-500 mt-1">Symbol displayed before prices (e.g., ₦, $, €)</p>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <i class="mdi mdi-information text-blue-600 mr-2 mt-0.5"></i>
              <div class="text-sm text-blue-800">
                <p class="font-medium">Preview</p>
                <p class="mt-1">Price will be displayed as: <strong>{{ settings.currency_symbol }}1,000.00</strong></p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="settings.paystack_enabled"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
              </label>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Public Key</label>
                <input
                  v-model="settings.paystack_public_key"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="pk_test_..."
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                <input
                  v-model="settings.paystack_secret_key"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="sk_test_..."
                />
              </div>
            </div>
          </div>

          <!-- Flutterwave -->
          <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <i class="mdi mdi-wallet text-orange-600 text-2xl"></i>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">Flutterwave</h3>
                  <p class="text-xs text-gray-500">Accept payments via Flutterwave</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="settings.flutterwave_enabled"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
              </label>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Public Key</label>
                <input
                  v-model="settings.flutterwave_public_key"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="FLWPUBK_TEST-..."
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                <input
                  v-model="settings.flutterwave_secret_key"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="FLWSECK_TEST-..."
                />
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50"
            >
              <i v-if="saving" class="mdi mdi-loading mdi-spin mr-2"></i>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Features Settings -->
      <div v-if="activeTab === 'features'" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Feature Toggles</h2>
        <form @submit.prevent="saveSettings" class="space-y-4">
          <div class="flex items-center justify-between py-3 border-b border-gray-200">
            <div>
              <h3 class="font-medium text-gray-900">Product Reviews</h3>
              <p class="text-sm text-gray-500">Allow customers to review products</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="settings.enable_reviews"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between py-3 border-b border-gray-200">
            <div>
              <h3 class="font-medium text-gray-900">Wishlist</h3>
              <p class="text-sm text-gray-500">Enable wishlist functionality</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="settings.enable_wishlist"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between py-3 border-b border-gray-200">
            <div>
              <h3 class="font-medium text-gray-900">Coupons & Discounts</h3>
              <p class="text-sm text-gray-500">Enable coupon code functionality</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="settings.enable_coupons"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
            </label>
          </div>

          <div class="flex justify-end pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50"
            >
              <i v-if="saving" class="mdi mdi-loading mdi-spin mr-2"></i>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';

const activeTab = ref('general');
const saving = ref(false);

const tabs = [
  { id: 'general', label: 'General', icon: 'mdi mdi-cog' },
  { id: 'currency', label: 'Currency', icon: 'mdi mdi-currency-usd' },
  { id: 'payment', label: 'Payment', icon: 'mdi mdi-credit-card' },
  { id: 'features', label: 'Features', icon: 'mdi mdi-toggle-switch' },
];

const settings = ref({
  site_name: '',
  site_description: '',
  site_email: '',
  currency: 'NGN',
  currency_symbol: '₦',
  tax_rate: 7.5,
  enable_reviews: true,
  enable_wishlist: true,
  enable_coupons: true,
  paystack_enabled: false,
  paystack_public_key: '',
  paystack_secret_key: '',
  flutterwave_enabled: false,
  flutterwave_public_key: '',
  flutterwave_secret_key: '',
});

// Fetch settings
const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/admin/settings');

    // Convert settings array to object
    data.forEach(setting => {
      const value = setting.value;
      // Parse boolean values
      if (value === 'true') settings.value[setting.key] = true;
      else if (value === 'false') settings.value[setting.key] = false;
      // Parse numeric values
      else if (!isNaN(value) && value !== '') settings.value[setting.key] = parseFloat(value);
      else settings.value[setting.key] = value;
    });
  } catch (error) {
    console.error('Failed to fetch settings:', error);
  }
};

// Save settings
const saveSettings = async () => {
  saving.value = true;
  try {
    await axios.put('/api/admin/settings', settings.value);
    alert('Settings saved successfully!');
  } catch (error) {
    console.error('Failed to save settings:', error);
    alert('Failed to save settings. Please try again.');
  } finally {
    saving.value = false;
  }
};

// Initialize
onMounted(() => {
  fetchSettings();
});
</script>
