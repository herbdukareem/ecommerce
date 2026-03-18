<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Payment Gateways</h1>
          <p class="text-sm text-gray-600 mt-1">Manage gateway behavior from admin while credentials stay in env.</p>
        </div>
        <button
          @click="saveOrder"
          class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          Save Sort Order
        </button>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-5">
        <p class="text-sm text-gray-700">Default Gateway: <strong>{{ defaultGatewayLabel }}</strong></p>
      </div>

      <div class="space-y-4">
        <div v-for="gateway in gateways" :key="gateway.provider" class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ gateway.display_name }}</h2>
              <p class="text-sm text-gray-600">{{ gateway.description || 'No description provided.' }}</p>
              <p class="text-xs mt-2" :class="gateway.readiness.is_configured ? 'text-green-600' : 'text-red-600'">
                {{ gateway.readiness.is_configured ? 'Configured' : ('Missing: ' + gateway.readiness.missing_requirements.join(', ')) }}
              </p>
            </div>

            <div class="flex items-center gap-3">
              <button
                :disabled="!gateway.readiness.is_configured || !gateway.is_enabled"
                @click="setDefault(gateway)"
                class="px-3 py-2 rounded border"
                :class="gateway.is_default ? 'bg-green-100 border-green-300 text-green-700' : 'bg-white border-gray-300 text-gray-700 disabled:opacity-40'"
              >
                {{ gateway.is_default ? 'Default' : 'Set Default' }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
              <input v-model="gateway.display_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
              <input v-model.number="gateway.sort_order" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea v-model="gateway.description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
              <select v-model="gateway.mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="sandbox">Sandbox</option>
                <option value="live">Live</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Supported Currencies (comma-separated)</label>
              <input
                :value="(gateway.supported_currencies || []).join(',')"
                @input="onCurrenciesInput(gateway, $event.target.value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              />
            </div>

            <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
              <span class="text-sm text-gray-700">Enabled</span>
              <input type="checkbox" v-model="gateway.is_enabled" />
            </div>

            <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
              <span class="text-sm text-gray-700">Visible at Checkout</span>
              <input type="checkbox" v-model="gateway.is_visible" />
            </div>
          </div>

          <div class="mt-4 flex justify-end">
            <button
              @click="saveGateway(gateway)"
              class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
            >
              Save {{ gateway.display_name }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';

const toast = inject('toast');
const gateways = ref([]);

const defaultGatewayLabel = computed(() => {
  const gateway = gateways.value.find(item => item.is_default);
  return gateway ? gateway.display_name : 'Not Set';
});

const loadGateways = async () => {
  const { data } = await axios.get('/api/admin/payment-gateways');
  gateways.value = data.gateways || [];
};

const onCurrenciesInput = (gateway, raw) => {
  gateway.supported_currencies = raw
    .split(',')
    .map(value => value.trim().toUpperCase())
    .filter(Boolean);
};

const saveGateway = async (gateway) => {
  try {
    const payload = {
      display_name: gateway.display_name,
      description: gateway.description,
      is_enabled: !!gateway.is_enabled,
      is_visible: !!gateway.is_visible,
      sort_order: gateway.sort_order,
      mode: gateway.mode,
      supported_currencies: gateway.supported_currencies || ['NGN'],
      fee_type: gateway.fee_type,
      fee_value: gateway.fee_value || 0,
      extra_config: gateway.extra_config || {},
    };

    await axios.put(`/api/admin/payment-gateways/${gateway.provider}`, payload);
    toast?.success('Gateway saved successfully');
    await loadGateways();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to save gateway');
  }
};

const setDefault = async (gateway) => {
  try {
    await axios.post(`/api/admin/payment-gateways/${gateway.provider}/set-default`);
    toast?.success('Default gateway updated');
    await loadGateways();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to set default gateway');
  }
};

const saveOrder = async () => {
  try {
    await axios.post('/api/admin/payment-gateways/reorder', {
      gateways: gateways.value.map(item => ({
        provider: item.provider,
        sort_order: item.sort_order,
      })),
    });
    toast?.success('Sort order saved');
    await loadGateways();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to save order');
  }
};

onMounted(loadGateways);
</script>
