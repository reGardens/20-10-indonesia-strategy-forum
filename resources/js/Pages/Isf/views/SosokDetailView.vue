<script setup>
/**
 * SosokDetailView.vue — Halaman Detail Tokoh Rubrik Sosok ISF (/sosok/:slug dan /sosok/:slug/:tab).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik, Hukum, & SosokView:
 *   Breadcrumb responsif (whitespace-nowrap & truncate anti-pecah kata), kicker badge merah,
 *   foto tokoh portrait, headline serif, badge kategori & skor pengaruh bintang emas,
 *   analisis ringkas ISF, tombol aksi ikuti & bagikan profil.
 * - 5 Tab dengan URL mandiri yang dapat dibuka langsung & dibagikan:
 *   - Tab 1: Profil & Rekam Jejak (/profil)
 *   - Tab 2: Analisis ISF & Rating (/analisis) [Skor Sorotan, Policy Matrix/Karya, & Indeks Tokoh #41 Rating & Ulasan Lengkap]
 *   - Tab 3: Jejaring & Afiliasi (/jejaring)
 *   - Tab 4: Berita & Aktivitas (/berita)
 *   - Tab 5: Kutipan & Kontroversi (/kontroversi)
 * - Penyelarasan visual identik dengan rubrik Politik:
 *   - Setiap seksi memiliki garis aksen merah khas ISF di atas border (`border-t-4 border-t-isf-red`).
 *   - Kartu berstruktur `rounded-lg border border-neutral-300 shadow-xs overflow-hidden`.
 *   - Header tiap seksi menggunakan SectionHeading dengan slot eyebrow di dalam `border-b border-neutral-200`.
 *   - Seksi Tokoh Terkait bergaris 4-kolom divide-neutral-200 dengan latar cream #FBF9F4 (persis Politik).
 *   - Seksi Berita Terkait berformat grid 2-kolom bergaris dengan line-clamp-2 (persis Politik).
 * - Transisi tab mulus tanpa scroll liar (scrollBehavior router guard & router.push tanpa window.scrollTo).
 * - Fungsionalitas penuh Rating & Ulasan Tokoh (#41):
 *   - Sub-tab: Karya (Policy Matrix), Tingkat Kesukaan (★ 4-5), Tidak Suka (★ 1-2)
 *   - Kondisi Guest: Callout login wajib akun ISF (data-testid="ulasan-login")
 *   - Kondisi User Sudah Mengulas: Kartu ulasan user (data-testid="ulasan-mine") + tombol Edit (data-testid="ulasan-edit")
 *   - Form Beri/Edit Ulasan: Rating bintang interaktif, textarea ulasan, checkbox anonim, pesan status & batal
 *   - Daftar Ulasan: ReviewCard dengan sebaran bintang (RatingSummary) dan tombol muat ulasan lainnya
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IconUser,
  IconAward,
  IconChartBar,
  IconNetwork,
  IconNews,
  IconQuote,
  IconShare,
  IconCheck,
  IconHeart,
  IconBuildingBank,
  IconFileText,
  IconLock,
  IconPencil,
  IconCalendar,
  IconExternalLink,
  IconChevronRight,
  IconArrowRight,
  IconClock,
  IconThumbUp,
  IconThumbDown,
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  ImagePlaceholder,
  IsfLink,
  MonthCalendar,
  RatingSummary,
  ReviewCard,
  SectionHeading,
  StarRating,
  SosokCard,
  SosokNetworkGraph,
} from '@/Components/Isf'
import Button from '@/Components/UI/Button.vue'
import Label from '@/Components/UI/Label.vue'
import Table from '@/Components/UI/Table.vue'
import TableHeader from '@/Components/UI/TableHeader.vue'
import TableBody from '@/Components/UI/TableBody.vue'
import TableRow from '@/Components/UI/TableRow.vue'
import TableHead from '@/Components/UI/TableHead.vue'
import TableCell from '@/Components/UI/TableCell.vue'
import Tabs from '@/Components/UI/Tabs.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSosokDetail } from '@/api/services/isfPublicService'
import { getTokohUlasan, submitTokohUlasan } from '@/api/services/isfTokohReviewService'
import { useIsfContent } from '@/Composables/useIsfContent'
import { formatDate } from '@/Utils/formatters'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const linkClass =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2'

const slug = computed(() => route.params.slug)

// Tab active from route param (/sosok/:slug/:tab) with fallback to 'profil'
const activeTab = computed(() => {
  const t = route.params.tab
  const validTabs = ['profil', 'analisis', 'jejaring', 'berita', 'kontroversi']
  return validTabs.includes(t) ? t : 'profil'
})

// Fetch detail tokoh sosok
const { data, status, load } = useIsfContent(() => {
  return getSosokDetail(slug.value)
})

const tokoh = computed(() => data.value?.tokoh || null)
const tabsData = computed(() => data.value?.tabs || {})
const calendarData = computed(() => data.value?.calendar || null)
const relatedTokoh = computed(() => data.value?.relatedTokoh || [])
const tokohEksekutif = computed(() => data.value?.tokohEksekutif || data.value?.relatedTokoh || [])
const tokohLegislatif = computed(() => data.value?.tokohLegislatif || data.value?.relatedTokoh || [])

// Non-functional local follow toggle
const isFollowing = ref(false)
function toggleFollow() {
  isFollowing.value = !isFollowing.value
}

function switchTab(tabKey) {
  if (tabKey === 'profil') {
    router.push({ path: `/sosok/${slug.value}` })
  } else {
    router.push({ path: `/sosok/${slug.value}/${tabKey}` })
  }
}

function shareUrl() {
  if (typeof navigator !== 'undefined' && navigator.share) {
    navigator.share({
      title: tokoh.value?.name,
      text: tokoh.value?.summary_analysis,
      url: window.location.href,
    }).catch(() => {})
  } else if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
    alert('Tautan berhasil disalin ke papan klip!')
  }
}

/* ══════════════════════════════════════════════════════════════════════════
 * INDEKS TOKOH: RATING & ULASAN (#41) LOGIC LENGKAP & TERUJI
 * ══════════════════════════════════════════════════════════════════════════ */
const reviewFilterTab = ref('karya') // 'karya' | 'suka' | 'tidak-suka'
const ulasan = ref(null)
const ulasanStatus = ref('loading') // loading | ready | error
const reviews = ref([])
const reviewPage = ref({ currentPage: 1, lastPage: 1, total: 0 })
const loadingMore = ref(false)
let ulasanRequest = 0

