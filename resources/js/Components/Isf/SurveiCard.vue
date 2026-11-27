<script setup>
/**
 * SurveiCard.vue — Kartu tampilan ringkas survei opini publik ISF
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  IconClock,
  IconArrowRight,
  IconCircleCheck,
  IconBuilding,
} from '@tabler/icons-vue'

const props = defineProps({
  survey: {
    type: Object,
    required: true,
  },
  theme: {
    type: String,
    default: 'light',
    validator: (v) => ['light', 'dark'].includes(v),
  },
  featured: {
    type: Boolean,
    default: false,
  },
})

const topicLabel = computed(() => {
  const map = {
    politik: 'Politik',
    ekonomi: 'Ekonomi',
    sosial: 'Sosial',
    hukum: 'Hukum',
  }
  return map[props.survey?.topic?.toLowerCase()] || props.survey?.topic || 'Umum'
})

const targetRoute = computed(() => {
  if (!props.survey?.is_member_active) {
    return `/survei/${props.survey.slug}`
  }
  if (props.survey?.member_status === 'selesai') {
    return `/survei/${props.survey.slug}/selesai`
  }
  return `/survei/${props.survey.slug}/isi`
})
</script>

<template>
  <div
    class="group flex flex-col justify-between rounded-lg border border-neutral-300 bg-white text-neutral-900 shadow-xs hover:border-neutral-400 hover:shadow-md transition-all overflow-hidden"
  >
    <div class="p-5 space-y-3">
      <!-- Baris Status & Topik -->
      <div class="flex items-center justify-between gap-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
          {{ topicLabel }}
        </span>

        <template v-if="survey.is_member_active">
          <span
            v-if="survey.member_status === 'selesai'"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-600 text-white"
          >
            <IconCircleCheck class="w-3.5 h-3.5" />
            Selesai
          </span>
          <span
            v-else-if="survey.member_status === 'draft'"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-amber-500 text-white"
          >
            <IconClock class="w-3.5 h-3.5" />
            Draft
          </span>
          <span
            v-else
            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-blue-600 text-white"
          >
            Belum Diisi
          </span>
        </template>
      </div>

      <!-- Judul Survei -->
      <h3 class="font-headline text-lg font-bold leading-snug text-neutral-950 group-hover:text-isf-red transition-colors line-clamp-2">
        <RouterLink :to="targetRoute">
          {{ survey.title }}
        </RouterLink>
      </h3>

      <!-- Ringkasan Singkat -->
      <p class="text-sm text-neutral-600 line-clamp-2 leading-relaxed">
        {{ survey.editorial_intro || survey.summary }}
      </p>

      <!-- Info Riset (hanya untuk laporan non-partisipatif / kompatibilitas) -->
      <div v-if="!survey.is_member_active && survey.headline_stat_value" class="pt-2 flex items-center justify-between text-xs text-neutral-500 border-t border-neutral-100">
        <span class="flex items-center gap-1 truncate">
          <IconBuilding class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
          {{ survey.institution || 'ISF Riset' }}
        </span>
        <span class="font-bold text-isf-red">
          {{ survey.headline_stat_value }} · {{ survey.sample_size?.toLocaleString('id-ID') }}
        </span>
      </div>
    </div>

    <!-- Footer Tombol Aksi -->
    <div class="px-5 py-3.5 border-t border-neutral-200 bg-neutral-50 flex items-center justify-between gap-3">
      <span class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-600">
        <IconClock class="w-4 h-4 text-neutral-400" />
        {{ survey.estimated_minutes || 5 }} Menit
      </span>

      <RouterLink
        :to="targetRoute"
        class="inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-xs font-bold transition-colors"
        :class="[
          survey.member_status === 'selesai'
            ? 'bg-emerald-600 text-white hover:bg-emerald-700'
            : survey.member_status === 'draft'
              ? 'bg-amber-500 text-white hover:bg-amber-600'
              : 'bg-isf-red text-white hover:bg-red-700'
        ]"
      >
        <span v-if="!survey.is_member_active">Lihat Survei</span>
        <span v-else-if="survey.member_status === 'selesai'">Lihat Hasil</span>
        <span v-else-if="survey.member_status === 'draft'">Lanjutkan</span>
        <span v-else>Isi Survei</span>
        <IconArrowRight class="w-3.5 h-3.5" />
      </RouterLink>
    </div>
  </div>
</template>
