<template>
  <div class="flex items-center" :class="sizeClass">
    <span
      v-for="star in 5"
      :key="star"
      @click="editable && updateRating(star)"
      :class="[
        star <= rating ? 'text-yellow-400' : 'text-gray-300',
        editable ? 'cursor-pointer hover:text-yellow-500' : '',
      ]"
    >
      ★
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  rating: {
    type: Number,
    default: 0,
  },
  editable: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'medium',
  },
});

const emit = defineEmits(['update:rating']);

const sizeClass = computed(() => {
  const sizes = {
    small: 'text-sm',
    medium: 'text-xl',
    large: 'text-3xl',
  };
  return sizes[props.size] || sizes.medium;
});

const updateRating = (star) => {
  emit('update:rating', star);
};
</script>

