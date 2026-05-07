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
          <Select v-model="form.vehicle_type" label="Vehicle Type" :options="vehicleTypeOptions" />
          <Select
            v-model="form.status"
            label="Status"
            :options="[
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]"
          />
          <Select v-model="coverageCity" label="Coverage City" :options="cityOptions" class="md:col-span-1" />
          <Select v-model="coverageArea" label="Coverage Area" :options="areaOptions" class="md:col-span-1" />
          <Input label="Contact/Dispatch Picture" type="file" accept="image/*" @change="onFileChange('contact_photo', $event)" />
          <Input label="Vehicle Image" type="file" accept="image/*" @change="onFileChange('vehicle_image', $event)" />
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
                <p class="text-sm text-secondary">Cities: {{ (partner.coverage_cities || []).join(', ') || 'N/A' }}</p>
                <p class="text-sm text-secondary">Areas: {{ (partner.coverage_areas || []).join(', ') || 'N/A' }}</p>
                <p class="text-sm text-secondary">Vehicle: {{ humanize(partner.vehicle_type) }}</p>
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
import { computed, inject, onMounted, ref, watch } from 'vue';
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
const cities = ref([]);
const areas = ref([]);
const coverageCity = ref('');
const coverageArea = ref('');
const files = ref({
  contact_photo: null,
  vehicle_image: null,
});

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

const vehicleTypeOptions = [
  { value: '', label: 'Select vehicle type' },
  { value: 'motorbike', label: 'Motorbike' },
  { value: 'bicycle', label: 'Bicycle' },
  { value: 'tricycle', label: 'Tricycle' },
  { value: 'car', label: 'Car' },
  { value: 'van', label: 'Van' },
  { value: 'truck', label: 'Truck' },
];

const cityOptions = computed(() => [
  { value: '', label: 'Select city' },
  ...cities.value.map((city) => ({ value: city.name, label: city.name, id: city.id })),
]);

const areaOptions = computed(() => [
  { value: '', label: 'Select area' },
  ...areas.value.map((area) => ({ value: area.name, label: area.name })),
]);

const humanize = (value) => String(value || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const onFileChange = (key, event) => {
  files.value[key] = event.target.files?.[0] || null;
};

const loadCities = async () => {
  const { data } = await axios.get('/api/checkout/cities');
  cities.value = data.cities || [];
};

const loadAreas = async () => {
  coverageArea.value = '';
  const city = cities.value.find((entry) => entry.name === coverageCity.value);
  if (!city) {
    areas.value = [];
    return;
  }
  const { data } = await axios.get('/api/checkout/areas', { params: { city_id: city.id } });
  areas.value = data.areas || [];
};

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
    const payload = new FormData();
    Object.entries(form.value).forEach(([key, value]) => payload.append(key, value ?? ''));
    payload.append('coverage_cities[0]', coverageCity.value || '');
    payload.append('coverage_areas[0]', coverageArea.value || '');
    if (files.value.contact_photo) payload.append('contact_photo', files.value.contact_photo);
    if (files.value.vehicle_image) payload.append('vehicle_image', files.value.vehicle_image);

    await axios.post('/api/admin/delivery-partners', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
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
    coverageCity.value = '';
    coverageArea.value = '';
    files.value = { contact_photo: null, vehicle_image: null };
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

watch(coverageCity, loadAreas);

onMounted(async () => {
  await Promise.all([loadPartners(), loadCities()]);
});
</script>
