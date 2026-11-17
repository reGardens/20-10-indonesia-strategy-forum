<script setup>
/**
 * SosokView.vue — Direktori Profil Tokoh & Aktor Strategis Nasional (/sosok dan /sosok/:kategori).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik:
 *   Breadcrumb responsif (whitespace-nowrap & truncate), red kicker badge, serif heading, dan quick pill tabs.
 * - Multi-Filter: Klaster Kategori (Eksekutif, Legislatif, Yudikatif, dll.), Fokus Isu, dan Indeks Alfabet A-Z.
 * - Seksi 1: Tokoh Paling Berpengaruh (Featured Hero) dengan aksen border-t-4 border-t-isf-red dan layout 12-kolom.
 * - Seksi 2: Direktori Profil Tokoh Nasional dalam grid SosokCard responsif lengkap dengan pagination.
 * - Skeleton loader desktop dan mobile yang presisi mencerminkan struktur konten.
 * - Sinkronisasi URL query string agar tautan hasil filter shareable.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconSearch,
  IconX,
  IconAward,
  IconArrowRight,
  IconChevronRight,
  IconFilter,
  IconUser,
  IconSparkles,
} from '@tabler/icons-vue'
import {
  SectionHeading,
  SosokCard,
} from '@/Components/Isf'
import Button from '@/Components/UI/Button.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
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
    { slug: 'semua', name: 'Semua Tokoh' },
    { slug: 'eksekutif', name: 'Eksekutif' },
    { slug: 'legislatif', name: 'Legislatif' },
    { slug: 'yudikatif', name: 'Yudikatif' },
    { slug: 'pakar', name: 'Pakar & Akademisi' },
    { slug: 'militer', name: 'Militer & Hankam' },
    { slug: 'pengusaha', name: 'Pengusaha' },
  ],
  isuOptions: [
    { slug: 'semua', name: 'Semua Isu' },
    { slug: 'politik', name: 'Politik' },
    { slug: 'hukum', name: 'Hukum' },
    { slug: 'ekonomi', name: 'Ekonomi' },
    { slug: 'pertahanan', name: 'Pertahanan' },
    { slug: 'kebijakan', name: 'Kebijakan' },
  ],
  activeKategori: 'semua',
  activeIsu: 'semua',
  activeAbjad: 'semua',
})

const hero = computed(() => data.value?.hero || null)
const items = computed(() => data.value?.items || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 12 })

// Options untuk FilterSelect Isu & Abjad (Desain Simpel)
const isuSelectOptions = computed(() => {
  return (rubrik.value.isuOptions || []).map(opt => ({
    value: opt.slug,
    label: opt.name,
  }))
})

const abjadSelectOptions = computed(() => {
  const list = [{ value: 'SEMUA', label: 'Semua Huruf (A–Z)' }]
  alphabetList.forEach(letter => {
    list.push({ value: letter, label: `Huruf ${letter}` })
  })
  return list
})

const hasActiveFilter = computed(() => {
  return Boolean(
    (currentSearch.value && currentSearch.value.trim()) ||
    (currentIsu.value && currentIsu.value !== 'semua') ||
    (currentAbjad.value && currentAbjad.value !== 'SEMUA' && currentAbjad.value !== 'semua')
  )
})

function resetAllFilters() {
  currentSearch.value = ''
  currentPage.value = 1
  updateQuery({ isu: 'semua', abjad: 'semua', q: '', page: 1 })
}

// Dynamic Page Title
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
    const val = newQuery[key] ? newQuery[key].toString().toLowerCase() : ''
    if (!val || val === 'semua' || (key === 'page' && Number(newQuery[key]) <= 1)) {
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
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK SOSOK (Gaya Editorial Navy identik dengan Politik) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink
            to="/sosok"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              currentKategori === 'semua' ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Sosok
          </RouterLink>
          <template v-if="currentKategori !== 'semua'">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-white font-bold whitespace-nowrap shrink-0 capitalize">
              {{ rubrik.kategoriOptions?.find(o => o.slug === currentKategori)?.name || currentKategori }}
            </span>
          </template>
        </nav>

        <!-- Rubrik Title & Deskripsi -->
        <div>
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>DIREKTORI TOKOH & AKTOR STRATEGIS</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
            SOSOK
          </h1>
          <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
            {{ rubrik.description }}
          </p>

          <!-- FILTER KATEGORI TOKOH (GAMBAR 5: TETAP DI BAWAH DESKRIPSI JUDUL DI TEMPAT SEBELUMNYA) -->
          <div class="mt-6 flex items-center gap-2 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1" role="tablist" aria-label="Filter Kategori Sosok">
            <button
              v-for="cat in rubrik.kategoriOptions"
              :key="cat.slug"
              type="button"
              @click="selectKategori(cat.slug)"
              :class="[
                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                currentKategori === cat.slug
                  ? 'bg-isf-red text-white'
                  : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
              ]"
            >
              {{ cat.name }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ FILTER BAR (SEARCH, ISU, ABJAD) ═══ -->
    <div class="bg-white border-b border-neutral-200/90 shadow-2xs py-3.5 sm:py-4">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Row: Dropdown Isu & Abjad (Kiri) + Search Input (Kanan) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 text-xs">
          <!-- Left: Dropdown Filter Isu + Indeks Abjad + Reset Button -->
          <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
            <!-- Filter Isu Dropdown (FilterSelect) -->
            <div class="flex items-center gap-1.5 text-xs text-neutral-600">
              <span class="text-neutral-500 font-medium whitespace-nowrap">Isu:</span>
              <FilterSelect
                :model-value="currentIsu"
                :options="isuSelectOptions"
                :is-dark="false"
                size="sm"
                aria-label="Filter Fokus Isu"
                @update:model-value="selectIsu"
              />
            </div>

            <!-- Filter Abjad A-Z Dropdown (FilterSelect) -->
            <div class="flex items-center gap-1.5 text-xs text-neutral-600">
              <span class="text-neutral-500 font-medium whitespace-nowrap">Abjad:</span>
              <FilterSelect
                :model-value="currentAbjad"
                :options="abjadSelectOptions"
                :is-dark="false"
                size="sm"
                aria-label="Filter Indeks Abjad A-Z"
                @update:model-value="selectAbjad"
              />
            </div>

            <!-- Tombol Reset Filter jika ada filter aktif -->
            <button
              v-if="hasActiveFilter"
              type="button"
              @click="resetAllFilters"
              class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-isf-red bg-red-50 hover:bg-red-100 border border-red-200 transition-colors cursor-pointer"
              title="Reset semua filter pencarian"
            >
              <IconX class="w-3.5 h-3.5" />
              <span>Reset</span>
            </button>
          </div>

          <!-- Right: Search Input Utama (Sebelah Kanan) -->
          <div class="relative w-full md:w-72 lg:w-80 shrink-0">
            <IconSearch class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              :value="currentSearch"
              placeholder="Cari nama tokoh, jabatan, atau rekam jejak..."
              class="w-full h-9 pl-9 pr-8 rounded-lg bg-neutral-50 border border-neutral-300 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-isf-red focus:border-isf-red transition-all"
              @input="onSearchInput"
            />
            <button
              v-if="currentSearch"
              type="button"
              @click="clearSearch"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-700 text-xs px-1 cursor-pointer"
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
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse"
      aria-busy="true"
      aria-label="Memuat direktori tokoh"
    >
      <!-- Hero Skeleton -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
          <div class="md:col-span-3 aspect-[3/4] bg-neutral-200 rounded-lg"></div>
          <div class="md:col-span-9 space-y-3">
            <div class="h-3 w-32 bg-neutral-200 rounded"></div>
            <div class="h-8 w-2/3 bg-neutral-300 rounded"></div>
            <div class="h-4 w-1/2 bg-neutral-200 rounded"></div>
            <div class="h-4 w-full bg-neutral-200 rounded mt-4"></div>
            <div class="h-4 w-5/6 bg-neutral-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Grid Cards Skeleton -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden p-6">
        <div class="h-4 w-40 bg-neutral-300 rounded mb-6"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="n in 4" :key="n" class="h-72 bg-neutral-100 rounded-lg border border-neutral-200"></div>
        </div>
      </div>
    </main>

    <!-- Error State -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        message="Gagal memuat direktori tokoh. Harap coba beberapa saat lagi."
        action-label="Coba Lagi"
        retryable
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: HERO TOKOH PALING BERPENGARUH (BORDER-T-4 ISF-RED) ══ -->
      <section
        v-if="hero && currentKategori === 'semua' && !currentSearch && currentAbjad === 'semua'"
        aria-labelledby="hero-tokoh-title"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="hero-tokoh-title"
            title="Sorotan Tokoh"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Tokoh Paling Berpengaruh Minggu Ini</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 lg:gap-8 items-center">
            <!-- Left: Tokoh Photo -->
            <div class="md:col-span-4 lg:col-span-3">
              <div class="relative aspect-[3/4] rounded-lg overflow-hidden border border-neutral-200 shadow-sm bg-neutral-100">
                <img
                  v-if="hero.photo_url"
                  :src="hero.photo_url"
                  :alt="hero.photo_alt || hero.name"
                  class="w-full h-full object-cover object-top"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-neutral-400">
                  <IconUser class="w-16 h-16 opacity-40" />
                </div>

                <div class="absolute bottom-2.5 left-2.5 px-2.5 py-0.5 rounded text-[10px] font-mono font-bold bg-isf-red text-white uppercase tracking-wider shadow-xs">
                  Sorotan Utama
                </div>
              </div>
            </div>

            <!-- Right: Profile highlights -->
            <div class="md:col-span-8 lg:col-span-9 flex flex-col justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span class="px-2.5 py-0.5 rounded bg-neutral-100 text-neutral-700 text-xs font-mono font-bold uppercase tracking-wider border border-neutral-200">
                    {{ hero.role_category_label || hero.role_category || 'Legislatif' }}
                  </span>
                  <span class="px-2.5 py-0.5 rounded bg-amber-500/10 text-amber-700 text-xs font-mono font-bold flex items-center gap-1 border border-amber-500/20">
                    <IconAward class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                    <span>Skor Pengaruh {{ hero.influence_score || hero.skor_value }}/100</span>
                  </span>
                </div>

                <h2 class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-neutral-950 mb-1 hover:text-isf-red transition-colors">
                  <RouterLink :to="hero.to || `/sosok/${hero.slug}`">
                    {{ hero.name }}
                  </RouterLink>
                </h2>
                <p class="text-xs sm:text-sm text-neutral-500 font-space-grotesk font-semibold mb-3">
                  {{ hero.position }}
                </p>

                <p class="font-serif text-sm sm:text-base text-neutral-600 leading-relaxed line-clamp-3 sm:line-clamp-4 mb-6">
                  "{{ hero.summary_analysis || hero.bio || hero.teaser }}"
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-neutral-200">
                <RouterLink
                  :to="hero.to || `/sosok/${hero.slug}`"
                  class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm shadow-xs transition-all cursor-pointer"
                >
                  <span>Eksplorasi Profil & Rekam Jejak</span>
                  <IconArrowRight class="w-4 h-4 shrink-0" />
                </RouterLink>

                <RouterLink
                  :to="`/sosok/${hero.slug}/analisis`"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-semibold text-xs sm:text-sm border border-neutral-300 transition-all cursor-pointer"
                >
                  <span>Tinjau Skor & Ulasan (#41)</span>
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 2: DIREKTORI PROFIL TOKOH (GRID KARTU) ═════════════════ -->
      <section
        aria-labelledby="direktori-tokoh-title"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="direktori-tokoh-title"
            :title="currentKategori !== 'semua' ? `Tokoh Klaster: ${currentKategori.toUpperCase()}` : 'Direktori Tokoh Nasional'"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>
              Menampilkan {{ meta.total }} Profil Tokoh{{ currentIsu !== 'semua' ? ` (Isu: ${currentIsu.toUpperCase()})` : '' }}
            </template>
          </SectionHeading>
        </div>

        <!-- Empty State -->
        <div
          v-if="items.length === 0"
          class="p-12 text-center"
        >
          <div class="w-14 h-14 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
            <IconUser class="w-7 h-7" />
          </div>
          <h4 class="font-headline text-lg font-bold text-neutral-900 mb-1">
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

        <!-- Cards Grid -->
        <div v-else class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <SosokCard
              v-for="tokoh in items"
              :key="tokoh.id"
              :tokoh="tokoh"
            />
          </div>
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
    </main>
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
