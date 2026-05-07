<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Dispatch Riders</h1>
        <p class="text-secondary">Manage rider accounts, availability, vehicles, and partner assignments.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <h2 class="text-lg font-semibold text-primary mb-3">Create Dispatch Rider</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <Input v-model="form.name" label="Full Name" />
          <Input v-model="form.email" label="Email" type="email" />
          <Input v-model="form.phone" label="Phone" />
          <Input v-model="form.password" label="Password" type="password" />
          <Input v-model="form.password_confirmation" label="Confirm Password" type="password" />
          <Select v-model="form.delivery_partner_id" label="Delivery Partner" :options="partnerOptions" />
          <Select v-model="form.vehicle_type" label="Vehicle Type" :options="vehicleTypeOptions" />
          <Input v-model="form.vehicle_plate_number" label="Plate Number" />
          <Select v-model="form.availability_status" label="Availability" :options="availabilityOptions" />
          <Select v-model="form.status" label="Account Status" :options="statusOptions" />
          <Input label="Profile Picture" type="file" accept="image/*" @change="onFileChange('profile_picture', $event)" />
          <Input label="Vehicle Image" type="file" accept="image/*" @change="onFileChange('vehicle_image', $event)" />
        </div>
        <Button class="mt-4" :loading="saving" @click="saveRider">Save Rider</Button>
      </Card>

      <Card :elevation="2" class="p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
          <h2 class="text-lg font-semibold text-primary">Riders</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <Input v-model="filters.q" placeholder="Search riders" @keyup.enter="loadRiders" />
            <Select v-model="filters.status" :options="[{ value: '', label: 'All statuses' }, ...statusOptions]" @change="loadRiders" />
            <Button variant="outline" @click="loadRiders">Search</Button>
          </div>
        </div>

        <div v-if="loading" class="text-secondary">Loading dispatch riders...</div>
        <div v-else-if="riders.length === 0" class="text-secondary">No dispatch riders available.</div>
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div v-for="rider in riders" :key="rider.id" class="border border-DEFAULT rounded-lg p-4 flex gap-3">
            <img
              :src="rider.profile_photo_url || '/images/placeholders/product-placeholder.svg'"
              :alt="rider.user?.name || 'Rider'"
              class="h-16 w-16 rounded-lg object-cover border border-DEFAULT"
            />
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="font-semibold text-primary">{{ rider.user?.name }}</p>
                  <p class="text-sm text-secondary">{{ rider.phone || rider.user?.phone || 'No phone' }} | {{ rider.user?.email }}</p>
                </div>
                <Select
                  :model-value="rider.status"
                  :options="statusOptions"
                  @update:model-value="(value) => updateStatus(rider.id, value)"
                />
              </div>
              <p class="text-sm text-secondary mt-2">Partner: {{ rider.delivery_partner?.name || 'Independent' }}</p>
              <p class="text-sm text-secondary">Vehicle: {{ humanize(rider.vehicle_type) }} {{ rider.vehicle_plate_number ? `(${rider.vehicle_plate_number})` : '' }}</p>
              <p class="text-sm text-secondary">Availability: {{ humanize(rider.availability_status) }}</p>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';

const toast = inject('toast');

const riders = ref([]);
const partners = ref([]);
const loading = ref(false);
const saving = ref(false);
const files = ref({ profile_picture: null, vehicle_image: null });

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  delivery_partner_id: '',
  vehicle_type: '',
  vehicle_plate_number: '',
  availability_status: 'available',
  status: 'active',
});

const filters = reactive({
  q: '',
  status: '',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const availabilityOptions = [
  { value: 'available', label: 'Available' },
  { value: 'unavailable', label: 'Unavailable' },
  { value: 'on_delivery', label: 'On Delivery' },
];

const vehicleTypeOptions = [
  { value: '', label: 'Select vehicle type' },
  { value: 'motorbike', label: 'Motorbike' },
  { value: 'bicycle', label: 'Bicycle' },
  { value: 'tricycle', label: 'Tricycle' },
  { value: 'car', label: 'Car' },
  { value: 'van', label: 'Van' },
  { value: 'truck', label: 'Truck' },
];

const partnerOptions = computed(() => [
  { value: '', label: 'Independent rider' },
  ...partners.value.map((partner) => ({ value: partner.id, label: `${partner.name} (${partner.status})` })),
]);

const humanize = (value) => String(value || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const onFileChange = (key, event) => {
  files.value[key] = event.target.files?.[0] || null;
};

const loadRiders = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/dispatch-riders', {
      params: {
        q: filters.q || undefined,
        status: filters.status || undefined,
        per_page: 100,
      },
    });
    riders.value = data.data || [];
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to load dispatch riders');
  } finally {
    loading.value = false;
  }
};

const loadPartners = async () => {
  const { data } = await axios.get('/api/admin/delivery-partners', { params: { per_page: 100 } });
  partners.value = data.data || [];
};

const saveRider = async () => {
  saving.value = true;
  try {
    const payload = new FormData();
    Object.entries(form).forEach(([key, value]) => payload.append(key, value ?? ''));
    if (files.value.profile_picture) payload.append('profile_picture', files.value.profile_picture);
    if (files.value.vehicle_image) payload.append('vehicle_image', files.value.vehicle_image);

    await axios.post('/api/admin/dispatch-riders', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    toast?.success('Dispatch rider created');
    Object.assign(form, {
      name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
      delivery_partner_id: '',
      vehicle_type: '',
      vehicle_plate_number: '',
      availability_status: 'available',
      status: 'active',
    });
    files.value = { profile_picture: null, vehicle_image: null };
    await loadRiders();
  } catch (error) {
    const errors = error.response?.data?.errors;
    const message = errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message;
    toast?.error(message || 'Unable to save dispatch rider');
  } finally {
    saving.value = false;
  }
};

const updateStatus = async (riderId, status) => {
  try {
    await axios.patch(`/api/admin/dispatch-riders/${riderId}/status`, { status });
    toast?.success('Rider status updated');
    await loadRiders();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update rider status');
  }
};

onMounted(async () => {
  await Promise.all([loadRiders(), loadPartners()]);
});
</script>
