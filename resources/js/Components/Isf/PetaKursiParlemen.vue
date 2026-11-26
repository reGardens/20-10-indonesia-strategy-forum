<script setup>
/**
 * PetaKursiParlemen.vue — Infografis Interaktif Peta Kursi Parlemen (DPR/DPD/MPR)
 * Menggunakan Apache ECharts (https://echarts.apache.org/en/index.html).
 *
 * Fitur:
 * - Hemicycle / Half-Doughnut Chart (180° - 360°) menggunakan Apache ECharts.
 * - Interactive hover & tooltip dengan formatting detail fraksi/partai.
 * - Stacked bar persentase perolehan kursi.
 * - Legenda interaktif dua arah (bidirectional highlight dengan chart ECharts).
 * - Aksesibilitas via keyboard dan ARIA support.
 * - Tabel data alternatif lengkap untuk screen reader.
 * - Switch tab lembaga (DPR, DPD, MPR) via API /api/isf/peta-kursi.
 */
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import * as echarts from 'echarts/core'
import { PieChart } from 'echarts/charts'
import {
  TooltipComponent,
  LegendComponent,
  AriaComponent
} from 'echarts/components'
import { SVGRenderer } from 'echarts/renderers'
import { getPetaKursi } from '@/api/services/isfPublicService'
import {
  IconBuildingBank,
  IconTable,
  IconChevronDown,
  IconChevronUp
} from '@tabler/icons-vue'

// Daftarkan modul ECharts yang dibutuhkan agar ukuran bundle ringan & optimal
echarts.use([
  PieChart,
  TooltipComponent,
  LegendComponent,
  AriaComponent,
  SVGRenderer,
])

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({}),
  },
  class: {
    type: String,
    default: '',
  },
})

const activeLembaga = ref(props.initialData?.lembaga || 'DPR')
const activePeriode = ref(props.initialData?.periode || '2024-2029')
const seatsData = ref(props.initialData?.items || [])
const totalKursi = ref(props.initialData?.totalKursi || 0)
const loading = ref(false)
const showTable = ref(false)
const activeParty = ref(null)

const chartContainerRef = ref(null)
let chartInstance = null
let resizeObserver = null

// Ambang batas mayoritas sederhana (50% + 1)
const thresholdMajority = computed(() => {
  return Math.floor(totalKursi.value / 2) + 1
})

function buildChartOption() {
  const items = seatsData.value || []
  const total = totalKursi.value || 1
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 640

  const chartData = items.map((item) => ({
    name: item.singkatan || item.partai,
    value: item.jumlah_kursi,
    itemStyle: {
      color: item.warna_partai,
      borderColor: '#0B1220',
      borderWidth: 2,
    },
    raw: item,
  }))

  return {
    backgroundColor: 'transparent',
    aria: {
      enabled: true,
      decal: { show: false },
      label: {
        description: `Diagram busur perolehan kursi parlemen ${activeLembaga.value} RI periode ${activePeriode.value}`,
      },
    },
    tooltip: {
      trigger: 'item',
      backgroundColor: '#0B1220',
      borderColor: '#374151',
      borderWidth: 1,
      padding: [8, 12],
      textStyle: {
        color: '#FFFFFF',
        fontFamily: "'Space Grotesk', sans-serif",
      },
      formatter: (params) => {
        const item = params.data?.raw
        if (!item) return ''
        const pct = item.persentase || ((item.jumlah_kursi / total) * 100).toFixed(2)
        return `
          <div style="font-family: 'Space Grotesk', sans-serif; min-width: 140px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
              <span style="display: inline-block; width: 10px; height: 10px; border-radius: 9999px; background-color: ${item.warna_partai};"></span>
              <strong style="color: #FFFFFF; font-size: 13px;">${item.partai} (${item.singkatan || item.partai})</strong>
            </div>
            <div style="color: #9CA3AF; font-size: 12px; margin-bottom: 2px;">
              Jumlah Kursi: <strong style="color: #F3F4F6;">${item.jumlah_kursi} kursi</strong>
            </div>
            <div style="color: #9CA3AF; font-size: 12px;">
              Porsi Parlemen: <strong style="color: #F59E0B;">${pct}%</strong>
            </div>
          </div>
        `
      },
    },
    series: [
      {
        name: `Kursi ${activeLembaga.value}`,
        type: 'pie',
        radius: isMobile ? ['58%', '85%'] : ['58%', '85%'],
        center: ['50%', '50%'],
        padAngle: 2,
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 4,
          borderColor: '#0B1220',
          borderWidth: 2,
        },
        label: {
          show: false,
        },
        emphasis: {
          scale: true,
          scaleSize: isMobile ? 4 : 6,
          itemStyle: {
            shadowBlur: 14,
            shadowOffsetX: 0,
            shadowColor: 'rgba(255, 255, 255, 0.45)',
            borderColor: '#FFFFFF',
            borderWidth: 2.5,
          },
        },
        data: chartData,
      },
    ],
  }
}

