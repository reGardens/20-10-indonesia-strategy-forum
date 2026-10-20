<template>
  <div class="rounded-xl bg-white shadow-sm border border-gray-100 p-5">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Ringkasan RSVP</h3>

    <div class="space-y-4">
      <!-- Status bars -->
      <div v-for="item in statusItems" :key="item.key" class="space-y-1">
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-600">{{ item.label }}</span>
          <span class="font-semibold text-gray-900">{{ item.count }}</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
          <div
            :class="item.barColor"
            class="h-full rounded-full transition-all duration-500"
            :style="{ width: barWidth(item.count) }"
          ></div>
        </div>
      </div>

      <!-- Total -->
      <div
        class="pt-3 border-t border-gray-100 flex items-center justify-between"
      >
        <span class="text-sm font-medium text-gray-600">Total RSVP</span>
        <span class="text-lg font-bold text-gray-900">{{ total }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({ hadir: 0, tidakHadir: 0, mungkin: 0 }),
  },
});

const statusItems = computed(() => [
  {
    key: "hadir",
    label: "Hadir",
    count: props.summary.hadir || 0,
    barColor: "bg-green-500",
  },
  {
    key: "tidakHadir",
    label: "Tidak Hadir",
    count: props.summary.tidakHadir || 0,
    barColor: "bg-red-400",
  },
  {
    key: "mungkin",
    label: "Mungkin",
    count: props.summary.mungkin || 0,
    barColor: "bg-yellow-400",
  },
]);

const total = computed(() => {
  return (
    (props.summary.hadir || 0) +
    (props.summary.tidakHadir || 0) +
    (props.summary.mungkin || 0)
  );
});

function barWidth(count) {
  if (total.value === 0) return "0%";
  return `${(count / total.value) * 100}%`;
}
</script>
