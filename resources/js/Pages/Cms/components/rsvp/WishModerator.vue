<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      :class="[
        'rounded-full px-4 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
        modelValue === tab.value
          ? 'bg-emerald-600 text-white'
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
      ]"
      @click="$emit('update:modelValue', tab.value)"
    >
      {{ tab.label }}
      <span
        v-if="tab.count !== undefined"
        :class="[
          'ml-1.5 inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-xs font-medium',
          modelValue === tab.value
            ? 'bg-emerald-500 text-white'
            : 'bg-gray-200 text-gray-600',
        ]"
      >
        {{ tab.count }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  modelValue: {
    type: String,
    default: "all",
  },
  wishes: {
    type: Array,
    default: () => [],
  },
});

defineEmits(["update:modelValue"]);

const tabs = computed(() => [
  { label: "Semua", value: "all", count: props.wishes.length },
  {
    label: "Disetujui",
    value: "approved",
    count: props.wishes.filter((w) => w.status === "approved").length,
  },
  {
    label: "Disembunyikan",
    value: "hidden",
    count: props.wishes.filter((w) => w.status === "hidden").length,
  },
  {
    label: "Menunggu",
    value: "pending",
    count: props.wishes.filter((w) => w.status === "pending").length,
  },
]);
</script>
