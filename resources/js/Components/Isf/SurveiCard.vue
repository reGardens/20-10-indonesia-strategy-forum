<script setup>
/**
 * SurveiCard.vue — Kartu tampilan ringkas laporan survei opini publik ISF & lembaga riset
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  IconChartBar,
  IconBuilding,
  IconCalendar,
  IconUsers,
  IconScale,
  IconArrowRight,
  IconFileText,
  IconMapPin,
  IconAward,
} from '@tabler/icons-vue'

const props = defineProps({
  survey: {
    type: Object,
    required: true,
  },
  theme: {
    type: String,
    default: 'light', // 'light' | 'dark'
    validator: (v) => ['light', 'dark'].includes(v),
  },
  featured: {
    type: Boolean,
    default: false,
  },
})

const topicColor = computed(() => {
  const map = {
    politik: 'bg-red-500/10 text-isf-red border-red-500/20',
    ekonomi: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    sosial: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    hukum: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  }
  return map[props.survey?.topic?.toLowerCase()] || 'bg-neutral-500/10 text-neutral-600 border-neutral-500/20'
})

const topicLabel = computed(() => {
  const map = {
    politik: 'Politik',
    ekonomi: 'Ekonomi',
    sosial: 'Sosial',
    hukum: 'Hukum',
  }
  return map[props.survey?.topic?.toLowerCase()] || props.survey?.topic || 'Isu Nasional'
})

const regionLabel = computed(() => {
  const map = {
    nasional: 'Nasional',
    jawa: 'Pulau Jawa',
    sumatera: 'Sumatera',
    kalimantan: 'Kalimantan',
    sulawesi: 'Sulawesi',
    bali_nusra: 'Bali & Nusra',
    maluku_papua: 'Maluku & Papua',
  }
  return map[props.survey?.region?.toLowerCase()] || props.survey?.region || 'Nasional'
})
</script>

<template>
  <div
    :class="[
      'group relative flex flex-col justify-between rounded-xl border transition-all duration-300 hover:shadow-lg overflow-hidden',
      theme === 'dark'
        ? 'bg-neutral-900 border-neutral-800 hover:border-neutral-700 text-neutral-100'
        : 'bg-white border-neutral-300 hover:border-neutral-400 text-neutral-900 shadow-xs',
      featured ? 'ring-2 ring-isf-red/30' : ''
    ]"
  >
    <div class="p-5 sm:p-6">
      <!-- Top Row: Badges & Institution -->
      <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <!-- Topic & Region Tags -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <span
            :class="[
              'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border font-mono',
              topicColor
            ]"
          >
            {{ topicLabel }}
          </span>

          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 border border-neutral-200 dark:border-neutral-700">
            <IconMapPin class="w-3 h-3 text-neutral-400 shrink-0" />
            {{ regionLabel }}
          </span>

          <span
            v-if="survey.is_isf"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-isf-red text-white uppercase tracking-wider"
          >
            <IconAward class="w-3 h-3 shrink-0" />
            Riset ISF
          </span>
        </div>

        <!-- Release Date -->
        <span class="text-[11px] font-mono text-neutral-400 shrink-0">
          {{ survey.survey_period || survey.release_date }}
        </span>
      </div>

      <!-- Institution / Lembaga Pelaksana -->
      <div class="flex items-center gap-1.5 text-xs text-neutral-500 font-space-grotesk font-semibold mb-2">
        <IconBuilding class="w-3.5 h-3.5 shrink-0 text-neutral-400" />
        <span class="truncate">{{ survey.institution }}</span>
      </div>

      <!-- Survey Title -->
      <h3
        :class="[
          'font-headline font-bold leading-snug line-clamp-2 mb-3 transition-colors group-hover:text-isf-red',
          featured ? 'text-xl sm:text-2xl text-white' : (theme === 'dark' ? 'text-lg text-white' : 'text-lg text-neutral-950')
        ]"
      >
        <RouterLink :to="`/survei/${survey.slug}`">
          {{ survey.title }}
        </RouterLink>
      </h3>

      <!-- Headline Metric Callout Box -->
      <div
        :class="[
          'p-3.5 rounded-lg mb-4 border transition-colors',
          theme === 'dark'
            ? 'bg-neutral-950/70 border-neutral-800 group-hover:border-neutral-700'
            : 'bg-[#FBF9F4] border-neutral-200 group-hover:border-neutral-300'
        ]"
      >
        <div class="flex items-baseline gap-2">
          <span class="font-headline font-black text-2xl sm:text-3xl text-isf-red tracking-tight">
            {{ survey.headline_stat_value }}
          </span>
          <span
            v-if="survey.headline_stat_comparison"
            class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 truncate"
          >
            {{ survey.headline_stat_comparison }}
          </span>
        </div>
        <p class="text-xs text-neutral-600 dark:text-neutral-400 line-clamp-2 mt-1 leading-relaxed">
          {{ survey.headline_stat_label }}
        </p>
      </div>

      <!-- Summary Excerpt -->
      <p class="text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 line-clamp-2 leading-relaxed mb-4">
        {{ survey.summary }}
      </p>

      <!-- Survey Meta Info Grid (Sample, Margin of Error, Method) -->
      <div class="grid grid-cols-2 gap-2 text-[11px] text-neutral-500 font-space-grotesk pt-3 border-t border-neutral-200 dark:border-neutral-800">
        <div class="flex items-center gap-1.5 truncate">
          <IconUsers class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
          <span>Sampel: <strong class="text-neutral-700 dark:text-neutral-300">{{ survey.sample_size ? `${survey.sample_size.toLocaleString('id-ID')} Rsp.` : '-' }}</strong></span>
        </div>
        <div class="flex items-center gap-1.5 truncate">
          <IconScale class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
          <span>MoE: <strong class="text-neutral-700 dark:text-neutral-300">{{ survey.margin_of_error || '-' }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Footer Action -->
    <div
      :class="[
        'px-5 py-3 border-t flex items-center justify-between text-xs font-semibold font-space-grotesk',
        theme === 'dark' ? 'border-neutral-800 bg-neutral-950/40' : 'border-neutral-200 bg-neutral-50/50'
      ]"
    >
      <span class="text-neutral-500 text-[11px]">
        {{ survey.pdf_path ? (survey.pdf_size || 'PDF Laporan') : 'Visualisasi Interaktif' }}
      </span>

      <RouterLink
        :to="`/survei/${survey.slug}`"
        class="inline-flex items-center gap-1.5 text-isf-red hover:underline transition-all"
        :aria-label="`Buka detail survei: ${survey.title}`"
      >
        <span>Lihat Hasil Survei</span>
        <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
      </RouterLink>
    </div>
  </div>
</template>

