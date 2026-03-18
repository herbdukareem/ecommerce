<template>
  <MainLayout>
    <div class="max-w-5xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-primary mb-6">Checkout</h1>

      <div v-if="loadingInitial" class="text-center py-8 text-secondary">Loading checkout details...</div>
      <div v-else-if="errorMessage" class="text-center py-8 text-danger">{{ errorMessage }}</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <Card :elevation="2" title="Delivery Address" icon="map-marker">
            <div v-if="addressesLoading" class="text-sm text-secondary">Loading your saved addresses...</div>
            <div v-else-if="addressesError" class="text-sm text-danger">{{ addressesError }}</div>
            <div v-else-if="addresses.length === 0" class="text-sm text-secondary mb-4">
              No saved address found. Add one below.
            </div>

            <div v-if="addresses.length" class="space-y-3 mb-4">
              <label
                v-for="address in addresses"
                :key="address.id"
                class="block p-3 border rounded-lg cursor-pointer"
                :class="selectedAddressId === address.id ? 'border-primary bg-primary/5' : 'border-DEFAULT'"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="flex gap-3">
                    <input type="radio" :value="address.id" v-model="selectedAddressId" />
                    <div class="text-sm">
                      <p class="font-semibold text-primary">
                        {{ address.full_name || address.name }} - {{ address.phone }}
                        <span v-if="address.is_default" class="text-xs text-success ml-2">Default</span>
                      </p>
                      <p class="text-secondary">
                        {{ address.address_line_1 }}, {{ address.area_or_district }}, {{ address.city }}, {{ address.state }}
                      </p>
                      <p class="text-secondary">Landmark: {{ address.landmark }}</p>
                    </div>
                  </div>

                  <div class="flex gap-2">
                    <Button variant="ghost" size="sm" @click.prevent="startEditAddress(address)">Edit</Button>
                    <Button
                      v-if="!address.is_default"
                      variant="outline"
                      size="sm"
                      :loading="defaultingAddressId === address.id"
                      @click.prevent="setDefaultAddress(address.id)"
                    >
                      Default
                    </Button>
                    <Button
                      variant="danger"
                      size="sm"
                      :loading="deletingAddressId === address.id"
                      @click.prevent="deleteAddress(address.id)"
                    >
                      Delete
                    </Button>
                  </div>
                </div>
              </label>
            </div>

            <div class="border-t pt-4 mt-4">
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-primary">{{ editingAddressId ? 'Edit Address' : 'Add New Address' }}</h3>
                <Button v-if="editingAddressId" variant="ghost" size="sm" @click="resetAddressForm">Cancel Edit</Button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input v-model="addressForm.full_name" placeholder="Full name" class="border p-2 rounded" />
                <input v-model="addressForm.phone" placeholder="Phone (e.g. 08012345678)" class="border p-2 rounded" />
                <input v-model="addressForm.email" placeholder="Email (optional)" class="border p-2 rounded" />
                <select
                  v-model="addressForm.country_code"
                  class="border p-2 rounded"
                  :disabled="locationsLoading.countries"
                  @change="onCountryChange"
                >
                  <option value="">Select country</option>
                  <option v-for="country in countries" :key="country.code" :value="country.code">
                    {{ country.name }}
                  </option>
                </select>
                <select
                  v-model="addressForm.state_code"
                  class="border p-2 rounded"
                  :disabled="!addressForm.country_code || locationsLoading.states"
                  @change="onStateChange"
                >
                  <option value="">Select state</option>
                  <option v-for="state in states" :key="state.code || state.name" :value="state.code || state.name">
                    {{ state.name }}
                  </option>
                </select>
                <select
                  v-model="addressForm.city"
                  class="border p-2 rounded"
                  :disabled="!addressForm.state_code || locationsLoading.cities"
                >
                  <option value="">Select city</option>
                  <option v-for="city in cities" :key="city.code || city.name" :value="city.name">
                    {{ city.name }}
                  </option>
                </select>
                <div>
                  <input
                    v-model="addressForm.area_or_district"
                    list="area-suggestions"
                    placeholder="Area or district"
                    class="border p-2 rounded w-full"
                  />
                  <datalist id="area-suggestions">
                    <option v-for="suggestion in areaSuggestions" :key="`area-${suggestion.value}`" :value="suggestion.value">
                      {{ suggestion.label }}
                    </option>
                  </datalist>
                </div>
                <div>
                  <input
                    v-model="addressForm.landmark"
                    list="landmark-suggestions"
                    placeholder="Nearest landmark"
                    class="border p-2 rounded w-full"
                  />
                  <datalist id="landmark-suggestions">
                    <option v-for="suggestion in landmarkSuggestions" :key="`landmark-${suggestion.value}`" :value="suggestion.value">
                      {{ suggestion.label }}
                    </option>
                  </datalist>
                </div>
                <input v-model="addressForm.postal_code" placeholder="Postal code (optional)" class="border p-2 rounded" />
                <input v-model="addressForm.address_line_1" placeholder="Address line 1" class="border p-2 rounded md:col-span-2" />
                <input v-model="addressForm.address_line_2" placeholder="Address line 2 (optional)" class="border p-2 rounded md:col-span-2" />
                <textarea
                  v-model="addressForm.delivery_note"
                  placeholder="Delivery note (optional)"
                  class="border p-2 rounded md:col-span-2"
                  rows="2"
                ></textarea>
              </div>

              <div class="mt-3 flex items-center gap-2">
                <input id="default-address" type="checkbox" v-model="addressForm.is_default" />
                <label for="default-address" class="text-sm text-secondary">Set as default address</label>
              </div>

              <Button class="mt-4" variant="outline" :loading="savingAddress" @click="saveAddress">
                {{ editingAddressId ? 'Update Address' : 'Save Address' }}
              </Button>
            </div>
          </Card>

          <Card :elevation="2" title="Delivery Method" icon="truck-fast">
            <Button variant="outline" :loading="quotingShipping" @click="loadShippingQuotes">Get Delivery Options</Button>

            <div v-if="quotes.length" class="mt-4 space-y-2">
              <label
                v-for="quote in quotes"
                :key="quote.method"
                class="flex items-center justify-between p-3 border rounded-lg cursor-pointer"
                :class="shippingMethod === quote.method ? 'border-primary bg-primary/5' : 'border-DEFAULT'"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" :value="quote.method" v-model="shippingMethod" />
                  <span class="text-sm text-primary">{{ quote.name }}</span>
                </div>
                <span class="text-sm font-semibold">{{ formatCurrency(quote.amount) }}</span>
              </label>
            </div>
          </Card>

          <Card :elevation="2" title="Payment Method" icon="credit-card-outline">
            <Select v-model="paymentMethod" :options="paymentOptions" />
          </Card>
        </div>

        <div>
          <Card :elevation="2" title="Order Summary" icon="receipt" class="sticky top-20">
            <div v-if="cartLoading" class="text-secondary text-sm">Loading cart...</div>
            <div v-else>
              <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ formatCurrency(cartSummary.subtotal) }}</span></div>
                <div class="flex justify-between"><span>Discount</span><span>-{{ formatCurrency(cartSummary.discount) }}</span></div>
                <div class="flex justify-between"><span>Delivery Fee</span><span>{{ formatCurrency(selectedShippingAmount) }}</span></div>
                <div class="border-t pt-2 mt-2 flex justify-between font-semibold text-base"><span>Total</span><span>{{ formatCurrency(grandTotal) }}</span></div>
              </div>

              <Button variant="primary" class="w-full" :loading="placingOrder" @click="placeOrder">Place Order</Button>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Select from '../components/ui/Select.vue';
