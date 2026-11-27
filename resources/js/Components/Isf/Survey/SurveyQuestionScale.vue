<script setup>
import { computed } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [Number, String],
    default: null,
  },
  number: {
    type: Number,
    default: 1,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const min = computed(() => props.question.scale_min || 1)
const max = computed(() => props.question.scale_max || 10)
const minLabel = computed(() => props.question.scale_min_label || 'Sangat Tidak Setuju')
const maxLabel = computed(() => props.question.scale_max_label || 'Sangat Setuju')

const scaleValues = computed(() => {
  const arr = []
  for (let i = min.value; i <= max.value; i++) {
    arr.push(i)
  }
  return arr
})

function select(val) {
  emit('update:modelValue', val)
}
</script>

<template>
  <div
    class="rounded-2xl border p-5 sm:p-7 transition-all duration-200"
    :class="[
      error
        ? 'border-red-400 bg-red-50/20 dark:border-red-500/40 dark:bg-red-950/10'
        : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 shadow-xs'
    ]"
  >
    <!-- Header Pertanyaan -->
    <div class="flex items-start gap-3">
      <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold text-white dark:bg-neutral-800">
        {{ number }}
      </span>
      <div class="flex-1 min-w-0">
        <h3 class="font-heading text-lg font-bold text-neutral-900 dark:text-neutral-100 leading-snug">
          {{ question.question }}
          <span v-if="question.required" class="text-isf-red ml-1" title="Pertanyaan Wajib">*</span>
        </h3>

        <!-- Konteks / Intro Editorial ISF (Pembeda dari polling biasa) -->
        <div v-if="question.context" class="mt-2.5 rounded-xl border-l-3 border-isf-red bg-neutral-50 dark:bg-neutral-800/60 p-3.5 text-sm text-neutral-700 dark:text-neutral-300 leading-relaxed font-body">
          <p class="font-ui text-xs font-semibold text-isf-red uppercase tracking-wider mb-1">Konteks Analisis ISF</p>
          <p>{{ question.context }}</p>
        </div>
      </div>
    </div>

    <!-- Skala Linier 1-10 Interaktif -->
    <div class="mt-6 pt-4 border-t border-neutral-100 dark:border-neutral-800">
      <div class="flex items-center justify-between text-xs font-ui font-medium text-neutral-600 dark:text-neutral-400 mb-3 px-1">
        <span class="flex items-center gap-1.5">
          <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
          1 = {{ minLabel }}
        </span>
        <span class="flex items-center gap-1.5">
          10 = {{ maxLabel }}
          <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
        </span>
      </div>

      <!-- Tombol Skala -->
      <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
        <button
          v-for="val in scaleValues"
          :key="val"
          type="button"
          @click="select(val)"
          class="group relative flex flex-col items-center justify-center rounded-xl border py-3 text-sm font-bold transition-all focus:outline-none focus:ring-2 focus:ring-isf-red focus:ring-offset-2"
          :class="[
            modelValue === val
              ? 'border-isf-red bg-isf-red text-white shadow-md scale-105'
              : 'border-neutral-200 bg-neutral-50/70 text-neutral-800 hover:border-neutral-300 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700'
          ]"
        >
          <span>{{ val }}</span>
          <span
            class="text-[9px] font-normal uppercase tracking-tighter opacity-70"
            v-if="val === 1 || val === 10"
          >
            {{ val === 1 ? 'Min' : 'Max' }}
          </span>
        </button>
      </div>

      <!-- Keterangan Pilihan Saat Ini -->
      <div v-if="modelValue !== null && modelValue !== undefined" class="mt-3.5 text-center text-xs font-ui text-neutral-600 dark:text-neutral-400">
        Pilihan Anda: <strong class="text-isf-red font-bold text-sm">{{ modelValue }}</strong> dari 10
        <span class="text-neutral-500">
          ({{ modelValue <= 3 ? minLabel : modelValue >= 8 ? maxLabel : 'Netral / Cukup Setuju' }})
        </span>
      </div>

      <!-- Pesan Error Validasi -->
      <p v-if="error" class="mt-3 text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <circle cx="12" cy="12" r="9" stroke-width="2"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
        </svg>
        {{ error }}
      </p>
    </div>
  </div>
</template>
