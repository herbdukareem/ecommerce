<template>
  <section
    v-if="visible"
    class="relative overflow-hidden"
    :style="{ backgroundColor: flash.backgroundColor, color: flash.textColor }"
  >
    <div class="absolute inset-0 pointer-events-none opacity-20">
      <div class="absolute -left-12 top-8 h-40 w-40 rounded-full bg-white blur-3xl"></div>
      <div class="absolute bottom-0 right-8 h-52 w-52 rounded-full bg-accent blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_0.72fr] gap-8 items-center">
        <div class="text-center lg:text-left">
          <p v-if="flash.title" class="text-sm font-semibold uppercase tracking-[0.22em] opacity-75">
            {{ flash.title }}
          </p>
          <h2 v-if="flash.highlight" class="mt-3 text-4xl md:text-5xl font-black leading-tight">
            {{ flash.highlight }}
          </h2>
          <p v-if="flash.message" class="mt-4 max-w-2xl text-base md:text-lg leading-8 opacity-85 mx-auto lg:mx-0">
            {{ flash.message }}
          </p>

          <div v-if="showCountdown" class="mt-7 flex justify-center lg:justify-start gap-3">
            <div v-for="item in countdownItems" :key="item.label" class="min-w-[72px] rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-center backdrop-blur">
              <div class="text-2xl font-bold">{{ item.value }}</div>
              <div class="text-[11px] uppercase tracking-wide opacity-75">{{ item.label }}</div>
            </div>
          </div>

          <button
            v-if="flash.buttonLabel && flash.buttonUrl"
            type="button"
            class="mt-7 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-bold text-primary shadow-sm transition hover:-translate-y-0.5"
            @click="goTo(flash.buttonUrl)"
          >
            {{ flash.buttonLabel }}
            <i class="mdi mdi-arrow-right"></i>
          </button>
        </div>

        <div class="hidden lg:flex justify-end">
          <img
            v-if="flash.imageUrl"
            :src="flash.imageUrl"
            :alt="flash.title || 'Homepage promotion'"
            class="max-h-72 w-full max-w-md object-contain"
          />
          <div v-else class="flex h-64 w-full max-w-sm items-center justify-center rounded-2xl border border-white/20 bg-white/10">
            <i class="mdi mdi-sale-outline text-8xl opacity-60"></i>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSettingsStore } from '../../stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();
const now = ref(Date.now());
let intervalId = null;

const flash = computed(() => settingsStore.homepageFlash);
const visible = computed(() => flash.value.enabled && flash.value.location === 'home');
const targetTime = computed(() => flash.value.countdownTarget ? new Date(flash.value.countdownTarget).getTime() : null);
const remaining = computed(() => targetTime.value ? Math.max(0, targetTime.value - now.value) : 0);
const showCountdown = computed(() => flash.value.countdownEnabled && targetTime.value && remaining.value > 0);

const countdownItems = computed(() => {
  const distance = remaining.value;
  return [
    { label: 'Days', value: Math.floor(distance / (1000 * 60 * 60 * 24)) },
    { label: 'Hours', value: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)) },
    { label: 'Mins', value: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)) },
    { label: 'Secs', value: Math.floor((distance % (1000 * 60)) / 1000) },
  ];
});

const goTo = (url) => {
  if (!url) return;
  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.location.href = url;
    return;
  }
  router.push(url);
};

onMounted(() => {
  intervalId = setInterval(() => {
    now.value = Date.now();
  }, 1000);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});
</script>
