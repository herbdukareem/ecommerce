<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 font-medium rounded-lg border-none cursor-pointer transition-all duration-300 relative overflow-hidden"
    :class="[variantClass, sizeClass, { 'opacity-50 cursor-not-allowed': disabled || loading }]"
    @click="handleClick"
  >
    <span v-if="loading" class="inline-flex">
      <i class="mdi mdi-loading mdi-spin"></i>
    </span>
    <i v-else-if="icon && !iconRight" :class="`mdi mdi-${icon}`"></i>
    <span v-if="!iconOnly" class="leading-none">
      <slot></slot>
    </span>
    <i v-if="icon && iconRight && !loading" :class="`mdi mdi-${icon}`"></i>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) =>
      ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline', 'ghost', 'link'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
  },
  icon: String,
  iconRight: Boolean,
  iconOnly: Boolean,
  loading: Boolean,
  disabled: Boolean,
});

const emit = defineEmits(['click']);

const sizeClass = computed(() => {
  const sizes = {
    xs: 'px-3 py-1.5 text-xs',
    sm: 'px-4 py-2 text-sm',
    md: 'px-5 py-2.5 text-sm',
    lg: 'px-6 py-3 text-base',
    xl: 'px-8 py-4 text-lg',
  };
  return props.iconOnly ? 'p-2.5 aspect-square' : sizes[props.size];
});

const variantClass = computed(() => {
  const variants = {
    primary: 'bg-primary text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    secondary: 'bg-secondary text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    success: 'bg-success text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    danger: 'bg-danger text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    warning: 'bg-warning text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    info: 'bg-info text-white shadow-material-2 hover:-translate-y-0.5 hover:shadow-material-3 active:translate-y-0',
    outline: 'bg-transparent border-2 border-primary text-primary hover:bg-primary hover:text-white',
    ghost: 'bg-transparent text-primary hover:bg-base',
    link: 'bg-transparent text-primary hover:underline shadow-none',
  };
  return variants[props.variant];
});

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event);
  }
};
</script>

