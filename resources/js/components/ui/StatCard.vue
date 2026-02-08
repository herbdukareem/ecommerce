<template>
  <div 
    class="stat-card"
    :class="[colorClass, { 'hover-lift': hoverable }]"
    :style="{ '--stat-color': color }"
  >
    <div class="stat-icon-wrapper">
      <div class="stat-icon-bg"></div>
      <font-awesome-icon :icon="icon" class="stat-icon" />
    </div>
    
    <div class="stat-content">
      <p class="stat-label">{{ label }}</p>
      <h3 class="stat-value">{{ formattedValue }}</h3>
      
      <div v-if="trend !== undefined" class="stat-trend">
        <font-awesome-icon 
          :icon="['fas', trendIcon]" 
          class="trend-icon"
          :class="trendClass"
        />
        <span class="trend-value" :class="trendClass">
          {{ Math.abs(trend) }}%
        </span>
        <span class="trend-label">vs last period</span>
      </div>
    </div>

    <div v-if="showProgress && progress !== undefined" class="stat-progress">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
      <span class="progress-text">{{ progress }}%</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: Array,
    required: true,
  },
  color: {
    type: String,
    default: 'var(--color-primary)',
  },
  colorClass: {
    type: String,
    default: 'primary',
  },
  trend: Number,
  progress: Number,
  showProgress: Boolean,
  hoverable: {
    type: Boolean,
    default: true,
  },
  prefix: String,
  suffix: String,
});

const formattedValue = computed(() => {
  let val = props.value;
  if (props.prefix) val = props.prefix + val;
  if (props.suffix) val = val + props.suffix;
  return val;
});

const trendIcon = computed(() => {
  return props.trend >= 0 ? 'arrow-up' : 'arrow-down';
});

const trendClass = computed(() => {
  return props.trend >= 0 ? 'trend-up' : 'trend-down';
});
</script>

<style scoped>
.stat-card {
  position: relative;
  background: var(--color-surface);
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--color-border);
  overflow: hidden;
  transition: all var(--transition-base);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--stat-color);
}

.stat-card.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}

.stat-icon-wrapper {
  position: relative;
  width: 3.5rem;
  height: 3.5rem;
  margin-bottom: 1rem;
}

.stat-icon-bg {
  position: absolute;
  inset: 0;
  background: var(--stat-color);
  opacity: 0.1;
  border-radius: 0.75rem;
  animation: pulse 2s ease-in-out infinite;
}

.stat-icon {
  position: relative;
  font-size: 1.75rem;
  color: var(--stat-color);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.stat-content {
  margin-bottom: 0.75rem;
}

.stat-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
  line-height: 1;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin-top: 0.75rem;
  font-size: 0.875rem;
}

.trend-icon {
  font-size: 0.75rem;
}

.trend-value {
  font-weight: 600;
}

.trend-label {
  color: var(--color-text-secondary);
  font-size: 0.75rem;
}

.trend-up {
  color: var(--color-success);
}

.trend-down {
  color: var(--color-danger);
}

.stat-progress {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-bar {
  flex: 1;
  height: 0.5rem;
  background: var(--color-background);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--stat-color);
  border-radius: 9999px;
  transition: width var(--transition-slow);
}

.progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  min-width: 3rem;
  text-align: right;
}

/* Color Variants */
.stat-card.primary {
  --stat-color: var(--color-primary);
}

.stat-card.success {
  --stat-color: var(--color-success);
}

.stat-card.warning {
  --stat-color: var(--color-warning);
}

.stat-card.danger {
  --stat-color: var(--color-danger);
}

.stat-card.info {
  --stat-color: var(--color-info);
}

.stat-card.secondary {
  --stat-color: var(--color-secondary);
}
</style>