function updateChart() {
  if (!chartInstance) return
  chartInstance.setOption(buildChartOption(), true)
}

function initChart() {
  if (!chartContainerRef.value) return
  if (chartInstance) {
    chartInstance.dispose()
    chartInstance = null
  }

  const el = chartContainerRef.value
  const width = el.clientWidth || 320
  const height = el.clientHeight || 220

  chartInstance = echarts.init(el, null, {
    renderer: 'svg',
    width: width > 0 ? width : 320,
    height: height > 0 ? height : 220,
  })

  updateChart()

  chartInstance.on('mouseover', (params) => {
    if (params.data?.raw) {
      activeParty.value = params.data.raw
    }
  })

  chartInstance.on('mouseout', () => {
    activeParty.value = null
  })

  chartInstance.on('click', (params) => {
    if (params.data?.raw) {
      activeParty.value = activeParty.value?.id === params.data.raw.id ? null : params.data.raw
    }
  })

  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const { width: w, height: h } = entry.contentRect
        if (w > 0 && h > 0 && chartInstance) {
          chartInstance.resize({ width: w, height: h })
          updateChart()
        }
      }
    })
    resizeObserver.observe(el)
  }
}

// Muat data jika lembaga diganti
async function changeLembaga(lembaga) {
  if (activeLembaga.value === lembaga && seatsData.value.length > 0) return
  activeLembaga.value = lembaga
  activeParty.value = null
  loading.value = true
  try {
    const res = await getPetaKursi(lembaga, activePeriode.value)
    if (res) {
      seatsData.value = res.items || []
      totalKursi.value = res.totalKursi || 0
      activePeriode.value = res.periode || '2024-2029'
      await nextTick()
      updateChart()
    }
  } catch (err) {
    console.error('Gagal memuat data kursi parlemen:', err)
  } finally {
    loading.value = false
  }
}

watch(
  () => props.initialData,
  async (val) => {
    if (val?.items?.length) {
      seatsData.value = val.items
      totalKursi.value = val.totalKursi || 0
      activeLembaga.value = val.lembaga || 'DPR'
      activePeriode.value = val.periode || '2024-2029'
      await nextTick()
      updateChart()
    }
  },
  { deep: true }
)

function setActiveParty(party) {
  activeParty.value = party
  if (chartInstance && party) {
    chartInstance.dispatchAction({
      type: 'highlight',
      seriesIndex: 0,
      name: party.singkatan || party.partai,
    })
    chartInstance.dispatchAction({
      type: 'showTip',
      seriesIndex: 0,
      name: party.singkatan || party.partai,
    })
  }
}

function clearActiveParty() {
  activeParty.value = null
  if (chartInstance) {
    chartInstance.dispatchAction({
      type: 'downplay',
      seriesIndex: 0,
    })
    chartInstance.dispatchAction({
      type: 'hideTip',
    })
  }
}

onMounted(() => {
  nextTick(() => {
    initChart()
  })
})

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (chartInstance) {
    chartInstance.dispose()
    chartInstance = null
  }
})
</script>

