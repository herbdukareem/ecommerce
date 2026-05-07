<template>
  <div class="h-64">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  formatCurrency: {
    type: Function,
    default: (value) => Number(value || 0).toLocaleString(),
  },
});

const chartCanvas = ref(null);
let chartInstance = null;

const chartRows = computed(() => props.data.map((row) => ({
  label: row.period || row.date || '',
  revenue: Number(row.revenue ?? row.sales ?? row.total ?? 0),
  orders: Number(row.orders ?? 0),
})));

const renderChart = () => {
  if (!chartCanvas.value) return;

  if (chartInstance) {
    chartInstance.destroy();
    chartInstance = null;
  }

  if (!chartRows.value.length) {
    return;
  }

  const ctx = chartCanvas.value.getContext('2d');
  
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartRows.value.map((row) => row.label),
      datasets: [
        {
          label: 'Revenue',
          data: chartRows.value.map((row) => row.revenue),
          borderColor: 'rgb(249, 115, 22)',
          backgroundColor: 'rgba(249, 115, 22, 0.14)',
          tension: 0.4,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label(context) {
              const row = chartRows.value[context.dataIndex] || {};
              return `Revenue: ${props.formatCurrency(context.parsed.y)} (${row.orders || 0} orders)`;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return props.formatCurrency(value);
            },
          },
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

