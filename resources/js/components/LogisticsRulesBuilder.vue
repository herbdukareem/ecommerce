<template>
  <div class="space-y-6">
    <Card :elevation="2" class="p-5">
      <h3 class="text-lg font-semibold text-primary mb-4">Shipping Zones</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <Input v-model="newZone.name" label="Zone Name" placeholder="e.g. Lagos Metro" />
        <Input v-model="newZone.region" label="Region" placeholder="e.g. Lagos" />
        <Input v-model="newZone.default_fee" label="Default Fee" type="number" placeholder="e.g. 2500" />
        <Input v-model="newZone.coverage_states" label="Coverage States" placeholder="lagos, ogun" class="md:col-span-3" />
        <Input v-model="newZone.coverage_cities" label="Coverage Cities" placeholder="ikeja, lekki" class="md:col-span-3" />
        <Input v-model="newZone.coverage_areas" label="Coverage Areas" placeholder="allen, admiralty" class="md:col-span-3" />
        <Input v-model="newZone.description" label="Description" placeholder="Optional" class="md:col-span-3" />
      </div>

      <div class="flex items-center gap-4 mb-4">
        <label class="text-sm text-secondary flex items-center gap-2">
          <input type="checkbox" v-model="newZone.is_fallback" /> Fallback Zone
        </label>
        <label class="text-sm text-secondary flex items-center gap-2">
          <input type="checkbox" v-model="newZone.active" /> Active
        </label>
        <Button variant="primary" :disabled="savingZone" @click="createZone">{{ savingZone ? 'Saving...' : 'Create Zone' }}</Button>
      </div>

      <div v-if="zonesLoading" class="text-secondary">Loading zones...</div>
      <div v-else-if="!zones.length" class="text-secondary">No shipping zones configured yet.</div>

      <div v-else class="space-y-3">
        <div v-for="zone in zones" :key="zone.id" class="border border-DEFAULT rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <Input v-model="zone.name" label="Zone Name" />
            <Input v-model="zone.region" label="Region" />
            <Input v-model="zone.default_fee" label="Default Fee" type="number" />
            <Input v-model="zone._coverage_states" label="Coverage States" placeholder="comma separated" class="md:col-span-3" />
            <Input v-model="zone._coverage_cities" label="Coverage Cities" placeholder="comma separated" class="md:col-span-3" />
            <Input v-model="zone._coverage_areas" label="Coverage Areas" placeholder="comma separated" class="md:col-span-3" />
            <Input v-model="zone.description" label="Description" class="md:col-span-3" />
          </div>

          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-4">
              <label class="text-sm text-secondary flex items-center gap-2">
                <input type="checkbox" v-model="zone.is_fallback" /> Fallback
              </label>
              <label class="text-sm text-secondary flex items-center gap-2">
                <input type="checkbox" v-model="zone.active" /> Active
              </label>
            </div>

            <div class="flex items-center gap-2">
              <Button variant="ghost" @click="selectZone(zone.id)">Rules</Button>
              <Button variant="outline" :disabled="savingZone" @click="updateZone(zone)">Save</Button>
              <Button variant="danger" icon="delete" icon-only :disabled="savingZone" @click="deleteZone(zone.id)" />
            </div>
          </div>
        </div>
      </div>
    </Card>

    <Card :elevation="2" class="p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-primary">Zone Rules</h3>
        <span class="text-sm text-secondary" v-if="selectedZoneId">Zone #{{ selectedZoneId }}</span>
      </div>

      <div v-if="!selectedZoneId" class="text-secondary">Select a zone to manage rules.</div>

      <template v-else>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
          <Select v-model="newRule.shipping_method_id" label="Shipping Method" :options="methodOptions" />
          <Select v-model="newRule.rule_type" label="Rule Type" :options="ruleTypeOptions" />
          <Input v-model="newRule.priority" label="Priority" type="number" placeholder="100" />
          <Input v-model="newRule.config.rate" label="Rate" type="number" placeholder="0" />
          <Input v-model="newRule.config.per_kg" label="Per KG" type="number" placeholder="0" />
          <Input v-model="newRule.config.min" label="Minimum" type="number" placeholder="0" />
          <Input v-model="newRule.config.min_order" label="Free Shipping Threshold" type="number" placeholder="0" />
          <div class="flex items-end">
            <Button variant="primary" class="w-full" :disabled="savingRule" @click="createRule">
              {{ savingRule ? 'Saving...' : 'Add Rule' }}
            </Button>
          </div>
        </div>

        <div v-if="rulesLoading" class="text-secondary">Loading rules...</div>
        <div v-else-if="!rules.length" class="text-secondary">No rules configured for this zone.</div>

        <div v-else class="space-y-3">
          <div v-for="rule in rules" :key="rule.id" class="border border-DEFAULT rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <Select v-model="rule.shipping_method_id" label="Shipping Method" :options="methodOptions" />
              <Select v-model="rule.rule_type" label="Rule Type" :options="ruleTypeOptions" />
              <Input v-model="rule.priority" label="Priority" type="number" />
              <Input v-model="rule.config.rate" label="Rate" type="number" />
              <Input v-model="rule.config.per_kg" label="Per KG" type="number" />
              <Input v-model="rule.config.min" label="Minimum" type="number" />
              <Input v-model="rule.config.min_order" label="Threshold" type="number" />
              <div class="flex items-end gap-2">
                <Button variant="outline" class="flex-1" :disabled="savingRule" @click="updateRule(rule)">Save</Button>
                <Button variant="danger" icon="delete" icon-only :disabled="savingRule" @click="deleteRule(rule.id)" />
              </div>
            </div>
            <label class="text-sm text-secondary flex items-center gap-2 mt-2">
              <input type="checkbox" v-model="rule.active" /> Active
            </label>
          </div>
        </div>
      </template>
    </Card>

    <Card :elevation="2" class="p-5">
      <h3 class="text-lg font-semibold text-primary mb-4">Shipping Methods</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <Input v-model="newMethod.name" label="Method Name" placeholder="e.g. Express" />
        <Input v-model="newMethod.code" label="Method Code" placeholder="e.g. express" />
        <Input v-model="newMethod.base_fee" label="Base Fee" type="number" />
        <Input v-model="newMethod.per_kg_surcharge" label="Per KG Surcharge" type="number" />
        <Input v-model="newMethod.express_surcharge" label="Express Surcharge" type="number" />
        <Input v-model="newMethod.free_shipping_threshold" label="Free Shipping Threshold" type="number" />
        <div class="md:col-span-3">
          <Input v-model="newMethod.description" label="Description" />
        </div>
      </div>

      <div class="flex items-center gap-4 mb-4">
        <label class="text-sm text-secondary flex items-center gap-2">
          <input type="checkbox" v-model="newMethod.supports_cod" /> Supports COD
        </label>
        <label class="text-sm text-secondary flex items-center gap-2">
          <input type="checkbox" v-model="newMethod.is_pickup" /> Pickup Method
        </label>
        <label class="text-sm text-secondary flex items-center gap-2">
          <input type="checkbox" v-model="newMethod.active" /> Active
        </label>
        <Button variant="primary" :disabled="savingMethod" @click="createMethod">
          {{ savingMethod ? 'Saving...' : 'Create Method' }}
        </Button>
      </div>

      <div v-if="methodsLoading" class="text-secondary">Loading methods...</div>
      <div v-else-if="!methods.length" class="text-secondary">No shipping methods found.</div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="method in methods" :key="method.id" class="border border-DEFAULT rounded-lg p-3">
          <p class="font-medium text-primary">{{ method.name }} ({{ method.code }})</p>
          <p class="text-sm text-secondary">Base Fee: {{ method.base_fee }}</p>
          <p class="text-sm text-secondary">COD: {{ method.supports_cod ? 'Yes' : 'No' }} | Pickup: {{ method.is_pickup ? 'Yes' : 'No' }}</p>
        </div>
      </div>
    </Card>

    <p v-if="feedback" class="text-sm text-success">{{ feedback }}</p>
    <p v-if="error" class="text-sm text-danger">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import Card from './ui/Card.vue';
