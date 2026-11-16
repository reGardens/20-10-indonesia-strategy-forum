<script setup>
/**
 * MonitoringKebijakan.vue — Widget Interaktif Pelacakan Program Pemerintah (Janji vs Realisasi).
 *
 * Menampilkan:
 * - Filter sektor: Ekonomi, Kesehatan, Pendidikan, Pertahanan, Lingkungan
 * - Filter status: Tercapai, Dalam Proses, Tertunda, Perlu Evaluasi
 * - Filter pencarian program
 * - Kartu program dengan progres bar %, komparasi Janji vs Realisasi
 * - Tanggal pembaruan & tautan sumber dokumen resmi
 * - Tabel alternatif yang aksesibel untuk pembaca layar
 */
import { ref, computed } from 'vue'
import {
  IconSearch,
  IconFilter,
  IconCheck,
  IconClock,
  IconAlertTriangle,
  IconExternalLink,
  IconTable,
  IconChevronDown,
  IconChevronUp,
} from '@tabler/icons-vue'

const props = defineProps({
  trackers: {
    type: Array,
    required: true,
  },
  isDark: {
    type: Boolean,
    default: false,
  },
})

const selectedSector = ref('semua')
const selectedStatus = ref('semua')
const searchQuery = ref('')
const showTable = ref(false)

const sectors = [
  { slug: 'semua', label: 'Semua Sektor' },
  { slug: 'ekonomi', label: 'Ekonomi & Fiskal' },
  { slug: 'kesehatan', label: 'Kesehatan' },
  { slug: 'pendidikan', label: 'Pendidikan' },
  { slug: 'pertahanan', label: 'Pertahanan' },
  { slug: 'lingkungan', label: 'Lingkungan' },
]

const statusOptions = [
  { slug: 'semua', label: 'Semua Status' },
  { slug: 'tercapai', label: 'Tercapai' },
  { slug: 'dalam_proses', label: 'Dalam Proses' },
  { slug: 'tertunda', label: 'Tertunda' },
  { slug: 'perlu_evaluasi', label: 'Perlu Evaluasi' },
]

const statusStyles = {
  tercapai: {
    label: 'Tercapai',
    badge: 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-400 border-emerald-600/30',
    bar: 'bg-emerald-600',
    icon: IconCheck,
  },
  dalam_proses: {
    label: 'Dalam Proses',
    badge: 'bg-blue-600/15 text-blue-700 dark:text-blue-400 border-blue-600/30',
    bar: 'bg-blue-600',
    icon: IconClock,
  },
  tertunda: {
    label: 'Tertunda',
    badge: 'bg-amber-600/15 text-amber-700 dark:text-amber-400 border-amber-600/30',
    bar: 'bg-amber-600',
    icon: IconAlertTriangle,
  },
  perlu_evaluasi: {
    label: 'Perlu Evaluasi',
    badge: 'bg-rose-600/15 text-rose-700 dark:text-rose-400 border-rose-600/30',
    bar: 'bg-rose-600',
    icon: IconAlertTriangle,
  },
}

function getStatusInfo(status) {
  return statusStyles[status] || {
    label: status || 'Dipantau',
    badge: 'bg-neutral-600/15 text-neutral-600 border-neutral-600/30',
    bar: 'bg-neutral-500',
    icon: IconClock,
  }
}

const sectorLabels = {
  ekonomi: 'Ekonomi & Fiskal',
  kesehatan: 'Kesehatan Masyarakat',
  pendidikan: 'Pendidikan & Riset',
  pertahanan: 'Pertahanan & Keamanan',
  lingkungan: 'Lingkungan & Energi',
}

const filteredTrackers = computed(() => {
  return props.trackers.filter((item) => {
    // Sector filter
    if (selectedSector.value !== 'semua' && item.sector?.toLowerCase() !== selectedSector.value) {
      return false
    }
    // Status filter
    if (selectedStatus.value !== 'semua' && item.status_label?.toLowerCase() !== selectedStatus.value) {
      return false
    }
    // Search query
    if (searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase()
      const matchName = item.program_name?.toLowerCase().includes(q)
      const matchPromise = item.promise?.toLowerCase().includes(q)
      const matchRealization = item.realization?.toLowerCase().includes(q)
      if (!matchName && !matchPromise && !matchRealization) {
        return false
      }
    }
    return true
  })
})
</script>

