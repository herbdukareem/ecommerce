<template>
  <div class="h-64">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
});

const chartCanvas = ref(null);
let chartInstance = null;

const renderChart = () => {
  if (!chartCanvas.value || !props.data.length) return;

  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = chartCanvas.value.getContext('2d');
  
  chartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: props.data.map(d => d.status),
      datasets: [{
        data: props.data.map(d => d.count),
        backgroundColor: [
          'rgba(251, 191, 36, 0.8)',  // yellow - pending
          'rgba(59, 130, 246, 0.8)',  // blue - processing
          'rgba(139, 92, 246, 0.8)',  // purple - shipped
          'rgba(16, 185, 129, 0.8)',  // green - delivered
        ],
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
        },
      },
    },
  });
};

onMounted(() => {
  renderChart();
});

watch(() => props.data, () => {
  renderChart();
}, { deep: true });
</script>