<template>
  <div class="peta-kursi-widget w-full" :class="props.class">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 pb-3 sm:pb-4 border-b border-neutral-700/60">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-isf-red/20 text-isf-red border border-isf-red/30 shrink-0">
            <IconBuildingBank class="w-4 h-4" />
          </span>
          <h3 class="font-space-grotesk text-sm sm:text-base md:text-lg font-bold text-white tracking-tight">
            Komposisi Parlemen {{ activeLembaga }} RI
          </h3>
          <span class="text-[10px] sm:text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-white/10 text-neutral-300 shrink-0">
            Periode {{ activePeriode }}
          </span>
        </div>
        <p class="text-[11px] sm:text-xs text-neutral-400 mt-1 font-space-grotesk">
          Total <strong class="text-white">{{ totalKursi }} Kursi</strong> • Ambang Mayoritas: <strong class="text-amber-400">{{ thresholdMajority }} Kursi</strong>
        </p>
      </div>
    </div>

    <!-- Main Content: Clean Doughnut Chart & Detail Komposisi Fraksi -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-center py-4 sm:py-6">
      <!-- Doughnut Chart Visualization (Left col 6) -->
      <div class="lg:col-span-6 flex flex-col items-center justify-center relative w-full">
        <!-- Container with responsive clean height -->
        <div class="w-full max-w-[320px] sm:max-w-[360px] h-[240px] sm:h-[280px] relative flex items-center justify-center mx-auto">
          <!-- Apache ECharts Container -->
          <div
            ref="chartContainerRef"
            class="echarts-parliament-chart w-full h-full select-none"
            role="img"
            :aria-label="`Diagram lingkaran komposisi perolehan kursi parlemen ${activeLembaga} RI periode ${activePeriode}`"
          />

          <!-- Center Hole Information -->
          <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none select-none px-4">
            <div class="font-space-grotesk text-2xl sm:text-3xl font-black text-white leading-none">
              {{ activeParty ? activeParty.jumlah_kursi : totalKursi }}
            </div>
            <div class="font-space-grotesk text-[10px] sm:text-[11px] font-bold text-neutral-300 uppercase tracking-wider mt-1 truncate max-w-[140px]">
              {{ activeParty ? (activeParty.singkatan || activeParty.partai) : `Total Kursi ${activeLembaga}` }}
            </div>
            <div class="font-mono text-[10px] sm:text-[11px] font-semibold text-amber-400 mt-0.5 truncate">
              {{ activeParty ? `${activeParty.persentase}% Porsi` : `Ambang 50%: ${thresholdMajority}` }}
            </div>
          </div>
        </div>

        <div class="text-[11px] font-mono text-neutral-400 text-center mt-1">
          Ambang Batas Mayoritas Parlemen (50% + 1): <strong class="text-amber-400">{{ thresholdMajority }} Kursi</strong>
        </div>
      </div>

      <!-- Interactive Legend & Details List (Right col 6) -->
      <div class="lg:col-span-6 flex flex-col gap-2 w-full mt-3 lg:mt-0">
        <div class="text-xs font-space-grotesk font-bold text-neutral-300 uppercase tracking-wider pb-1 flex items-center justify-between">
          <span>Komposisi Fraksi / Partai</span>
          <span class="text-[10px] font-normal text-neutral-400 normal-case hidden sm:inline">Arahkan atau pilih untuk menyorot</span>
          <span class="text-[10px] font-normal text-neutral-400 normal-case sm:hidden">Sentuh untuk menyorot</span>
        </div>

        <!-- 2 Kolom di mobile/tablet, 1 kolom scrollable di desktop -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-1.5 max-h-none lg:max-h-[300px] overflow-y-visible lg:overflow-y-auto pr-0 lg:pr-1 isf-no-scrollbar">
          <button
            v-for="item in seatsData"
            :key="item.id"
            type="button"
            @click="setActiveParty(activeParty?.id === item.id ? null : item)"
            @mouseenter="setActiveParty(item)"
            @mouseleave="clearActiveParty"
            @focus="setActiveParty(item)"
            @blur="clearActiveParty"
            :class="[
              'w-full flex flex-col gap-1 p-2 rounded-lg border transition-all text-left font-space-grotesk cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-isf-red',
              activeParty && activeParty.id === item.id
                ? 'bg-white/15 border-white/60 shadow-xs'
                : 'bg-neutral-900/60 border-neutral-800 hover:bg-neutral-800/80 hover:border-neutral-700'
            ]"
          >
            <div class="flex items-center justify-between gap-2 min-w-0">
              <div class="flex items-center gap-2 min-w-0">
                <span
                  class="w-2.5 h-2.5 rounded-full shrink-0 shadow-2xs"
                  :style="{ backgroundColor: item.warna_partai }"
                />
                <span class="text-xs font-bold text-white truncate">
                  {{ item.singkatan || item.partai }}
                </span>
                <span v-if="item.singkatan && item.singkatan !== item.partai" class="hidden sm:inline text-[10px] text-neutral-400 truncate">
                  {{ item.partai }}
                </span>
              </div>
              <div class="text-right shrink-0 flex items-center gap-2">
                <span class="text-xs font-bold text-white">
                  {{ item.jumlah_kursi }} <span class="text-[10px] font-normal text-neutral-400">kursi</span>
                </span>
                <span class="text-[10px] font-mono font-medium text-amber-400 min-w-[38px] text-right">
                  {{ item.persentase }}%
                </span>
              </div>
            </div>
            <!-- Mini progress bar per fraksi -->
            <div class="w-full h-1 bg-neutral-800 rounded-full overflow-hidden mt-0.5">
              <div
                class="h-full rounded-full transition-all duration-300"
                :style="{ width: `${item.persentase}%`, backgroundColor: item.warna_partai }"
              />
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Alternative Table Toggle for Screen Readers & Full Transparency -->
    <div class="pt-3 sm:pt-4 border-t border-neutral-700/60">
      <button
        type="button"
        @click="showTable = !showTable"
        :aria-expanded="showTable"
        aria-controls="isf-parliament-accessible-table"
        class="inline-flex items-center gap-2 text-xs font-space-grotesk font-semibold text-neutral-300 hover:text-white transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red rounded py-1 px-1.5"
      >
        <IconTable class="w-4 h-4 text-isf-red shrink-0" />
        <span class="hidden sm:inline">{{ showTable ? 'Sembunyikan Tabel Alternatif' : 'Tampilkan Tabel Alternatif (Untuk Aksesibilitas & Pembaca Layar)' }}</span>
        <span class="sm:hidden">{{ showTable ? 'Tutup Tabel Alternatif' : 'Tampilkan Tabel Alternatif' }}</span>
        <component :is="showTable ? IconChevronUp : IconChevronDown" class="w-3.5 h-3.5 shrink-0" />
      </button>

      <!-- The Accessible Table -->
      <div
        v-show="showTable"
        id="isf-parliament-accessible-table"
        class="mt-3 overflow-x-auto rounded-lg border border-neutral-700/80 bg-neutral-900/95 p-1 w-full"
      >
        <div class="sm:hidden flex items-center justify-between text-[10px] text-neutral-400 px-2 py-1 border-b border-neutral-800">
          <span>← Geser ke samping untuk melihat seluruh kolom →</span>
        </div>
        <table class="w-full text-left font-space-grotesk text-xs border-collapse" style="min-width: 540px;">
          <caption class="sr-only">
            Tabel komposisi perolehan kursi lembaga {{ activeLembaga }} periode {{ activePeriode }}
          </caption>
          <thead>
            <tr class="border-b border-neutral-800 text-neutral-400 bg-neutral-800/60 text-[11px] uppercase tracking-wider">
              <th scope="col" class="py-2.5 px-2 text-center w-10 whitespace-nowrap">#</th>
              <th scope="col" class="py-2.5 px-3 font-semibold whitespace-nowrap" style="min-width: 190px;">Nama Partai / Fraksi</th>
              <th scope="col" class="py-2.5 px-3 font-semibold whitespace-nowrap" style="min-width: 110px;">Singkatan</th>
              <th scope="col" class="py-2.5 px-3 font-semibold text-right whitespace-nowrap" style="min-width: 100px;">Jumlah Kursi</th>
              <th scope="col" class="py-2.5 px-3 font-semibold text-right whitespace-nowrap" style="min-width: 80px;">Persentase</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-800/60">
            <tr
              v-for="(row, idx) in seatsData"
              :key="row.id"
              :class="[
                'transition-colors',
                activeParty && activeParty.id === row.id ? 'bg-white/10' : 'hover:bg-white/5'
              ]"
            >
              <td class="py-2.5 px-2 text-center text-neutral-500 font-mono text-[11px] whitespace-nowrap">
                {{ idx + 1 }}
              </td>
              <td class="py-2.5 px-3 font-medium text-white" style="min-width: 190px; max-width: 250px;">
                <span class="block text-xs font-semibold text-white line-clamp-2 leading-snug break-words">
                  {{ row.partai }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-neutral-300 font-mono text-xs whitespace-nowrap" style="min-width: 110px;">
                {{ row.singkatan || '-' }}
              </td>
              <td class="py-2.5 px-3 text-right font-bold text-white font-mono text-xs whitespace-nowrap" style="min-width: 100px;">
                {{ row.jumlah_kursi }} <span class="text-[10px] font-normal text-neutral-400 font-space-grotesk">kursi</span>
              </td>
              <td class="py-2.5 px-3 text-right text-amber-400 font-mono text-xs whitespace-nowrap" style="min-width: 80px;">
                {{ row.persentase }}%
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t-2 border-neutral-700 font-bold text-white bg-neutral-800/50">
              <td colspan="3" class="py-2.5 px-3 text-right text-xs whitespace-nowrap uppercase tracking-wider">
                TOTAL KURSI {{ activeLembaga }}
              </td>
              <td class="py-2.5 px-3 text-right font-mono text-xs text-isf-red whitespace-nowrap">
                {{ totalKursi }} kursi
              </td>
              <td class="py-2.5 px-3 text-right font-mono text-xs whitespace-nowrap">
                100%
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>
