<template>
  <div class="relative">
    <button
      @click="toggleDropdown"
      class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium shadow-material-2 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-material-3"
      :style="{ backgroundColor: theme.primary }"
    >
      <i class="mdi mdi-palette text-lg"></i>
      <span class="text-sm">Theme</span>
      <i
        class="mdi mdi-chevron-down text-xs transition-transform duration-300"
        :class="{ 'rotate-180': isOpen }"
      ></i>
    </button>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-2"
    >
      <div
        v-if="isOpen"
        class="absolute top-full right-0 mt-2 min-w-[220px] bg-surface rounded-xl border border-DEFAULT shadow-material-4 p-2 z-50"
      >
        <div
          v-for="themeOption in themeList"
          :key="themeOption.key"
          @click="selectTheme(themeOption.key)"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-all duration-200 hover:bg-base"
          :class="{ 'bg-primary text-white': currentTheme === themeOption.key }"
        >
          <div class="flex gap-1">
            <div
              class="w-4 h-4 rounded-full border-2 border-white/30"
              :style="{ backgroundColor: themeOption.colors.primary }"
            ></div>
            <div
              class="w-4 h-4 rounded-full border-2 border-white/30"
              :style="{ backgroundColor: themeOption.colors.secondary }"
            ></div>
            <div
              class="w-4 h-4 rounded-full border-2 border-white/30"
              :style="{ backgroundColor: themeOption.colors.accent }"
            ></div>
          </div>
          <span class="flex-1 text-sm font-medium">{{ themeOption.name }}</span>
          <i
            v-if="currentTheme === themeOption.key"
            class="mdi mdi-check text-sm"
          ></i>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useThemeStore } from '../../stores/theme';
import { storeToRefs } from 'pinia';

const themeStore = useThemeStore();
const { currentTheme, theme, themeList } = storeToRefs(themeStore);

const isOpen = ref(false);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectTheme = (themeName) => {
  themeStore.setTheme(themeName);
  isOpen.value = false;
};
</script>

