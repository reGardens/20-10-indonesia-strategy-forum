<script setup>
/**
 * PolicyBriefCard.vue — Kartu Ringkasan Risalah Kebijakan & Dokumen Publik.
 *
 * Dibuat simpel terinspirasi standar peraturan.bpk.go.id:
 * - Title (Judul Risalah Kebijakan / Regulasi)
 * - Sub Title / Kicker Kategori (Ekonomi, Kesehatan, dsb)
 * - Tanggal publikasi / penetapan
 * - Baris bawah sejajar (between):
 *   - Kiri: Sumber / Afiliasi Lembaga
 *   - Kanan: CTA Unduh Dokumen (Tautan Google Drive PDF)
 */
import { computed } from 'vue'
import {
  IconDownload,
  IconBuilding,
  IconCalendar,
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

const sectorLabels = {
  ekonomi: { name: 'Ekonomi & Fiskal', bg: 'bg-blue-600/10 text-blue-700 border-blue-200' },
  kesehatan: { name: 'Kesehatan', bg: 'bg-emerald-600/10 text-emerald-700 border-emerald-200' },
  pendidikan: { name: 'Pendidikan', bg: 'bg-amber-600/10 text-amber-700 border-amber-200' },
  pertahanan: { name: 'Pertahanan', bg: 'bg-slate-600/10 text-slate-700 border-slate-200' },
  lingkungan: { name: 'Lingkungan & Energi', bg: 'bg-teal-600/10 text-teal-700 border-teal-200' },
}

const sectorBadge = computed(() => {
  const key = props.brief.sector?.toLowerCase()
  return sectorLabels[key] || { name: props.brief.sector || 'Kebijakan Publik', bg: 'bg-neutral-100 text-neutral-700 border-neutral-200' }
})
</script>

<template>
  <article
    :class="[
      'rounded-xl border transition-all duration-200 flex flex-col justify-between p-5 sm:p-6 shadow-2xs hover:shadow-md',
      isDark
        ? 'bg-neutral-900/90 border-neutral-800 text-neutral-100'
        : 'bg-white border-neutral-200/90 text-neutral-900 hover:border-neutral-300'
    ]"
  >
    <!-- Content Card: Title, Subtitle/Penulis, Tanggal -->
    <div>
      <!-- Title -->
      <h3
        :class="[
          'font-headline text-base sm:text-lg font-bold tracking-tight leading-snug line-clamp-2 group-hover:text-isf-red transition-colors',
          isDark ? 'text-white' : 'text-neutral-900'
        ]"
      >
        {{ brief.title }}
      </h3>

      <!-- Sub Title / Keterangan Penulis Singkat -->
      <p v-if="brief.author" class="text-xs text-neutral-500 font-space-grotesk mt-1 line-clamp-1">
        Penyusun: <strong :class="isDark ? 'text-neutral-300' : 'text-neutral-700'">{{ brief.author }}</strong>
      </p>

      <!-- Tanggal Terbit / Penetapan -->
      <div class="mt-2.5 flex items-center gap-1.5 text-xs text-neutral-500 font-mono">
        <IconCalendar class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
        <span>{{ brief.published_at_formatted || brief.published_at || '2026' }}</span>
      </div>
    </div>

    <!-- Bottom Row: Sumber (Kiri) Between CTA Unduh Dokumen (Kanan) -->
    <div
      :class="[
        'mt-4 pt-3.5 border-t flex items-center justify-between gap-3 text-xs',
        isDark ? 'border-neutral-800' : 'border-neutral-100'
      ]"
    >
      <!-- Sumber di Kiri -->
      <div class="flex items-center gap-1.5 min-w-0 flex-1 text-neutral-500">
        <IconBuilding class="w-3.5 h-3.5 shrink-0 opacity-70" />
        <span class="truncate text-[11px]" :title="brief.institution || 'Sekretariat Riset ISF'">
          Sumber: {{ brief.institution || 'Sekretariat Riset ISF' }}
        </span>
      </div>

      <!-- CTA Unduh Dokumen (Tautan Google Drive PDF) di Kanan -->
      <a
        :href="brief.pdf_path || '#'"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-space-grotesk font-bold text-white bg-isf-red hover:bg-[#b50500] transition-colors shrink-0 shadow-2xs cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
      >
        <IconDownload class="w-3.5 h-3.5" />
        <span>Unduh PDF</span>
      </a>
    </div>
  </article>
</template>