const summary = computed(
  () =>
    ulasan.value?.summary ?? {
      average: 0,
      total: 0,
      distribution: {},
      suka: 0,
      tidakSuka: 0,
    }
)

const viewer = computed(
  () => ulasan.value?.viewer ?? { authenticated: false, myReview: null }
)

const reviewTab = computed(() =>
  reviewFilterTab.value === 'tidak-suka' ? 'tidak-suka' : 'suka'
)

const karyaCount = computed(() => {
  return tabsData.value?.analisis?.policyMatrix?.rows?.length ??
    tokoh.value?.policyMatrix?.rows?.length ?? 0
})

const indeksStats = computed(() => [
  { key: 'karya', label: 'Karya', value: karyaCount.value },
  {
    key: 'suka',
    label: 'Tingkat Kesukaan',
    value: ulasan.value ? summary.value.suka : '–',
  },
  {
    key: 'tidak-suka',
    label: 'Tidak Suka',
    value: ulasan.value ? summary.value.tidakSuka : '–',
  },
])

const indeksTabs = computed(() => [
  { key: 'karya', label: `Karya (${karyaCount.value})` },
  { key: 'suka', label: `Tingkat Kesukaan (${summary.value.suka})` },
  { key: 'tidak-suka', label: `Tidak Suka (${summary.value.tidakSuka})` },
])

function applyUlasan(payload, { append = false } = {}) {
  ulasan.value = payload
  reviews.value = append
    ? [...reviews.value, ...(payload.reviews?.data ?? [])]
    : payload.reviews?.data ?? []
  reviewPage.value = {
    currentPage: payload.reviews?.currentPage ?? 1,
    lastPage: payload.reviews?.lastPage ?? 1,
    total: payload.reviews?.total ?? 0,
  }
}

async function loadUlasan({ page = 1, append = false } = {}) {
  const currentSlug = route.params.slug
  if (!currentSlug) return
  const requestId = ++ulasanRequest
  if (append) loadingMore.value = true
  else ulasanStatus.value = 'loading'
  try {
    const payload = await getTokohUlasan(currentSlug, {
      tab: reviewTab.value,
      page,
    })
    if (requestId !== ulasanRequest) return
    applyUlasan(payload, { append })
    ulasanStatus.value = 'ready'
  } catch {
    if (requestId !== ulasanRequest) return
    if (!append) ulasanStatus.value = 'error'
  } finally {
    if (requestId === ulasanRequest) loadingMore.value = false
  }
}

// Daftar ulasan dimuat ulang saat pindah antara "Tingkat Kesukaan" dan "Tidak Suka"
watch(reviewTab, () => {
  if (reviewFilterTab.value !== 'karya') {
    loadUlasan()
  }
})

/* Form ulasan */
const form = ref({ rating: 0, body: '', isAnonymous: false })
const formErrors = ref({})
const formMessage = ref('')
const submitting = ref(false)

function resetForm(myReview) {
  form.value = {
    rating: myReview?.rating ?? 0,
    body: myReview?.body ?? '',
    isAnonymous: myReview?.isAnonymous ?? false,
  }
  formErrors.value = {}
}

// Isi form dengan ulasan milik user (kirim ulang = perbarui)
watch(
  () => viewer.value.myReview?.updatedAt ?? null,
  () => resetForm(viewer.value.myReview)
)

const editing = ref(false)
const showForm = computed(
  () =>
    viewer.value.authenticated && (!viewer.value.myReview || editing.value)
)
const myReviewCard = computed(() => {
  const mine = viewer.value.myReview
  if (!mine) return null
  const name = mine.isAnonymous
    ? 'Anonim'
    : viewer.value.name || 'Pengguna ISF'
  return {
    id: 'mine',
    rating: mine.rating,
    body: mine.body,
    isAnonymous: mine.isAnonymous,
    isMine: true,
    author: {
      name,
      initials: mine.isAnonymous
        ? null
        : name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((p) => p[0].toUpperCase())
            .join(''),
    },
    updatedAt: mine.updatedAt,
  }
})

function startEdit() {
  resetForm(viewer.value.myReview)
  formMessage.value = ''
  editing.value = true
  nextTick(() => document.getElementById('ulasan-body')?.focus())
}

function cancelEdit() {
  editing.value = false
  resetForm(viewer.value.myReview)
  nextTick(() => document.getElementById('ulasan-edit-btn')?.focus())
}

const loginHref = computed(
  () => `/login?redirect=${encodeURIComponent(route.fullPath)}`
)

async function submitUlasan() {
  formErrors.value = {}
  formMessage.value = ''
  if (!form.value.rating) {
    formErrors.value = { rating: 'Pilih rating bintang 1–5.' }
    return
  }
  if (form.value.body.trim().length < 3) {
    formErrors.value = { body: 'Ulasan minimal 3 karakter.' }
    return
  }
  submitting.value = true
  const requestId = ++ulasanRequest
  try {
    const payload = await submitTokohUlasan(
      route.params.slug,
      {
        rating: form.value.rating,
        body: form.value.body.trim(),
        is_anonymous: form.value.isAnonymous,
      },
      { tab: reviewTab.value }
    )
    if (requestId === ulasanRequest) applyUlasan(payload)
    ulasanStatus.value = 'ready'
    editing.value = false
    formMessage.value =
      'Terima kasih, ulasan Anda tersimpan dan skor sudah diperbarui.'
  } catch (error) {
    const errStatus = error?.response?.status
    if (errStatus === 401 || errStatus === 403) {
      ulasan.value = {
        ...ulasan.value,
        viewer: { authenticated: false, myReview: null },
      }
      formMessage.value =
        'Sesi login berakhir. Silakan login kembali untuk mengirim ulasan.'
    } else if (errStatus === 422) {
      const errs = error.response.data?.errors ?? {}
      formErrors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [
          k === 'is_anonymous' ? 'isAnonymous' : k,
          v[0],
        ])
      )
    } else if (errStatus === 429) {
      formMessage.value =
        'Terlalu banyak percobaan. Coba lagi dalam satu menit.'
    } else {
      formMessage.value =
        'Ulasan gagal dikirim. Periksa koneksi Anda lalu coba lagi.'
    }
  } finally {
    submitting.value = false
  }
}

// Watcher untuk perubahan slug (hanya scroll bila slug tokoh berganti, bukan saat ganti tab)
watch(
  () => route.params.slug,
  (newSlug, oldSlug) => {
    if (oldSlug && newSlug === oldSlug) return
    load()
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  },
  { immediate: true }
)

