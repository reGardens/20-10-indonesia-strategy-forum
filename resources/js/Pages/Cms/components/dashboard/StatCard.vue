<template>
  <div
    class="rounded-xl bg-white shadow-sm border border-gray-100 p-5 flex items-center gap-4"
  >
    <div
      :class="color"
      class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
      aria-hidden="true"
    >
      {{ icon }}
    </div>
    <div class="min-w-0">
      <p class="text-sm text-gray-500 truncate">{{ title }}</p>
      <p class="text-2xl font-bold text-gray-900">{{ value }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: "📊",
  },
  color: {
    type: String,
    default: "bg-blue-100",
  },
});
</script>