import Button from './ui/Button.vue';
import Input from './ui/Input.vue';
import Select from './ui/Select.vue';

const zones = ref([]);
const rules = ref([]);
const methods = ref([]);
const selectedZoneId = ref(null);

const zonesLoading = ref(false);
const rulesLoading = ref(false);
const methodsLoading = ref(false);
const savingZone = ref(false);
const savingRule = ref(false);
const savingMethod = ref(false);

const feedback = ref('');
const error = ref('');

const newZone = ref({
  name: '',
  region: '',
  coverage_states: '',
  coverage_cities: '',
  coverage_areas: '',
  default_fee: '',
  description: '',
  is_fallback: false,
  active: true,
});

const newRule = ref({
  shipping_method_id: '',
  rule_type: 'flat',
  priority: 100,
  active: true,
  config: {
    rate: '',
    per_kg: '',
    min: '',
    min_order: '',
  },
});

const newMethod = ref({
  name: '',
  code: '',
  description: '',
  base_fee: 0,
  per_kg_surcharge: 0,
  express_surcharge: 0,
  free_shipping_threshold: '',
  supports_cod: false,
  is_pickup: false,
  active: true,
});

const ruleTypeOptions = [
  { value: 'flat', label: 'Flat' },
  { value: 'weight_based', label: 'Weight Based' },
  { value: 'price_based', label: 'Price Based' },
  { value: 'free', label: 'Free' },
];

