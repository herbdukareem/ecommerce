<template>
  <div
    v-if="visible && !closed"
    class="relative overflow-hidden"
    :style="{ backgroundColor: flash.backgroundColor, color: flash.textColor }"
  >
    <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2 text-sm sm:px-6 lg:px-8">
      <button type="button" class="min-w-0 flex-1 text-left" @click="goTo(flash.buttonUrl)">
        <span class="font-bold">{{ flash.title }}</span>
        <span v-if="flash.highlight" class="mx-2 hidden sm:inline">|</span>
        <span v-if="flash.highlight" class="font-semibold">{{ flash.highlight }}</span>
        <span v-if="flash.message" class="ml-2 hidden md:inline opacity-85">{{ flash.message }}</span>
      </button>

      <button
        v-if="flash.buttonLabel && flash.buttonUrl"
        type="button"
        class="hidden rounded-md bg-white/15 px-3 py-1 font-semibold transition hover:bg-white/25 sm:inline-flex"
        @click="goTo(flash.buttonUrl)"
      >
        {{ flash.buttonLabel }}
      </button>

      <button
        type="button"
        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/15 transition hover:bg-white/25"
        aria-label="Close banner"
        @click="closed = true"
      >
        <i class="mdi mdi-close"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();
const closed = ref(false);

const flash = computed(() => settingsStore.homepageFlash);
const visible = computed(() => flash.value.enabled && flash.value.location === 'global');

const goTo = (url) => {
  if (!url) return;
  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.location.href = url;
    return;
  }
  router.push(url);
};
</script>
