<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Cities of Operation</h1>
        <p class="text-secondary">Manage active cities available at checkout.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <Input v-model="form.name" label="City Name" />
          <Input v-model="form.code" label="Code or Slug" />
          <Select v-model="form.status" label="Status" :options="statusOptions" />
          <Input v-model="form.sort_order" label="Sort" type="number" />
        </div>
        <div class="mt-4 flex gap-2">
          <Button :loading="saving" @click="saveCity">{{ form.id ? 'Update' : 'Create' }} City</Button>
          <Button v-if="form.id" variant="outline" @click="resetForm">Cancel</Button>
        </div>
      </Card>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Name</th>
              <th class="text-left p-3">Code</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3">Areas</th>
              <th class="text-right p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="city in cities" :key="city.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ city.name }}</td>
              <td class="p-3">{{ city.code }}</td>
              <td class="p-3">{{ city.status }}</td>
              <td class="p-3">{{ city.areas_count || 0 }}</td>
              <td class="p-3 text-right space-x-2">
                <Button size="sm" variant="outline" @click="editCity(city)">Edit</Button>
                <Button size="sm" variant="danger" @click="deleteCity(city.id)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { inject, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';

const toast = inject('toast');
const cities = ref([]);
const saving = ref(false);

const form = reactive({
  id: null,
  name: '',
  code: '',
  status: 'active',
  sort_order: 0,
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const loadCities = async () => {
  const { data } = await axios.get('/api/admin/operation-cities', { params: { per_page: 100 } });
  cities.value = data.data || [];
};

const resetForm = () => {
  form.id = null;
  form.name = '';
  form.code = '';
  form.status = 'active';
  form.sort_order = 0;
};

const editCity = (city) => {
  form.id = city.id;
  form.name = city.name;
  form.code = city.code;
  form.status = city.status;
  form.sort_order = city.sort_order || 0;
};

const saveCity = async () => {
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      code: form.code || null,
      status: form.status,
      sort_order: Number(form.sort_order || 0),
    };

    if (form.id) {
      await axios.put(`/api/admin/operation-cities/${form.id}`, payload);
      toast?.success('City updated.');
    } else {
      await axios.post('/api/admin/operation-cities', payload);
      toast?.success('City created.');
    }

    resetForm();
    await loadCities();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to save city.');
  } finally {
    saving.value = false;
  }
};

const deleteCity = async (id) => {
  if (!confirm('Delete this city and its areas?')) return;
  await axios.delete(`/api/admin/operation-cities/${id}`);
  toast?.success('City deleted.');
  await loadCities();
};

onMounted(loadCities);
</script>
