<template>
  <AdminLayout>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Shipping Zones</h1>
        <p class="text-secondary">Manage delivery zones and shipping rates</p>
      </div>
      <Button variant="primary" icon="plus" @click="showAddZone = true">Add Zone</Button>
    </div>

    <!-- Zones List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card
        v-for="(zone, index) in zones"
        :key="zone.id"
        :elevation="2"
        hoverable
        animation="fade-in-up"
        :class="`stagger-${index + 1}`"
      >
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary-dark/20 flex items-center justify-center">
                <i class="mdi mdi-map-marker-radius text-2xl text-primary"></i>
              </div>
              <div>
                <h3 class="font-semibold text-primary">{{ zone.name }}</h3>
                <p class="text-xs text-secondary">{{ zone.regions.length }} regions</p>
              </div>
            </div>
            <Badge :variant="zone.active ? 'success' : 'secondary'">
              {{ zone.active ? 'Active' : 'Inactive' }}
            </Badge>
          </div>
        </template>

        <!-- Regions -->
        <div class="mb-4">
          <p class="text-sm font-medium text-primary mb-2">Covered Regions:</p>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="region in zone.regions"
              :key="region"
              variant="outline"
              size="sm"
            >
              {{ region }}
            </Badge>
          </div>
        </div>

        <!-- Shipping Methods -->
        <div class="mb-4">
          <p class="text-sm font-medium text-primary mb-3">Shipping Methods:</p>
          <div class="space-y-2">
            <div
              v-for="method in zone.methods"
              :key="method.id"
              class="flex items-center justify-between p-3 bg-base rounded-lg"
            >
              <div class="flex items-center gap-3">
                <i :class="`mdi mdi-${method.icon} text-primary`"></i>
                <div>
                  <p class="text-sm font-medium text-primary">{{ method.name }}</p>
                  <p class="text-xs text-secondary">{{ method.duration }}</p>
                </div>
              </div>
              <p class="text-sm font-semibold text-primary">{{ formatCurrency(method.cost) }}</p>
            </div>
          </div>
        </div>

        <template #actions>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" icon="pencil" class="flex-1">Edit</Button>
            <Button variant="outline" size="sm" icon="cog" class="flex-1">Configure</Button>
            <Button variant="danger" size="sm" icon="delete" icon-only />
          </div>
        </template>
      </Card>
    </div>

    <!-- Add Zone Modal -->
    <Modal v-model="showAddZone" title="Add Shipping Zone" icon="map-marker-radius" size="xl">
      <div class="space-y-6">
        <!-- Basic Info -->
        <div>
          <h3 class="text-lg font-semibold text-primary mb-4">Basic Information</h3>
          <div class="space-y-4">
            <Input
              v-model="newZone.name"
              label="Zone Name"
              placeholder="e.g., Lagos Metro"
              required
            />
            <div class="grid grid-cols-2 gap-4">
              <Select
                v-model="newZone.country"
                label="Country"
                :options="countryOptions"
                required
              />
              <Select
                v-model="newZone.status"
                label="Status"
                :options="[{ value: true, label: 'Active' }, { value: false, label: 'Inactive' }]"
              />
            </div>
          </div>
        </div>

        <!-- Regions -->
        <div>
          <h3 class="text-lg font-semibold text-primary mb-4">Regions</h3>
          <div class="space-y-3">
            <div
              v-for="(region, index) in newZone.regions"
              :key="index"
              class="flex gap-2"
            >
              <Input
                v-model="newZone.regions[index]"
                placeholder="Enter region name"
                class="flex-1"
              />
              <Button
                variant="danger"
                icon="delete"
                icon-only
                @click="newZone.regions.splice(index, 1)"
              />
            </div>
            <Button variant="outline" icon="plus" size="sm" @click="newZone.regions.push('')">
              Add Region
            </Button>
          </div>
        </div>

        <!-- Shipping Methods -->
        <div>
          <h3 class="text-lg font-semibold text-primary mb-4">Shipping Methods</h3>
          <div class="space-y-4">
            <Card
              v-for="(method, index) in newZone.methods"
              :key="index"
              :elevation="1"
              class="bg-base"
            >
              <div class="grid grid-cols-2 gap-4">
                <Input
                  v-model="method.name"
                  label="Method Name"
                  placeholder="e.g., Standard Delivery"
                />
                <Input
                  v-model="method.duration"
                  label="Delivery Time"
                  placeholder="e.g., 2-3 days"
                />
                <Input
                  v-model="method.cost"
                  type="number"
                  label="Cost"
                  placeholder="0"
                />
                <Select
                  v-model="method.icon"
                  label="Icon"
                  :options="iconOptions"
                />
              </div>
              <div class="mt-4 flex justify-end">
                <Button
                  variant="danger"
                  size="sm"
                  icon="delete"
                  @click="newZone.methods.splice(index, 1)"
                >
                  Remove Method
                </Button>
              </div>
            </Card>
            <Button
              variant="outline"
              icon="plus"
              @click="newZone.methods.push({ name: '', duration: '', cost: 0, icon: 'truck-fast' })"
            >
              Add Shipping Method
            </Button>
          </div>
        </div>
      </div>

      <template #actions>
        <Button variant="ghost" @click="showAddZone = false">Cancel</Button>
        <Button variant="primary" icon="check">Create Zone</Button>
      </template>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';
