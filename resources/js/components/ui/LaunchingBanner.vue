<template>
  <section class="relative overflow-hidden bg-gradient-to-r from-orange-900 via-orange-800 to-orange-900">
    <!-- Animated confetti background -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="confetti-container">
        <div v-for="i in 50" :key="i" class="confetti" :style="confettiStyle(i)"></div>
      </div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <!-- Left side - Logo and Text -->
        <div class="text-center lg:text-left">
          <div class="flex items-center justify-center lg:justify-start gap-3 mb-6">
            <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center shadow-lg">
              <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-shopping text-white text-2xl"></i>
                 
              </div>
            </div>
            <h1 class="text-4xl font-bold text-white">Ashlab</h1>
          </div>

          <h2 class="text-5xl md:text-6xl font-bold text-orange-300 mb-8 italic" style="font-family: 'Brush Script MT', cursive;">
            Launching Soon
          </h2>

          <div class="mb-8">
            <p class="text-2xl md:text-2xl font-bold text-yellow-300 mb-2">UP TO</p>
            <p class="text-6xl md:text-5xl font-black text-yellow-300 drop-shadow-lg">60% OFF</p>
          </div>

          <!-- Countdown Timer -->
          <div class="flex justify-center lg:justify-start gap-4 mb-8">
            <div class="bg-orange-950/50 backdrop-blur-sm border-2 border-orange-500 rounded-lg px-3 py-2 min-w-[80px]">
              <div class="text-3xl font-bold text-white">{{ countdown.days }}</div>
              <div class="text-xs text-orange-300 uppercase">Days</div>
            </div>
            <div class="text-3xl text-white self-center">:</div>
            <div class="bg-orange-950/50 backdrop-blur-sm border-2 border-orange-500 rounded-lg px-3 py-2 min-w-[80px]">
              <div class="text-3xl font-bold text-white">{{ countdown.hours }}</div>
              <div class="text-xs text-orange-300 uppercase">Hours</div>
            </div>
            <div class="text-3xl text-white self-center">:</div>
            <div class="bg-orange-950/50 backdrop-blur-sm border-2 border-orange-500 rounded-lg px-3 py-2 min-w-[80px]">
              <div class="text-3xl font-bold text-white">{{ countdown.minutes }}</div>
              <div class="text-xs text-orange-300 uppercase">Mins</div>
            </div>
            <div class="text-3xl text-white self-center">:</div>
            <div class="bg-orange-950/50 backdrop-blur-sm border-2 border-orange-500 rounded-lg px-4 py-3 min-w-[80px]">
              <div class="text-3xl font-bold text-white">{{ countdown.seconds }}</div>
              <div class="text-xs text-orange-300 uppercase">Secs</div>
            </div>
          </div>

          <button class="bg-yellow-400 hover:bg-yellow-500 text-orange-900 font-bold px-8 py-3 rounded-lg text-lg transition-all transform hover:scale-105 shadow-lg">
            Learn More
          </button>
        </div>

        <!-- Right side - Illustration -->
        <div class="relative hidden lg:block">
          <div class="relative z-10">
            <!-- Gift boxes -->
             <img :src="launchingSoon" alt="Logo" class="w-full h-full object-contain">
           
           
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import launchingSoon from '@/assets/images/cart-bag.png';

const countdown = ref({
  days: 0,
  hours: 0,
  minutes: 0,
  seconds: 0,
});

let intervalId = null;

// Set launch date (you can change this)
const launchDate = new Date('2026-03-01T00:00:00').getTime();

const updateCountdown = () => {
  const now = new Date().getTime();
  const distance = launchDate - now;

  if (distance > 0) {
    countdown.value = {
      days: Math.floor(distance / (1000 * 60 * 60 * 24)),
      hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
      minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
      seconds: Math.floor((distance % (1000 * 60)) / 1000),
    };
  } else {
    countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
  }
};

const confettiStyle = (index) => {
  const colors = ['#FCD34D', '#FBBF24', '#F59E0B', '#F97316', '#EA580C'];
  return {
    left: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 5}s`,
    backgroundColor: colors[index % colors.length],
  };
};

onMounted(() => {
  updateCountdown();
  intervalId = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId);
  }
});
</script>

<style scoped>
.confetti {
  position: absolute;
  width: 10px;
  height: 10px;
  top: -10px;
  animation: fall 5s linear infinite;
}

@keyframes fall {
  to {
    transform: translateY(100vh) rotate(360deg);
  }
}
</style>
