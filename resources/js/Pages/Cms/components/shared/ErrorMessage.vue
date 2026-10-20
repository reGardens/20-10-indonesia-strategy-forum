<template>
  <div
    class="rounded-lg border border-red-200 bg-red-50 p-4"
    role="alert"
    aria-live="assertive"
  >
    <div class="flex items-start gap-3">
      <span
        class="text-red-500 text-lg leading-none flex-shrink-0"
        aria-hidden="true"
        >⚠️</span
      >
      <div class="flex-1 min-w-0">
        <p class="text-sm text-red-700">{{ message }}</p>
        <button
          v-if="retryable"
          type="button"
          class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
          @click="$emit('retry')"
        >
          Coba Lagi
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    required: true,
  },
  retryable: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["retry"]);
</script>
