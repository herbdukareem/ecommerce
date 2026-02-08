<template>
  <div
    class="bg-white rounded-lg border border-gray-200 overflow-hidden transition-all duration-200"
    :class="[
      shadowClass,
      { 'hover:shadow-md hover:border-gray-300': hoverable },
      { 'cursor-pointer': clickable },
      animationClass
    ]"
    @click="handleClick"
  >
    <div v-if="$slots.header || title" class="flex items-center justify-between px-6 py-4 border-b border-DEFAULT">
      <slot name="header">
        <div class="flex items-center gap-3">
          <i
            v-if="icon"
            :class="`mdi mdi-${icon}`"
            class="text-xl"
            :style="{ color: iconColor || 'var(--color-primary)' }"
          ></i>
          <h3 class="text-lg font-semibold text-primary">{{ title }}</h3>
        </div>
        <div v-if="$slots.actions" class="flex gap-2">
          <slot name="actions"></slot>
        </div>
      </slot>
    </div>

    <div class="p-6" :class="{ 'p-0': noPadding }">
      <slot></slot>
    </div>

    <div v-if="$slots.footer" class="px-6 py-4 border-t border-DEFAULT bg-base">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  icon: String,
  iconColor: String,
  elevation: {
    type: Number,
    default: 1,
    validator: (value) => value >= 0 && value <= 3,
  },
  hoverable: {
    type: Boolean,
    default: false,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
  noPadding: {
    type: Boolean,
    default: false,
  },
  animation: {
    type: String,
    default: 'fade-in-up',
  },
});

const emit = defineEmits(['click']);

const shadowClass = computed(() => {
  const shadows = {
    0: 'shadow-none border-0',
    1: 'shadow-sm',
    2: 'shadow',
    3: 'shadow-md',
  };
  return shadows[props.elevation] || 'shadow-sm';
});

const animationClass = computed(() => {
  return props.animation ? `animate-${props.animation}` : '';
});

const handleClick = () => {
  if (props.clickable) {
    emit('click');
  }
};
</script>
