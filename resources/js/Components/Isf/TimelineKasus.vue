<script setup>
/**
 * TimelineKasus.vue — Infografis Interaktif Alur Kasus Hukum Besar
 * Menggunakan konsep timeline modern & minimalis bergaya shadcn UI.
 *
 * Fitur:
 * - Minimalist hairline vertical track dengan node dot indicator.
 * - Pulsing radar indicator pada peristiwa terkini.
 * - Chip status & tahapan peradilan berbasis color coding.
 * - Responsif sempurna di mobile, tablet, dan desktop tanpa clipping.
 * - Switcher kasus jika terdapat lebih dari satu perkara.
 */
import { computed, ref, watch } from 'vue'
import {
  IconCalendar,
  IconFileText,
  IconExternalLink,
} from '@tabler/icons-vue'

const props = defineProps({
  /** Array of case timelines: { id, judul_kasus, slug, kategori_kasus, deskripsi, status_kasus, gambar, events: [...] } */
  timelines: {
    type: Array,
    default: () => [],
  },
  /** Dark mode container styling */
  isDark: {
    type: Boolean,
    default: true,
  },
})

// Active selected case timeline
const activeTimelineId = ref(props.timelines[0]?.id || null)

watch(
  () => props.timelines,
  (newVal) => {
    if (newVal && newVal.length > 0 && !activeTimelineId.value) {
      activeTimelineId.value = newVal[0].id
    }
  },
  { immediate: true }
)

const activeCase = computed(() => {
  return props.timelines.find((t) => t.id === activeTimelineId.value) || props.timelines[0] || null
})

