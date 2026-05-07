<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-primary mb-2">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <div ref="rootRef" class="relative">
      <div v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none z-10">
        <i :class="`mdi mdi-${icon} text-lg`"></i>
      </div>

      <button
        :id="id"
        ref="buttonRef"
        type="button"
        role="combobox"
        :aria-expanded="open"
        :aria-controls="listboxId"
        :disabled="disabled"
        class="w-full px-4 py-2.5 rounded-lg border transition-all duration-300 bg-surface text-primary focus:outline-none focus:ring-2 text-left min-h-[42px]"
        :class="[
          icon ? 'pl-11' : '',
          error ? 'border-danger focus:ring-danger/50' : 'border-DEFAULT focus:ring-primary/50',
          disabled ? 'opacity-50 cursor-not-allowed bg-base' : ''
        ]"
        @click="toggle"
        @keydown.down.prevent="openAndMove(1)"
        @keydown.up.prevent="openAndMove(-1)"
        @keydown.enter.prevent="open ? chooseActive() : toggle()"
        @keydown.esc.prevent="close"
      >
        <span v-if="selectedLabel" class="block truncate">{{ selectedLabel }}</span>
        <span v-else class="block truncate text-secondary">{{ placeholder || 'Select option' }}</span>
      </button>

      <button
        v-if="clearable && hasValue && !disabled"
        type="button"
        class="absolute right-9 top-1/2 -translate-y-1/2 text-secondary hover:text-primary"
        aria-label="Clear selection"
        @click.stop="selectValue('')"
      >
        <i class="mdi mdi-close-circle text-lg"></i>
      </button>

      <div class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none">
        <i class="mdi mdi-chevron-down text-lg"></i>
      </div>

      <Teleport to="body">
      <div
        v-if="open"
        ref="dropdownRef"
        class="fixed z-[9999] rounded-lg border border-DEFAULT bg-surface shadow-xl overflow-hidden"
        :style="dropdownStyle"
      >
        <div class="p-2 border-b border-DEFAULT">
          <div class="relative">
            <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-secondary"></i>
            <input
              ref="searchRef"
              v-model="query"
              type="text"
              class="w-full rounded-md border border-DEFAULT bg-base py-2 pl-9 pr-3 text-sm text-primary outline-none focus:ring-2 focus:ring-primary/40"
              :placeholder="searchPlaceholder"
              @keydown.down.prevent="move(1)"
              @keydown.up.prevent="move(-1)"
              @keydown.enter.prevent="chooseActive"
              @keydown.esc.prevent="close"
            />
          </div>
        </div>

        <div v-if="loading" class="px-3 py-3 text-sm text-secondary">Loading options...</div>
        <ul
          v-else
          :id="listboxId"
          role="listbox"
          class="max-h-64 overflow-y-auto py-1"
        >
          <li v-if="filteredOptions.length === 0" class="px-3 py-3 text-sm text-secondary">
            {{ emptyText }}
          </li>
          <li
            v-for="(option, index) in filteredOptions"
            :key="`${getOptionValue(option)}-${index}`"
            role="option"
            :aria-selected="isSelected(option)"
            class="px-3 py-2 text-sm cursor-pointer flex items-center justify-between gap-3"
            :class="[
              index === activeIndex ? 'bg-primary/10 text-primary' : 'text-primary hover:bg-base',
              isSelected(option) ? 'font-semibold' : ''
            ]"
            @mouseenter="activeIndex = index"
            @mousedown.prevent="selectValue(getOptionValue(option))"
          >
            <span class="truncate">{{ getOptionLabel(option) }}</span>
            <i v-if="isSelected(option)" class="mdi mdi-check text-primary"></i>
          </li>
        </ul>
      </div>
      </Teleport>
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
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  searchPlaceholder: {
    type: String,
    default: 'Search options...',
  },
  emptyText: {
    type: String,
    default: 'No options found.',
  },
  icon: String,
  error: String,
  hint: String,
  disabled: Boolean,
  required: Boolean,
  loading: Boolean,
  clearable: {
    type: Boolean,
    default: true,
  },
  id: {
    type: String,
    default: () => `select-${Math.random().toString(36).slice(2)}`,
  },
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

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const rootRef = ref(null);
const buttonRef = ref(null);
const searchRef = ref(null);
const dropdownRef = ref(null);
const dropdownRect = ref({ left: 0, top: 0, width: 0 });
const listboxId = `${props.id}-listbox`;

const getOptionValue = (option) => (typeof option === 'object' ? option[props.valueKey] : option);
const getOptionLabel = (option) => (typeof option === 'object' ? option[props.labelKey] : option);

const hasValue = computed(() => props.modelValue !== undefined && props.modelValue !== null && String(props.modelValue) !== '');

const selectedOption = computed(() => props.options.find((option) => String(getOptionValue(option)) === String(props.modelValue)));
const selectedLabel = computed(() => selectedOption.value ? getOptionLabel(selectedOption.value) : '');

const filteredOptions = computed(() => {
  const term = query.value.trim().toLowerCase();
  if (!term) return props.options;

  return props.options.filter((option) => String(getOptionLabel(option)).toLowerCase().includes(term));
});

const isSelected = (option) => String(getOptionValue(option)) === String(props.modelValue);

const dropdownStyle = computed(() => ({
  left: `${dropdownRect.value.left}px`,
  top: `${dropdownRect.value.top}px`,
  width: `${dropdownRect.value.width}px`,
}));

const updateDropdownPosition = () => {
  const rect = buttonRef.value?.getBoundingClientRect();
  if (!rect) return;

  dropdownRect.value = {
    left: rect.left,
    top: rect.bottom + 4,
    width: rect.width,
  };
};

const focusSearch = async () => {
  await nextTick();
  updateDropdownPosition();
  searchRef.value?.focus();
};

const toggle = () => {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) focusSearch();
};

const close = () => {
  open.value = false;
  query.value = '';
  activeIndex.value = 0;
};

const selectValue = (value) => {
  emit('update:modelValue', value);
  emit('change', value);
  close();
};

const move = (step) => {
  if (filteredOptions.value.length === 0) return;
  activeIndex.value = (activeIndex.value + step + filteredOptions.value.length) % filteredOptions.value.length;
};

const openAndMove = (step) => {
  if (!open.value) {
    open.value = true;
    focusSearch();
  }
  move(step);
};

const chooseActive = () => {
  const option = filteredOptions.value[activeIndex.value];
  if (option) {
    selectValue(getOptionValue(option));
  }
};

const onDocumentClick = (event) => {
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    if (dropdownRef.value?.contains(event.target)) {
      return;
    }
    close();
  }
};

const onViewportChange = () => {
  if (open.value) {
    updateDropdownPosition();
  }
};

watch(open, (value) => {
  if (value) {
    updateDropdownPosition();
    document.addEventListener('mousedown', onDocumentClick);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
  } else {
    document.removeEventListener('mousedown', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
  }
});

watch(filteredOptions, () => {
  activeIndex.value = 0;
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentClick);
  window.removeEventListener('resize', onViewportChange);
  window.removeEventListener('scroll', onViewportChange, true);
});
</script>
