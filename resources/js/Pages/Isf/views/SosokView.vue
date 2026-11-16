<script setup>
/**
 * Rubrik Sosok — Indeks Tokoh & Sub-Kategori (/sosok dan /sosok/:kategori).
 * Menampilkan:
 * - Hero: Tokoh Paling Berpengaruh Minggu Ini (Konsep Tokoh Pekan Ini)
 * - Baris Pencarian & Multi-Filter: Kategori peran, Isu, dan Alfabet A-Z
 * - Grid kartu profil tokoh publik (SosokCard.vue)
 * - Sinkronisasi URL query string agar tautan hasil filter shareable
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IconSearch,
  IconX,
  IconAward,
  IconArrowRight,
  IconFilter,
  IconUser,
  IconSparkles,
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  SectionHeading,
  SosokCard,
} from '@/Components/Isf'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSosok } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'

// Available alphabets
const alphabetList = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']

// Active category from route param (e.g. /sosok/eksekutif) or query (?kategori=eksekutif)
const currentKategori = computed(() => {
  return (route.params.kategori || route.query.kategori || 'semua').toLowerCase()
})

const currentIsu = computed(() => {
  return (route.query.isu || route.query.issue || 'semua').toLowerCase()
})

const currentAbjad = computed(() => {
  return (route.query.abjad || route.query.alphabet || 'semua').toUpperCase()
})

const currentSearch = ref(route.query.q || '')
const currentPage = ref(parseInt(route.query.page, 10) || 1)

let searchDebounce = null

// Fetch data sosok
const { data, status, load } = useIsfContent(() => {
  return getSosok({
    kategori: currentKategori.value,
    isu: currentIsu.value,
    abjad: currentAbjad.value,
    q: currentSearch.value,
    page: currentPage.value,
    limit: 12,
  })
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Sosok',
  label: 'Sosok',
  description: 'Direktori komprehensif profil tokoh nasional, rekam jejak, skor pengaruh, dan pemetaan jejaring strategis.',
  kategoriOptions: [
    { slug: 'semua', name: 'Semua Kategori' },
    { slug: 'eksekutif', name: 'Eksekutif' },
    { slug: 'legislatif', name: 'Legislatif' },
    { slug: 'yudikatif', name: 'Yudikatif' },
    { slug: 'pakar', name: 'Akademisi / Pakar' },
    { slug: 'militer', name: 'Militer / Hankam' },
    { slug: 'pengusaha', name: 'Pengusaha' },
  ],
  isuOptions: [
    { slug: 'semua', name: 'Semua Isu' },
    { slug: 'politik', name: 'Politik' },
    { slug: 'hukum', name: 'Hukum' },
    { slug: 'ekonomi', name: 'Ekonomi' },
    { slug: 'pertahanan', name: 'Pertahanan' },
  ],
  activeKategori: 'semua',
  activeIsu: 'semua',
  activeAbjad: 'semua',
})

const hero = computed(() => data.value?.hero || null)
const items = computed(() => data.value?.items || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 12 })

// Dynamic Page Title for Subcategory SEO
const pageTitle = computed(() => {
  const cat = currentKategori.value
  if (cat && cat !== 'semua') {
    const opt = rubrik.value.kategoriOptions?.find(o => o.slug === cat)
    const label = opt ? opt.name : cat.toUpperCase()
    return `Tokoh ${label} — Rubrik Sosok · Indonesia Strategy Forum`
  }
  return 'Sosok & Tokoh Berpengaruh Nasional · Indonesia Strategy Forum'
})

function updateQuery(changes) {
  const newQuery = { ...route.query, ...changes }
  Object.keys(newQuery).forEach(key => {
    if (!newQuery[key] || newQuery[key] === 'semua' || (key === 'page' && newQuery[key] <= 1)) {
      delete newQuery[key]
    }
  })

  // If selecting a category and we are on /sosok/:kategori, keep or push to /sosok/:category
  if (changes.kategori !== undefined) {
    if (changes.kategori && changes.kategori !== 'semua') {
      router.push({ path: `/sosok/${changes.kategori}`, query: newQuery })
      return
    } else {
      router.push({ path: '/sosok', query: newQuery })
      return
    }
  }

  router.push({ query: newQuery })
}

function selectKategori(slug) {
  currentPage.value = 1
  updateQuery({ kategori: slug, page: 1 })
}

function selectIsu(slug) {
  currentPage.value = 1
  updateQuery({ isu: slug, page: 1 })
}

function selectAbjad(letter) {
  currentPage.value = 1
  updateQuery({ abjad: letter, page: 1 })
}

function onSearchInput(e) {
  currentSearch.value = e.target.value
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    currentPage.value = 1
    updateQuery({ q: currentSearch.value, page: 1 })
  }, 400)
}

function clearSearch() {
  currentSearch.value = ''
  currentPage.value = 1
  updateQuery({ q: '', page: 1 })
}

function onPageChange(page) {
  currentPage.value = page
  updateQuery({ page })
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 380, behavior: 'smooth' })
  }
}

// Watch route changes to reload data
watch(
  () => [route.params.kategori, route.query.kategori, route.query.isu, route.query.abjad, route.query.q, route.query.page],
  () => {
    currentSearch.value = route.query.q || ''
    currentPage.value = parseInt(route.query.page, 10) || 1
    load()
  },
  { immediate: true }
)

watch(
  pageTitle,
  (title) => {
    if (title && typeof document !== 'undefined') {
      document.title = title
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-content-layer min-h-screen bg-neutral-100 dark:bg-neutral-950 pb-20">
    <!-- ═══ HEADER BREADCRUMB ═══════════════════════════════════════════════ -->
    <header class="border-b border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 py-4">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-space-grotesk text-neutral-500">
          <router-link to="/beranda" class="hover:text-isf-red transition-colors">Beranda</router-link>
          <span class="text-neutral-300 dark:text-neutral-700">/</span>
          <router-link to="/sosok" :class="[currentKategori === 'semua' ? 'font-bold text-neutral-900 dark:text-white' : 'hover:text-isf-red transition-colors']">
            Sosok
          </router-link>
          <template v-if="currentKategori !== 'semua'">
            <span class="text-neutral-300 dark:text-neutral-700">/</span>
            <span class="font-bold text-neutral-900 dark:text-white capitalize">
              {{ currentKategori }}
            </span>
          </template>
        </nav>
      </div>
    </header>

    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 pt-8 sm:pt-10 space-y-12 sm:space-y-14">
      <!-- ═══ 1. HERO SECTION: TOKOH PALING BERPENGARUH MINGGU INI ═════════ -->
      <section v-if="hero && currentKategori === 'semua' && !currentSearch && currentAbjad === 'semua'" aria-labelledby="hero-tokoh-title">
        <div class="rounded-2xl border-2 border-[#E10600] bg-[#0B1220] text-white p-6 sm:p-8 md:p-10 shadow-xl relative overflow-hidden">
          <!-- Ambient pattern / badge -->
          <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-red-600/10 blur-3xl pointer-events-none"></div>

          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#F87171] uppercase mb-4">
            <IconSparkles class="w-4 h-4 shrink-0 text-amber-400" />
            <span>Tokoh Paling Berpengaruh Minggu Ini</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            <!-- Left: Tokoh Photo -->
            <div class="md:col-span-4 lg:col-span-3">
              <div class="relative aspect-[3/4] rounded-xl overflow-hidden border border-white/20 shadow-2xl bg-neutral-800">
                <img
                  v-if="hero.photo_url"
                  :src="hero.photo_url"
                  :alt="hero.photo_alt || hero.name"
                  class="w-full h-full object-cover object-top"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-neutral-500">
                  <IconUser class="w-20 h-20 opacity-30" />
                </div>

                <div class="absolute bottom-2.5 left-2.5 px-3 py-1 rounded-md bg-isf-red text-white text-xs font-mono font-bold shadow-md">
                  Sorotan Utama
                </div>
              </div>
            </div>

            <!-- Right: Profile highlights -->
            <div class="md:col-span-8 lg:col-span-9 flex flex-col justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2.5 mb-2">
                  <span class="px-2.5 py-0.5 rounded bg-white/10 text-white text-xs font-mono font-bold uppercase tracking-wider border border-white/20">
                    {{ hero.role_category_label || hero.role_category || 'Legislatif' }}
                  </span>
                  <span class="px-2.5 py-0.5 rounded bg-amber-400/20 text-amber-300 text-xs font-mono font-bold flex items-center gap-1 border border-amber-400/30">
                    <IconAward class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                    <span>Skor Pengaruh {{ hero.influence_score || hero.skor_value }}/100</span>
                  </span>
                </div>

                <h2 id="hero-tokoh-title" class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-white mb-2">
                  {{ hero.name }}
                </h2>
                <p class="text-sm sm:text-base text-neutral-300 font-space-grotesk font-medium mb-4">
                  {{ hero.position }}
                </p>

                <p class="text-sm sm:text-base text-neutral-200 font-space-grotesk leading-relaxed max-w-3xl mb-6">
                  "{{ hero.summary_analysis || hero.bio || hero.teaser }}"
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-white/15">
                <router-link
                  :to="hero.to || `/sosok/${hero.slug}`"
                  class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm shadow-md transition-all cursor-pointer"
                >
                  <span>Eksplorasi Profil & Rekam Jejak</span>
                  <IconArrowRight class="w-4 h-4 shrink-0" />
                </router-link>

                <router-link
                  :to="`/sosok/${hero.slug}/analisis`"
                  class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-white/10 hover:bg-white/15 text-white font-semibold text-xs sm:text-sm border border-white/20 transition-all cursor-pointer"
                >
                  <span>Tinjau Skor & Ulasan (#41)</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ 2. FILTER & SEARCH CONTROL BAR ═══════════════════════════════ -->
      <section aria-labelledby="filter-heading" class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-xs space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-neutral-200 dark:border-neutral-800">
          <div>
            <h3 id="filter-heading" class="font-headline text-lg sm:text-xl font-bold text-neutral-950 dark:text-white flex items-center gap-2">
              <IconFilter class="w-5 h-5 text-isf-red" />
              <span>Pencarian & Direktori Tokoh Nasional</span>
            </h3>
            <p class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
              Saring berdasarkan cabang kekuasaan, rumpun kepakaran, bidang isu, atau abjad nama.
            </p>
          </div>

          <!-- Search Input -->
          <div class="relative w-full lg:w-80">
            <IconSearch class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
            <input
              type="text"
              :value="currentSearch"
              placeholder="Cari nama, jabatan, isu..."
              class="w-full pl-9 pr-9 py-2 text-xs font-space-grotesk rounded-lg border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 focus:outline-none focus:ring-1 focus:ring-isf-red"
              @input="onSearchInput"
            />
            <button
              v-if="currentSearch"
              type="button"
              @click="clearSearch"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 cursor-pointer"
            >
              <IconX class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- 2A. Sub-Kategori Tabs (Eksekutif, Legislatif, Yudikatif, Pakar, Militer) -->
        <div>
          <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-neutral-400 mb-2">
            Cabang / Klaster Tokoh
          </label>
          <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
            <button
              v-for="cat in rubrik.kategoriOptions"
              :key="cat.slug"
              type="button"
              @click="selectKategori(cat.slug)"
              :class="[
                'px-3.5 py-1.5 rounded-lg text-xs font-space-grotesk font-semibold whitespace-nowrap transition-all cursor-pointer',
                currentKategori === cat.slug
                  ? 'bg-isf-navy text-white shadow-xs'
                  : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'
              ]"
            >
              {{ cat.name }}
            </button>
          </div>
        </div>

        <!-- 2B. Filter Isu & Filter Abjad A-Z -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-2 border-t border-neutral-100 dark:border-neutral-800">
          <!-- Isu Filter -->
          <div class="md:col-span-5">
            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-neutral-400 mb-1.5">
              Fokus Isu
            </label>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
              <button
                v-for="isu in rubrik.isuOptions"
                :key="isu.slug"
                type="button"
                @click="selectIsu(isu.slug)"
                :class="[
                  'px-2.5 py-1 rounded text-[11px] font-mono font-bold whitespace-nowrap transition-all cursor-pointer',
                  currentIsu === isu.slug
                    ? 'bg-isf-red text-white'
                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700'
                ]"
              >
                {{ isu.name }}
              </button>
            </div>
          </div>

          <!-- Abjad A-Z Filter -->
          <div class="md:col-span-7">
            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-neutral-400 mb-1.5">
              Indeks Alfabet (A–Z)
            </label>
            <div class="flex items-center gap-1 overflow-x-auto pb-1">
              <button
                type="button"
                @click="selectAbjad('semua')"
                :class="[
                  'px-2 py-0.5 rounded text-[10px] font-mono font-bold cursor-pointer transition-colors',
                  currentAbjad === 'SEMUA' ? 'bg-[#0B1220] text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 hover:bg-neutral-200'
                ]"
              >
                Semua
              </button>
              <button
                v-for="letter in alphabetList"
                :key="letter"
                type="button"
                @click="selectAbjad(letter)"
                :class="[
                  'w-6 h-6 rounded text-[10px] font-mono font-bold flex items-center justify-center cursor-pointer transition-colors shrink-0',
                  currentAbjad === letter ? 'bg-isf-red text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'
                ]"
              >
                {{ letter }}
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ 3. PROFILE GRID & RESULTS ════════════════════════════════════ -->
      <!-- Loading Skeleton -->
      <div v-if="status === 'loading'" class="py-12">
        <ArtikelSkeleton variant="rubrik" />
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="py-12">
        <ErrorMessage
          message="Gagal memuat direktori tokoh. Harap coba beberapa saat lagi."
          action-label="Coba Lagi"
          @retry="load"
        />
      </div>

      <!-- Tokoh Cards Grid -->
      <div v-else class="space-y-8">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-200 dark:border-neutral-800">
          <span class="text-xs font-mono font-bold text-neutral-500 uppercase tracking-wider">
            Menampilkan {{ meta.total }} Profil Tokoh
          </span>
          <span v-if="currentKategori !== 'semua'" class="text-xs font-mono text-isf-red capitalize">
            Klaster: {{ currentKategori }}
          </span>
        </div>

        <!-- Empty State -->
        <div
          v-if="items.length === 0"
          class="rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-12 text-center"
        >
          <div class="w-14 h-14 rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-400 flex items-center justify-center mx-auto mb-4">
            <IconUser class="w-7 h-7" />
          </div>
          <h4 class="font-headline text-lg font-bold text-neutral-900 dark:text-white mb-1">
            Tidak Ada Tokoh yang Sesuai
          </h4>
          <p class="text-xs text-neutral-500 font-space-grotesk max-w-md mx-auto mb-4">
            Tidak ditemukan tokoh dengan kriteria pencarian atau filter yang Anda pilih. Coba sesuaikan kata kunci atau bersihkan filter.
          </p>
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="selectKategori('semua'); selectIsu('semua'); selectAbjad('semua'); clearSearch()"
          >
            Reset Semua Filter
          </Button>
        </div>

        <!-- Cards Grid (1 col mobile, 2 col tablet, 3-4 col desktop) -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <SosokCard
            v-for="tokoh in items"
            :key="tokoh.id"
            :tokoh="tokoh"
          />
        </div>

        <!-- Pagination -->
        <div v-if="meta.lastPage > 1" class="pt-6 flex justify-center">
          <Pagination
            :current-page="meta.currentPage"
            :last-page="meta.lastPage"
            :total="meta.total"
            :per-page="meta.perPage"
            @page-change="onPageChange"
          />
        </div>
      </div>
    </main>
  </div>
</template>