const methodOptions = computed(() => methods.value.map((method) => ({
  value: method.id,
  label: `${method.name} (${method.code})`,
})));

const splitCsv = (value) => String(value || '')
  .split(',')
  .map((item) => item.trim().toLowerCase())
  .filter(Boolean);

const setError = (message) => {
  error.value = message;
  if (message) feedback.value = '';
};

const setFeedback = (message) => {
  feedback.value = message;
  if (message) error.value = '';
};

const normalizeZone = (zone) => ({
  ...zone,
  _coverage_states: (zone.coverage_states || []).join(', '),
  _coverage_cities: (zone.coverage_cities || []).join(', '),
  _coverage_areas: (zone.coverage_areas || []).join(', '),
});

const normalizeRule = (rule) => ({
  ...rule,
  config: {
    rate: rule?.config?.rate ?? 0,
    per_kg: rule?.config?.per_kg ?? 0,
    min: rule?.config?.min ?? 0,
    min_order: rule?.config?.min_order ?? 0,
    method: rule?.config?.method ?? '',
  },
});

const toRulePayload = (rule) => ({
  shipping_method_id: rule.shipping_method_id || null,
  rule_type: rule.rule_type,
  priority: Number(rule.priority || 100),
  active: !!rule.active,
  config: {
    rate: Number(rule.config.rate || 0),
    per_kg: Number(rule.config.per_kg || 0),
    min: Number(rule.config.min || 0),
    min_order: Number(rule.config.min_order || 0),
  },
});

const fetchZones = async () => {
  zonesLoading.value = true;
  try {
    const { data } = await axios.get('/api/admin/shipping/zones', { params: { per_page: 100 } });
    const records = Array.isArray(data?.data) ? data.data : [];
    zones.value = records.map(normalizeZone);
    if (!selectedZoneId.value && zones.value.length) {
      await selectZone(zones.value[0].id);
    }
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to load shipping zones.');
  } finally {
    zonesLoading.value = false;
  }
};

const fetchMethods = async () => {
  methodsLoading.value = true;
  try {
    const { data } = await axios.get('/api/admin/shipping/methods');
    methods.value = Array.isArray(data) ? data : [];
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to load shipping methods.');
  } finally {
    methodsLoading.value = false;
  }
};

const selectZone = async (zoneId) => {
  selectedZoneId.value = zoneId;
  rulesLoading.value = true;
  try {
    const { data } = await axios.get(`/api/admin/shipping/zones/${zoneId}/rules`);
    rules.value = Array.isArray(data) ? data.map(normalizeRule) : [];
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to load zone rules.');
  } finally {
    rulesLoading.value = false;
  }
};

const createZone = async () => {
  if (!newZone.value.name || !newZone.value.region) {
    setError('Zone name and region are required.');
    return;
  }

  savingZone.value = true;
  try {
    await axios.post('/api/admin/shipping/zones', {
      name: newZone.value.name,
      region: newZone.value.region,
      coverage_states: splitCsv(newZone.value.coverage_states),
      coverage_cities: splitCsv(newZone.value.coverage_cities),
      coverage_areas: splitCsv(newZone.value.coverage_areas),
      default_fee: Number(newZone.value.default_fee || 0),
      description: newZone.value.description || null,
      is_fallback: !!newZone.value.is_fallback,
      active: !!newZone.value.active,
    });

    newZone.value = {
      name: '',
      region: '',
      coverage_states: '',
      coverage_cities: '',
      coverage_areas: '',
      default_fee: '',
      description: '',
      is_fallback: false,
      active: true,
    };
    await fetchZones();
    setFeedback('Shipping zone created successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to create shipping zone.');
  } finally {
    savingZone.value = false;
  }
};

