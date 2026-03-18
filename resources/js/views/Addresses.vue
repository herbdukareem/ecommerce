<template>
  <MainLayout>
    <section class="max-w-5xl mx-auto px-4 py-10 space-y-6">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold text-primary">Saved Addresses</h1>
          <p class="text-sm text-secondary mt-1">Manage your delivery addresses for faster checkout.</p>
        </div>
        <router-link to="/checkout">
          <Button variant="outline">Go to Checkout</Button>
        </router-link>
      </div>

      <Card :elevation="2" class="p-5">
        <div v-if="loading" class="text-secondary">Loading addresses...</div>
        <div v-else-if="errorMessage" class="text-danger">{{ errorMessage }}</div>
        <div v-else-if="addresses.length === 0" class="text-secondary">No saved addresses yet.</div>

        <div v-else class="space-y-3">
          <div
            v-for="address in addresses"
            :key="address.id"
            class="border border-DEFAULT rounded-lg p-4"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="font-semibold text-primary">
                  {{ address.full_name || address.name }} - {{ address.phone }}
                  <span v-if="address.is_default" class="text-xs text-success ml-2">Default</span>
                </p>
                <p class="text-sm text-secondary">
                  {{ address.address_line_1 }}, {{ address.area_or_district }}, {{ address.city }}, {{ address.state }}
                </p>
                <p class="text-sm text-secondary">Landmark: {{ address.landmark }}</p>
              </div>

              <div class="flex gap-2">
                <Button
                  v-if="!address.is_default"
                  variant="outline"
                  size="sm"
                  @click="setDefault(address.id)"
                >
                  Set Default
                </Button>
                <Button variant="danger" size="sm" @click="remove(address.id)">Delete</Button>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </section>
  </MainLayout>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import axios from 'axios';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';

const toast = inject('toast');
const addresses = ref([]);
const loading = ref(false);
const errorMessage = ref('');

const load = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const { data } = await axios.get('/api/addresses');
    addresses.value = data;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Unable to load addresses';
  } finally {
    loading.value = false;
  }
};

const setDefault = async (id) => {
  try {
    await axios.patch(`/api/addresses/${id}/default`);
    toast?.success('Default address updated');
    await load();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update default address');
  }
};

const remove = async (id) => {
  if (!confirm('Delete this address?')) return;

  try {
    await axios.delete(`/api/addresses/${id}`);
    toast?.success('Address deleted');
    await load();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to delete address');
  }
};

onMounted(load);
</script>
