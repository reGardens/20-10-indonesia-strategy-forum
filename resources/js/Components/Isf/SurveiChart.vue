<script setup>
/**
 * SurveiChart.vue — Komponen Visualisasi Data Survei Interaktif dengan filter segmen
 * (Agregat Nasional, Usia, Gender, Wilayah) serta Tabel Data Alternatif Aksesibel.
 */
import { ref, computed } from 'vue'
import {
  IconChartBar,
  IconTable,
  IconChevronDown,
  IconChevronUp,
  IconUsers,
  IconFilter,
} from '@tabler/icons-vue'

const props = defineProps({
  chartData: {
    type: Object,
    default: () => ({ segments: [] }),
  },
  surveyTitle: {
    type: String,
    default: 'Survei Opini Publik',
  },
  institution: {
    type: String,
    default: '',
  },
})

const segments = computed(() => {
  return props.chartData?.segments || []
})

// Active segment tab ID (defaults to 'overall' or first available)
const activeSegmentId = ref('overall')

// Accessible table toggle
const showTable = ref(false)

const activeSegment = computed(() => {
  if (!segments.value || segments.value.length === 0) return null
  const found = segments.value.find((s) => (s.id || s.key) === activeSegmentId.value)
  return found || segments.value[0]
})

// Palette warna konsisten untuk item grafik
const defaultColors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280']

function getColor(idx, explicitColor) {
  if (explicitColor) return explicitColor
  return defaultColors[idx % defaultColors.length]
}
</script>

