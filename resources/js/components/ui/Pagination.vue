<template>
  <div class="flex items-center justify-between gap-4 flex-wrap">
    <div class="text-sm text-secondary">
      Showing <span class="font-medium text-primary">{{ from }}</span> to 
      <span class="font-medium text-primary">{{ to }}</span> of 
      <span class="font-medium text-primary">{{ total }}</span> results
    </div>
    
    <div class="flex items-center gap-2">
      <Button
        variant="ghost"
        size="sm"
        icon="chevron-left"
        icon-only
        :disabled="currentPage === 1"
        @click="goToPage(currentPage - 1)"
      />
      
      <template v-for="page in pages" :key="page">
        <Button
          v-if="page !== '...'"
          :variant="page === currentPage ? 'primary' : 'ghost'"
          size="sm"
          @click="goToPage(page)"
        >
          {{ page }}
        </Button>
        <span v-else class="px-2 text-secondary">...</span>
      </template>
      
      <Button
        variant="ghost"
        size="sm"
        icon="chevron-right"
        icon-only
        :disabled="currentPage === totalPages"
        @click="goToPage(currentPage + 1)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from './Button.vue';

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  totalPages: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  perPage: {
    type: Number,
    default: 10,
  },
  maxVisible: {
    type: Number,
    default: 7,
  },
});

const emit = defineEmits(['page-change']);

const from = computed(() => {
  return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total);
});

const pages = computed(() => {
  const pages = [];
  const half = Math.floor(props.maxVisible / 2);
  
  let start = Math.max(1, props.currentPage - half);
  let end = Math.min(props.totalPages, start + props.maxVisible - 1);
  
  if (end - start < props.maxVisible - 1) {
    start = Math.max(1, end - props.maxVisible + 1);
  }
  
  if (start > 1) {
    pages.push(1);
    if (start > 2) pages.push('...');
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  if (end < props.totalPages) {
    if (end < props.totalPages - 1) pages.push('...');
    pages.push(props.totalPages);
  }
  
  return pages;
});

const goToPage = (page) => {
  if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
    emit('page-change', page);
  }
};
</script>

