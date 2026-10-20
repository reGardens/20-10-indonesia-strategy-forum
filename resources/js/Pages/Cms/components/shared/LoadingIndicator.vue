<template>
  <div
    class="flex flex-col items-center justify-center gap-2"
    role="status"
    aria-live="polite"
  >
    <svg
      :class="spinnerClasses"
      class="animate-spin text-emerald-600"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    <span v-if="text" class="text-sm text-gray-500">{{ text }}</span>
    <span v-else class="sr-only">Memuat...</span>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  size: {
    type: String,
    default: "md",
    validator: (value) => ["sm", "md", "lg"].includes(value),
  },
  text: {
    type: String,
    default: "",
  },
});

const spinnerClasses = computed(() => {
  const sizes = {
    sm: "h-5 w-5",
    md: "h-8 w-8",
    lg: "h-12 w-12",
  };
  return sizes[props.size];
});
</script>