import { useCheckoutStore } from '../stores/checkout';
import { useSettingsStore } from '../stores/settings';

const router = useRouter();
const toast = inject('toast');
const checkoutStore = useCheckoutStore();
const settingsStore = useSettingsStore();

const loadingInitial = ref(false);
const errorMessage = ref('');
const addresses = ref([]);
const addressesLoading = ref(false);
const addressesError = ref('');
const selectedAddressId = ref(null);
const savingAddress = ref(false);
const deletingAddressId = ref(null);
const defaultingAddressId = ref(null);
const editingAddressId = ref(null);

const quotingShipping = ref(false);
const placingOrder = ref(false);
const cartLoading = ref(false);
const quotes = ref([]);
const shippingMethod = ref('');
const paymentMethod = ref('');
const cartSummary = ref({ subtotal: 0, discount: 0, total: 0 });

const countries = ref([]);
const states = ref([]);
const cities = ref([]);
const locationsLoading = ref({
  countries: false,
  states: false,
  cities: false,
});
const areaSuggestions = ref([]);
const landmarkSuggestions = ref([]);

const addressForm = ref(getDefaultAddressForm());

function getDefaultAddressForm() {
  return {
    full_name: '',
    phone: '',
    email: '',
    country: 'Nigeria',
    country_code: '',
    country_name: 'Nigeria',
    state: '',
    state_code: '',
    state_name: '',
    city: '',
    city_name: '',
    area_or_district: '',
    address_line_1: '',
    address_line_2: '',
    landmark: '',
    postal_code: '',
    delivery_note: '',
    is_default: false,
  };
}

