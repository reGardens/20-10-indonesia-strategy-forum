<script setup>
const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number],
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

function select(opt) {
  emit('update:modelValue', opt)
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

    <!-- Opsi Pilihan Ganda -->
    <div class="mt-5 space-y-2.5 pt-4 border-t border-neutral-100 dark:border-neutral-800">
      <div
        v-for="(option, idx) in question.options"
        :key="idx"
        @click="select(option)"
        class="group flex cursor-pointer items-center justify-between rounded-xl border p-3.5 sm:p-4 text-sm font-medium transition-all"
        :class="[
          modelValue === option
            ? 'border-isf-red bg-red-50/40 dark:bg-red-950/20 text-neutral-900 dark:text-neutral-100 ring-1 ring-isf-red'
            : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 text-neutral-700 dark:text-neutral-300 hover:border-neutral-300 hover:bg-neutral-100/60 dark:hover:bg-neutral-800'
        ]"
      >
        <div class="flex items-center gap-3">
          <span
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-all"
            :class="[
              modelValue === option
                ? 'border-isf-red bg-isf-red'
                : 'border-neutral-400 group-hover:border-neutral-500'
            ]"
          >
            <span v-if="modelValue === option" class="h-2 w-2 rounded-full bg-white"></span>
          </span>
          <span class="leading-normal">{{ option }}</span>
        </div>

        <span
          v-if="modelValue === option"
          class="font-ui text-xs font-semibold text-isf-red ml-2 shrink-0 hidden sm:inline"
        >
          Dipilih
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
