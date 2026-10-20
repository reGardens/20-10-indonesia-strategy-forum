<template>
  <div class="rounded-xl bg-white shadow-sm border border-gray-100 p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-900">Total Pengunjung</h3>
      <div class="flex rounded-lg border border-gray-200 overflow-hidden">
        <button
          v-for="period in periods"
          :key="period.value"
          type="button"
          :class="[
            'px-3 py-1 text-xs font-medium transition-colors',
            activePeriod === period.value
              ? 'bg-emerald-600 text-white'
              : 'bg-white text-gray-600 hover:bg-gray-50',
          ]"
          @click="activePeriod = period.value"
        >
          {{ period.label }}
        </button>
      </div>
    </div>

    <div class="space-y-3">
      <div
        v-for="(item, index) in chartData"
        :key="index"
        class="flex items-center gap-3"
      >
        <span class="text-xs text-gray-500 w-20 text-right flex-shrink-0">{{
          item.label
        }}</span>
        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
          <div
            class="bg-emerald-500 h-full rounded-full transition-all duration-500"
            :style="{ width: barWidth(item.count) }"
          ></div>
        </div>
        <span class="text-xs font-medium text-gray-700 w-8 flex-shrink-0">{{
          item.count
        }}</span>
      </div>
    </div>

    <div class="mt-4 pt-3 border-t border-gray-100 text-center">
      <p class="text-sm text-gray-500">
        Total:
        <span class="font-semibold text-gray-900">{{ totalVisitors }}</span>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  visitors: {
    type: Array,
    default: () => [],
  },
});

const periods = [
  { label: "Harian", value: "daily" },
  { label: "Mingguan", value: "weekly" },
  { label: "Bulanan", value: "monthly" },
];

const activePeriod = ref("daily");

function generatePlaceholderData(period) {
  const now = new Date();
  if (period === "daily") {
    return Array.from({ length: 7 }, (_, i) => {
      const d = new Date(now);
      d.setDate(d.getDate() - (6 - i));
      return {
        label: d.toLocaleDateString("id-ID", {
          day: "2-digit",
          month: "short",
        }),
        count: Math.floor(Math.random() * 20) + 1,
      };
    });
  } else if (period === "weekly") {
    return Array.from({ length: 4 }, (_, i) => ({
      label: `Minggu ${i + 1}`,
      count: Math.floor(Math.random() * 80) + 10,
    }));
  } else {
    return Array.from({ length: 6 }, (_, i) => {
      const d = new Date(now);
      d.setMonth(d.getMonth() - (5 - i));
      return {
        label: d.toLocaleDateString("id-ID", { month: "short" }),
        count: Math.floor(Math.random() * 200) + 20,
      };
    });
  }
}

const chartData = computed(() => {
  if (props.visitors && props.visitors.length > 0) {
    return props.visitors;
  }
  return generatePlaceholderData(activePeriod.value);
});

const maxCount = computed(() => {
  const max = Math.max(...chartData.value.map((d) => d.count), 1);
  return max;
});

const totalVisitors = computed(() => {
  return chartData.value.reduce((sum, d) => sum + d.count, 0);
});

function barWidth(count) {
  return `${(count / maxCount.value) * 100}%`;
}
</script>