const paymentOptions = computed(() => {
  return (checkoutStore.gateways || []).map(gateway => ({
    value: gateway.provider,
    label: gateway.display_name,
  }));
});

const formatCurrency = settingsStore.formatCurrency;

const selectedShippingAmount = computed(() => {
  const selected = quotes.value.find((item) => item.method === shippingMethod.value);
  return Number(selected?.amount || 0);
});

const grandTotal = computed(() => {
  return Math.max(0, Number(cartSummary.value.total || 0) + selectedShippingAmount.value);
});

const loadCartSummary = async () => {
  cartLoading.value = true;
  try {
    const { data } = await axios.get('/api/cart');
    cartSummary.value = {
      subtotal: Number(data.subtotal || 0),
      discount: Number(data.discount || 0),
      total: Number(data.total || 0),
    };
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to load cart';
  } finally {
    cartLoading.value = false;
  }
};

const loadAddresses = async () => {
  addressesLoading.value = true;
  addressesError.value = '';

  try {
    const { data } = await axios.get('/api/addresses');
    addresses.value = data;
    const defaultAddress = data.find((address) => address.is_default);
    selectedAddressId.value = defaultAddress?.id || data[0]?.id || null;
  } catch (error) {
    addressesError.value = error.response?.data?.message || 'Unable to load your addresses';
  } finally {
    addressesLoading.value = false;
  }
};

const resetAddressForm = () => {
  editingAddressId.value = null;
  addressForm.value = getDefaultAddressForm();
  states.value = [];
  cities.value = [];
  areaSuggestions.value = [];
  landmarkSuggestions.value = [];
};

const getCountryByCode = (code) => countries.value.find((item) => item.code === code);
const getCountryByName = (name) => countries.value.find((item) => item.name?.toLowerCase() === String(name || '').toLowerCase());
const getStateByCode = (code) => states.value.find((item) => (item.code || item.name) === code);
const getStateByName = (name) => states.value.find((item) => item.name?.toLowerCase() === String(name || '').toLowerCase());

const fetchCountries = async () => {
  locationsLoading.value.countries = true;
  try {
    const { data } = await axios.get('/api/locations/countries');
    countries.value = data.countries || [];

    if (!addressForm.value.country_code) {
      const preferred = getCountryByName('Nigeria') || countries.value[0];
      if (preferred) {
        addressForm.value.country_code = preferred.code;
        addressForm.value.country = preferred.name;
        addressForm.value.country_name = preferred.name;
        await fetchStates(preferred.code);
      }
    }
  } catch (error) {
    console.error('Failed to load countries:', error);
  } finally {
    locationsLoading.value.countries = false;
  }
};

