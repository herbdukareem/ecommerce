<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Delivery Partners</h1>
        <p class="text-secondary">Manage local dispatch riders and logistics partners.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <h2 class="text-lg font-semibold text-primary mb-3">Add Delivery Partner</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <Input v-model="form.name" label="Name" />
          <Input v-model="form.phone" label="Phone" />
          <Input v-model="form.email" label="Email" />
          <Input v-model="form.company_name" label="Company" />
          <Input v-model="form.vehicle_type" label="Vehicle Type" placeholder="motorbike, van" />
          <Select
            v-model="form.status"
            label="Status"
            :options="[
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]"
          />
          <Input v-model="coverageStates" label="Coverage States" placeholder="Comma separated" class="md:col-span-2" />
          <Input v-model="coverageCities" label="Coverage Cities" placeholder="Comma separated" class="md:col-span-2" />
          <Input v-model="coverageAreas" label="Coverage Areas" placeholder="Comma separated" class="md:col-span-2" />
          <textarea v-model="form.pricing_notes" class="border border-DEFAULT rounded-lg px-3 py-2 md:col-span-2" rows="2" placeholder="Pricing notes"></textarea>
          <textarea v-model="form.notes" class="border border-DEFAULT rounded-lg px-3 py-2 md:col-span-2" rows="2" placeholder="Internal notes"></textarea>
        </div>

        <Button class="mt-4" :loading="saving" @click="savePartner">Save Partner</Button>
      </Card>

      <Card :elevation="2" class="p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-primary">Partners</h2>
          <Select
            v-model="filterStatus"
            :options="[
              { value: '', label: 'All statuses' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]"
            @change="loadPartners"
          />
        </div>

        <div v-if="loading" class="text-secondary">Loading delivery partners...</div>
        <div v-else-if="partners.length === 0" class="text-secondary">No delivery partners available.</div>

        <div v-else class="space-y-3">
          <div v-for="partner in partners" :key="partner.id" class="border border-DEFAULT rounded-lg p-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="font-semibold text-primary">{{ partner.name }} ({{ partner.company_name || 'Individual' }})</p>
                <p class="text-sm text-secondary">{{ partner.phone }}{{ partner.email ? ` | ${partner.email}` : '' }}</p>
                <p class="text-sm text-secondary">States: {{ (partner.coverage_states || []).join(', ') || 'N/A' }}</p>
                <p class="text-sm text-secondary">Cities: {{ (partner.coverage_cities || []).join(', ') || 'N/A' }}</p>
              </div>

              <div class="flex items-center gap-2">
                <Select
                  :model-value="partner.status"
                  :options="[
                    { value: 'active', label: 'Active' },
                    { value: 'inactive', label: 'Inactive' },
                  ]"
                  @update:model-value="(value) => updateStatus(partner.id, value)"
                />
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';

const toast = inject('toast');

const loading = ref(false);
const saving = ref(false);
const partners = ref([]);
const filterStatus = ref('');

const form = ref({
  name: '',
  phone: '',
  email: '',
  company_name: '',
  vehicle_type: '',
  pricing_notes: '',
  notes: '',
  status: 'active',
});

const coverageStates = ref('');
const coverageCities = ref('');
const coverageAreas = ref('');

const splitList = (value) => value
  .split(',')
  .map((item) => item.trim())
  .filter(Boolean);

const loadPartners = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/delivery-partners', {
      params: { status: filterStatus.value || undefined },
    });
    partners.value = data.data || [];
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to load delivery partners');
  } finally {
    loading.value = false;
  }
};

const savePartner = async () => {
  saving.value = true;
  try {
    await axios.post('/api/admin/delivery-partners', {
      ...form.value,
      coverage_states: splitList(coverageStates.value),
      coverage_cities: splitList(coverageCities.value),
      coverage_areas: splitList(coverageAreas.value),
    });

    toast?.success('Delivery partner saved');
    form.value = {
      name: '',
      phone: '',
      email: '',
      company_name: '',
      vehicle_type: '',
      pricing_notes: '',
      notes: '',
      status: 'active',
    };
    coverageStates.value = '';
    coverageCities.value = '';
    coverageAreas.value = '';
    await loadPartners();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to save delivery partner');
  } finally {
    saving.value = false;
  }
};

const updateStatus = async (partnerId, status) => {
  try {
    await axios.patch(`/api/admin/delivery-partners/${partnerId}/status`, { status });
    toast?.success('Partner status updated');
    await loadPartners();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update partner status');
  }
};

onMounted(loadPartners);
</script>
