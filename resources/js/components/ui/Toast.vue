<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-md">
      <transition-group
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-full"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-full"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="flex items-start gap-3 px-4 py-3 rounded-lg shadow-material-4 backdrop-blur-sm animate-fade-in-right"
          :class="getToastClass(toast.type)"
        >
          <i :class="`mdi mdi-${getIcon(toast.type)} text-xl flex-shrink-0`"></i>
          <div class="flex-1 min-w-0">
            <p v-if="toast.title" class="font-semibold text-sm mb-0.5">{{ toast.title }}</p>
            <p class="text-sm opacity-90">{{ toast.message }}</p>
          </div>
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity"
          >
            <i class="mdi mdi-close text-lg"></i>
          </button>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

const getToastClass = (type) => {
  const classes = {
    success: 'bg-success text-white',
    error: 'bg-danger text-white',
    warning: 'bg-warning text-white',
    info: 'bg-info text-white',
  };
  return classes[type] || classes.info;
};

const getIcon = (type) => {
  const icons = {
    success: 'check-circle',
    error: 'alert-circle',
    warning: 'alert',
    info: 'information',
  };
  return icons[type] || icons.info;
};

const addToast = (message, type = 'info', title = '', duration = 5000) => {
  const id = toastId++;
  toasts.value.push({ id, message, type, title });
  
  if (duration > 0) {
    setTimeout(() => {
      removeToast(id);
    }, duration);
  }
  
  return id;
};

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) {
    toasts.value.splice(index, 1);
  }
};

defineExpose({
  addToast,
  removeToast,
  success: (message, title = '', duration = 5000) => addToast(message, 'success', title, duration),
  error: (message, title = '', duration = 5000) => addToast(message, 'error', title, duration),
  warning: (message, title = '', duration = 5000) => addToast(message, 'warning', title, duration),
  info: (message, title = '', duration = 5000) => addToast(message, 'info', title, duration),
});
</script>

