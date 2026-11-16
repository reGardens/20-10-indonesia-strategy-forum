<script setup>
/**
 * SurveiView.vue — Dashboard Rilis Survei Opini Publik Nasional & Regional (/survei).
 *
 * Mengimplementasikan:
 * - Filter Isu (Politik, Ekonomi, Sosial, Hukum) dan Wilayah (Nasional, Jawa, Sumatera, dll.)
 * - Filter tersimpan di URL (query string) supaya bisa dibagikan (shareable).
 * - Headline Featured Survey dengan angka metrik utama.
 * - Grid kartu survei lengkap dengan sumber lembaga (ISF / Eksternal), sampel, margin of error, dan PDF link.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconChartBar,
  IconChevronRight,
  IconFilter,
  IconSearch,
  IconMapPin,
  IconUsers,
  IconScale,
  IconAward,
  IconFileText,
  IconArrowRight,
  IconBuilding,
  IconInfoCircle,
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  SectionHeading,
  SurveiCard,
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
    { slug: 'bali_nusra', name: 'Bali & Nusra' },
    { slug: 'maluku_papua', name: 'Maluku & Papua' },
  ],
})

const featuredSurvey = computed(() => data.value?.featuredSurvey || null)
const surveys = computed(() => data.value?.surveys || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })

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

function selectWilayah(e) {
  const val = e.target.value
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
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-[#FBF9F4] text-neutral-900 antialiased pb-20">
    <!-- Header Hero Rubrik Survei -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-4 select-none">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <span class="text-white font-bold">Survei & Riset Opini Publik</span>
        </nav>

        <!-- Rubrik Title & Kicker -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-neutral-800/80 pb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
              <span class="inline-block w-2 h-2 bg-isf-red rounded-full"></span>
              <span>Pusat Data Persepsi & Temuan Empiris</span>
            </div>
            <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
              SURVEI OPINI PUBLIK
            </h1>
            <p class="text-neutral-300 text-sm sm:text-base max-w-2xl mt-3 font-normal leading-relaxed">
              {{ rubrik.description }}
            </p>
          </div>
        </div>

        <!-- Filter Bar: Isu Pills, Wilayah Dropdown, Search Input (Saved to URL) -->
        <div class="mt-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <!-- Isu Tabs -->
          <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none text-xs sm:text-sm">
            <button
              v-for="top in rubrik.topics"
              :key="top.slug"
              type="button"
              @click="selectIsu(top.slug)"
              :class="[
                'px-4 py-2 rounded-full font-semibold transition-all whitespace-nowrap cursor-pointer',
                currentIsu === top.slug
                  ? 'bg-isf-red text-white shadow-sm'
                  : 'bg-neutral-800/90 text-neutral-300 hover:bg-neutral-700 hover:text-white'
              ]"
            >
              {{ top.name }}
            </button>
          </div>

          <!-- Secondary Filters: Wilayah & Search Input -->
          <div class="flex items-center gap-3 shrink-0">
            <!-- Wilayah Select -->
            <div class="relative min-w-[160px]">
              <select
                :value="currentWilayah"
                @change="selectWilayah"
                class="w-full h-9 pl-3 pr-8 rounded-lg bg-neutral-800 border border-neutral-700 text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-isf-red appearance-none cursor-pointer"
              >
                <option
                  v-for="reg in rubrik.regions"
                  :key="reg.slug"
                  :value="reg.slug"
                >
                  {{ reg.name }}
                </option>
              </select>
              <IconMapPin class="w-3.5 h-3.5 text-neutral-400 absolute right-3 top-3 pointer-events-none" />
            </div>

            <!-- Search Input -->
            <div class="relative w-48 sm:w-60">
              <input
                v-model="currentSearch"
                type="text"
                placeholder="Cari survei / lembaga..."
                @input="onSearchInput"
                class="w-full h-9 pl-8 pr-3 rounded-lg bg-neutral-800 border border-neutral-700 text-xs text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all"
              />
              <IconSearch class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-3 pointer-events-none" />
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 pt-8 sm:pt-10">
      <!-- Loading State -->
      <div v-if="status === 'loading'" class="py-12">
        <ArtikelSkeleton variant="rubrik" />
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="py-12">
        <ErrorMessage
          title="Gagal Memuat Data Survei"
          message="Terjadi kendala saat mengambil basis data hasil survei. Silakan periksa koneksi Anda."
          action-label="Coba Lagi"
          @retry="load"
        />
      </div>

      <!-- Loaded Content -->
      <div v-else class="space-y-12 sm:space-y-16">
        <!-- ═══ 1. FEATURED SURVEI UTAMA (NAVY ACCENT) ═════════════════════════ -->
        <section
          v-if="featuredSurvey && currentPage === 1 && currentIsu === 'semua' && currentWilayah === 'semua' && !currentSearch"
          aria-labelledby="featured-survei-title"
        >
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden shadow-xl text-white">
            <div class="p-6 sm:p-8 lg:p-10">
              <!-- Top Row Badges -->
              <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-isf-red text-white">
                    <IconAward class="w-4 h-4 shrink-0" />
                    Survei Pilihan Redaksi
                  </span>
                  <span
                    v-if="featuredSurvey.is_isf"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                  >
                    Riset In-House ISF
                  </span>
                </div>

                <div class="text-xs font-mono text-neutral-400 flex items-center gap-2">
                  <span>{{ featuredSurvey.survey_period }}</span>
                  <span>•</span>
                  <span>Rilis: {{ featuredSurvey.release_date }}</span>
                </div>
              </div>

              <!-- Main Grid -->
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-center">
                <!-- Content Column -->
                <div class="lg:col-span-8">
                  <!-- Institution -->
                  <div class="flex items-center gap-2 text-xs text-neutral-400 font-semibold mb-2 font-space-grotesk">
                    <IconBuilding class="w-4 h-4 text-isf-red" />
                    <span>{{ featuredSurvey.institution }}</span>
                  </div>

                  <!-- Title -->
                  <h2 id="featured-survei-title" class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight mb-4">
                    <RouterLink
                      :to="`/survei/${featuredSurvey.slug}`"
                      class="hover:text-isf-red transition-colors"
                    >
                      {{ featuredSurvey.title }}
                    </RouterLink>
                  </h2>

                  <!-- Summary -->
                  <p class="text-neutral-300 text-sm sm:text-base leading-relaxed line-clamp-3 mb-6">
                    {{ featuredSurvey.summary }}
                  </p>

                  <!-- Metodologi Quick Info -->
                  <div class="flex flex-wrap items-center gap-4 text-xs font-mono text-neutral-400 pt-4 border-t border-neutral-800">
                    <div class="flex items-center gap-1.5">
                      <IconUsers class="w-3.5 h-3.5 text-neutral-400" />
                      <span>Sampel: <strong class="text-white">{{ featuredSurvey.sample_size?.toLocaleString('id-ID') }} Responden</strong></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <IconScale class="w-3.5 h-3.5 text-neutral-400" />
                      <span>Margin of Error: <strong class="text-white">{{ featuredSurvey.margin_of_error }}</strong></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <IconMapPin class="w-3.5 h-3.5 text-neutral-400" />
                      <span>Wilayah: <strong class="text-white uppercase">{{ featuredSurvey.region }}</strong></span>
                    </div>
                  </div>
                </div>

                <!-- Highlight Metric Callout Box Column -->
                <div class="lg:col-span-4 bg-neutral-950 p-6 rounded-xl border border-neutral-800 flex flex-col justify-between h-full">
                  <div>
                    <span class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 block mb-1">
                      Temuan Utama Survei
                    </span>
                    <div class="font-headline font-black text-4xl sm:text-5xl text-isf-red tracking-tight my-2">
                      {{ featuredSurvey.headline_stat_value }}
                    </div>
                    <p class="text-xs sm:text-sm text-neutral-300 leading-snug">
                      {{ featuredSurvey.headline_stat_label }}
                    </p>
                    <div
                      v-if="featuredSurvey.headline_stat_comparison"
                      class="mt-2 text-xs font-mono text-emerald-400"
                    >
                      {{ featuredSurvey.headline_stat_comparison }}
                    </div>
                  </div>

                  <!-- CTA Button -->
                  <div class="pt-6 mt-6 border-t border-neutral-800">
                    <RouterLink
                      :to="`/survei/${featuredSurvey.slug}`"
                      class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm tracking-wide transition-all shadow-md"
                    >
                      <span>Lihat Grafik & Analisis Lengkap</span>
                      <IconArrowRight class="w-4 h-4" />
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ═══ 2. DAFTAR KARTU SURVEI (GRID) ═════════════════════════════════ -->
        <section aria-labelledby="daftar-survei-title">
          <div class="flex items-center justify-between border-b border-neutral-300 pb-4 mb-6">
            <SectionHeading
              id="daftar-survei-title"
              :title="currentIsu !== 'semua' ? `Survei Isu: ${currentIsu.toUpperCase()}` : 'Laporan Survei Terkini'"
              size="lg"
            >
              <template #eyebrow>Basis Data Riset Terpublikasi</template>
            </SectionHeading>

            <span class="text-xs font-mono text-neutral-500">
              Total {{ meta.total }} laporan survei
            </span>
          </div>

          <!-- Grid -->
          <div v-if="surveys && surveys.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SurveiCard
              v-for="survey in surveys"
              :key="survey.id"
              :survey="survey"
              theme="light"
            />
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-16 bg-white rounded-xl border border-neutral-200">
            <IconChartBar class="w-12 h-12 text-neutral-400 mx-auto mb-3" />
            <h4 class="font-bold text-neutral-800 text-base font-space-grotesk">Tidak Ada Survei yang Sesuai dengan Filter</h4>
            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
              Cobalah memilih isu atau wilayah lain, atau hapus kata kunci pencarian Anda.
            </p>
            <button
              type="button"
              @click="updateUrlQuery({ isu: undefined, wilayah: undefined, q: undefined })"
              class="mt-4 px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition-colors"
            >
              Reset Semua Filter
            </button>
          </div>

          <!-- Pagination -->
          <div v-if="meta.lastPage > 1" class="mt-8 flex justify-center">
            <Pagination
              :current-page="meta.currentPage"
              :last-page="meta.lastPage"
              @change="onPageChange"
            />
          </div>
        </section>

        <!-- ═══ 3. STANDAR METODOLOGI & INTEGRITAS DATA ISF ═══════════════════ -->
        <section class="bg-neutral-900 text-neutral-100 rounded-2xl border border-neutral-800 p-6 sm:p-8 lg:p-10">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <div class="lg:col-span-8">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-isf-red/20 text-isf-red border border-isf-red/30 mb-3">
                <IconInfoCircle class="w-4 h-4 shrink-0" />
                Standar Etika & Kredibilitas
              </div>
              <h3 class="font-headline text-xl sm:text-2xl font-bold text-white mb-2">
                Transparansi Metodologi Riset Indonesia Strategy Forum
              </h3>
              <p class="text-xs sm:text-sm text-neutral-300 leading-relaxed max-w-2xl">
                Seluruh survei yang dipublikasikan di rubrik ini tunduk pada kode etik riset opini publik internasional (WAPOR/ESOMAR). ISF memuat secara transparan ukuran sampel, metode penarikan sampel, rentang kesalahan (*margin of error*), serta batas waktu pengambilan data lapangan guna menjaga integritas analisis ilmiah.
              </p>
            </div>
            <div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-end">
              <div class="p-4 rounded-xl bg-neutral-950 border border-neutral-800">
                <strong class="text-xs font-bold text-white block mb-0.5">Independensi Data</strong>
                <span class="text-[11px] text-neutral-400">Bebas dari intervensi pesanan elektoral komersial.</span>
              </div>
              <div class="p-4 rounded-xl bg-neutral-950 border border-neutral-800">
                <strong class="text-xs font-bold text-white block mb-0.5">Akses Terbuka</strong>
                <span class="text-[11px] text-neutral-400">Laporan lengkap dapat diunduh cuma-cuma oleh publik.</span>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

