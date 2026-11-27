<script setup>
import { computed } from 'vue'
import { IconClock, IconCheck, IconDeviceFloppy } from '@tabler/icons-vue'

const props = defineProps({
  currentStep: {
    type: Number,
    default: 1,
  },
  totalSteps: {
    type: Number,
    default: 2,
  },
  estimatedMinutes: {
    type: Number,
    default: 5,
  },
  progress: {
    type: Number,
    default: 0,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
  lastSavedAt: {
    type: String,
    default: '',
  },
})

const clampedProgress = computed(() => {
  return Math.min(100, Math.max(0, props.progress))
})
</script>

<template>
  <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 sm:p-5 shadow-xs transition-all">
    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800 px-3 py-1 font-ui text-xs font-semibold text-neutral-700 dark:text-neutral-300">
          <IconClock class="h-3.5 w-3.5 text-isf-red" />
          Estimasi: {{ estimatedMinutes }} Menit
        </span>
        <span class="font-ui text-xs text-neutral-500 dark:text-neutral-400">
          Langkah {{ currentStep }} dari {{ totalSteps }}
        </span>
      </div>

      <div class="flex items-center gap-2 text-xs">
        <span v-if="isSaving" class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
          <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
          Menyimpan draf...
        </span>
        <span v-else-if="lastSavedAt" class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
          <IconCheck class="h-3.5 w-3.5" />
          Draf tersimpan ({{ lastSavedAt }})
        </span>
        <span v-else class="text-neutral-500 dark:text-neutral-400">
          Auto-save aktif
        </span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="mt-3.5">
      <div class="flex items-center justify-between text-xs font-ui text-neutral-500 dark:text-neutral-400 mb-1.5">
        <span>Kelengkapan Pengisian</span>
        <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ clampedProgress }}%</span>
      </div>
      <div class="h-2.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
        <div
          class="h-full rounded-full bg-isf-red transition-all duration-300 ease-out"
          :style="{ width: `${clampedProgress}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>