<template>
  <div class="isf-monitoring-kebijakan-widget">
    <!-- Widget Title Header -->
    <div class="mb-6 pb-4 border-b" :class="isDark ? 'border-neutral-800' : 'border-neutral-200'">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-2">
            <IconCheck class="w-3.5 h-3.5 shrink-0" />
            <span>Pelacakan Program Strategis Pemerintah</span>
          </div>
          <h3 class="font-headline text-xl sm:text-2xl font-bold tracking-tight" :class="isDark ? 'text-white' : 'text-neutral-900'">
            Monitoring Kebijakan Publik
          </h3>
          <p class="text-xs sm:text-sm mt-1" :class="isDark ? 'text-neutral-400' : 'text-neutral-600'">
            Pelacakan komparatif janji kampanye dan target RPJMN terhadap realisasi riil di lapangan serta indikator pencapaian resmi.
          </p>
        </div>
      </div>
    </div>

    <!-- Header Controls & Filters -->
    <div
      :class="[
        'p-4 sm:p-5 rounded-xl border mb-6 transition-all',
        isDark ? 'bg-neutral-900/90 border-neutral-800' : 'bg-white border-neutral-200/90 shadow-2xs'
      ]"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search Input -->
        <div class="relative flex-1 max-w-md">
          <IconSearch class="w-4 h-4 text-neutral-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari program strategis atau janji pemerintah..."
            :class="[
              'w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-isf-red',
              isDark
                ? 'bg-neutral-950 border-neutral-700 text-white placeholder-neutral-500'
                : 'bg-neutral-50 border-neutral-300 text-neutral-900 placeholder-neutral-400'
            ]"
          />
        </div>

        <!-- Filter Selects -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Status Filter -->
          <div class="flex items-center gap-1.5 text-xs">
            <span class="text-neutral-500 font-medium">Status:</span>
            <select
              v-model="selectedStatus"
              :class="[
                'px-3 py-1.5 rounded-lg border text-xs font-space-grotesk font-semibold cursor-pointer focus:outline-none focus:ring-2 focus:ring-isf-red',
                isDark ? 'bg-neutral-950 border-neutral-700 text-white' : 'bg-neutral-50 border-neutral-300 text-neutral-800'
              ]"
            >
              <option v-for="opt in statusOptions" :key="opt.slug" :value="opt.slug">
                {{ opt.label }}
              </option>
            </select>
          </div>

          <!-- Table View Toggle -->
          <button
            type="button"
            @click="showTable = !showTable"
            :class="[
              'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-space-grotesk font-semibold transition-colors cursor-pointer border',
              showTable
                ? 'bg-isf-red text-white border-isf-red'
                : (isDark ? 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:text-white' : 'bg-neutral-100 border-neutral-300 text-neutral-700 hover:bg-neutral-200')
            ]"
            :aria-expanded="showTable"
          >
            <IconTable class="w-3.5 h-3.5" />
            <span>{{ showTable ? 'Tutup Tabel' : 'Tabel Alternatif' }}</span>
          </button>
        </div>
      </div>

      <!-- Sector Pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800">
        <button
          v-for="s in sectors"
          :key="s.slug"
          type="button"
          @click="selectedSector = s.slug"
          :class="[
            'px-3 py-1 rounded-full text-xs font-space-grotesk font-bold whitespace-nowrap transition-all shrink-0 cursor-pointer',
            selectedSector === s.slug
              ? 'bg-isf-red text-white shadow-2xs'
              : (isDark ? 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700 hover:text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 hover:text-neutral-900')
          ]"
        >
          {{ s.label }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="filteredTrackers.length === 0"
      :class="[
        'text-center py-12 px-4 rounded-xl border border-dashed text-sm',
        isDark ? 'bg-neutral-900/50 border-neutral-800 text-neutral-400' : 'bg-white border-neutral-300 text-neutral-500'
      ]"
    >
      Tidak ada program kebijakan yang cocok dengan filter atau kata kunci pencarian.
    </div>

    <!-- Mode 1: Grid Cards (Default) -->
    <div
      v-else-if="!showTable"
      class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6"
    >
      <div
        v-for="item in filteredTrackers"
        :key="item.id"
        :class="[
          'rounded-xl border p-5 sm:p-6 flex flex-col justify-between transition-all duration-200 shadow-2xs hover:shadow-md',
          isDark
            ? 'bg-neutral-900/90 border-neutral-800 hover:border-neutral-700 text-neutral-100'
            : 'bg-white border-neutral-200/90 hover:border-neutral-300 text-neutral-900'
        ]"
      >
        <div>
          <!-- Header Card: Sektor & Status Badge -->
          <div class="flex items-center justify-between gap-3 mb-2.5">
            <span class="text-[11px] font-space-grotesk font-bold uppercase tracking-wider text-neutral-500">
              {{ sectorLabels[item.sector] || item.sector }}
            </span>

            <span
              :class="[
                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-space-grotesk font-bold border',
                getStatusInfo(item.status_label).badge
              ]"
            >
              <component :is="getStatusInfo(item.status_label).icon" class="w-3 h-3" />
              <span>{{ getStatusInfo(item.status_label).label }}</span>
            </span>
          </div>

          <!-- Program Title -->
          <h3
            :class="[
              'font-headline text-base sm:text-lg font-bold leading-snug tracking-tight mb-3',
              isDark ? 'text-white' : 'text-neutral-900'
            ]"
          >
            {{ item.program_name }}
          </h3>

          <!-- Progress Bar -->
          <div class="mb-4">
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="font-space-grotesk font-semibold text-neutral-500">Capaian Progres:</span>
              <span class="font-mono font-bold text-isf-red">{{ item.progress_percent }}%</span>
            </div>
            <div class="w-full bg-neutral-200 dark:bg-neutral-800 h-2 rounded-full overflow-hidden">
              <div
                :class="['h-full rounded-full transition-all duration-500', getStatusInfo(item.status_label).bar]"
                :style="{ width: `${Math.min(100, Math.max(0, item.progress_percent))}%` }"
              ></div>
            </div>
          </div>

          <!-- Structured Comparison: Janji vs Realisasi -->
          <div class="space-y-3 pt-3 border-t border-neutral-100 dark:border-neutral-800 text-xs sm:text-sm">
            <!-- Janji -->
            <div :class="['p-3 rounded-lg border', isDark ? 'bg-neutral-950/60 border-neutral-800' : 'bg-neutral-50/80 border-neutral-200/60']">
              <div class="flex items-center gap-1.5 font-space-grotesk font-bold text-neutral-500 uppercase tracking-wider text-[10px] mb-1">
                <span>🎯 Target / Janji Kebijakan:</span>
              </div>
              <p :class="isDark ? 'text-neutral-300' : 'text-neutral-700'" class="leading-relaxed">
                {{ item.promise }}
              </p>
            </div>

            <!-- Realisasi -->
            <div :class="['p-3 rounded-lg border', isDark ? 'bg-emerald-950/20 border-emerald-900/40' : 'bg-emerald-50/60 border-emerald-200/80']">
              <div class="flex items-center gap-1.5 font-space-grotesk font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider text-[10px] mb-1">
                <IconCheck class="w-3.5 h-3.5" />
                <span>Capaian & Realisasi Lapangan:</span>
              </div>
              <p :class="isDark ? 'text-neutral-200' : 'text-neutral-800'" class="leading-relaxed">
                {{ item.realization }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer Card -->
        <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800 flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-500">
          <span v-if="item.last_updated_formatted || item.last_updated_date">
            Pembaruan: {{ item.last_updated_formatted || item.last_updated_date }}
          </span>

          <a
            v-if="item.source_url"
            :href="item.source_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 text-isf-red font-medium hover:underline focus-visible:outline-none"
          >
            <span>{{ item.source_title || 'Sumber Resmi' }}</span>
            <IconExternalLink class="w-3 h-3" />
          </a>
          <span v-else-if="item.source_title" class="truncate max-w-[200px]" :title="item.source_title">
            Sumber: {{ item.source_title }}
          </span>
        </div>
      </div>
    </div>

    <!-- Mode 2: Accessible Alternative Table -->
    <div
      v-else
      :class="[
        'rounded-xl border overflow-x-auto isf-no-scrollbar',
        isDark ? 'bg-neutral-900 border-neutral-800' : 'bg-white border-neutral-200 shadow-2xs'
      ]"
    >
      <table class="w-full text-left text-xs sm:text-sm min-w-[700px]">
        <thead :class="[isDark ? 'bg-neutral-950 text-neutral-400' : 'bg-neutral-50 text-neutral-600', 'font-space-grotesk font-bold uppercase text-[11px] border-b border-neutral-200 dark:border-neutral-800']">
          <tr>
            <th class="p-3.5 pl-5">Program Strategis</th>
            <th class="p-3.5">Sektor</th>
            <th class="p-3.5">Target / Janji</th>
            <th class="p-3.5">Realisasi Lapangan</th>
            <th class="p-3.5 text-center">Progres</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5 pr-5">Pembaruan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
          <tr
            v-for="item in filteredTrackers"
            :key="item.id"
            :class="isDark ? 'hover:bg-neutral-800/50' : 'hover:bg-neutral-50/70'"
          >
            <td class="p-3.5 pl-5 font-semibold text-neutral-900 dark:text-white max-w-[200px]">
              {{ item.program_name }}
            </td>
            <td class="p-3.5 text-neutral-500 whitespace-nowrap">
              {{ sectorLabels[item.sector] || item.sector }}
            </td>
            <td class="p-3.5 text-neutral-600 dark:text-neutral-300 max-w-[240px]">
              {{ item.promise }}
            </td>
            <td class="p-3.5 text-neutral-600 dark:text-neutral-300 max-w-[240px]">
              {{ item.realization }}
            </td>
            <td class="p-3.5 text-center font-mono font-bold text-isf-red whitespace-nowrap">
              {{ item.progress_percent }}%
            </td>
            <td class="p-3.5 whitespace-nowrap">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                  getStatusInfo(item.status_label).badge
                ]"
              >
                {{ getStatusInfo(item.status_label).label }}
              </span>
            </td>
            <td class="p-3.5 pr-5 text-neutral-500 whitespace-nowrap">
              {{ item.last_updated_formatted || item.last_updated_date }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

