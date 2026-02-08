<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-primary mb-2">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    
    <div class="relative">
      <div v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary">
        <i :class="`mdi mdi-${icon} text-lg`"></i>
      </div>
      
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :autocomplete="autocomplete"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        class="w-full px-4 py-2.5 rounded-lg border transition-all duration-300 bg-surface text-primary placeholder-secondary/50 focus:outline-none focus:ring-2"
        :class="[
          icon ? 'pl-11' : '',
          error ? 'border-danger focus:ring-danger/50' : 'border-DEFAULT focus:ring-primary/50',
          disabled ? 'opacity-50 cursor-not-allowed bg-base' : '',
          sizeClass
        ]"
      />
      
      <div v-if="clearable && modelValue" class="absolute right-3 top-1/2 -translate-y-1/2">
        <button
          type="button"
          @click="clear"
          class="text-secondary hover:text-primary transition-colors"
        >
          <i class="mdi mdi-close-circle text-lg"></i>
        </button>
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
import { computed } from 'vue';

const props = defineProps({
  modelValue: [String, Number],
  type: {
    type: String,
    default: 'text',
  },
  label: String,
  placeholder: String,
  icon: String,
  error: String,
  hint: String,
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
  clearable: Boolean,
  id: String,
  min: [String, Number],
  max: [String, Number],
  step: [String, Number],
  autocomplete: String,
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const sizeClass = computed(() => {
  const sizes = {
    sm: 'text-sm py-2',
    md: 'text-sm py-2.5',
    lg: 'text-base py-3',
  };
  return sizes[props.size];
});

const handleInput = (event) => {
  emit('update:modelValue', event.target.value);
};

const handleBlur = (event) => {
  emit('blur', event);
};

const handleFocus = (event) => {
  emit('focus', event);
};

const clear = () => {
  emit('update:modelValue', '');
};
</script>