// Watcher untuk activeTab: jika tab analisis dibuka, muat data ulasan
watch(
  () => activeTab.value,
  (tab) => {
    if (tab === 'analisis' && !ulasan.value) {
      loadUlasan()
    }
  },
  { immediate: true }
)

// Update document title
watch(
  () => tokoh.value?.name,
  (name) => {
    if (name && typeof document !== 'undefined') {
      document.title = `${name} — Profil Sosok · ISF`
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK SOSOK (GAYA EDITORIAL NAVY KONSISTEN) ═════ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-10 pb-0">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav: Responsive with anti-breaking letter classes -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-6 select-none min-w-0">
          <router-link to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</router-link>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <router-link to="/sosok" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Sosok</router-link>
          <template v-if="tokoh?.role_category">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <router-link :to="`/sosok/${tokoh.role_category}`" class="whitespace-nowrap shrink-0 hover:text-white transition-colors capitalize">
              {{ tokoh.role_category_label || tokoh.role_category }}
            </router-link>
          </template>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <span class="text-white font-bold whitespace-nowrap shrink-0 truncate max-w-[180px] sm:max-w-xs md:max-w-md">
            {{ tokoh?.name || 'Detail Tokoh' }}
          </span>
        </nav>

        <!-- Kicker Badge & Tokoh Header -->
        <div v-if="tokoh" class="pb-8">
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-3">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>DIREKTORI TOKOH & AKTOR STRATEGIS</span>
          </div>

          <!-- Hero Profile 12-Column Grid -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 lg:gap-8 items-start">
            <!-- Left: Foto Tokoh Portrait -->
            <div class="md:col-span-4 lg:col-span-3">
              <div class="relative aspect-[3/4] rounded-xl overflow-hidden border border-neutral-700/80 shadow-md bg-neutral-900">
                <img
                  v-if="tokoh.photo_url"
                  :src="tokoh.photo_url"
                  :alt="tokoh.photo_alt || tokoh.name"
                  class="w-full h-full object-cover object-top"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-neutral-600">
                  <IconUser class="w-20 h-20 opacity-30" />
                </div>

                <div
                  v-if="tokoh.is_influential_weekly"
                  class="absolute bottom-2.5 left-2.5 px-2.5 py-0.5 rounded bg-isf-red text-white text-[10px] font-mono font-bold tracking-wider uppercase shadow-xs"
                >
                  Tokoh Pekan Ini
                </div>
              </div>
            </div>

            <!-- Right: Identitas, Kategori, Skor Pengaruh, Analisis, & Tombol Aksi -->
            <div class="md:col-span-8 lg:col-span-9 flex flex-col justify-between h-full">
              <div>
                <!-- Badges & Skor -->
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                  <span class="px-2.5 py-0.5 rounded bg-isf-red text-white text-xs font-mono font-bold uppercase tracking-wider">
                    {{ tokoh.role_category_label || tokoh.role_category }}
                  </span>

                  <span
                    v-if="tokoh.influence_score || tokoh.skor_value"
                    class="px-2.5 py-0.5 rounded bg-white/10 text-white text-xs font-mono font-bold flex items-center gap-1.5 border border-white/20 shadow-2xs"
                  >
                    <IconAward class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                    <span>Skor Pengaruh {{ tokoh.influence_score || tokoh.skor_value }}/100</span>
                  </span>

                  <span
                    v-for="isu in tokoh.issues"
                    :key="isu"
                    class="px-2 py-0.5 rounded bg-white/10 text-neutral-300 text-[11px] font-mono capitalize border border-white/10"
                  >
                    #{{ isu }}
                  </span>
                </div>

                <!-- Tokoh Name & Position -->
                <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-tight text-white mb-2">
                  {{ tokoh.name }}
                </h1>
                <p class="text-sm sm:text-base md:text-lg text-neutral-300 font-space-grotesk font-medium mb-4">
                  {{ tokoh.position }}
                </p>

                <!-- Ringkasan Analisis ISF (Editorial Dark Box) -->
                <div class="p-4 sm:p-5 rounded-xl bg-white/5 border border-white/10 mb-6 backdrop-blur-2xs">
                  <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-isf-red mb-1">
                    Catatan Analisis ISF
                  </div>
                  <p class="text-xs sm:text-sm md:text-[15px] text-neutral-200 font-serif italic leading-relaxed">
                    "{{ tokoh.summary_analysis || tokoh.bio }}"
                  </p>
                </div>
              </div>

              <!-- Action Buttons: Ikuti Tokoh & Bagikan -->
              <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-neutral-800">
                <button
                  type="button"
                  @click="toggleFollow"
                  :class="[
                    'inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs sm:text-sm font-bold font-space-grotesk transition-all cursor-pointer shadow-xs',
                    isFollowing
                      ? 'bg-neutral-800 text-white border border-neutral-700'
                      : 'bg-isf-red text-white hover:bg-red-700'
                  ]"
                  :aria-pressed="isFollowing"
                >
                  <IconCheck v-if="isFollowing" class="w-4 h-4 text-emerald-400" />
                  <IconHeart v-else class="w-4 h-4 text-white" />
                  <span>{{ isFollowing ? 'Mengikuti Tokoh' : 'Ikuti Pembaruan Tokoh' }}</span>
                </button>

                <button
                  type="button"
                  @click="shareUrl"
                  class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-neutral-700 bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-semibold font-space-grotesk cursor-pointer transition-colors"
                >
                  <IconShare class="w-4 h-4 text-neutral-300" />
                  <span>Bagikan Profil</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ 2. 5 TAB NAVIGATOR WITH DEDICATED URLS (NAVY HEADER BAR) ═════ -->
        <nav
          v-if="tokoh"
          aria-label="Navigasi Tab Tokoh"
          class="border-t border-neutral-800 flex items-center gap-1 sm:gap-2 overflow-x-auto isf-no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0"
        >
          <!-- Tab 1: Profil -->
          <button
            type="button"
            @click="switchTab('profil')"
            :class="[
              'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-all shrink-0',
              activeTab === 'profil'
                ? 'border-isf-red text-white bg-white/5'
                : 'border-transparent text-neutral-400 hover:text-white hover:bg-white/5'
            ]"
          >
            <IconUser class="w-4 h-4" />
            <span>Profil & Rekam Jejak</span>
          </button>

          <!-- Tab 2: Analisis ISF & Rating -->
          <button
            type="button"
            @click="switchTab('analisis')"
            :class="[
              'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-all shrink-0',
              activeTab === 'analisis'
                ? 'border-isf-red text-white bg-white/5'
                : 'border-transparent text-neutral-400 hover:text-white hover:bg-white/5'
            ]"
          >
            <IconChartBar class="w-4 h-4" />
            <span>Analisis ISF & Rating</span>
            <span v-if="summary.total" class="px-1.5 py-0.2 rounded-full bg-isf-red text-[10px] text-white font-mono font-bold">
              {{ summary.total }}
            </span>
          </button>

          <!-- Tab 3: Jejaring & Afiliasi -->
          <button
            type="button"
            @click="switchTab('jejaring')"
            :class="[
              'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-all shrink-0',
              activeTab === 'jejaring'
                ? 'border-isf-red text-white bg-white/5'
                : 'border-transparent text-neutral-400 hover:text-white hover:bg-white/5'
            ]"
          >
            <IconNetwork class="w-4 h-4" />
            <span>Jejaring & Afiliasi</span>
          </button>

          <!-- Tab 4: Berita & Aktivitas -->
          <button
            type="button"
            @click="switchTab('berita')"
            :class="[
              'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-all shrink-0',
              activeTab === 'berita'
                ? 'border-isf-red text-white bg-white/5'
                : 'border-transparent text-neutral-400 hover:text-white hover:bg-white/5'
            ]"
          >
            <IconNews class="w-4 h-4" />
            <span>Berita & Aktivitas</span>
          </button>

          <!-- Tab 5: Kutipan & Kontroversi -->
          <button
            type="button"
            @click="switchTab('kontroversi')"
            :class="[
              'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-all shrink-0',
              activeTab === 'kontroversi'
                ? 'border-isf-red text-white bg-white/5'
                : 'border-transparent text-neutral-400 hover:text-white hover:bg-white/5'
            ]"
          >
            <IconQuote class="w-4 h-4" />
            <span>Kutipan & Kontroversi</span>
          </button>
        </nav>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan struktur detail konten) ═══ -->
    <main
      v-if="status === 'loading' && !data"
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse"
      aria-busy="true"
      aria-label="Memuat data profil tokoh"
    >
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red p-6 sm:p-8 space-y-4">
        <div class="h-4 w-40 bg-neutral-200 rounded"></div>
        <div class="h-10 w-3/4 bg-neutral-300 rounded"></div>
        <div class="h-4 w-1/2 bg-neutral-200 rounded"></div>
      </div>
    </main>

    <!-- Error / 404 State -->
    <div v-else-if="status === 'error' || status === 'not-found'" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Profil Tokoh Tidak Ditemukan"
        message="Profil tokoh tidak ditemukan atau belum dipublikasikan."
        action-label="Kembali ke Direktori Sosok"
        @retry="router.push('/sosok')"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW (STANDAR POLITIS KONSISTEN) ═══════════════ -->
    <main v-else-if="tokoh" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ── TAB 1: PROFIL & REKAM JEJAK ───────────────────────────────── -->
      <div v-if="activeTab === 'profil'" class="space-y-8 sm:space-y-12">
        <!-- 1.1 Narasi Biografi: Bergaya Card Editorial dengan BORDER-T-4 ISF-RED -->
        <section
          aria-labelledby="biografi-tokoh-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="biografi-tokoh-title"
              title="Biografi & Perjalanan Karier"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Rekam Jejak Kepemimpinan</template>
            </SectionHeading>
          </div>
          <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
            <p class="text-sm sm:text-base text-neutral-700 font-serif leading-relaxed whitespace-pre-line">
              {{ tabsData.profil?.bio || tokoh.bio || 'Belum ada data biografi naratif yang dicatat untuk tokoh ini.' }}
            </p>
          </div>
        </section>

        <!-- 1.2 Linimasa Karier & Riwayat Jabatan: BORDER-T-4 ISF-RED -->
        <section
          aria-labelledby="karier-jabatan-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="karier-jabatan-title"
              title="Linimasa Karier & Pengabdian Strategis"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Kronologi Peran Publik & Pendidikan Formal</template>
            </SectionHeading>
          </div>

          <div :class="[sectionPad, 'py-6 lg:py-8']">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
              <!-- Kiri: Linimasa Jabatan -->
              <div>
                <h4 class="font-headline text-base sm:text-lg font-bold text-neutral-950 mb-4 pb-2 border-b border-neutral-200 flex items-center gap-2">
                  <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
                  <span>Linimasa Jabatan & Peran</span>
                </h4>

                <div v-if="!tabsData.profil?.career_timeline?.length" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
                  Belum ada linimasa karier terdaftar.
                </div>

                <div v-else class="relative border-l-2 border-neutral-200 ml-3 space-y-6 pb-2">
                  <div v-for="(item, idx) in tabsData.profil.career_timeline" :key="idx" class="relative pl-6">
                    <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-isf-red border-4 border-white shadow-2xs"></div>
                    <span class="text-xs font-mono font-bold text-isf-red">{{ item.year }}</span>
                    <h5 class="text-sm font-bold text-neutral-900 mt-0.5">{{ item.title }}</h5>
                    <p class="text-xs text-neutral-600 font-space-grotesk mt-1 leading-relaxed">{{ item.description }}</p>
                  </div>
                </div>
              </div>

              <!-- Kanan: Riwayat Jabatan & Pendidikan Formal -->
              <div class="space-y-6">
                <!-- Riwayat Jabatan -->
                <div>
                  <h4 class="font-headline text-base sm:text-lg font-bold text-neutral-950 mb-3 pb-2 border-b border-neutral-200 flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
                    <span>Riwayat Jabatan Strategis</span>
                  </h4>
                  <div v-if="!tabsData.profil?.positions_history?.length" class="py-6 text-center text-xs text-neutral-400">
                    Belum ada riwayat jabatan tercatat.
                  </div>
                  <div v-else class="space-y-2.5">
                    <div v-for="(p, idx) in tabsData.profil.positions_history" :key="idx" class="p-3 rounded-lg bg-neutral-50 border border-neutral-200">
                      <span class="text-[10px] font-mono font-bold text-neutral-500">{{ p.period }}</span>
                      <div class="text-xs font-bold text-neutral-900">{{ p.position }}</div>
                      <div class="text-[11px] text-neutral-600 font-space-grotesk">{{ p.institution }}</div>
                    </div>
                  </div>
                </div>

                <!-- Pendidikan Formal -->
                <div>
                  <h4 class="font-headline text-base sm:text-lg font-bold text-neutral-950 mb-3 pb-2 border-b border-neutral-200 flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
                    <span>Latar Belakang Pendidikan</span>
                  </h4>
                  <div v-if="!tabsData.profil?.education?.length" class="py-6 text-center text-xs text-neutral-400">
                    Belum ada catatan pendidikan formal.
                  </div>
                  <div v-else class="space-y-2.5">
                    <div v-for="(edu, idx) in tabsData.profil.education" :key="idx" class="flex items-start justify-between p-3 rounded-lg bg-neutral-50 border border-neutral-200">
                      <div>
                        <div class="text-xs font-bold text-neutral-900">{{ edu.degree }}</div>
                        <div class="text-[11px] text-neutral-600 font-space-grotesk">{{ edu.institution }}</div>
                      </div>
                      <span class="text-[11px] font-mono font-bold text-isf-red">{{ edu.year }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- ── TAB 2: ANALISIS ISF & RATING (#41) ────────────────────────── -->
      <div v-else-if="activeTab === 'analisis'" class="space-y-8 sm:space-y-12">
        <!-- 2.1 INDEKS TOKOH: RATING & ULASAN PUBLIK (#41): BORDER-T-4 ISF-RED -->
        <section
          aria-labelledby="indeks-tokoh-heading"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="indeks-tokoh-heading"
              title="Indeks Persepsi Publik & Jejak Kebijakan"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Evaluasi Partisipatif & Rekam Jejak RUU (#41)</template>
            </SectionHeading>
          </div>

          <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8 space-y-6']">
            <!-- Rerata Star Callout Pill -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-neutral-200">
              <div>
                <h4 class="font-headline text-lg sm:text-xl font-bold text-neutral-950">
                  Tingkat Kepuasan & Jejak Kebijakan
                </h4>
                <p class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
                  Partisipasi anggota terverifikasi dalam mengevaluasi kepemimpinan, integritas, dan capaian kebijakan tokoh.
                </p>
              </div>

              <div class="flex items-center gap-2 text-xs font-mono font-bold px-3 py-1.5 rounded-lg bg-[#0B1220] text-white shrink-0 shadow-2xs">
                <span class="text-amber-400">★</span>
                <span>Rerata {{ Number(summary.average || 0).toFixed(1) }} ({{ summary.total }} ulasan)</span>
              </div>
            </div>

            <!-- Komponen RatingSummary Terstandarisasi -->
            <RatingSummary
              :class="{ 'opacity-60': ulasanStatus === 'loading' && !ulasan }"
              :average="summary.average"
              :total="summary.total"
              :distribution="summary.distribution"
              :aria-busy="ulasanStatus === 'loading' ? 'true' : 'false'"
            />

            <!-- Sub-Tabs: Karya | Tingkat Kesukaan (★ 4-5) | Tidak Suka (★ 1-2) -->
            <Tabs
              v-model="reviewFilterTab"
              :tabs="indeksTabs"
              variant="pills"
            >
              <template #icon-karya>
                <IconFileText class="h-4 w-4" aria-hidden="true" />
              </template>
              <template #icon-suka>
                <IconThumbUp class="h-4 w-4" aria-hidden="true" />
              </template>
              <template #icon-tidak-suka>
                <IconThumbDown class="h-4 w-4" aria-hidden="true" />
              </template>
            </Tabs>

            <!-- SUB-TAB 1: KARYA (Policy Matrix / Jejak Kebijakan) -->
            <section
              v-if="reviewFilterTab === 'karya'"
              aria-labelledby="jejak-kebijakan"
              class="space-y-4 pt-2"
            >
              <div class="flex items-center justify-between">
                <h4 id="jejak-kebijakan" class="font-headline text-base font-bold text-neutral-900">
                  Jejak Kebijakan (Policy Matrix)
                </h4>
                <span class="text-xs text-neutral-500 font-mono">{{ karyaCount }} instrumen kebijakan</span>
              </div>

              <div v-if="!karyaCount" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
                Belum ada data jejak kebijakan (policy matrix) yang dicatat untuk tokoh ini.
              </div>

              <div v-else class="overflow-x-auto rounded-lg border border-neutral-200">
                <Table class="min-w-[44rem]">
                  <caption class="sr-only">
                    Keterlibatan {{ tokoh.name }} per kebijakan/RUU dan tahap
                  </caption>
                  <TableHeader class="bg-neutral-50">
                    <TableRow>
                      <TableHead class="p-3 text-neutral-700 font-bold">Kebijakan / RUU</TableHead>
                      <TableHead class="p-3 text-neutral-700 font-bold">Tahap</TableHead>
                      <TableHead
                        v-for="col in (tabsData.analisis?.policyMatrix?.columns ?? tokoh.policyMatrix?.columns ?? [])"
                        :key="col"
                        class="p-3 text-center text-neutral-700 font-bold"
                      >
                        {{ col }}
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody class="divide-y divide-neutral-200 text-xs">
                    <TableRow
                      v-for="(row, rIdx) in (tabsData.analisis?.policyMatrix?.rows ?? tokoh.policyMatrix?.rows ?? [])"
                      :key="rIdx"
                    >
                      <TableHead scope="row" class="p-3 font-semibold text-neutral-900">
                        {{ row.kebijakan }}
                      </TableHead>
                      <TableCell class="p-3 text-neutral-600 font-mono">{{ row.tahap }}</TableCell>
                      <TableCell
                        v-for="(inv, iIdx) in row.involvement"
                        :key="iIdx"
                        class="p-3 text-center"
                      >
                        <span v-if="inv" class="inline-block w-4 h-4 rounded-full bg-emerald-600 text-white font-bold leading-none text-center">✓</span>
                        <span v-else class="text-neutral-300">—</span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </section>

            <!-- SUB-TAB 2 & 3: TINGKAT KESUKAAN (4-5) / TIDAK SUKA (1-2) -->
            <section
              v-else
              :aria-label="reviewFilterTab === 'suka' ? 'Ulasan Tingkat Kesukaan' : 'Ulasan Tidak Suka'"
              :aria-busy="ulasanStatus === 'loading' ? 'true' : 'false'"
              data-testid="ulasan-list"
              class="space-y-4 pt-2"
            >
              <div v-if="ulasanStatus === 'loading'" class="py-8 text-center text-sm text-neutral-500 font-space-grotesk">
                Memuat ulasan pembaca…
              </div>

              <div v-else-if="ulasanStatus === 'error'" class="py-8 text-center text-sm text-neutral-600">
                Ulasan gagal dimuat.
                <button
                  type="button"
                  class="font-semibold text-isf-red underline underline-offset-2 ml-1 cursor-pointer"
                  @click="loadUlasan()"
                >
                  Coba lagi
                </button>
              </div>

              <div v-else-if="!reviews.length" class="py-8 text-center text-sm text-neutral-500 font-space-grotesk">
                {{
                  reviewFilterTab === 'suka'
                    ? 'Belum ada ulasan positif (bintang 4–5).'
                    : 'Belum ada kritik atau catatan evaluasi (bintang 1–2).'
                }}
              </div>

              <template v-else>
                <div class="divide-y divide-neutral-200">
                  <ReviewCard
                    v-for="review in reviews"
                    :key="review.id"
                    :review="review"
                  >
                    <template v-if="review.isMine" #action>
                      <Button
                        variant="outline"
                        size="sm"
                        aria-label="Edit ulasan Anda"
                        data-testid="ulasan-edit-list"
                        @click="startEdit"
                      >
                        <IconPencil class="mr-1.5 h-4 w-4" aria-hidden="true" />
                        Edit
                      </Button>
                    </template>
                  </ReviewCard>
                </div>

                <div v-if="reviewPage.currentPage < reviewPage.lastPage" class="pt-2 text-center">
                  <Button
                    variant="outline"
                    size="sm"
                    :disabled="loadingMore"
                    data-testid="ulasan-load-more"
                    @click="loadUlasan({ page: reviewPage.currentPage + 1, append: true })"
                  >
                    {{ loadingMore ? 'Memuat…' : 'Muat ulasan lainnya' }}
                  </Button>
                </div>
              </template>
            </section>

            <!-- ── FORM BERI ULASAN / KARTU ULASAN SAYA ────────────────────── -->
            <section
              aria-labelledby="beri-ulasan"
              class="space-y-4 border border-neutral-300 rounded-xl bg-neutral-50/60 p-5 sm:p-6 mt-6"
            >
              <h4 id="beri-ulasan" class="font-headline text-base font-bold text-neutral-900">
                {{
                  editing
                    ? 'Edit Ulasan Anda'
                    : viewer.myReview
                      ? 'Ulasan Anda'
                      : 'Beri Rating & Ulasan Tokoh'
                }}
              </h4>

              <!-- KONDISI 1: User Sudah Mengulas (Kartu Ulasan + Tombol Edit) -->
              <div
                v-if="viewer.authenticated && myReviewCard && !editing"
                data-testid="ulasan-mine"
                class="space-y-3"
              >
                <ReviewCard :review="myReviewCard">
                  <template #action>
                    <Button
                      id="ulasan-edit-btn"
                      variant="outline"
                      size="sm"
                      aria-label="Edit ulasan Anda"
                      data-testid="ulasan-edit"
                      @click="startEdit"
                    >
                      <IconPencil class="mr-1.5 h-4 w-4" aria-hidden="true" />
                      Edit Ulasan
                    </Button>
                  </template>
                </ReviewCard>
                <p v-if="formMessage" class="text-sm text-emerald-700 font-medium" role="status">
                  {{ formMessage }}
                </p>
              </div>

              <!-- KONDISI 2: Belum Login (Guest Callout) -->
              <div
                v-if="!viewer.authenticated"
                class="flex flex-wrap items-center gap-3 p-4 rounded-lg bg-white border border-neutral-200"
                data-testid="ulasan-login"
              >
                <IconLock class="h-5 w-5 text-neutral-500 shrink-0" aria-hidden="true" />
                <p class="text-xs sm:text-sm text-neutral-700 font-space-grotesk flex-1">
                  Login dengan akun ISF untuk memberi rating dan ulasan pada profil tokoh publik ini.
                </p>
                <a
                  :href="loginHref"
                  class="inline-flex items-center rounded-lg bg-isf-red px-4 py-2 font-space-grotesk text-xs font-bold uppercase tracking-wider text-white hover:bg-red-700 transition-colors shadow-2xs"
                >
                  Login untuk memberi ulasan
                </a>
                <p v-if="formMessage" class="w-full text-xs text-neutral-700" role="status">
                  {{ formMessage }}
                </p>
              </div>

              <!-- KONDISI 3: Form Beri / Edit Ulasan -->
              <form
                v-else-if="showForm"
                class="space-y-4"
                novalidate
                data-testid="ulasan-form"
                @submit.prevent="submitUlasan"
              >
                <!-- Rating Bintang -->
                <div class="space-y-1">
                  <p id="ulasan-rating-label" class="text-xs font-bold text-neutral-900 uppercase font-space-grotesk">
                    Rating Bintang (1–5)
                  </p>
                  <StarRating
                    v-model="form.rating"
                    interactive
                    size="lg"
                    label="Rating bintang untuk tokoh"
                    :describedby="formErrors.rating ? 'ulasan-rating-error' : undefined"
                  />
                  <p v-if="formErrors.rating" id="ulasan-rating-error" class="text-xs text-isf-red font-medium">
                    {{ formErrors.rating }}
                  </p>
                </div>

                <!-- Textarea Ulasan -->
                <div class="space-y-1">
                  <Label for="ulasan-body" class="text-xs font-bold text-neutral-900 uppercase font-space-grotesk">
                    Ulasan & Catatan Kinerja
                  </Label>
                  <textarea
                    id="ulasan-body"
                    v-model="form.body"
                    rows="4"
                    maxlength="2000"
                    placeholder="Tulis ulasan konstruktif mengenai rekam jejak, integritas, dan capaian kebijakan tokoh ini…"
                    :aria-invalid="formErrors.body ? 'true' : 'false'"
                    :aria-describedby="formErrors.body ? 'ulasan-body-error' : 'ulasan-body-count'"
                    class="w-full rounded-lg border border-neutral-300 bg-white p-3 font-serif text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-isf-red focus:outline-none focus:ring-1 focus:ring-isf-red"
                  ></textarea>
                  <p v-if="formErrors.body" id="ulasan-body-error" class="text-xs text-isf-red font-medium">
                    {{ formErrors.body }}
                  </p>
                  <p v-else id="ulasan-body-count" class="text-xs text-neutral-500 font-mono">
                    {{ form.body.length }}/2000 karakter
                  </p>
                </div>

                <!-- Checkbox Anonim -->
                <label class="flex items-start gap-2.5 text-xs text-neutral-800 cursor-pointer select-none">
                  <input
                    v-model="form.isAnonymous"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 accent-isf-red rounded border-neutral-300"
                    data-testid="ulasan-anonim"
                  />
                  <span>
                    <span class="font-bold">Kirim sebagai Anonim</span>
                    <span class="block text-[11px] text-neutral-500">
                      Nama Anda tidak akan ditampilkan pada kartu ulasan publik.
                    </span>
                  </span>
                </label>

                <!-- Tombol Submit & Batal -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                  <Button
                    type="submit"
                    class="bg-isf-red text-white hover:bg-red-700 shadow-2xs"
                    :disabled="submitting"
                  >
                    {{
                      submitting
                        ? 'Mengirim…'
                        : viewer.myReview
                          ? 'Simpan Perubahan'
                          : 'Kirim Ulasan'
                    }}
                  </Button>
                  <Button
                    v-if="editing"
                    type="button"
                    variant="outline"
                    :disabled="submitting"
                    data-testid="ulasan-batal"
                    @click="cancelEdit"
                  >
                    Batal
                  </Button>
                  <p v-if="formMessage" class="text-xs font-medium text-neutral-700" role="status">
                    {{ formMessage }}
                  </p>
                </div>
              </form>
            </section>
          </div>
        </section>
      </div>

      <!-- ── TAB 3: JEJARING & AFILIASI ────────────────────────────────── -->
      <div v-else-if="activeTab === 'jejaring'" class="space-y-8 sm:space-y-12">
        <section
          aria-labelledby="jejaring-afiliasi-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="jejaring-afiliasi-title"
              title="Peta Jejaring Kekuasaan & Afiliasi Strategis"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Konstelasi Politik & Relasi Pengaruh</template>
            </SectionHeading>
          </div>
          <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
            <SosokNetworkGraph
              :affiliations="tabsData.jejaring?.affiliations || []"
              :allies="tabsData.jejaring?.allies || []"
              :critics="tabsData.jejaring?.critics || []"
              :tokoh-name="tokoh.name"
            />
          </div>
        </section>
      </div>

      <!-- ── TAB 4: BERITA & AKTIVITAS (2 KOLOM ARTIKEL BERGARIS PERSIS POLITIK) ─ -->
      <div v-else-if="activeTab === 'berita'" class="space-y-8 sm:space-y-12">
        <section
          aria-labelledby="berita-aktivitas-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="berita-aktivitas-title"
              title="Feed Berita & Liputan Media Terkait"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>
                {{ tabsData.berita?.articles?.length || 0 }} Artikel Terhubung dengan {{ tokoh.name }}
              </template>
            </SectionHeading>
          </div>

          <div v-if="!tabsData.berita?.articles?.length" :class="[sectionPad, 'py-16 text-center']">
            <p class="text-neutral-500 text-sm font-space-grotesk">
              Belum ada artikel berita yang menyebut nama tokoh ini secara langsung.
            </p>
          </div>

          <!-- 2 Kolom Artikel Bergaris persis seperti di PolitikView -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-2">
            <IsfLink
              v-for="item in tabsData.berita.articles"
              :key="item.slug"
              :to="item.to || `/artikel/${item.slug}`"
              :class="[
                linkClass,
                'group grid min-w-0 grid-cols-[6rem_minmax(0,1fr)] sm:grid-cols-[12rem_minmax(0,1fr)] lg:grid-cols-[10rem_minmax(0,1fr)] xl:grid-cols-[14rem_minmax(0,1fr)] items-start gap-3 sm:gap-5 border-b border-neutral-200 px-3 py-3.5 sm:px-4 sm:py-5 xl:px-[30px] lg:[&:nth-child(odd)]:border-r last:border-b-0 lg:last:border-b'
              ]"
            >
              <ImagePlaceholder
                :label="item.imageAlt || item.title"
                :src="item.imageUrl || ''"
                aspect="photo"
                bare
              />
              <div class="min-w-0">
                <span class="isf-kicker text-isf-red text-[11px] sm:text-xs">
                  {{ item.category }}{{ item.subcategory ? ` · ${item.subcategory}` : '' }}
                </span>
                <span class="isf-headline mt-1 block text-[15px] sm:text-[17px] leading-[1.25] group-hover:text-isf-red xl:text-[19px] transition-colors line-clamp-2">
                  {{ item.title }}
                </span>
                <p v-if="item.summary" class="mt-1.5 block font-serif text-[13px] sm:text-[14px] leading-[1.45] text-neutral-600 line-clamp-2">
                  {{ item.summary }}
                </p>
                <div class="isf-byline mt-2 sm:mt-3 flex items-center gap-2 text-[11px] sm:text-xs">
                  <time v-if="item.publishedAt" :datetime="item.publishedAt">
                    {{ formatDate(item.publishedAt) }}
                  </time>
                  <span v-if="item.readingMinutes" class="text-neutral-400">• {{ item.readingMinutes }} mnt</span>
                </div>
              </div>
            </IsfLink>
          </div>
        </section>
      </div>

      <!-- ── TAB 5: KUTIPAN & KONTROVERSI ──────────────────────────────── -->
      <div v-else-if="activeTab === 'kontroversi'" class="space-y-8 sm:space-y-12">
        <!-- 5.1 Kumpulan Kutipan Kunci: BORDER-T-4 ISF-RED -->
        <section
          aria-labelledby="kutipan-kunci-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="kutipan-kunci-title"
              title="Pernyataan Kunci & Kutipan Strategis"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Sikap Politik & Pandangan Kebijakan</template>
            </SectionHeading>
          </div>

          <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
            <div v-if="!tabsData.kontroversi?.quotes?.length" class="py-8 text-center text-xs text-neutral-400">
              Belum ada kutipan penting terdaftar.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div
                v-for="(q, idx) in tabsData.kontroversi.quotes"
                :key="idx"
                class="p-5 rounded-xl border border-neutral-200 bg-white relative"
              >
                <p class="text-xs sm:text-sm font-serif italic text-neutral-800 leading-relaxed mb-3">
                  "{{ q.text }}"
                </p>
                <div class="text-[11px] font-mono text-neutral-500">
                  <span class="font-bold text-neutral-700">{{ q.context }}</span>
                  <span v-if="q.date"> • {{ q.date }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 5.2 Dinamika Kontroversi: BORDER-T-4 ISF-RED -->
        <section
          aria-labelledby="kontroversi-title"
          class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
        >
          <div class="border-b border-neutral-200">
            <SectionHeading
              id="kontroversi-title"
              title="Dinamika Kontroversi & Peristiwa Kritis"
              size="lg"
              :class="sectionPad"
            >
              <template #eyebrow>Catatan Perdebatan Publik & Kepatuhan Hukum</template>
            </SectionHeading>
          </div>

          <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
            <div v-if="!tabsData.kontroversi?.controversies?.length" class="py-8 text-center text-xs text-neutral-400">
              Tidak ada catatan kontroversi atau kasus hukum besar yang tercatat.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(c, idx) in tabsData.kontroversi.controversies"
                :key="idx"
                class="p-4 rounded-lg border border-neutral-200 bg-neutral-50"
              >
                <div class="flex items-center justify-between gap-2 mb-1">
                  <span class="text-xs font-mono font-bold text-isf-red">{{ c.year }}</span>
                  <span v-if="c.status" class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-neutral-200 text-neutral-700">
                    {{ c.status }}
                  </span>
                </div>
                <h4 class="text-sm font-bold text-neutral-900 mb-1">{{ c.title }}</h4>
                <p class="text-xs text-neutral-600 font-space-grotesk leading-relaxed">{{ c.description }}</p>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- ═══ 4.1 TOKOH EKSEKUTIF ═══════════════════════════════════════════ -->
      <section
        v-if="tokohEksekutif.length"
        aria-labelledby="tokoh-eksekutif-title"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="tokoh-eksekutif-title"
            title="Tokoh Eksekutif"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Aktor & Pembuat Kebijakan Eksekutif</template>
          </SectionHeading>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200">
          <router-link
            v-for="rel in tokohEksekutif"
            :key="rel.id"
            :to="rel.to || ('/sosok/' + rel.slug)"
            class="group p-3 sm:p-5 flex flex-col justify-between hover:bg-neutral-50/80 transition-colors block cursor-pointer"
          >
            <div>
              <div class="flex items-start gap-3 sm:block mb-2 sm:mb-3">
                <div class="w-16 h-16 sm:w-full sm:h-auto sm:aspect-square rounded-md overflow-hidden bg-neutral-200 shrink-0 border border-neutral-200 shadow-2xs group-hover:shadow-md transition-shadow">
                  <img
                    v-if="rel.photo_url || rel.photoUrl"
                    :src="rel.photo_url || rel.photoUrl"
                    :alt="rel.photo_alt || rel.name"
                    class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-neutral-100 text-neutral-400">
                    <IconUser class="w-8 h-8 sm:w-12 sm:h-12" />
                  </div>
                </div>

                <div class="min-w-0 flex-1 sm:mt-2">
                  <span class="text-[10px] font-space-grotesk font-bold uppercase tracking-wider text-isf-red block">
                    Eksekutif
                  </span>
                  <h3 class="font-space-grotesk font-bold text-sm sm:text-base text-neutral-900 mt-0.5 line-clamp-1 group-hover:text-isf-red transition-colors">
                    {{ rel.name }}
                  </h3>
                  <p class="text-xs text-neutral-500 font-medium line-clamp-1 mt-0.5">
                    {{ rel.position }}
                  </p>
                </div>
              </div>

              <p v-if="rel.summary_analysis || rel.teaser || rel.bio" class="text-xs font-serif text-neutral-600 line-clamp-2 sm:line-clamp-3 leading-relaxed">
                {{ rel.summary_analysis || rel.teaser || rel.bio }}
              </p>
            </div>

            <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-neutral-200">
              <div class="inline-flex items-center gap-1.5 text-xs font-space-grotesk font-bold text-isf-red group-hover:text-isf-red-dark transition-colors">
                <span>Lihat Profil Sosok</span>
                <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
              </div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- ═══ 4.2 TOKOH LEGISLATIF ════════════════════════════════════════════ -->
      <section
        v-if="tokohLegislatif.length"
        aria-labelledby="tokoh-legislatif-title"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-navy shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="tokoh-legislatif-title"
            title="Tokoh Legislatif"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Aktor & Pembuat Kebijakan Legislatif</template>
          </SectionHeading>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200">
          <router-link
            v-for="rel in tokohLegislatif"
            :key="rel.id"
            :to="rel.to || ('/sosok/' + rel.slug)"
            class="group p-3 sm:p-5 flex flex-col justify-between hover:bg-neutral-50/80 transition-colors block cursor-pointer"
          >
            <div>
              <div class="flex items-start gap-3 sm:block mb-2 sm:mb-3">
                <div class="w-16 h-16 sm:w-full sm:h-auto sm:aspect-square rounded-md overflow-hidden bg-neutral-200 shrink-0 border border-neutral-200 shadow-2xs group-hover:shadow-md transition-shadow">
                  <img
                    v-if="rel.photo_url || rel.photoUrl"
                    :src="rel.photo_url || rel.photoUrl"
                    :alt="rel.photo_alt || rel.name"
                    class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-neutral-100 text-neutral-400">
                    <IconUser class="w-8 h-8 sm:w-12 sm:h-12" />
                  </div>
                </div>

                <div class="min-w-0 flex-1 sm:mt-2">
                  <span class="text-[10px] font-space-grotesk font-bold uppercase tracking-wider text-isf-red block">
                    Legislatif
                  </span>
                  <h3 class="font-space-grotesk font-bold text-sm sm:text-base text-neutral-900 mt-0.5 line-clamp-1 group-hover:text-isf-red transition-colors">
                    {{ rel.name }}
                  </h3>
                  <p class="text-xs text-neutral-500 font-medium line-clamp-1 mt-0.5">
                    {{ rel.position }}
                  </p>
                </div>
              </div>

              <p v-if="rel.summary_analysis || rel.teaser || rel.bio" class="text-xs font-serif text-neutral-600 line-clamp-2 sm:line-clamp-3 leading-relaxed">
                {{ rel.summary_analysis || rel.teaser || rel.bio }}
              </p>
            </div>

            <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-neutral-200">
              <div class="inline-flex items-center gap-1.5 text-xs font-space-grotesk font-bold text-isf-red group-hover:text-isf-red-dark transition-colors">
                <span>Lihat Profil Sosok</span>
                <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
              </div>
            </div>
          </router-link>
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
