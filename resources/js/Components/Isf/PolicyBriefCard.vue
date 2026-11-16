<script setup>
/**
 * PolicyBriefCard.vue — Kartu Ringkasan Eksekutif Riset Kebijakan & Dokumen PDF.
 *
 * Menampilkan:
 * - Badge sektor (Ekonomi, Kesehatan, Pendidikan, Pertahanan, Lingkungan)
 * - Judul riset, nama penulis, afiliasi lembaga, tanggal publikasi
 * - Ringkasan eksekutif dengan kemampuan expand/collapse
 * - Poin rekomendasi kunci kebijakan
 * - Tombol unduh dokumen PDF resmi beserta keterangan ukuran file
 */
import { ref, computed } from 'vue'
import {
  IconDownload,
  IconFileText,
  IconChevronDown,
  IconChevronUp,
  IconBuilding,
  IconCalendar,
  IconCheck,
} from '@tabler/icons-vue'

const props = defineProps({
  brief: {
    type: Object,
    required: true,
  },
  isDark: {
    type: Boolean,
    default: false,
  },
})

const isExpanded = ref(false)

const sectorLabels = {
  ekonomi: { name: 'Ekonomi & Fiskal', bg: 'bg-blue-600/15 text-blue-700 dark:text-blue-300 border-blue-600/25' },
  kesehatan: { name: 'Kesehatan', bg: 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border-emerald-600/25' },
  pendidikan: { name: 'Pendidikan', bg: 'bg-amber-600/15 text-amber-700 dark:text-amber-300 border-amber-600/25' },
  pertahanan: { name: 'Pertahanan', bg: 'bg-slate-600/15 text-slate-700 dark:text-slate-300 border-slate-600/25' },
  lingkungan: { name: 'Lingkungan & Energi', bg: 'bg-teal-600/15 text-teal-700 dark:text-teal-300 border-teal-600/25' },
}

const sectorBadge = computed(() => {
  const key = props.brief.sector?.toLowerCase()
  return sectorLabels[key] || { name: props.brief.sector || 'Kebijakan', bg: 'bg-neutral-600/15 text-neutral-700 border-neutral-600/25' }
})

const recommendations = computed(() => {
  if (Array.isArray(props.brief.key_recommendations)) {
    return props.brief.key_recommendations
  }
  if (typeof props.brief.key_recommendations === 'string') {
    try {
      const parsed = JSON.parse(props.brief.key_recommendations)
      return Array.isArray(parsed) ? parsed : []
    } catch {
      return []
    }
  }
  return []
})

function toggleExpand() {
  isExpanded.value = !isExpanded.value
}
</script>

<template>
  <article
    :class="[
      'rounded-xl border transition-all duration-200 flex flex-col justify-between overflow-hidden self-start h-auto',
      isDark
        ? 'bg-neutral-900/90 border-neutral-800 text-neutral-100 hover:border-neutral-700 shadow-sm'
        : 'bg-white border-neutral-200/90 text-neutral-900 hover:border-neutral-300 shadow-xs hover:shadow-md'
    ]"
  >
    <!-- Header Card -->
    <div class="p-5 sm:p-6 pb-4">
      <div class="flex items-center justify-between gap-3 mb-3">
        <!-- Sector Tag -->
        <span
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-space-grotesk font-bold uppercase tracking-wider border',
            sectorBadge.bg
          ]"
        >
          {{ sectorBadge.name }}
        </span>

        <!-- PDF Badge -->
        <span
          class="inline-flex items-center gap-1.5 text-xs font-mono font-medium text-neutral-500"
          :title="`Ukuran dokumen: ${brief.pdf_size || 'PDF'}`"
        >
          <IconFileText class="w-3.5 h-3.5 text-isf-red" />
          <span>{{ brief.pdf_size || 'PDF' }}</span>
        </span>
      </div>

      <!-- Title -->
      <h3
        :class="[
          'font-headline text-lg sm:text-xl font-bold tracking-tight leading-snug',
          isDark ? 'text-white' : 'text-neutral-900'
        ]"
      >
        {{ brief.title }}
      </h3>

      <!-- Author & Metadata -->
      <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-neutral-500">
        <span class="font-medium text-neutral-700 dark:text-neutral-300">
          {{ brief.author }}
        </span>
        <span v-if="brief.institution" class="inline-flex items-center gap-1">
          <IconBuilding class="w-3.5 h-3.5 opacity-70" />
          <span>{{ brief.institution }}</span>
        </span>
        <span v-if="brief.published_at_formatted || brief.published_at" class="inline-flex items-center gap-1">
          <IconCalendar class="w-3.5 h-3.5 opacity-70" />
          <span>{{ brief.published_at_formatted || brief.published_at }}</span>
        </span>
      </div>

      <!-- Executive Summary -->
      <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800">
        <p
          :class="[
            'text-xs sm:text-sm leading-relaxed transition-all',
            isDark ? 'text-neutral-300' : 'text-neutral-600',
            !isExpanded ? 'line-clamp-3' : ''
          ]"
        >
          {{ brief.executive_summary }}
        </p>

        <!-- Key Recommendations (if expanded) -->
        <div v-if="isExpanded && recommendations.length > 0" class="mt-4 pt-3 border-t border-dashed border-neutral-200 dark:border-neutral-800">
          <h4 class="text-xs font-space-grotesk font-bold uppercase tracking-wider text-isf-red mb-2">
            Rekomendasi Kebijakan Utama:
          </h4>
          <ul class="space-y-1.5 text-xs sm:text-sm">
            <li
              v-for="(rec, idx) in recommendations"
              :key="idx"
              class="flex items-start gap-2"
            >
              <IconCheck class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
              <span :class="isDark ? 'text-neutral-300' : 'text-neutral-700'">{{ rec }}</span>
            </li>
          </ul>
        </div>

        <!-- Toggle Read Summary -->
        <button
          type="button"
          @click="toggleExpand"
          class="mt-2 inline-flex items-center gap-1 text-xs font-space-grotesk font-semibold text-isf-red hover:underline cursor-pointer"
        >
          <span>{{ isExpanded ? 'Sembunyikan Ringkasan' : 'Baca Ringkasan Eksekutif' }}</span>
          <IconChevronUp v-if="isExpanded" class="w-3.5 h-3.5" />
          <IconChevronDown v-else class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Card Action Footer: Download PDF Button -->
    <div
      :class="[
        'px-5 sm:px-6 py-3.5 border-t flex items-center justify-between gap-3 mt-auto',
        isDark ? 'bg-neutral-950/60 border-neutral-800' : 'bg-neutral-50/80 border-neutral-100'
      ]"
    >
      <span class="text-xs font-mono text-neutral-500">
        Format: PDF Resmi ISF
      </span>

      <a
        :href="brief.pdf_path || `/storage/policy-briefs/${brief.slug || 'policy-brief'}.pdf`"
        target="_blank"
        rel="noopener noreferrer"
        :download="`${brief.slug || 'policy-brief'}.pdf`"
        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-space-grotesk font-bold text-white bg-isf-red hover:bg-[#b50500] transition-colors shadow-2xs cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
      >
        <IconDownload class="w-3.5 h-3.5" />
        <span>Unduh Dokumen ({{ brief.pdf_size || 'PDF' }})</span>
      </a>
    </div>
  </article>
</template>

