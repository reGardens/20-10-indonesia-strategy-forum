<script setup>
import { computed } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: String,
    default: '',
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

const length = computed(() => (props.modelValue || '').length)

function onInput(e) {
  emit('update:modelValue', e.target.value)
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

        <!-- Konteks / Intro Editorial ISF -->
        <div v-if="question.context" class="mt-2.5 rounded-xl border-l-3 border-isf-red bg-neutral-50 dark:bg-neutral-800/60 p-3.5 text-sm text-neutral-700 dark:text-neutral-300 leading-relaxed font-body">
          <p class="font-ui text-xs font-semibold text-isf-red uppercase tracking-wider mb-1">Konteks Analisis ISF</p>
          <p>{{ question.context }}</p>
        </div>
      </div>
    </div>

    <!-- Text Area Input -->
    <div class="mt-5 pt-4 border-t border-neutral-100 dark:border-neutral-800">
      <div class="relative">
        <textarea
          :value="modelValue"
          @input="onInput"
          rows="4"
          :placeholder="question.placeholder || 'Tuliskan tanggapan, analisis, atau usulan Anda di sini...'"
          class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-800/60 p-3.5 text-sm text-neutral-900 dark:text-neutral-100 placeholder:text-neutral-400 focus:border-isf-red focus:bg-white dark:focus:bg-neutral-900 focus:outline-none focus:ring-1 focus:ring-isf-red transition-all"
        ></textarea>
        <div class="mt-2 flex items-center justify-between text-xs font-ui text-neutral-500 dark:text-neutral-400 px-1">
          <span>Format: Teks Bebas</span>
          <span>{{ length }} karakter</span>
        </div>
      </div>

      <!-- Pesan Error Validasi -->
      <p v-if="error" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <circle cx="12" cy="12" r="9" stroke-width="2"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
        </svg>
        {{ error }}
      </p>
    </div>
  </div>
</template>
