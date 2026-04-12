<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-primary">Dispatch Time Slots</h1>
        <p class="text-secondary">Manage checkout delivery windows.</p>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <Input v-model="form.label" label="Label" class="md:col-span-2" />
          <Input v-model="form.start_time" label="Start" type="time" />
          <Input v-model="form.end_time" label="End" type="time" />
          <Select v-model="form.status" label="Status" :options="statusOptions" />
          <Input v-model="form.sort_order" label="Sort" type="number" />
          <Input v-model="form.description" label="Description" class="md:col-span-4" />
        </div>
        <div class="mt-4 flex gap-2">
          <Button :loading="saving" @click="saveSlot">{{ form.id ? 'Update' : 'Create' }} Slot</Button>
          <Button v-if="form.id" variant="outline" @click="resetForm">Cancel</Button>
        </div>
      </Card>

      <Card :elevation="2" class="p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-base text-secondary">
            <tr>
              <th class="text-left p-3">Label</th>
              <th class="text-left p-3">Time</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3">Sort</th>
              <th class="text-right p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="slot in slots" :key="slot.id" class="border-t border-DEFAULT">
              <td class="p-3">{{ slot.label }}</td>
              <td class="p-3">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</td>
              <td class="p-3">{{ slot.status }}</td>
              <td class="p-3">{{ slot.sort_order }}</td>
              <td class="p-3 text-right space-x-2">
                <Button size="sm" variant="outline" @click="editSlot(slot)">Edit</Button>
                <Button size="sm" variant="danger" @click="deleteSlot(slot.id)">Delete</Button>
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
const slots = ref([]);
const saving = ref(false);

const form = reactive({
  id: null,
  label: '',
  start_time: '',
  end_time: '',
  status: 'active',
  sort_order: 0,
  description: '',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const loadSlots = async () => {
  const { data } = await axios.get('/api/admin/dispatch-time-slots', { params: { per_page: 100 } });
  slots.value = data.data || [];
};

const resetForm = () => {
  form.id = null;
  form.label = '';
  form.start_time = '';
  form.end_time = '';
  form.status = 'active';
  form.sort_order = 0;
  form.description = '';
};

const editSlot = (slot) => {
  form.id = slot.id;
  form.label = slot.label;
  form.start_time = slot.start_time;
  form.end_time = slot.end_time;
  form.status = slot.status;
  form.sort_order = slot.sort_order || 0;
  form.description = slot.description || '';
};

const saveSlot = async () => {
  saving.value = true;

  try {
    const payload = {
      label: form.label,
      start_time: form.start_time,
      end_time: form.end_time,
      status: form.status,
      sort_order: Number(form.sort_order || 0),
      description: form.description || null,
    };

    if (form.id) {
      await axios.put(`/api/admin/dispatch-time-slots/${form.id}`, payload);
      toast?.success('Dispatch slot updated.');
    } else {
      await axios.post('/api/admin/dispatch-time-slots', payload);
      toast?.success('Dispatch slot created.');
    }

    resetForm();
    await loadSlots();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to save dispatch slot.');
  } finally {
    saving.value = false;
  }
};

const deleteSlot = async (id) => {
  if (!confirm('Delete this dispatch slot?')) return;

  await axios.delete(`/api/admin/dispatch-time-slots/${id}`);
  toast?.success('Dispatch slot deleted.');
  await loadSlots();
};

const formatTime = (value) => {
  if (!value) return '';
  const [h, m] = String(value).split(':');
  const hour = Number(h);
  const suffix = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${m} ${suffix}`;
};

onMounted(loadSlots);
</script>