<template>
  <div class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-7 shadow-xs">
    <!-- Header: Title & Segment Selector Pills -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-neutral-200 dark:border-neutral-800">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
          <IconChartBar class="w-4 h-4 shrink-0" />
          <span>Visualisasi Temuan Riset</span>
        </div>
        <h4 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 dark:text-white">
          {{ activeSegment?.name || activeSegment?.label || 'Distribusi Jawaban Responden' }}
        </h4>
        <p v-if="institution" class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
          Sumber Data: {{ institution }}
        </p>
      </div>

      <!-- Segment Filter Pills (Usia, Gender, Wilayah, Overall) -->
      <div v-if="segments.length > 1" class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
        <button
          v-for="seg in segments"
          :key="seg.id || seg.key"
          type="button"
          @click="activeSegmentId = seg.id || seg.key"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold font-space-grotesk transition-all whitespace-nowrap cursor-pointer',
            activeSegmentId === (seg.id || seg.key)
              ? 'bg-isf-navy text-white shadow-xs'
              : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'
          ]"
        >
          {{ seg.name || seg.label }}
        </button>
      </div>
    </div>

    <!-- Chart Body -->
    <div class="py-6">
      <!-- 1. Mode Bar Sederhana / Overall -->
      <div v-if="activeSegment?.items" class="space-y-4 max-w-2xl mx-auto">
        <div
          v-for="(item, idx) in activeSegment.items"
          :key="idx"
          class="space-y-1.5"
        >
          <div class="flex justify-between items-center text-xs sm:text-sm font-space-grotesk">
            <span class="font-medium text-neutral-800 dark:text-neutral-200 flex items-center gap-2">
              <span
                class="w-2.5 h-2.5 rounded-full shrink-0"
                :style="{ backgroundColor: getColor(idx, item.color) }"
              />
              {{ item.label }}
            </span>
            <span class="font-mono font-bold text-neutral-950 dark:text-white">
              {{ item.value }}%
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="w-full h-3 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden relative">
            <div
              class="h-full rounded-full transition-all duration-700 ease-out"
              :style="{
                width: `${Math.min(100, Math.max(0, item.value))}%`,
                backgroundColor: getColor(idx, item.color),
              }"
            />
          </div>
        </div>
      </div>

      <!-- 2. Mode Grouped Bar (Berdasarkan Kelompok: Usia, Gender, Wilayah) -->
      <div v-else-if="activeSegment?.groups" class="space-y-6">
        <div
          v-for="(grp, gIdx) in activeSegment.groups"
          :key="gIdx"
          class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800"
        >
          <div class="flex items-center gap-2 mb-3">
            <IconUsers class="w-4 h-4 text-isf-red shrink-0" />
            <h5 class="text-xs sm:text-sm font-bold font-space-grotesk text-neutral-900 dark:text-neutral-100 uppercase tracking-wide">
              {{ grp.name }}
            </h5>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="(item, iIdx) in grp.items"
              :key="iIdx"
              class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 p-3 rounded-md"
            >
              <div class="flex justify-between items-center text-xs mb-1.5 font-space-grotesk">
                <span class="text-neutral-600 dark:text-neutral-400 font-medium truncate">{{ item.label }}</span>
                <span class="font-mono font-bold text-neutral-950 dark:text-white">{{ item.value }}%</span>
              </div>
              <div class="w-full h-2 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{
                    width: `${Math.min(100, Math.max(0, item.value))}%`,
                    backgroundColor: getColor(iIdx, item.color),
                  }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Fallback No Data -->
      <div v-else class="text-center py-8 text-neutral-400 text-xs">
        Data grafik untuk segmen ini belum tersedia.
      </div>
    </div>

    <!-- ═══ ACCESSIBLE DATA TABLE TOGGLE ════════════════════════════════════ -->
    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
      <button
        type="button"
        @click="showTable = !showTable"
        :aria-expanded="showTable"
        aria-controls="isf-survey-accessible-table"
        class="inline-flex items-center gap-2 text-xs font-space-grotesk font-semibold text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white transition-colors cursor-pointer py-1"
      >
        <IconTable class="w-4 h-4 text-isf-red shrink-0" />
        <span>{{ showTable ? 'Sembunyikan Tabel Alternatif' : 'Tampilkan Tabel Alternatif (Untuk Aksesibilitas & Pembaca Layar)' }}</span>
        <component :is="showTable ? IconChevronUp : IconChevronDown" class="w-3.5 h-3.5 shrink-0" />
      </button>

      <!-- The Table -->
      <div
        v-show="showTable"
        id="isf-survey-accessible-table"
        class="mt-3 overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-800 bg-[#FBF9F4] dark:bg-neutral-950 p-2"
      >
        <table class="w-full text-left font-space-grotesk text-xs border-collapse">
          <caption class="sr-only">
            Tabel rekapitulasi data hasil survei: {{ surveyTitle }} - Segmen {{ activeSegment?.name }}
          </caption>
          <thead>
            <tr class="border-b border-neutral-300 dark:border-neutral-800 text-neutral-600 dark:text-neutral-400 bg-neutral-200/50 dark:bg-neutral-900/50 text-[11px] uppercase tracking-wider">
              <th scope="col" class="py-2.5 px-3 font-semibold">Kategori / Opsi Tanggapan</th>
              <th scope="col" class="py-2.5 px-3 font-semibold text-right">Persentase (%)</th>
              <th scope="col" class="py-2.5 px-3 font-semibold text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800/80">
            <!-- Table Rows for Overall -->
            <template v-if="activeSegment?.items">
              <tr v-for="(it, idx) in activeSegment.items" :key="idx" class="hover:bg-black/5 dark:hover:bg-white/5">
                <td class="py-2 px-3 font-medium text-neutral-800 dark:text-neutral-200">
                  {{ it.label }}
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-neutral-900 dark:text-white">
                  {{ it.value }}%
                </td>
                <td class="py-2 px-3 text-center text-[10px] text-neutral-500 font-mono">
                  Terbit
                </td>
              </tr>
            </template>

            <!-- Table Rows for Groups -->
            <template v-else-if="activeSegment?.groups">
              <template v-for="(grp, gIdx) in activeSegment.groups" :key="gIdx">
                <tr class="bg-neutral-200/30 dark:bg-neutral-800/40 font-bold text-neutral-900 dark:text-white">
                  <td colspan="3" class="py-2 px-3 uppercase tracking-wider text-[10px]">
                    {{ grp.name }}
                  </td>
                </tr>
                <tr v-for="(it, iIdx) in grp.items" :key="`${gIdx}-${iIdx}`" class="hover:bg-black/5 dark:hover:bg-white/5">
                  <td class="py-1.5 px-3 pl-6 text-neutral-700 dark:text-neutral-300">
                    {{ it.label }}
                  </td>
                  <td class="py-1.5 px-3 text-right font-mono font-bold text-neutral-900 dark:text-white">
                    {{ it.value }}%
                  </td>
                  <td class="py-1.5 px-3 text-center text-[10px] text-neutral-500 font-mono">
                    Terbit
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

