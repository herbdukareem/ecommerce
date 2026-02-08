<template>
  <div v-if="!closed" class="relative bg-gradient-to-r from-orange-500 via-orange-400 to-orange-500 overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute top-0 left-0 w-10 h-10 bg-yellow-300 rounded-full opacity-30 animate-pulse"></div>
      <div class="absolute top-2 right-20 w-14 h-14 bg-yellow-400 rounded-full opacity-40 animate-bounce"></div>
      <div class="absolute bottom-0 left-1/4 w-10 h-10 bg-orange-300 rounded-full opacity-30"></div>
    </div>

    <div class="relative max-w-4xl mx-auto px-2 py-1">
      <div class="flex items-center justify-between gap-4">
        <!-- Left side - Gift icon -->
        <div class="hidden sm:flex items-center gap-3">
          <div class="relative">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg animate-bounce">
              <i class="mdi mdi-gift text-orange-500 text-2xl"></i>
            </div>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full animate-ping"></div>
          </div>
          <div class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-700 rounded-lg flex items-center justify-center shadow-lg transform -rotate-12">
            <i class="mdi mdi-camera text-white text-2xl"></i>
          </div>
        </div>

        <!-- Center - Main message -->
        <div class="flex-1 flex items-center justify-center gap-4 text-center">
          <div class="flex items-center gap-3">
            <span class="text-white font-bold text-lg sm:text-xl md:text-2xl drop-shadow-lg">
              Launching Soon
            </span>
            <span class="hidden sm:inline text-white text-2xl">•</span>
            <span class="text-white font-bold text-lg sm:text-xl md:text-2xl">
              UP TO
            </span>
            <span class="text-yellow-300 font-black text-2xl sm:text-3xl md:text-3xl drop-shadow-lg animate-pulse">
              50% OFF!
            </span>
          </div>
        </div>

        <!-- Right side - Shopping bag icon and close button -->
        <div class="flex items-center gap-3">
          <div class="hidden md:block relative">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg transform rotate-6">
              <i class="mdi mdi-shopping text-white text-2xl"></i>
            </div>
            <!-- Cute face on bag -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              <div class="flex gap-1">
                <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
              </div>
              <div class="w-3 h-1 bg-white rounded-full mt-1 mx-auto"></div>
            </div>
          </div>

          <!-- Close button -->
          <button
            @click="closed = true"
            class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors"
            aria-label="Close banner"
          >
            <i class="mdi mdi-close text-white text-xl"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Decorative confetti -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div v-for="i in 20" :key="i" class="confetti-piece" :style="confettiStyle(i)"></div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const closed = ref(false);

const confettiStyle = (index) => {
  const shapes = ['square', 'circle', 'triangle'];
  const colors = ['#FCD34D', '#FBBF24', '#F59E0B', '#FFFFFF', '#FEF3C7'];
  const size = Math.random() * 8 + 4;
  
  return {
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    width: `${size}px`,
    height: `${size}px`,
    backgroundColor: colors[index % colors.length],
    animationDelay: `${Math.random() * 3}s`,
    animationDuration: `${Math.random() * 2 + 2}s`,
    opacity: Math.random() * 0.5 + 0.3,
    borderRadius: shapes[index % shapes.length] === 'circle' ? '50%' : '0',
  };
};
</script>

<style scoped>
.confetti-piece {
  position: absolute;
  animation: float 4s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-10px) rotate(180deg);
  }
}

/* Pulse animation for the discount text */
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}
</style>

