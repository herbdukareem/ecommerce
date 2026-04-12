<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Areas of Operation</h1>
        <p class="text-secondary">Manage city-specific delivery areas and fees.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Select v-model="form.city_id" label="City" :options="cityOptions" />
          <Input v-model="form.name" label="Area Name" />
          <Input v-model="form.delivery_fee" label="Delivery Fee" type="number" />
          <Select v-model="form.status" label="Status" :options="statusOptions" />
          <Input v-model="form.sort_order" label="Sort" type="number" />
          <Input v-model="form.notes" label="Notes" />
        </div>
        <div class="mt-4 flex gap-2">
          <Button :loading="saving" @click="saveArea">{{ form.id ? 'Update' : 'Create' }} Area</Button>
          <Button v-if="form.id" variant="outline" @click="resetForm">Cancel</Button>
        </div>
      </Card>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">City</th>
              <th class="text-left p-3">Area</th>
              <th class="text-left p-3">Fee</th>
              <th class="text-left p-3">Status</th>
              <th class="text-right p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="area in areas" :key="area.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ area.city?.name }}</td>
              <td class="p-3">{{ area.name }}</td>
              <td class="p-3">{{ area.delivery_fee || 0 }}</td>
              <td class="p-3">{{ area.status }}</td>
              <td class="p-3 text-right space-x-2">
                <Button size="sm" variant="outline" @click="editArea(area)">Edit</Button>
                <Button size="sm" variant="danger" @click="deleteArea(area.id)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
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
const areas = ref([]);
const cities = ref([]);
const saving = ref(false);

const form = reactive({
  id: null,
  city_id: '',
  name: '',
  delivery_fee: 0,
  status: 'active',
  sort_order: 0,
  notes: '',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const cityOptions = computed(() => [
  { value: '', label: 'Select city' },
  ...cities.value.map((city) => ({ value: city.id, label: city.name })),
]);

const loadCities = async () => {
  const { data } = await axios.get('/api/admin/operation-cities', { params: { per_page: 100 } });
  cities.value = data.data || [];
};

const loadAreas = async () => {
  const { data } = await axios.get('/api/admin/operation-areas', { params: { per_page: 100 } });
  areas.value = data.data || [];
};

const resetForm = () => {
  form.id = null;
  form.city_id = '';
  form.name = '';
  form.delivery_fee = 0;
  form.status = 'active';
  form.sort_order = 0;
  form.notes = '';
};

const editArea = (area) => {
  form.id = area.id;
  form.city_id = area.city_id;
  form.name = area.name;
  form.delivery_fee = area.delivery_fee || 0;
  form.status = area.status;
  form.sort_order = area.sort_order || 0;
  form.notes = area.notes || '';
};

const saveArea = async () => {
  saving.value = true;

  try {
    const payload = {
      city_id: Number(form.city_id),
      name: form.name,
      delivery_fee: Number(form.delivery_fee || 0),
      status: form.status,
      sort_order: Number(form.sort_order || 0),
      notes: form.notes || null,
    };

    if (form.id) {
      await axios.put(`/api/admin/operation-areas/${form.id}`, payload);
      toast?.success('Area updated.');
    } else {
      await axios.post('/api/admin/operation-areas', payload);
      toast?.success('Area created.');
    }

    resetForm();
    await loadAreas();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to save area.');
  } finally {
    saving.value = false;
  }
};

const deleteArea = async (id) => {
  if (!confirm('Delete this area?')) return;

  await axios.delete(`/api/admin/operation-areas/${id}`);
  toast?.success('Area deleted.');
  await loadAreas();
};

onMounted(async () => {
  await Promise.all([loadCities(), loadAreas()]);
});
</script>
