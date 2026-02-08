<template>
  <span
    class="inline-flex items-center gap-1.5 font-medium rounded-full transition-all duration-300"
    :class="[variantClass, sizeClass, { 'cursor-pointer hover:opacity-80': clickable }]"
    @click="handleClick"
  >
    <i v-if="icon" :class="`mdi mdi-${icon}`"></i>
    <slot></slot>
    <button
      v-if="closable"
      @click.stop="handleClose"
      class="hover:opacity-70 transition-opacity"
    >
      <i class="mdi mdi-close text-xs"></i>
    </button>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) =>
      ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  icon: String,
  closable: Boolean,
  clickable: Boolean,
});

const emit = defineEmits(['click', 'close']);

const sizeClass = computed(() => {
  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
    lg: 'px-3 py-1.5 text-base',
  };
  return sizes[props.size];
});

const variantClass = computed(() => {
  const variants = {
    primary: 'bg-primary/10 text-primary',
    secondary: 'bg-secondary/10 text-secondary',
    success: 'bg-success/10 text-success',
    danger: 'bg-danger/10 text-danger',
    warning: 'bg-warning/10 text-warning',
    info: 'bg-info/10 text-info',
    outline: 'border border-primary text-primary bg-transparent',
  };
  return variants[props.variant];
});

const handleClick = () => {
  if (props.clickable) {
    emit('click');
  }
};

const handleClose = () => {
  emit('close');
};
</script>