const fetchStates = async (countryCode) => {
  if (!countryCode) {
    states.value = [];
    return;
  }

  locationsLoading.value.states = true;
  try {
    const { data } = await axios.get('/api/locations/states', { params: { country: countryCode } });
    states.value = data.states || [];
  } catch (error) {
    states.value = [];
  } finally {
    locationsLoading.value.states = false;
  }
};

const fetchCities = async (countryCode, stateCode) => {
  if (!countryCode || !stateCode) {
    cities.value = [];
    return;
  }

  locationsLoading.value.cities = true;
  try {
    const { data } = await axios.get('/api/locations/cities', {
      params: {
        country: countryCode,
        state: stateCode,
      },
    });
    cities.value = data.cities || [];
  } catch (error) {
    cities.value = [];
  } finally {
    locationsLoading.value.cities = false;
  }
};

const onCountryChange = async () => {
  const selectedCountry = getCountryByCode(addressForm.value.country_code);
  addressForm.value.country = selectedCountry?.name || '';
  addressForm.value.country_name = selectedCountry?.name || '';
  addressForm.value.state = '';
  addressForm.value.state_code = '';
  addressForm.value.state_name = '';
  addressForm.value.city = '';
  addressForm.value.city_name = '';

  await fetchStates(addressForm.value.country_code);
};

const onStateChange = async () => {
  const selectedState = getStateByCode(addressForm.value.state_code);
  addressForm.value.state = selectedState?.name || '';
  addressForm.value.state_name = selectedState?.name || '';
  addressForm.value.city = '';
  addressForm.value.city_name = '';

  await fetchCities(addressForm.value.country_code, addressForm.value.state_code);
};

const fetchAutocomplete = async (type, query) => {
  if (!query || query.length < 2) {
    return [];
  }

  try {
    const { data } = await axios.get('/api/locations/autocomplete', {
      params: {
        type,
        query,
        country_code: addressForm.value.country_code || null,
        country_name: addressForm.value.country_name || null,
        state_name: addressForm.value.state_name || addressForm.value.state || null,
        city_name: addressForm.value.city_name || addressForm.value.city || null,
      },
    });

    return data.suggestions || [];
  } catch (error) {
    return [];
  }
};

const debounce = (fn, delay = 350) => {
  let timer = null;
  return (...args) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
};

const loadAreaSuggestions = debounce(async (query) => {
  areaSuggestions.value = await fetchAutocomplete('area_or_district', query);
});

const loadLandmarkSuggestions = debounce(async (query) => {
  landmarkSuggestions.value = await fetchAutocomplete('landmark', query);
});

const startEditAddress = async (address) => {
  editingAddressId.value = address.id;

  const countryName = address.country_name || address.country || 'Nigeria';
  const country = getCountryByName(countryName);
  const countryCode = address.country_code || country?.code || '';

  await fetchStates(countryCode);

  const stateName = address.state_name || address.state || '';
  const state = getStateByName(stateName);
  const stateCode = address.state_code || state?.code || state?.name || '';

  await fetchCities(countryCode, stateCode);

  addressForm.value = {
    full_name: address.full_name || address.name || '',
    phone: address.phone || '',
    email: address.email || '',
    country: countryName,
    country_code: countryCode,
    country_name: countryName,
    state: stateName,
    state_code: stateCode,
    state_name: stateName,
    city: address.city_name || address.city || '',
    city_name: address.city_name || address.city || '',
    area_or_district: address.area_or_district || '',
    address_line_1: address.address_line_1 || '',
    address_line_2: address.address_line_2 || '',
    landmark: address.landmark || '',
    postal_code: address.postal_code || '',
    delivery_note: address.delivery_note || '',
    is_default: !!address.is_default,
  };
};

