<script setup>
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const areaChartseries = ref([
{
    name: 'Plug 1',
    data: [90, 140, 65, 120, 110, 125, 95, 75, 110, 135, 145, 125, 95, 65, 85, 70, 135, 125, 140, 135, 85, 65, 95, 135],
},
{
    name: 'Plug 2',
    data: [50, 95, 85, 105, 115, 125, 130, 140, 135, 145, 140, 130, 90, 70, 60, 55, 65, 75, 85, 95, 135, 145, 140, 160],
},
{
    name: 'Plug 3',
    data: [65, 55, 45, 60, 90, 115, 125, 105, 95, 80, 70, 50, 45, 55, 70, 85, 95, 105, 125, 140, 145, 130, 115, 105],
}

]);const areaChartOption = {
  chart: {
    id: 'energy-consumption',
    toolbar: { show: false },
    background: 'transparent',
    height: '350px'
  },

  theme: {
    mode: 'dark'
  },

  colors: ['#22D3EE', '#22C55E', '#FBBF24'], // Cyan, Green, Yellow

  xaxis: {
    categories: [
      "10","11","12","13","14","15","16","17","18","19",
      "20","21","22","23","00","01","02","03","04","05",
      "06","07","08","09"
    ],
    labels: {
      style: { colors: '#94A3B8' }
    }
  },

  yaxis: {
    title: {
      text: 'Power (W)',
      style: { color: '#94A3B8' }
    },
    labels: {
      style: { colors: '#94A3B8' },
      formatter: function (val) {
        return val + 'W'
      }
    }
  },

  stroke: {
    curve: 'smooth',
    width: 3
  },

  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      type: "vertical",
      shadeIntensity: 0.5,
      opacityFrom: 0.6,
      opacityTo: 0.1,
      stops: [0, 100]
    }
  },

  grid: {
    borderColor: '#334155',
    strokeDashArray: 4
  },

  dataLabels: { enabled: false },

  legend: {
    position: 'bottom',
    horizontalAlign: 'center',
    labels: { colors: '#E2E8F0' },
    markers: {
      width: 8,
      height: 8,
      radius: 12
    }
  }
};

</script>

<template>
  <div class="w-full bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
    <div class="text-lg font-semibold text-slate-200 mb-">Power Consumption</div>
        <div class="text-gray-400">24-hour consumption overview</div>
    <VueApexCharts
      type="area" 
      :series="areaChartseries"
      :options="areaChartOption"
    />
  </div>
</template>