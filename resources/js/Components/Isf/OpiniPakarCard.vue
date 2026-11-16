<script setup>
import {
  IconQuote,
  IconClock,
  IconCalendar,
  IconArrowRight,
  IconUser,
} from '@tabler/icons-vue'

defineProps({
  opinion: {
    type: Object,
    required: true,
  },
  isDark: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <article
    :class="[
      'rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-6 group relative overflow-hidden',
      isDark
        ? 'bg-[#0B1220] border-neutral-800 text-white hover:border-neutral-700'
        : 'bg-[#FBF9F4] border-neutral-200/80 text-neutral-900 hover:border-neutral-300 shadow-2xs'
    ]"
  >
    <!-- Background Quote Watermark -->
    <IconQuote
      class="w-20 h-20 absolute -right-2 -bottom-2 pointer-events-none opacity-5 transition-transform group-hover:scale-110"
      :class="isDark ? 'text-white' : 'text-neutral-900'"
    />

    <!-- Header: Badge & Reading Time -->
    <div>
      <div class="flex items-center justify-between gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[10px] sm:text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-isf-red/10 text-isf-red border border-isf-red/20">
          <IconQuote class="w-3 h-3" />
          <span>Opini Pakar</span>
        </span>
        <span v-if="opinion.readingMinutes" class="inline-flex items-center gap-1 text-[11px] font-space-grotesk text-neutral-500 font-medium">
          <IconClock class="w-3 h-3" />
          {{ opinion.readingMinutes }} mnt
        </span>
      </div>

      <!-- Title -->
      <a
        :href="opinion.to || `/artikel/hukum/${opinion.slug}`"
        class="block font-headline text-lg sm:text-xl font-bold tracking-tight leading-snug group-hover:text-isf-red transition-colors"
        :class="isDark ? 'text-white' : 'text-neutral-900'"
      >
        {{ opinion.title }}
      </a>

      <!-- Summary -->
      <p
        v-if="opinion.summary"
        class="text-xs sm:text-sm mt-2 line-clamp-3 leading-relaxed font-space-grotesk"
        :class="isDark ? 'text-neutral-300' : 'text-neutral-600'"
      >
        {{ opinion.summary }}
      </p>
    </div>

    <!-- Author Profile Footer -->
    <div class="mt-5 pt-4 border-t flex items-center justify-between gap-3 font-space-grotesk" :class="isDark ? 'border-neutral-800' : 'border-neutral-200/60'">
      <div class="flex items-center gap-3 min-w-0">
        <!-- Author Photo / Avatar -->
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full overflow-hidden bg-neutral-200 shrink-0 border-2" :class="isDark ? 'border-neutral-700' : 'border-white shadow-xs'">
          <img
            v-if="opinion.expert_avatar"
            :src="opinion.expert_avatar"
            :alt="opinion.expert_name || 'Penulis Pakar'"
            class="w-full h-full object-cover"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-neutral-300 text-neutral-700 text-xs font-bold">
            {{ (opinion.expert_name || 'Pakar').substring(0, 2).toUpperCase() }}
          </div>
        </div>

        <div class="min-w-0">
          <div class="font-bold text-xs sm:text-sm truncate" :class="isDark ? 'text-white' : 'text-neutral-900'">
            {{ opinion.expert_name || 'Pakar Hukum' }}
          </div>
          <div class="text-[11px] truncate" :class="isDark ? 'text-neutral-400' : 'text-neutral-500'">
            {{ opinion.expert_title || 'Akademisi & Praktisi Hukum' }}
          </div>
        </div>
      </div>

      <!-- Arrow Action -->
      <a
        :href="opinion.to || `/artikel/hukum/${opinion.slug}`"
        class="p-2 rounded-full transition-colors shrink-0"
        :class="isDark ? 'bg-neutral-800 text-white group-hover:bg-isf-red' : 'bg-white text-neutral-800 shadow-2xs group-hover:bg-isf-red group-hover:text-white'"
        aria-label="Baca Opini Pakar Lengkap"
      >
        <IconArrowRight class="w-4 h-4" />
      </a>
    </div>
  </article>
</template>

