<script setup>
/**
 * SurveiView.vue — Halaman Daftar & Katalog Survei (/survei).
 * Didesain sederhana, jelas, dan mudah dipahami untuk semua kalangan usia.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconChartBar,
  IconChevronRight,
  IconSearch,
  IconX,
  IconLock,
  IconArrowRight,
} from '@tabler/icons-vue'
import {
  SectionHeading,
  SurveiCard,
  FilterSelect,
} from '@/Components/Isf'
import MemberSurveyWidget from '@/Components/Isf/Survey/MemberSurveyWidget.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSurvei } from '@/api/services/isfPublicService'
import { useIsfSession } from '@/Composables/useIsfSession'

const route = useRoute()
const router = useRouter()
const { isLoggedIn, ensureLoaded } = useIsfSession()

const currentIsu = computed(() => (route.query.isu || route.query.topic || 'semua').toLowerCase())
const currentStatus = computed(() => (route.query.status || 'semua').toLowerCase())
const currentSearch = ref(route.query.q || '')
const currentPage = ref(parseInt(route.query.page, 10) || 1)

let searchDebounce = null

const data = ref(null)
const loading = ref(true)
const error = ref(false)

async function load() {
  loading.value = !data.value
  error.value = false
  try {
    const [res] = await Promise.all([
      getSurvei({
        isu: currentIsu.value,
        q: currentSearch.value,
        page: currentPage.value,
        limit: 12,
      }),
      ensureLoaded(),
    ])
    data.value = res
  } catch {
    if (!data.value) error.value = true
  } finally {
    loading.value = false
  }
}

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Survei',
  topics: [
    { slug: 'semua', name: 'Semua Topik' },
    { slug: 'politik', name: 'Politik' },
    { slug: 'ekonomi', name: 'Ekonomi' },
    { slug: 'sosial', name: 'Sosial' },
    { slug: 'hukum', name: 'Hukum' },
  ],
})

const surveys = computed(() => data.value?.surveys || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 12 })

const counts = computed(() => {
  const list = surveys.value || []
  return {
    semua: list.length,
    belum_diisi: list.filter((s) => s.member_status !== 'selesai' && s.member_status !== 'draft').length,
    draft: list.filter((s) => s.member_status === 'draft').length,
    selesai: list.filter((s) => s.member_status === 'selesai').length,
  }
})

const filteredSurveys = computed(() => {
  const list = surveys.value || []
  if (currentStatus.value === 'semua') return list
  if (currentStatus.value === 'belum_diisi') {
    return list.filter((s) => s.member_status !== 'selesai' && s.member_status !== 'draft')
  }
  if (currentStatus.value === 'draft') {
    return list.filter((s) => s.member_status === 'draft')
  }
  if (currentStatus.value === 'selesai') {
    return list.filter((s) => s.member_status === 'selesai')
  }
  return list
})

function updateUrlQuery(updates = {}) {
  const query = { ...route.query, ...updates }
  if (!query.isu || query.isu === 'semua') delete query.isu
  if (!query.status || query.status === 'semua') delete query.status
  if (!query.q || query.q.trim() === '') delete query.q
  if (query.page === 1 || !query.page) delete query.page
  router.push({ path: '/survei', query })
}

function selectStatus(val) {
  currentPage.value = 1
  updateUrlQuery({ status: val === 'semua' ? undefined : val, page: undefined })
}

function selectIsu(slug) {
  currentPage.value = 1
  updateUrlQuery({ isu: slug, page: undefined })
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
  return currentIsu.value !== 'semua' || currentStatus.value !== 'semua' || Boolean(currentSearch.value?.trim())
})

function resetAllFilters() {
  currentSearch.value = ''
  currentPage.value = 1
  updateUrlQuery({ isu: undefined, status: undefined, q: undefined, page: undefined })
}

function onPageChange(page) {
  currentPage.value = page
  updateUrlQuery({ page })
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

watch(
  () => [route.query.isu, route.query.status, route.query.q, route.query.page],
  () => {
    currentSearch.value = route.query.q || ''
    currentPage.value = parseInt(route.query.page, 10) || 1
    load()
  },
  { immediate: true }
)

watch(
  () => rubrik.value?.title,
  (title) => {
    if (typeof document !== 'undefined') {
      document.title = `${title || 'Survei'} · Indonesia Strategy Forum`
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ HEADER HERO RUBRIK SURVEI ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-4">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <span class="text-white font-bold">Survei</span>
        </nav>

        <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
          <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
          <span>SURVEI OPINI PUBLIK</span>
        </div>
        <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl font-black tracking-tight leading-none text-white">
          SURVEI
        </h1>
        <p class="text-sm sm:text-base text-neutral-300 mt-2 max-w-2xl leading-relaxed">
          Pilih survei yang tersedia di bawah ini untuk memberikan pendapat Anda.
        </p>
      </div>
    </header>

    <!-- ═══ SKELETON LOADER ═══ -->
    <main
      v-if="loading && !data"
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 space-y-8 animate-pulse"
    >
      <div class="h-36 rounded-lg bg-neutral-100 border border-neutral-200"></div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="n in 3" :key="n" class="h-56 rounded-lg bg-neutral-100 border border-neutral-200"></div>
      </div>
    </main>

    <!-- ═══ ERROR STATE ═══ -->
    <div v-else-if="error && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Survei Gagal Dimuat"
        message="Silakan periksa koneksi Anda dan coba lagi."
        action-label="Muat Ulang"
        @retry="load"
      />
    </div>

    <!-- ═══ KONTEN UTAMA ═══ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 space-y-8">
      <!-- 1. WIDGET SURVEI MENUNGGU JAWABAN -->
      <section aria-label="Survei Menunggu Jawaban Anda">
        <MemberSurveyWidget v-if="isLoggedIn" />

        <div
          v-else
          class="rounded-lg border border-neutral-300 bg-white p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
          <div class="space-y-1">
            <h2 class="font-headline text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
              <IconLock class="w-5 h-5 text-isf-red shrink-0" />
              <span>Masuk untuk Mengisi Survei</span>
            </h2>
            <p class="text-sm text-neutral-600">
              Silakan masuk terlebih dahulu agar jawaban Anda tersimpan dan tidak hilang.
            </p>
          </div>
          <div class="flex items-center gap-2.5 shrink-0">
            <a
              href="/login?redirect=/survei"
              class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-md bg-isf-red hover:bg-red-700 text-white font-bold text-sm transition-colors"
            >
              <span>Masuk</span>
              <IconArrowRight class="w-4 h-4" />
            </a>
            <a
              href="/register"
              class="inline-flex items-center px-4 py-2.5 rounded-md border border-neutral-300 bg-neutral-50 hover:bg-neutral-100 text-neutral-800 font-semibold text-sm transition-colors"
            >
              Daftar
            </a>
          </div>
        </div>
      </section>

      <!-- 2. DAFTAR SURVEI AKTIF -->
      <section class="rounded-lg border border-neutral-300 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-5 py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <SectionHeading
            title="Daftar Survei"
            size="lg"
          />

          <!-- Filter Topik & Pencarian -->
          <div class="flex flex-wrap items-center gap-2.5 text-xs">
            <FilterSelect
              :model-value="currentIsu"
              :options="rubrik.topics"
              :is-dark="false"
              size="sm"
              aria-label="Filter Topik Survei"
              class="min-w-[140px]"
              @update:model-value="selectIsu"
            />
            <div class="relative w-full sm:w-56">
              <IconSearch class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                v-model="currentSearch"
                type="text"
                placeholder="Cari survei..."
                @input="onSearchInput"
                class="w-full h-8 pl-8 pr-7 rounded-md bg-neutral-50 border border-neutral-300 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-isf-red focus:border-isf-red"
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
            <button
              v-if="hasActiveFilter"
              type="button"
              @click="resetAllFilters"
              class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-isf-red bg-red-50 hover:bg-red-100 border border-red-200 cursor-pointer"
            >
              <IconX class="w-3.5 h-3.5" />
              <span>Reset</span>
            </button>
          </div>
        </div>

        <!-- Tab Filter Status -->
        <div class="px-5 py-3 bg-neutral-50 border-b border-neutral-200 flex flex-wrap gap-2">
          <button
            type="button"
            @click="selectStatus('semua')"
            class="rounded-md px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer"
            :class="[
              currentStatus === 'semua'
                ? 'bg-neutral-900 text-white'
                : 'bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100'
            ]"
          >
            Semua ({{ counts.semua }})
          </button>

          <button
            type="button"
            @click="selectStatus('belum_diisi')"
            class="rounded-md px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer"
            :class="[
              currentStatus === 'belum_diisi'
                ? 'bg-blue-600 text-white'
                : 'bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100'
            ]"
          >
            Belum Diisi ({{ counts.belum_diisi }})
          </button>

          <button
            type="button"
            @click="selectStatus('draft')"
            class="rounded-md px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer"
            :class="[
              currentStatus === 'draft'
                ? 'bg-amber-500 text-white'
                : 'bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100'
            ]"
          >
            Draft ({{ counts.draft }})
          </button>

          <button
            type="button"
            @click="selectStatus('selesai')"
            class="rounded-md px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer"
            :class="[
              currentStatus === 'selesai'
                ? 'bg-emerald-600 text-white'
                : 'bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100'
            ]"
          >
            Selesai ({{ counts.selesai }})
          </button>
        </div>

        <!-- Grid Kartu Survei -->
        <div v-if="filteredSurveys && filteredSurveys.length > 0" class="p-5 sm:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <SurveiCard
              v-for="survey in filteredSurveys"
              :key="survey.id"
              :survey="survey"
              theme="light"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-14 px-4">
          <IconChartBar class="w-10 h-10 text-neutral-400 mx-auto mb-2" />
          <p class="font-bold text-neutral-800 text-sm">Belum ada survei pada kategori ini.</p>
          <button
            type="button"
            @click="resetAllFilters"
            class="mt-3 px-4 py-2 rounded-md bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 cursor-pointer"
          >
            Tampilkan Semua
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="meta.lastPage > 1" class="py-4 px-5 flex justify-center border-t border-neutral-200">
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