const saveAddress = async () => {
  savingAddress.value = true;
  try {
    const selectedCountry = getCountryByCode(addressForm.value.country_code);
    const selectedState = getStateByCode(addressForm.value.state_code) || getStateByName(addressForm.value.state_name || addressForm.value.state);

    const payload = {
      ...addressForm.value,
      country_code: selectedCountry?.code || null,
      country_name: selectedCountry?.name || addressForm.value.country_name,
      state_code: selectedState?.code || null,
      state_name: selectedState?.name || addressForm.value.state_name || addressForm.value.state,
      city_name: addressForm.value.city_name || addressForm.value.city,
      country: addressForm.value.country_name || addressForm.value.country,
      state: addressForm.value.state_name || addressForm.value.state,
      city: addressForm.value.city_name || addressForm.value.city,
    };

    if (editingAddressId.value) {
      await axios.put(`/api/addresses/${editingAddressId.value}`, payload);
      toast?.success('Address updated');
    } else {
      await axios.post('/api/addresses', payload);
      toast?.success('Address added');
    }

    await loadAddresses();
    resetAddressForm();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const validation = errors ? Object.values(errors).flat().join(' ') : null;
    toast?.error(validation || error.response?.data?.message || 'Unable to save address');
  } finally {
    savingAddress.value = false;
  }
};

const deleteAddress = async (id) => {
  if (!confirm('Delete this address?')) return;

  deletingAddressId.value = id;
  try {
    await axios.delete(`/api/addresses/${id}`);
    toast?.success('Address deleted');
    await loadAddresses();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to delete address');
  } finally {
    deletingAddressId.value = null;
  }
};

const setDefaultAddress = async (id) => {
  defaultingAddressId.value = id;
  try {
    await axios.patch(`/api/addresses/${id}/default`);
    await loadAddresses();
    toast?.success('Default address updated');
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update default address');
  } finally {
    defaultingAddressId.value = null;
  }
};

const loadShippingQuotes = async () => {
  if (!selectedAddressId.value) {
    toast?.error('Select a delivery address first');
    return;
  }

  quotingShipping.value = true;
  try {
    const response = await checkoutStore.quoteShipping({ address_id: selectedAddressId.value });
    if (!response.success) {
      throw new Error(response.error || 'Quote fetch failed');
    }

    quotes.value = checkoutStore.quotes;
    shippingMethod.value = quotes.value[0]?.method || '';
  } catch (error) {
    toast?.error(error.message || 'Failed to load delivery options');
  } finally {
    quotingShipping.value = false;
  }
};

const placeOrder = async () => {
  if (!selectedAddressId.value) {
    toast?.error('Select a delivery address');
    return;
  }
  if (!shippingMethod.value) {
    toast?.error('Select a delivery method');
    return;
  }
  if (!paymentMethod.value) {
    toast?.error('Select a payment gateway');
    return;
  }

  placingOrder.value = true;
  try {
    const result = await checkoutStore.placeOrder(selectedAddressId.value, paymentMethod.value, shippingMethod.value);
    if (!result.success) {
      throw new Error(result.error || 'Order placement failed');
    }

    toast?.success('Order placed successfully');
    router.push(`/orders/${result.order.id}`);
  } catch (error) {
    toast?.error(error.message || 'Failed to place order');
  } finally {
    placingOrder.value = false;
  }
};

onMounted(async () => {
  loadingInitial.value = true;
  errorMessage.value = '';

  try {
    const [gatewayResponse] = await Promise.all([
      checkoutStore.fetchPaymentGateways(),
      fetchCountries(),
      loadAddresses(),
      loadCartSummary(),
    ]);

    if (!gatewayResponse.success || (checkoutStore.gateways || []).length === 0) {
      throw new Error(gatewayResponse.error || 'No payment gateway available right now.');
    }

    paymentMethod.value = checkoutStore.gateways[0].provider;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Failed to initialize checkout';
  } finally {
    loadingInitial.value = false;
  }
});

watch(
  () => addressForm.value.city,
  (value) => {
    addressForm.value.city_name = value;
  }
);

watch(
  () => addressForm.value.area_or_district,
  (value) => {
    loadAreaSuggestions(value || '');
  }
);

watch(
  () => addressForm.value.landmark,
  (value) => {
    loadLandmarkSuggestions(value || '');
  }
);
</script>
