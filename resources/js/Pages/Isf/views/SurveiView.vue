<script setup>
/**
 * SurveiView.vue — Dashboard Rilis Survei Opini Publik Nasional & Regional (/survei).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik, Hukum, Kebijakan Publik, dan Opini Publik:
 *   Breadcrumb responsif (whitespace-nowrap & truncate), red kicker badge, serif heading, dan quick pill tabs.
 * - Filter Isu (Politik, Ekonomi, Sosial, Hukum) dan Wilayah menggunakan FilterSelect terstandar.
 * - Filter tersimpan di URL (query string) sehingga shareable.
 * - Seksi 1: Sorotan Riset / Featured Survey dengan border-t-4 border-t-isf-red dan tata letak editorial 12-kolom.
 * - Seksi 2: Linimasa Rilis Survei Nasional bergaya shadcn UI (minimalist vertical hairline track, node dot indicator, pulse rilis terkini).
 * - Seksi 3: Basis Data Laporan Survei Terkini dengan grid 3-kolom SurveiCard dan Pagination terpusat.
 * - Seksi 4: Standar Metodologi & Integritas Data ISF (WAPOR/ESOMAR) pada frame krem.
 * - Skeleton loader desktop dan mobile yang presisi mencerminkan struktur konten.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconChartBar,
  IconChevronRight,
  IconSearch,
  IconMapPin,
  IconUsers,
  IconScale,
  IconAward,
  IconArrowRight,
  IconBuilding,
  IconInfoCircle,
  IconCalendar,
  IconX,
} from '@tabler/icons-vue'
import {
  SectionHeading,
  SurveiCard,
  FilterSelect,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSurvei } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'

// State filters synchronized with query string
const currentIsu = computed(() => (route.query.isu || route.query.topic || 'semua').toLowerCase())
const currentWilayah = computed(() => (route.query.wilayah || route.query.region || 'semua').toLowerCase())
const currentSearch = ref(route.query.q || '')
const currentPage = ref(parseInt(route.query.page, 10) || 1)

let searchDebounce = null

// Fetch data survei
const { data, status, load } = useIsfContent(() => {
  return getSurvei({
    isu: currentIsu.value,
    wilayah: currentWilayah.value,
    q: currentSearch.value,
    page: currentPage.value,
    limit: 9,
  })
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Survei',
  label: 'Survei',
  description: 'Dashboard rilis survei opini publik, metodologi riset terpercaya, agregasi persepsi nasional, dan visualisasi data interaktif.',
  topics: [
    { slug: 'semua', name: 'Semua Isu' },
    { slug: 'politik', name: 'Politik' },
    { slug: 'ekonomi', name: 'Ekonomi' },
    { slug: 'sosial', name: 'Sosial' },
    { slug: 'hukum', name: 'Hukum' },
  ],
  regions: [
    { slug: 'semua', name: 'Semua Wilayah' },
    { slug: 'nasional', name: 'Nasional' },
    { slug: 'jawa', name: 'Pulau Jawa' },
    { slug: 'sumatera', name: 'Sumatera' },
    { slug: 'kalimantan', name: 'Kalimantan' },
    { slug: 'sulawesi', name: 'Sulawesi' },
    { slug: 'bali_nusra', name: 'Bali & Nusa Tenggara' },
    { slug: 'maluku_papua', name: 'Maluku & Papua' },
  ],
})

const featuredSurvey = computed(() => data.value?.featuredSurvey || null)
const surveys = computed(() => data.value?.surveys || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })

// Top releases for timeline section
const timelineSurveys = computed(() => {
  if (!surveys.value || surveys.value.length === 0) return []
  return surveys.value.slice(0, 4)
})

function updateUrlQuery(updates = {}) {
  const query = { ...route.query, ...updates }

  // Clean empty or default values
  if (!query.isu || query.isu === 'semua') delete query.isu
  if (!query.wilayah || query.wilayah === 'semua') delete query.wilayah
  if (!query.q || query.q.trim() === '') delete query.q
  if (query.page === 1 || !query.page) delete query.page

  router.push({ path: '/survei', query })
}

function selectIsu(slug) {
  currentPage.value = 1
  updateUrlQuery({ isu: slug, page: undefined })
}

function onWilayahChange(val) {
  currentPage.value = 1
  updateUrlQuery({ wilayah: val, page: undefined })
}

function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    currentPage.value = 1
    updateUrlQuery({ q: currentSearch.value, page: undefined })
  }, 400)
}

function clearSearch() {
  currentSearch.value = ''
  currentPage.value = 1
  updateUrlQuery({ q: undefined, page: undefined })
}

const hasActiveFilter = computed(() => {
  return currentIsu.value !== 'semua' || currentWilayah.value !== 'semua' || Boolean(currentSearch.value?.trim())
})

function resetAllFilters() {
  currentSearch.value = ''
  currentPage.value = 1
  updateUrlQuery({ isu: undefined, wilayah: undefined, q: undefined, page: undefined })
}

function onPageChange(page) {
  currentPage.value = page
  updateUrlQuery({ page })
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Watch route changes to reload data
watch(
  () => [route.query.isu, route.query.wilayah, route.query.q, route.query.page],
  () => {
    currentSearch.value = route.query.q || ''
    currentPage.value = parseInt(route.query.page, 10) || 1
    load()
  },
  { immediate: true }
)

// Update document title
watch(
  [() => rubrik.value?.title, currentIsu],
  ([title, isu]) => {
    if (typeof document !== 'undefined') {
      const activeTopic = rubrik.value?.topics?.find(t => t.slug === isu)
      const pageTitle = isu && isu !== 'semua'
        ? `${activeTopic?.name || isu} · Survei · Indonesia Strategy Forum`
        : `${title || 'Survei'} · Indonesia Strategy Forum`
      document.title = pageTitle
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK SURVEI (Gaya Editorial Navy identik dengan Politik) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink
            to="/survei"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              currentIsu === 'semua' && currentWilayah === 'semua' && !currentSearch ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Survei
          </RouterLink>
          <template v-if="currentIsu !== 'semua'">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-white font-bold whitespace-nowrap shrink-0 capitalize">{{ currentIsu }}</span>
          </template>
          <template v-if="currentWilayah !== 'semua'">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-neutral-300 font-medium whitespace-nowrap shrink-0 capitalize">{{ currentWilayah.replace('_', ' ') }}</span>
          </template>
        </nav>

        <!-- Rubrik Title & Description (Tanpa filter di dalam background gelap) -->
        <div>
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>PUSAT DATA RISET & TEMUAN EMPIRIS</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
            SURVEI
          </h1>
          <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
            {{ rubrik.description }}
          </p>
        </div>
      </div>
    </header>

    <!-- ═══ FILTER BAR (SEMUA FILTER DI SEBELAH KANAN TANPA DIVIDER) ═══ -->
    <div class="bg-white py-3 sm:py-4">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Row: Semua Filter (Isu, Wilayah, Search, Reset) Diposisikan di Sebelah Kanan -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2.5 sm:gap-3 text-xs">
          <!-- Dropdown Filters & Reset Row -->
          <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 order-2 sm:order-1">
            <!-- Filter Isu Dropdown (FilterSelect) -->
            <div class="flex items-center gap-1.5 text-xs text-neutral-600">
              <span class="text-neutral-500 font-medium whitespace-nowrap">Isu:</span>
              <FilterSelect
                :model-value="currentIsu"
                :options="rubrik.topics"
                :is-dark="false"
                size="sm"
                aria-label="Filter Isu Survei"
                class="min-w-[140px]"
                @update:model-value="selectIsu"
              />
            </div>

            <!-- Filter Wilayah Dropdown (FilterSelect) -->
            <div class="flex items-center gap-1.5 text-xs text-neutral-600">
              <span class="text-neutral-500 font-medium whitespace-nowrap">Wilayah:</span>
              <FilterSelect
                :model-value="currentWilayah"
                :options="rubrik.regions"
                :is-dark="false"
                size="sm"
                aria-label="Filter Wilayah Survei"
                class="min-w-[140px]"
                @update:model-value="onWilayahChange"
              />
            </div>

            <!-- Tombol Reset Filter jika ada filter aktif -->
            <button
              v-if="hasActiveFilter"
              type="button"
              @click="resetAllFilters"
              class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-isf-red bg-red-50 hover:bg-red-100 border border-red-200 transition-colors cursor-pointer"
              title="Reset semua filter survei"
            >
              <IconX class="w-3.5 h-3.5" />
              <span>Reset</span>
            </button>
          </div>

          <!-- Search Input Utama (Full width di mobile, rapi di kanan di desktop) -->
          <div class="relative w-full sm:w-60 md:w-72 order-1 sm:order-2">
            <IconSearch class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="currentSearch"
              type="text"
              placeholder="Cari survei atau lembaga..."
              @input="onSearchInput"
              class="w-full h-8 pl-8 pr-7 rounded-lg bg-neutral-50 border border-neutral-300 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-isf-red focus:border-isf-red transition-all"
            />
            <button
              v-if="currentSearch"
              type="button"
              @click="clearSearch"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-700 text-xs px-1 cursor-pointer"
              aria-label="Hapus pencarian"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan struktur konten di Desktop & Mobile) ═══ -->
    <main
      v-if="status === 'loading' && !data"
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-0 space-y-8 sm:space-y-12 animate-pulse"
      aria-busy="true"
      aria-label="Memuat data survei opini publik"
    >
      <!-- Section 1 Skeleton: Featured Survey -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-36 bg-neutral-200 rounded"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-4 sm:p-6 lg:p-8">
          <div class="lg:col-span-7 space-y-4">
            <div class="h-3 w-32 bg-neutral-200 rounded"></div>
            <div class="h-8 w-full bg-neutral-300 rounded"></div>
            <div class="h-8 w-4/5 bg-neutral-300 rounded"></div>
            <div class="h-4 w-full bg-neutral-200 rounded mt-3"></div>
            <div class="h-4 w-5/6 bg-neutral-200 rounded"></div>
            <div class="pt-4 border-t border-neutral-200 flex gap-4">
              <div class="h-4 w-28 bg-neutral-200 rounded"></div>
              <div class="h-4 w-24 bg-neutral-200 rounded"></div>
            </div>
          </div>
          <div class="lg:col-span-5 h-56 bg-neutral-200 rounded-xl"></div>
        </div>
      </div>

      <!-- Section 2 Skeleton: Timeline -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-200 pb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
            <div class="h-4 w-48 bg-neutral-300 rounded"></div>
          </div>
          <div class="h-3 w-32 bg-neutral-200 rounded"></div>
        </div>
        <div class="space-y-4 py-2">
          <div class="h-16 w-full bg-neutral-100 rounded-lg border border-neutral-200"></div>
          <div class="h-16 w-full bg-neutral-100/70 rounded-lg border border-neutral-200"></div>
        </div>
      </div>

      <!-- Section 3 Skeleton: Grid Cards (4 Kolom) -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-40 bg-neutral-200 rounded"></div>
        </div>
        <div class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="n in 4"
              :key="n"
              class="p-4 bg-white rounded-xl border border-neutral-200 space-y-3"
            >
              <div class="flex items-center justify-between">
                <div class="h-3 w-20 bg-neutral-200 rounded"></div>
                <div class="h-3 w-16 bg-neutral-200 rounded"></div>
              </div>
              <div class="h-5 w-full bg-neutral-300 rounded"></div>
              <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
              <div class="h-16 bg-neutral-100 rounded-lg border border-neutral-200"></div>
              <div class="pt-2 border-t border-neutral-100 flex items-center justify-between">
                <div class="h-3 w-24 bg-neutral-200 rounded"></div>
                <div class="h-3 w-16 bg-neutral-200 rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Error State -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Gagal Memuat Data Survei"
        message="Terjadi kendala saat mengambil basis data hasil survei. Silakan periksa koneksi Anda lalu coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-0 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: SOROTAN UTAMA SURVEI (FEATURED REPORT) ══════════════ -->
      <section
        v-if="featuredSurvey && currentPage === 1 && currentIsu === 'semua' && currentWilayah === 'semua' && !currentSearch"
        aria-labelledby="featured-survei-title"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="featured-survei-title"
            title="Sorotan Survei"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Laporan Khusus & Temuan Kunci</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-stretch">
            <!-- Left Editorial Column -->
            <div class="lg:col-span-7 flex flex-col justify-between">
              <div>
                <!-- Top Badges & Meta -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                  <span
                    v-if="featuredSurvey.is_isf"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-mono font-bold bg-isf-red text-white uppercase tracking-wider"
                  >
                    <IconAward class="w-3 h-3 shrink-0" />
                    Riset In-House ISF
                  </span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-red-500/10 text-isf-red border border-red-500/20">
                    {{ featuredSurvey.topic }}
                  </span>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                    <IconMapPin class="w-3 h-3 text-neutral-400 shrink-0" />
                    {{ featuredSurvey.region?.toUpperCase() }}
                  </span>
                  <span class="text-[11px] font-mono text-neutral-400 ml-auto">
                    {{ featuredSurvey.survey_period }}
                  </span>
                </div>

                <!-- Institution -->
                <div class="flex items-center gap-1.5 text-xs text-neutral-500 font-semibold mb-2">
                  <IconBuilding class="w-4 h-4 text-isf-red shrink-0" />
                  <span>{{ featuredSurvey.institution }}</span>
                </div>

                <!-- Title -->
                <h2 class="font-headline text-2xl sm:text-3xl lg:text-3xl font-bold leading-tight text-neutral-950 hover:text-isf-red transition-colors mb-3">
                  <RouterLink :to="`/survei/${featuredSurvey.slug}`">
                    {{ featuredSurvey.title }}
                  </RouterLink>
                </h2>

                <!-- Summary -->
                <p class="font-serif text-sm sm:text-base text-neutral-600 line-clamp-3 sm:line-clamp-4 leading-relaxed mb-6">
                  {{ featuredSurvey.summary }}
                </p>
              </div>

              <!-- Methodology Quick Highlights -->
              <div class="pt-4 border-t border-neutral-200 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs font-mono text-neutral-600">
                <div class="flex items-center gap-2">
                  <IconUsers class="w-4 h-4 text-isf-red shrink-0" />
                  <div>
                    <span class="text-[10px] text-neutral-400 block uppercase">Sampel</span>
                    <strong class="text-neutral-900 font-bold">{{ featuredSurvey.sample_size?.toLocaleString('id-ID') }}</strong>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <IconScale class="w-4 h-4 text-isf-red shrink-0" />
                  <div>
                    <span class="text-[10px] text-neutral-400 block uppercase">Margin of Error</span>
                    <strong class="text-neutral-900 font-bold">{{ featuredSurvey.margin_of_error }}</strong>
                  </div>
                </div>
                <div class="flex items-center gap-2 col-span-2 sm:col-span-1">
                  <IconCalendar class="w-4 h-4 text-isf-red shrink-0" />
                  <div>
                    <span class="text-[10px] text-neutral-400 block uppercase">Rilis Publik</span>
                    <strong class="text-neutral-900 font-bold">{{ featuredSurvey.release_date }}</strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Callout Column: Headline Statistic -->
            <div class="lg:col-span-5 bg-neutral-950 text-white p-5 sm:p-7 rounded-xl border border-neutral-800 flex flex-col justify-between shadow-sm">
              <div>
                <span class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 block mb-1">
                  Temuan Kunci Survei
                </span>
                <div class="font-headline font-black text-4xl sm:text-5xl lg:text-6xl text-isf-red tracking-tight my-2">
                  {{ featuredSurvey.headline_stat_value }}
                </div>
                <p class="text-xs sm:text-sm text-neutral-300 font-medium leading-relaxed">
                  {{ featuredSurvey.headline_stat_label }}
                </p>
                <div
                  v-if="featuredSurvey.headline_stat_comparison"
                  class="mt-3 text-xs font-mono text-emerald-400 flex items-center gap-1.5"
                >
                  <span>•</span>
                  <span>{{ featuredSurvey.headline_stat_comparison }}</span>
                </div>
              </div>

              <!-- CTA Action Button -->
              <div class="pt-6 mt-6 border-t border-neutral-800">
                <RouterLink
                  :to="`/survei/${featuredSurvey.slug}`"
                  class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm tracking-wide transition-all shadow-md cursor-pointer"
                >
                  <span>Lihat Grafik & Analisis Lengkap</span>
                  <IconArrowRight class="w-4 h-4 shrink-0" />
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 2: LINIMASA RILIS SURVEI TERKINI (SHADCN TIMELINE - NAVY) ═══ -->
      <section
        v-if="timelineSurveys.length > 0 && currentPage === 1 && currentIsu === 'semua' && currentWilayah === 'semua' && !currentSearch"
        aria-labelledby="linimasa-survei-title"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
      >
        <div class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
              Linimasa Riset Terkini
            </span>
            <span class="text-neutral-600">•</span>
            <h2 id="linimasa-survei-title" class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider">
              Kronologi Rilis Survei Nasional
            </h2>
          </div>
          <span class="text-[11px] font-mono text-neutral-400">
            Agregasi Persepsi & Dinamika Opini Publik
          </span>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-7']">
          <!-- Shadcn timeline for recent survey releases -->
          <div class="relative pl-6 sm:pl-8 ml-3 sm:ml-4 border-l border-neutral-800 space-y-6 sm:space-y-8">
            <div
              v-for="(item, idx) in timelineSurveys"
              :key="item.id"
              class="relative group"
            >
              <!-- Node Dot on Line -->
              <div
                :class="[
                  'absolute -left-[1.95rem] sm:-left-[2.45rem] top-1.5 w-3.5 h-3.5 rounded-full ring-4 transition-all group-hover:scale-125',
                  idx === 0
                    ? 'bg-isf-red ring-isf-red/30'
                    : 'bg-neutral-700 ring-neutral-800 group-hover:bg-isf-red'
                ]"
              >
                <!-- Pulse animation on the latest item -->
                <span
                  v-if="idx === 0"
                  class="absolute inset-0 rounded-full bg-isf-red animate-ping opacity-75"
                ></span>
              </div>

              <!-- Content Card -->
              <div class="rounded-lg p-4 sm:p-5 bg-neutral-900/60 border border-neutral-800 hover:border-neutral-700 transition-colors">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-white/10 text-neutral-300 border border-white/15">
                      {{ item.topic }}
                    </span>
                    <span class="text-xs font-mono text-neutral-400">
                      {{ item.release_date }}
                    </span>
                  </div>
                  <span class="text-xs text-neutral-400 font-medium">
                    {{ item.institution }}
                  </span>
                </div>

                <h3 class="font-headline font-bold text-base sm:text-lg text-white hover:text-isf-red transition-colors mb-2">
                  <RouterLink :to="`/survei/${item.slug}`">
                    {{ item.title }}
                  </RouterLink>
                </h3>

                <!-- Highlight metric line -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-neutral-800 text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="font-headline font-black text-lg text-isf-red shrink-0">
                      {{ item.headline_stat_value }}
                    </span>
                    <span class="text-neutral-300 text-xs truncate">
                      {{ item.headline_stat_label }}
                    </span>
                  </div>

                  <RouterLink
                    :to="`/survei/${item.slug}`"
                    class="inline-flex items-center gap-1 text-xs font-bold text-isf-red hover:text-red-400 transition-colors shrink-0 cursor-pointer"
                  >
                    <span>Detail Analisis</span>
                    <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 3: BASIS DATA LAPORAN SURVEI TERKINI (GRID ARSIP) ══════ -->
      <section
        aria-labelledby="daftar-survei-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="daftar-survei-title"
            :title="currentIsu !== 'semua' ? `Survei Isu: ${currentIsu.toUpperCase()}` : 'Laporan Survei Terkini'"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>
              Basis Data Riset Terpublikasi ({{ meta.total }} Laporan{{ currentWilayah !== 'semua' ? ` · ${currentWilayah.toUpperCase()}` : '' }})
            </template>
          </SectionHeading>
        </div>

        <!-- Grid Survei -->
        <div v-if="surveys && surveys.length > 0" class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SurveiCard
              v-for="survey in surveys"
              :key="survey.id"
              :survey="survey"
              theme="light"
            />
          </div>
        </div>

        <!-- Empty State jika filter tidak menemukan survei -->
        <div v-else class="text-center py-16 px-4" :class="sectionPad">
          <IconChartBar class="w-12 h-12 text-neutral-400 mx-auto mb-3" />
          <h4 class="font-bold text-neutral-800 text-base font-space-grotesk">Tidak Ada Survei yang Sesuai dengan Filter</h4>
          <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
            Cobalah memilih isu atau wilayah lain, atau hapus kata kunci pencarian Anda.
          </p>
          <button
            type="button"
            @click="updateUrlQuery({ isu: undefined, wilayah: undefined, q: undefined })"
            class="mt-4 px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition-colors cursor-pointer"
          >
            Reset Semua Filter
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="meta.lastPage > 1" :class="['py-5 sm:py-6 flex justify-center border-t border-neutral-200', sectionPad]">
          <Pagination
            :current-page="meta.currentPage"
            :total-pages="meta.lastPage"
            @update:current-page="onPageChange"
          />
        </div>
      </section>

      <!-- ═══ SECTION 4: STANDAR METODOLOGI & INTEGRITAS DATA ISF ═════ -->
      <section
        aria-labelledby="standar-integritas-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="standar-integritas-title"
            title="Standar Etika & Kredibilitas Riset"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Integritas & Transparansi Metodologi</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <div class="lg:col-span-8">
              <h3 class="font-headline text-xl sm:text-2xl font-bold text-neutral-900 mb-2">
                Transparansi Metodologi Riset Indonesia Strategy Forum
              </h3>
              <p class="text-xs sm:text-sm text-neutral-600 font-serif leading-relaxed max-w-2xl">
                Seluruh survei yang dipublikasikan di rubrik ini tunduk pada kode etik riset opini publik internasional (WAPOR/ESOMAR). ISF memuat secara transparan ukuran sampel, metode penarikan sampel, rentang kesalahan (*margin of error*), serta batas waktu pengambilan data lapangan guna menjaga integritas analisis ilmiah.
              </p>
            </div>
            <div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-end">
              <div class="p-4 rounded-xl bg-white border border-neutral-200 shadow-2xs">
                <strong class="text-xs font-bold text-neutral-900 block mb-0.5">Independensi Data</strong>
                <span class="text-[11px] text-neutral-500">Bebas dari intervensi pesanan elektoral komersial.</span>
              </div>
              <div class="p-4 rounded-xl bg-white border border-neutral-200 shadow-2xs">
                <strong class="text-xs font-bold text-neutral-900 block mb-0.5">Akses Terbuka</strong>
                <span class="text-[11px] text-neutral-500">Laporan lengkap dapat diunduh cuma-cuma oleh publik.</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