import Badge from '../../components/ui/Badge.vue';
import Modal from '../../components/ui/Modal.vue';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();
const { formatCurrency } = settingsStore;

const showAddZone = ref(false);

const zones = ref([
  {
    id: 1,
    name: 'Lagos Metro',
    regions: ['Lagos Island', 'Victoria Island', 'Lekki', 'Ikoyi', 'Ajah'],
    active: true,
    methods: [
      { id: 1, name: 'Express Delivery', duration: 'Same day', cost: 2500, icon: 'rocket' },
      { id: 2, name: 'Standard Delivery', duration: '1-2 days', cost: 1500, icon: 'truck-fast' },
    ],
  },
  {
    id: 2,
    name: 'Lagos Mainland',
    regions: ['Ikeja', 'Surulere', 'Yaba', 'Maryland', 'Gbagada'],
    active: true,
    methods: [
      { id: 3, name: 'Standard Delivery', duration: '1-2 days', cost: 1800, icon: 'truck-fast' },
      { id: 4, name: 'Economy Delivery', duration: '3-5 days', cost: 1000, icon: 'truck' },
    ],
  },
  {
    id: 3,
    name: 'Abuja',
    regions: ['Wuse', 'Maitama', 'Garki', 'Asokoro', 'Gwarinpa'],
    active: true,
    methods: [
      { id: 5, name: 'Express Delivery', duration: '1-2 days', cost: 3000, icon: 'rocket' },
      { id: 6, name: 'Standard Delivery', duration: '2-4 days', cost: 2000, icon: 'truck-fast' },
    ],
  },
  {
    id: 4,
    name: 'Port Harcourt',
    regions: ['GRA', 'Trans Amadi', 'Rumuola', 'Eliozu'],
    active: false,
    methods: [
      { id: 7, name: 'Standard Delivery', duration: '3-5 days', cost: 2500, icon: 'truck-fast' },
    ],
  },
]);

const newZone = ref({
  name: '',
  country: 'NG',
  status: true,
  regions: [''],
  methods: [{ name: '', duration: '', cost: 0, icon: 'truck-fast' }],
});

const countryOptions = [
  { value: 'NG', label: 'Nigeria' },
  { value: 'GH', label: 'Ghana' },
  { value: 'KE', label: 'Kenya' },
];

const iconOptions = [
  { value: 'truck-fast', label: 'Fast Truck' },
  { value: 'truck', label: 'Truck' },
  { value: 'rocket', label: 'Rocket' },
  { value: 'bike', label: 'Bike' },
  { value: 'airplane', label: 'Airplane' },
];
</script>

