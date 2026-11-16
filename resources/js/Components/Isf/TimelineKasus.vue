<script setup>
import { computed, ref, watch } from 'vue'
import {
  IconClock,
  IconScale,
  IconExternalLink,
  IconCalendar,
  IconChevronRight,
  IconFileText,
  IconAlertCircle,
  IconCheck,
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

// Stage badge color helper
function getStageBadgeClass(tahap) {
  const t = (tahap || '').toLowerCase()
  if (t.includes('putusan') || t.includes('vonis') || t.includes('inkracht')) {
    return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
  }
  if (t.includes('sidang') || t.includes('tuntutan') || t.includes('dakwaan')) {
    return 'bg-amber-500/15 text-amber-400 border-amber-500/30'
  }
  if (t.includes('tersangka') || t.includes('penyidikan') || t.includes('audit')) {
    return 'bg-rose-500/15 text-rose-400 border-rose-500/30'
  }
  return 'bg-blue-500/15 text-blue-400 border-blue-500/30'
}
</script>

<template>
  <div
    class="rounded-xl border transition-colors overflow-hidden"
    :class="[
      isDark
        ? 'bg-[#0B1220] border-neutral-800 text-white'
        : 'bg-white border-neutral-200/80 text-neutral-900 shadow-xs'
    ]"
  >
    <!-- Case Header & Case Selector Tabs -->
    <div class="p-4 sm:p-6 border-b" :class="isDark ? 'border-neutral-800/80' : 'border-neutral-200/80'">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-isf-red/10 text-isf-red border border-isf-red/20 mb-2">
            <IconClock class="w-3.5 h-3.5 shrink-0" />
            <span>Alur Peristiwa Yuridis</span>
          </div>
          <h3 class="font-headline text-xl sm:text-2xl font-bold tracking-tight">
            Timeline Kasus Hukum Besar
          </h3>
          <p class="text-xs sm:text-sm mt-1" :class="isDark ? 'text-neutral-400' : 'text-neutral-600'">
            Rekam jejak kronologis perkara peradilan, pengujian konstitusi, dan penegakan hukum berdampak luas.
          </p>
        </div>

        <!-- Case Switcher (if multiple cases) -->
        <div v-if="timelines.length > 1" class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar shrink-0 pb-1">
          <button
            v-for="tc in timelines"
            :key="tc.id"
            type="button"
            @click="activeTimelineId = tc.id"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-space-grotesk font-semibold transition-all shrink-0 max-w-[220px] truncate cursor-pointer',
              activeTimelineId === tc.id
                ? 'bg-isf-red text-white shadow-xs'
                : isDark
                  ? 'bg-neutral-900 text-neutral-400 hover:text-white hover:bg-neutral-800 border border-neutral-800'
                  : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200'
            ]"
            :title="tc.judul_kasus"
          >
            {{ tc.kategori_kasus || 'Kasus' }}: {{ tc.judul_kasus }}
          </button>
        </div>
      </div>
    </div>

    <!-- Active Case Summary Box -->
    <div v-if="activeCase" class="px-4 sm:px-6 py-4 border-b font-space-grotesk" :class="isDark ? 'bg-neutral-900/40 border-neutral-800/80' : 'bg-neutral-50 border-neutral-200/60'">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-1">
            <span v-if="activeCase.kategori_kasus" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-800 text-neutral-300 border border-neutral-700">
              {{ activeCase.kategori_kasus }}
            </span>
            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-400">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
              {{ activeCase.status_kasus || 'Proses Berjalan' }}
            </span>
          </div>
          <h4 class="font-headline text-lg sm:text-xl font-bold leading-snug" :class="isDark ? 'text-white' : 'text-neutral-900'">
            {{ activeCase.judul_kasus }}
          </h4>
          <p v-if="activeCase.deskripsi" class="text-xs sm:text-sm mt-1 leading-relaxed" :class="isDark ? 'text-neutral-300' : 'text-neutral-600'">
            {{ activeCase.deskripsi }}
          </p>
        </div>
      </div>
    </div>

    <!-- Timeline Chronology Body -->
    <div v-if="activeCase" class="p-4 sm:p-6 lg:p-8">
      <div v-if="!activeCase.events || activeCase.events.length === 0" class="py-12 text-center text-xs text-neutral-400 font-space-grotesk">
        Belum ada peristiwa kronologi yang dicatat untuk kasus ini.
      </div>

      <!-- Vertical Timeline Path -->
      <div v-else class="relative border-l-2 ml-3 sm:ml-5 space-y-6 sm:space-y-8 font-space-grotesk" :class="isDark ? 'border-neutral-800' : 'border-neutral-200'">
        <div
          v-for="(event, idx) in activeCase.events"
          :key="event.id || idx"
          class="relative pl-6 sm:pl-8 group"
        >
          <!-- Bullet Node on Line -->
          <div
            class="absolute -left-[9px] top-1 w-4 h-4 rounded-full border-2 transition-transform duration-200 group-hover:scale-125"
            :class="[
              idx === activeCase.events.length - 1
                ? 'bg-isf-red border-white ring-4 ring-isf-red/20'
                : isDark
                  ? 'bg-neutral-900 border-neutral-600'
                  : 'bg-white border-neutral-400'
            ]"
          ></div>

          <!-- Event Card -->
          <div
            class="rounded-lg border p-3.5 sm:p-5 transition-all"
            :class="[
              isDark
                ? 'bg-neutral-900/60 border-neutral-800 hover:border-neutral-700 hover:bg-neutral-900'
                : 'bg-white border-neutral-200/90 hover:border-neutral-300 shadow-2xs'
            ]"
          >
            <!-- Date & Stage Header -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b" :class="isDark ? 'border-neutral-800/80' : 'border-neutral-100'">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-mono font-bold" :class="isDark ? 'text-neutral-400' : 'text-neutral-500'">
                  <IconCalendar class="w-3.5 h-3.5" />
                  {{ event.tanggal_formatted || event.tanggal }}
                </span>
              </div>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                :class="getStageBadgeClass(event.tahap)"
              >
                {{ event.tahap }}
              </span>
            </div>

            <!-- Title & Description -->
            <div class="mt-2.5">
              <h5 class="font-bold text-sm sm:text-base leading-snug tracking-tight" :class="isDark ? 'text-white' : 'text-neutral-900'">
                {{ event.judul }}
              </h5>
              <p v-if="event.deskripsi" class="text-xs sm:text-sm mt-1.5 leading-relaxed" :class="isDark ? 'text-neutral-300' : 'text-neutral-600'">
                {{ event.deskripsi }}
              </p>
            </div>

            <!-- Source Link Footer -->
            <div v-if="event.sumber_tautan" class="mt-3 pt-2 flex items-center justify-end">
              <a
                :href="event.sumber_tautan"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-xs font-semibold text-isf-red hover:underline transition-colors"
              >
                <IconFileText class="w-3.5 h-3.5" />
                <span>Dokumen / Rujukan Resmi</span>
                <IconExternalLink class="w-3 h-3 ml-0.5 opacity-70" />
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