const updateZone = async (zone) => {
  savingZone.value = true;
  try {
    await axios.put(`/api/admin/shipping/zones/${zone.id}`, {
      name: zone.name,
      region: zone.region,
      coverage_states: splitCsv(zone._coverage_states),
      coverage_cities: splitCsv(zone._coverage_cities),
      coverage_areas: splitCsv(zone._coverage_areas),
      default_fee: Number(zone.default_fee || 0),
      description: zone.description || null,
      is_fallback: !!zone.is_fallback,
      active: !!zone.active,
    });

    setFeedback('Shipping zone updated successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to update shipping zone.');
  } finally {
    savingZone.value = false;
  }
};

const deleteZone = async (zoneId) => {
  if (!confirm('Delete this shipping zone?')) return;

  savingZone.value = true;
  try {
    await axios.delete(`/api/admin/shipping/zones/${zoneId}`);
    if (selectedZoneId.value === zoneId) {
      selectedZoneId.value = null;
      rules.value = [];
    }
    await fetchZones();
    setFeedback('Shipping zone deleted successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to delete shipping zone.');
  } finally {
    savingZone.value = false;
  }
};

const createRule = async () => {
  if (!selectedZoneId.value) {
    setError('Select a zone first.');
    return;
  }

  if (!newRule.value.shipping_method_id) {
    setError('Select a shipping method for this rule.');
    return;
  }

  savingRule.value = true;
  try {
    await axios.post('/api/admin/shipping/rules', {
      shipping_zone_id: selectedZoneId.value,
      ...toRulePayload(newRule.value),
    });

    newRule.value = {
      shipping_method_id: '',
      rule_type: 'flat',
      priority: 100,
      active: true,
      config: { rate: '', per_kg: '', min: '', min_order: '' },
    };

    await selectZone(selectedZoneId.value);
    setFeedback('Shipping rule created successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to create shipping rule.');
  } finally {
    savingRule.value = false;
  }
};

const updateRule = async (rule) => {
  savingRule.value = true;
  try {
    await axios.put(`/api/admin/shipping/rules/${rule.id}`, toRulePayload(rule));
    setFeedback('Shipping rule updated successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to update shipping rule.');
  } finally {
    savingRule.value = false;
  }
};

const deleteRule = async (ruleId) => {
  if (!confirm('Delete this shipping rule?')) return;

  savingRule.value = true;
  try {
    await axios.delete(`/api/admin/shipping/rules/${ruleId}`);
    if (selectedZoneId.value) {
      await selectZone(selectedZoneId.value);
    }
    setFeedback('Shipping rule deleted successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to delete shipping rule.');
  } finally {
    savingRule.value = false;
  }
};

const createMethod = async () => {
  if (!newMethod.value.name) {
    setError('Method name is required.');
    return;
  }

  savingMethod.value = true;
  try {
    await axios.post('/api/admin/shipping/methods', {
      ...newMethod.value,
      base_fee: Number(newMethod.value.base_fee || 0),
      per_kg_surcharge: Number(newMethod.value.per_kg_surcharge || 0),
      express_surcharge: Number(newMethod.value.express_surcharge || 0),
      free_shipping_threshold: newMethod.value.free_shipping_threshold === '' ? null : Number(newMethod.value.free_shipping_threshold),
      active: !!newMethod.value.active,
      supports_cod: !!newMethod.value.supports_cod,
      is_pickup: !!newMethod.value.is_pickup,
    });

    newMethod.value = {
      name: '',
      code: '',
      description: '',
      base_fee: 0,
      per_kg_surcharge: 0,
      express_surcharge: 0,
      free_shipping_threshold: '',
      supports_cod: false,
      is_pickup: false,
      active: true,
    };

    await fetchMethods();
    setFeedback('Shipping method created successfully.');
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to create shipping method.');
  } finally {
    savingMethod.value = false;
  }
};

onMounted(async () => {
  await Promise.all([fetchZones(), fetchMethods()]);
});
</script>
