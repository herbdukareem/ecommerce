<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-primary mb-2">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    
    <div class="relative">
      <div v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none z-10">
        <i :class="`mdi mdi-${icon} text-lg`"></i>
      </div>
      
      <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        @change="handleChange"
        class="w-full px-4 py-2.5 rounded-lg border transition-all duration-300 bg-surface text-primary focus:outline-none focus:ring-2 appearance-none cursor-pointer"
        :class="[
          icon ? 'pl-11' : '',
          error ? 'border-danger focus:ring-danger/50' : 'border-DEFAULT focus:ring-primary/50',
          disabled ? 'opacity-50 cursor-not-allowed bg-base' : ''
        ]"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="getOptionValue(option)"
          :value="getOptionValue(option)"
        >
          {{ getOptionLabel(option) }}
        </option>
      </select>
      
      <div class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none">
        <i class="mdi mdi-chevron-down text-lg"></i>
      </div>
    </div>
    
    <p v-if="error" class="mt-1.5 text-xs text-danger flex items-center gap-1">
      <i class="mdi mdi-alert-circle"></i>
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-1.5 text-xs text-secondary">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  icon: String,
  error: String,
  hint: String,
  disabled: Boolean,
  required: Boolean,
  id: String,
  options: {
    type: Array,
    required: true,
  },
  valueKey: {
    type: String,
    default: 'value',
  },
  labelKey: {
    type: String,
    default: 'label',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const getOptionValue = (option) => {
  return typeof option === 'object' ? option[props.valueKey] : option;
};

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option[props.labelKey] : option;
};

const handleChange = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
  emit('change', value);
};
</script>

