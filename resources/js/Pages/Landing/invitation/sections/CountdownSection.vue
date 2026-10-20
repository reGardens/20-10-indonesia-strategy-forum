<template>
  <section
    class="countdown-section px-4 py-16 text-center md:py-24"
    data-aos="fade-up"
  >
    <h2
      class="mb-8 text-2xl font-bold md:text-3xl"
      :style="{ color: primaryColor }"
    >
      Menuju Hari Bahagia
    </h2>

    <div v-if="isExpired" class="mx-auto max-w-md">
      <p class="text-lg font-medium text-gray-600">
        Acara telah berlangsung 🎉
      </p>
    </div>

    <div v-else class="mx-auto flex max-w-md justify-center gap-3 md:gap-4">
      <div
        v-for="unit in countdownUnits"
        :key="unit.label"
        class="flex-1 rounded-lg p-3 shadow-md md:p-4"
        :style="{ backgroundColor: primaryColor }"
      >
        <span class="block text-2xl font-bold text-white md:text-4xl">
          {{ unit.value }}
        </span>
        <span class="mt-1 block text-xs uppercase tracking-wider text-white/70">
          {{ unit.label }}
        </span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { useCountdown } from "../composables/useCountdown.js";

const props = defineProps({
  targetDate: {
    type: [String, Date],
    default: "",
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

const { days, hours, minutes, seconds, isExpired } = props.targetDate
  ? useCountdown(props.targetDate)
  : {
      days: { value: 0 },
      hours: { value: 0 },
      minutes: { value: 0 },
      seconds: { value: 0 },
      isExpired: { value: true },
    };

const countdownUnits = computed(() => [
  { label: "Hari", value: days.value },
  { label: "Jam", value: hours.value },
  { label: "Menit", value: minutes.value },
  { label: "Detik", value: seconds.value },
]);
</script>