// Stage badge color helper (shadcn-style outline pills)
function getStageBadgeClass(tahap) {
  const t = (tahap || '').toLowerCase()
  if (t.includes('putusan') || t.includes('vonis') || t.includes('inkracht')) {
    return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
  }
  if (t.includes('sidang') || t.includes('tuntutan') || t.includes('dakwaan')) {
    return 'bg-amber-500/10 text-amber-400 border-amber-500/30'
  }
  if (t.includes('tersangka') || t.includes('penyidikan') || t.includes('audit')) {
    return 'bg-rose-500/10 text-rose-400 border-rose-500/30'
  }
  return 'bg-blue-500/10 text-blue-400 border-blue-500/30'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Screen-reader accessible title for semantic & test compatibility -->
    <div class="sr-only">
      <h3>Timeline Kasus Hukum Besar</h3>
    </div>

    <!-- 1. Case Switcher Tabs (jika ada lebih dari 1 kasus) -->
    <div v-if="timelines.length > 1" class="flex items-center gap-2 overflow-x-auto isf-no-scrollbar pb-1">
      <button
        v-for="tc in timelines"
        :key="tc.id"
        type="button"
        @click="activeTimelineId = tc.id"
        :class="[
          'px-3.5 py-1.5 rounded-full text-xs font-space-grotesk font-semibold whitespace-nowrap transition-all shrink-0 cursor-pointer',
          activeTimelineId === tc.id
            ? 'bg-isf-red text-white shadow-xs'
            : isDark
              ? 'bg-white/10 text-neutral-300 hover:bg-white/15 hover:text-white'
              : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200'
        ]"
      >
        {{ tc.kategori_kasus ? `${tc.kategori_kasus}: ` : '' }}{{ tc.judul_kasus }}
      </button>
    </div>

    <!-- 2. Active Case Summary Header -->
    <div
      v-if="activeCase"
      class="rounded-lg p-4 sm:p-5 border transition-colors font-space-grotesk"
      :class="isDark ? 'bg-neutral-900/50 border-neutral-800' : 'bg-neutral-50 border-neutral-200'"
    >
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-2">
        <div class="flex items-center gap-2 flex-wrap">
          <span
            v-if="activeCase.kategori_kasus"
            class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-white/10 text-neutral-300 border border-white/15"
          >
            {{ activeCase.kategori_kasus }}
          </span>
          <span class="inline-flex items-center gap-1.5 text-xs font-mono font-bold text-amber-400">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            {{ activeCase.status_kasus || 'Proses Berjalan' }}
          </span>
        </div>
        <span class="text-[11px] font-mono text-neutral-400">
          {{ activeCase.events?.length || 0 }} Rangkaian Peristiwa
        </span>
      </div>

      <h3
        class="font-headline text-base sm:text-lg lg:text-xl font-bold leading-snug tracking-tight"
        :class="isDark ? 'text-white' : 'text-neutral-900'"
      >
        {{ activeCase.judul_kasus }}
      </h3>
      <p
        v-if="activeCase.deskripsi"
        class="text-xs sm:text-sm mt-1.5 leading-relaxed"
        :class="isDark ? 'text-neutral-300' : 'text-neutral-600'"
      >
        {{ activeCase.deskripsi }}
      </p>
    </div>

    <!-- 3. Shadcn Style Vertical Timeline Track -->
    <div v-if="activeCase">
      <div v-if="!activeCase.events || activeCase.events.length === 0" class="py-10 text-center text-xs text-neutral-400 font-space-grotesk">
        Belum ada catatan peristiwa kronologis untuk perkara ini.
      </div>

      <div
        v-else
        class="relative border-l border-neutral-700/80 ml-2.5 sm:ml-4 pl-4 sm:pl-7 space-y-6 sm:space-y-8 font-space-grotesk py-2"
      >
        <div
          v-for="(event, idx) in activeCase.events"
          :key="event.id || idx"
          class="relative group"
        >
          <!-- Shadcn Timeline Node Marker -->
          <div class="absolute -left-[23px] sm:-left-[35px] top-1 flex items-center justify-center">
            <!-- Latest Event Pulse Marker -->
            <span
              v-if="idx === 0"
              class="relative flex h-3 w-3 sm:h-3.5 sm:w-3.5"
            >
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-isf-red opacity-75"></span>
              <span
                class="relative inline-flex rounded-full h-3 w-3 sm:h-3.5 sm:w-3.5 bg-isf-red ring-4"
                :class="isDark ? 'ring-[#0B1220]' : 'ring-white'"
              ></span>
            </span>
            <!-- Standard Event Dot -->
            <span
              v-else
              class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full border-2 transition-transform duration-200 group-hover:scale-125"
              :class="isDark ? 'bg-neutral-800 border-neutral-500 ring-4 ring-[#0B1220]' : 'bg-white border-neutral-400 ring-4 ring-white'"
            ></span>
          </div>

          <!-- Event Body Content (Sleek, direct, non-nested) -->
          <div class="space-y-1.5">
            <!-- Date & Stage Tag -->
            <div class="flex flex-wrap items-center gap-2">
              <time
                class="text-xs font-mono font-medium flex items-center gap-1"
                :class="isDark ? 'text-neutral-400' : 'text-neutral-500'"
              >
                <IconCalendar class="w-3.5 h-3.5 text-neutral-500 shrink-0" />
                <span>{{ event.tanggal_formatted || event.tanggal }}</span>
              </time>
              <span
                v-if="event.tahap"
                class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border"
                :class="getStageBadgeClass(event.tahap)"
              >
                {{ event.tahap }}
              </span>
            </div>

            <!-- Event Title -->
            <h4
              class="font-space-grotesk font-bold text-sm sm:text-base leading-snug tracking-tight"
              :class="isDark ? 'text-white group-hover:text-red-400 transition-colors' : 'text-neutral-900 group-hover:text-isf-red transition-colors'"
            >
              {{ event.judul }}
            </h4>

            <!-- Event Description -->
            <p
              v-if="event.deskripsi"
              class="text-xs sm:text-sm font-space-grotesk leading-relaxed"
              :class="isDark ? 'text-neutral-300' : 'text-neutral-600'"
            >
              {{ event.deskripsi }}
            </p>

            <!-- Document / External Source Link -->
            <div v-if="event.sumber_tautan" class="pt-1">
              <a
                :href="event.sumber_tautan"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-isf-red hover:underline transition-colors"
              >
                <IconFileText class="w-3.5 h-3.5" />
                <span>Dokumen / Rujukan Resmi</span>
                <IconExternalLink class="w-3 h-3 opacity-70" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.isf-no-scrollbar::-webkit-scrollbar {
  display: none;
}
.isf-no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
