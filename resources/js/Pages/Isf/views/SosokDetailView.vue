<script setup>
/**
 * Halaman Detail Tokoh Rubrik Sosok (/sosok/:slug dan /sosok/:slug/:tab).
 * 5 Tab dengan URL mandiri yang dapat dibuka langsung & dibagikan:
 * - Tab 1: Profil & Rekam Jejak (/profil)
 * - Tab 2: Analisis ISF (/analisis) [termasuk Skor Pengaruh, Policy Brief, Jejak Kebijakan, & Rating/Ulasan #41]
 * - Tab 3: Jejaring & Afiliasi (/jejaring)
 * - Tab 4: Berita & Aktivitas (/berita)
 * - Tab 5: Kutipan & Kontroversi (/kontroversi)
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
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSosokDetail } from '@/api/services/isfPublicService'
import { getTokohUlasan, submitTokohUlasan } from '@/api/services/isfTokohReviewService'
import { useIsfContent } from '@/Composables/useIsfContent'
import { formatDate } from '@/Utils/formatters'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'

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
 * INDEKS TOKOH: RATING & ULASAN (#41) LOGIC
 * ══════════════════════════════════════════════════════════════════════════ */
const reviewFilterTab = ref('karya') // 'karya' | 'suka' | 'tidak_suka'
const ulasan = ref(null)
const ulasanStatus = ref('loading')
const reviews = ref([])
const reviewPage = ref({ currentPage: 1, lastPage: 1, total: 0 })
const loadingMore = ref(false)
let ulasanRequest = 0

const reviewSummary = computed(() => {
  return ulasan.value?.summary ?? tabsData.value?.analisis?.ratingSummary ?? {
    average: 0,
    total: 0,
    positive_count: 0,
    negative_count: 0,
    neutral_count: 0,
    star_distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  }
})

async function fetchUlasan(page = 1, append = false) {
  const id = ++ulasanRequest
  if (!append) {
    ulasanStatus.value = 'loading'
    reviews.value = []
  } else {
    loadingMore.value = true
  }

  try {
    const res = await getTokohUlasan(slug.value, {
      tab: reviewFilterTab.value,
      page,
      per_page: 5,
    })
    if (id !== ulasanRequest) return
    ulasan.value = res
    reviews.value = append ? [...reviews.value, ...(res.reviews?.data ?? [])] : res.reviews?.data ?? []
    reviewPage.value = {
      currentPage: res.reviews?.current_page ?? 1,
      lastPage: res.reviews?.last_page ?? 1,
      total: res.reviews?.total ?? 0,
    }
    ulasanStatus.value = 'ready'
  } catch (err) {
    if (id !== ulasanRequest) return
    ulasanStatus.value = 'error'
  } finally {
    loadingMore.value = false
  }
}

function setReviewFilter(tab) {
  reviewFilterTab.value = tab
  if (tab !== 'karya') {
    fetchUlasan(1, false)
  }
}

// Modal Form Kirim Ulasan
const showReviewModal = ref(false)
const reviewRating = ref(0)
const reviewComment = ref('')
const reviewIsAnonymous = ref(false)
const reviewSubmitting = ref(false)
const reviewError = ref('')
const reviewSuccess = ref('')

const isAuth = computed(() => Boolean(window.__ISF_AUTH__?.user))

function openReviewModal() {
  if (!isAuth.value) {
    const currentPath = window.location.pathname
    window.location.href = `/login?redirect=${encodeURIComponent(currentPath)}`
    return
  }
  reviewRating.value = 0
  reviewComment.value = ''
  reviewIsAnonymous.value = false
  reviewError.value = ''
  reviewSuccess.value = ''
  showReviewModal.value = true
}

function closeReviewModal() {
  showReviewModal.value = false
}

async function handleReviewSubmit() {
  if (reviewRating.value < 1 || reviewRating.value > 5) {
    reviewError.value = 'Harap pilih rating 1–5 bintang.'
    return
  }
  if (!reviewComment.value.trim()) {
    reviewError.value = 'Harap tuliskan ulasan Anda.'
    return
  }

  reviewSubmitting.value = true
  reviewError.value = ''
  reviewSuccess.value = ''

  try {
    const res = await submitTokohUlasan(slug.value, {
      rating: reviewRating.value,
      comment: reviewComment.value,
      is_anonymous: reviewIsAnonymous.value,
    })
    reviewSuccess.value = res.message || 'Ulasan Anda berhasil dikirim!'
    setTimeout(() => {
      closeReviewModal()
      fetchUlasan(1, false)
    }, 1200)
  } catch (err) {
    reviewError.value = err?.response?.data?.message || 'Gagal mengirim ulasan. Coba lagi.'
  } finally {
    reviewSubmitting.value = false
  }
}

// Watchers
watch(
  () => route.params.slug,
  () => {
    load()
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  },
  { immediate: true }
)

watch(
  () => activeTab.value,
  (tab) => {
    if (tab === 'analisis') {
      fetchUlasan(1, false)
    }
  }
)

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
  <div class="isf-content-layer min-h-screen bg-neutral-100 dark:bg-neutral-950 pb-20">
    <!-- ═══ HEADER BREADCRUMB ═══════════════════════════════════════════════ -->
    <header class="border-b border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 py-4">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-space-grotesk text-neutral-500">
          <router-link to="/beranda" class="hover:text-isf-red transition-colors">Beranda</router-link>
          <span class="text-neutral-300 dark:text-neutral-700">/</span>
          <router-link to="/sosok" class="hover:text-isf-red transition-colors">Sosok</router-link>
          <template v-if="tokoh?.role_category">
            <span class="text-neutral-300 dark:text-neutral-700">/</span>
            <router-link :to="`/sosok/${tokoh.role_category}`" class="hover:text-isf-red transition-colors capitalize">
              {{ tokoh.role_category_label || tokoh.role_category }}
            </router-link>
          </template>
          <span class="text-neutral-300 dark:text-neutral-700">/</span>
          <span class="font-bold text-neutral-900 dark:text-white line-clamp-1">
            {{ tokoh?.name || 'Detail Tokoh' }}
          </span>
        </nav>
      </div>
    </header>

    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 pt-8 sm:pt-10 space-y-10 sm:space-y-12">
      <!-- Loading State -->
      <div v-if="status === 'loading'" class="py-12">
        <ArtikelSkeleton variant="tokoh" />
      </div>

      <!-- Error / 404 State -->
      <div v-else-if="status === 'error' || status === 'not-found'" class="py-12">
        <ErrorMessage
          message="Profil tokoh tidak ditemukan atau belum dipublikasikan."
          action-label="Kembali ke Direktori Sosok"
          @retry="router.push('/sosok')"
        />
      </div>

      <!-- Loaded Content -->
      <div v-else-if="tokoh" class="space-y-10">
        <!-- ═══ 1. PROFILE HEADER CARD ═════════════════════════════════════ -->
        <section aria-label="Profil Ringkas Tokoh">
          <div class="rounded-2xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 md:p-10 shadow-sm relative overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
              <!-- Left: Photo with status -->
              <div class="md:col-span-4 lg:col-span-3">
                <div class="relative aspect-[3/4] rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-800 shadow-md bg-neutral-100 dark:bg-neutral-800">
                  <img
                    v-if="tokoh.photo_url"
                    :src="tokoh.photo_url"
                    :alt="tokoh.photo_alt || tokoh.name"
                    class="w-full h-full object-cover object-top"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-neutral-400">
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

              <!-- Right: Identity, Badges, Metrics, & Action Buttons -->
              <div class="md:col-span-8 lg:col-span-9 flex flex-col justify-between h-full">
                <div>
                  <div class="flex flex-wrap items-center gap-2.5 mb-2">
                    <span class="px-2.5 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 text-xs font-mono font-bold uppercase tracking-wider border border-blue-200 dark:border-blue-900">
                      {{ tokoh.role_category_label || tokoh.role_category }}
                    </span>

                    <span
                      v-if="tokoh.influence_score || tokoh.skor_value"
                      class="px-2.5 py-1 rounded bg-[#0B1220] text-white text-xs font-mono font-bold flex items-center gap-1 shadow-xs"
                    >
                      <IconAward class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                      <span>Skor Pengaruh {{ tokoh.influence_score || tokoh.skor_value }}/100</span>
                    </span>

                    <span v-for="isu in tokoh.issues" :key="isu" class="px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-[11px] font-mono text-neutral-600 dark:text-neutral-400 capitalize">
                      #{{ isu }}
                    </span>
                  </div>

                  <h1 class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold text-neutral-950 dark:text-white mt-1 mb-2">
                    {{ tokoh.name }}
                  </h1>
                  <p class="text-sm sm:text-base text-neutral-600 dark:text-neutral-400 font-space-grotesk font-medium mb-4">
                    {{ tokoh.position }}
                  </p>

                  <!-- Ringkasan analisis 1 kalimat ISF -->
                  <div class="p-4 rounded-xl bg-[#FBF9F4] dark:bg-neutral-800/60 border border-neutral-200 dark:border-neutral-800 mb-6">
                    <div class="text-[10px] font-mono font-bold uppercase text-isf-red mb-1">
                      Catatan Analisis ISF
                    </div>
                    <p class="text-xs sm:text-sm text-neutral-800 dark:text-neutral-200 font-space-grotesk leading-relaxed">
                      "{{ tokoh.summary_analysis || tokoh.bio }}"
                    </p>
                  </div>
                </div>

                <!-- Actions: Ikuti Tokoh & Bagikan -->
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
                  <button
                    type="button"
                    @click="toggleFollow"
                    :class="[
                      'inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs sm:text-sm font-bold font-space-grotesk transition-all cursor-pointer shadow-xs',
                      isFollowing
                        ? 'bg-neutral-200 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200'
                        : 'bg-isf-navy text-white hover:bg-[#121c33]'
                    ]"
                    :aria-pressed="isFollowing"
                  >
                    <IconCheck v-if="isFollowing" class="w-4 h-4 text-emerald-500" />
                    <IconHeart v-else class="w-4 h-4 text-rose-400" />
                    <span>{{ isFollowing ? 'Mengikuti Tokoh' : 'Ikuti Pembaruan Tokoh' }}</span>
                  </button>

                  <button
                    type="button"
                    @click="shareUrl"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-200 text-xs sm:text-sm font-semibold font-space-grotesk cursor-pointer"
                  >
                    <IconShare class="w-4 h-4 text-neutral-500" />
                    <span>Bagikan Profil</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ═══ 2. 5 TAB NAVIGATOR WITH DEDICATED URLS ══════════════════════ -->
        <nav aria-label="Navigasi Tab Tokoh" class="border-b border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 rounded-t-xl px-4 sticky top-16 z-10 shadow-xs">
          <div class="flex items-center gap-2 overflow-x-auto">
            <!-- Tab 1: Profil -->
            <button
              type="button"
              @click="switchTab('profil')"
              :class="[
                'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-colors',
                activeTab === 'profil'
                  ? 'border-isf-red text-isf-red'
                  : 'border-transparent text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white'
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
                'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-colors',
                activeTab === 'analisis'
                  ? 'border-isf-red text-isf-red'
                  : 'border-transparent text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white'
              ]"
            >
              <IconChartBar class="w-4 h-4" />
              <span>Analisis & Ulasan ISF</span>
              <span v-if="reviewSummary.total" class="px-1.5 py-0.2 rounded-full bg-neutral-100 dark:bg-neutral-800 text-[10px] text-neutral-500 font-mono">
                {{ reviewSummary.total }}
              </span>
            </button>

            <!-- Tab 3: Jejaring & Afiliasi -->
            <button
              type="button"
              @click="switchTab('jejaring')"
              :class="[
                'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-colors',
                activeTab === 'jejaring'
                  ? 'border-isf-red text-isf-red'
                  : 'border-transparent text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white'
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
                'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-colors',
                activeTab === 'berita'
                  ? 'border-isf-red text-isf-red'
                  : 'border-transparent text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white'
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
                'flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold font-space-grotesk border-b-2 whitespace-nowrap cursor-pointer transition-colors',
                activeTab === 'kontroversi'
                  ? 'border-isf-red text-isf-red'
                  : 'border-transparent text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white'
              ]"
            >
              <IconQuote class="w-4 h-4" />
              <span>Kutipan & Kontroversi</span>
            </button>
          </div>
        </nav>

        <!-- ═══ 3. TAB CONTENT PANELS ═════════════════════════════════════════ -->

        <!-- ── TAB 1: PROFIL & REKAM JEJAK ───────────────────────────────── -->
        <div v-if="activeTab === 'profil'" class="space-y-10">
          <!-- Narasi Biografi -->
          <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white mb-4 pb-3 border-b border-neutral-200 dark:border-neutral-800">
              Biografi & Perjalanan Karier
            </h3>
            <p class="text-sm sm:text-base text-neutral-700 dark:text-neutral-300 font-space-grotesk leading-relaxed whitespace-pre-line">
              {{ tabsData.profil?.bio || tokoh.bio || 'Belum ada data biografi naratif yang dicatat untuk tokoh ini.' }}
            </p>
          </section>

          <!-- Timeline Karier & Jabatan -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Timeline Karier -->
            <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xs">
              <h4 class="font-headline text-lg font-bold text-neutral-950 dark:text-white mb-5 pb-3 border-b border-neutral-200 dark:border-neutral-800">
                Linimasa Karier & Pengabdian
              </h4>

              <div v-if="!tabsData.profil?.career_timeline?.length" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
                Belum ada linimasa karier terdaftar.
              </div>

              <div v-else class="relative border-l-2 border-neutral-200 dark:border-neutral-800 ml-3 space-y-6 pb-2">
                <div v-for="(item, idx) in tabsData.profil.career_timeline" :key="idx" class="relative pl-6">
                  <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-isf-red border-4 border-white dark:border-neutral-900"></div>
                  <span class="text-xs font-mono font-bold text-isf-red">{{ item.year }}</span>
                  <h5 class="text-sm font-bold text-neutral-900 dark:text-white mt-0.5">{{ item.title }}</h5>
                  <p class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-1 leading-relaxed">{{ item.description }}</p>
                </div>
              </div>
            </section>

            <!-- Riwayat Pendidikan & Jabatan -->
            <div class="space-y-8">
              <!-- Riwayat Jabatan -->
              <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xs">
                <h4 class="font-headline text-lg font-bold text-neutral-950 dark:text-white mb-4 pb-3 border-b border-neutral-200 dark:border-neutral-800">
                  Riwayat Jabatan Strategis
                </h4>
                <div v-if="!tabsData.profil?.positions_history?.length" class="py-6 text-center text-xs text-neutral-400">
                  Belum ada riwayat jabatan tercatat.
                </div>
                <div v-else class="space-y-3">
                  <div v-for="(p, idx) in tabsData.profil.positions_history" :key="idx" class="p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
                    <span class="text-[10px] font-mono font-bold text-neutral-400">{{ p.period }}</span>
                    <div class="text-xs font-bold text-neutral-900 dark:text-white">{{ p.position }}</div>
                    <div class="text-[11px] text-neutral-500 font-space-grotesk">{{ p.institution }}</div>
                  </div>
                </div>
              </section>

              <!-- Pendidikan Formal -->
              <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xs">
                <h4 class="font-headline text-lg font-bold text-neutral-950 dark:text-white mb-4 pb-3 border-b border-neutral-200 dark:border-neutral-800">
                  Latar Belakang Pendidikan
                </h4>
                <div v-if="!tabsData.profil?.education?.length" class="py-6 text-center text-xs text-neutral-400">
                  Belum ada catatan pendidikan formal.
                </div>
                <div v-else class="space-y-3">
                  <div v-for="(edu, idx) in tabsData.profil.education" :key="idx" class="flex items-start justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
                    <div>
                      <div class="text-xs font-bold text-neutral-900 dark:text-white">{{ edu.degree }}</div>
                      <div class="text-[11px] text-neutral-500 font-space-grotesk">{{ edu.institution }}</div>
                    </div>
                    <span class="text-[11px] font-mono font-bold text-isf-red">{{ edu.year }}</span>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>

        <!-- ── TAB 2: ANALISIS ISF & RATING (#41) ────────────────────────── -->
        <div v-else-if="activeTab === 'analisis'" class="space-y-10">
          <!-- Skor Sorotan & Pengaruh -->
          <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
              <IconChartBar class="w-4 h-4 shrink-0" />
              <span>Metrik Sorotan & Indeks Pengaruh Kebijakan</span>
            </div>
            <h3 class="font-headline text-xl sm:text-2xl font-bold text-neutral-950 dark:text-white mb-6">
              Skor Sorotan: {{ tabsData.analisis?.skorSorotan?.value ?? tokoh.skor_value }}/100
            </h3>

            <div class="p-4 rounded-xl bg-[#FBF9F4] dark:bg-neutral-800/60 border border-neutral-200 dark:border-neutral-800">
              <div class="text-xs font-mono text-neutral-500 mb-1">
                Periode Perhitungan: {{ tabsData.analisis?.skorSorotan?.period ?? tokoh.skor_period }}
              </div>
              <p class="text-xs sm:text-sm text-neutral-700 dark:text-neutral-300 font-space-grotesk leading-relaxed">
                {{ tabsData.analisis?.skorSorotan?.explanation ?? tokoh.skor_explanation }}
              </p>
            </div>
          </section>

          <!-- Jejak Kebijakan / Policy Matrix (#41) -->
          <section v-if="tabsData.analisis?.policyMatrix?.rows?.length" class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white mb-2">
              Jejak Kebijakan & Keterlibatan Regulasi
            </h3>
            <p class="text-xs text-neutral-500 font-space-grotesk mb-6">
              Matriks pengawalan penyusunan rancangan perundang-undangan dan instrumen kebijakan publik.
            </p>

            <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-800">
              <Table>
                <TableHeader>
                  <TableRow class="bg-neutral-50 dark:bg-neutral-800/60 font-mono text-xs font-bold">
                    <TableHead class="p-3">Tahap Pembahasan</TableHead>
                    <TableHead class="p-3">Nama RUU / Regulasi</TableHead>
                    <TableHead
                      v-for="(col, cIdx) in tabsData.analisis.policyMatrix.columns"
                      :key="cIdx"
                      class="p-3 text-center"
                    >
                      {{ col }}
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody class="divide-y divide-neutral-200 dark:divide-neutral-800 text-xs">
                  <TableRow v-for="(row, rIdx) in tabsData.analisis.policyMatrix.rows" :key="rIdx">
                    <TableCell class="p-3 font-semibold text-neutral-600 dark:text-neutral-400">{{ row.tahap }}</TableCell>
                    <TableCell class="p-3 font-bold text-neutral-900 dark:text-white">{{ row.kebijakan }}</TableCell>
                    <TableCell
                      v-for="(inv, iIdx) in row.involvement"
                      :key="iIdx"
                      class="p-3 text-center"
                    >
                      <span v-if="inv" class="inline-block w-4 h-4 rounded-full bg-emerald-500 text-white font-bold leading-none text-center">✓</span>
                      <span v-else class="text-neutral-300 dark:text-neutral-700">—</span>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </section>

          <!-- Indeks Tokoh: Rating & Ulasan Publik (#41) -->
          <section aria-labelledby="review-section-title" class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-200 dark:border-neutral-800">
              <div>
                <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
                  <span>Indeks Tokoh Publik (#41)</span>
                </div>
                <h3 id="review-section-title" class="font-headline text-xl font-bold text-neutral-950 dark:text-white">
                  Rating & Persepsi Masyarakat
                </h3>
                <p class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
                  Partisipasi anggota terverifikasi dalam mengevaluasi kepemimpinan dan kinerja tokoh.
                </p>
              </div>

              <Button
                type="button"
                size="sm"
                class="bg-isf-red hover:bg-red-700 text-white cursor-pointer shrink-0"
                @click="openReviewModal"
              >
                <IconPencil class="w-4 h-4 mr-1.5" />
                <span>Beri Ulasan / Rating</span>
              </Button>
            </div>

            <!-- Summary Block -->
            <RatingSummary :summary="reviewSummary" />

            <!-- Filter Tab Ulasan: Karya | Tingkat Kesukaan (4-5) | Tidak Suka (1-2) -->
            <div class="flex items-center gap-2 pt-2 border-t border-neutral-100 dark:border-neutral-800">
              <button
                type="button"
                @click="setReviewFilter('karya')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold font-space-grotesk transition-colors cursor-pointer',
                  reviewFilterTab === 'karya' ? 'bg-[#0B1220] text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600'
                ]"
              >
                Ikhtisar Karya
              </button>
              <button
                type="button"
                @click="setReviewFilter('suka')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold font-space-grotesk transition-colors cursor-pointer',
                  reviewFilterTab === 'suka' ? 'bg-emerald-600 text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600'
                ]"
              >
                Tingkat Kesukaan (★ 4–5)
              </button>
              <button
                type="button"
                @click="setReviewFilter('tidak_suka')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold font-space-grotesk transition-colors cursor-pointer',
                  reviewFilterTab === 'tidak_suka' ? 'bg-rose-600 text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600'
                ]"
              >
                Tidak Suka (★ 1–2)
              </button>
            </div>

            <!-- Review Items List -->
            <div v-if="reviewFilterTab !== 'karya'" class="space-y-4 pt-2">
              <div v-if="ulasanStatus === 'loading'" class="py-8 text-center text-xs text-neutral-400">
                Memuat ulasan pembaca...
              </div>
              <div v-else-if="reviews.length === 0" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
                Belum ada ulasan untuk kategori ini. Jadilah yang pertama memberikan ulasan!
              </div>
              <div v-else class="space-y-3">
                <ReviewCard
                  v-for="rev in reviews"
                  :key="rev.id"
                  :review="rev"
                />

                <div v-if="reviewPage.currentPage < reviewPage.lastPage" class="pt-4 text-center">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="loadingMore"
                    @click="fetchUlasan(reviewPage.currentPage + 1, true)"
                  >
                    {{ loadingMore ? 'Memuat...' : 'Muat Lebih Banyak Ulasan' }}
                  </Button>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ── TAB 3: JEJARING & AFILIASI ────────────────────────────────── -->
        <div v-else-if="activeTab === 'jejaring'" class="space-y-8">
          <SosokNetworkGraph
            :affiliations="tabsData.jejaring?.affiliations || []"
            :allies="tabsData.jejaring?.allies || []"
            :critics="tabsData.jejaring?.critics || []"
            :tokoh-name="tokoh.name"
          />
        </div>

        <!-- ── TAB 4: BERITA & AKTIVITAS ─────────────────────────────────── -->
        <div v-else-if="activeTab === 'berita'" class="space-y-8">
          <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-neutral-200 dark:border-neutral-800 mb-6">
              <div>
                <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white">
                  Feed Berita & Liputan Media Terkait
                </h3>
                <p class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
                  Artikel editorial dan analisis kebijakan yang otomatis terhubung berdasarkan penyebutan nama atau alias {{ tokoh.name }}.
                </p>
              </div>
            </div>

            <div v-if="!tabsData.berita?.articles?.length" class="py-12 text-center text-xs text-neutral-400 font-space-grotesk">
              Belum ada artikel berita yang menyebut nama tokoh ini secara langsung.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <article
                v-for="(art, idx) in tabsData.berita.articles"
                :key="idx"
                class="p-5 rounded-xl border border-neutral-200 dark:border-neutral-800 hover:border-neutral-400 transition-all flex flex-col justify-between"
              >
                <div>
                  <div class="flex items-center gap-2 text-[10px] font-mono text-isf-red font-bold uppercase mb-1.5">
                    <span>{{ art.category || 'Kebijakan' }}</span>
                    <span v-if="art.subcategory">• {{ art.subcategory }}</span>
                  </div>
                  <h4 class="font-headline text-base font-bold text-neutral-950 dark:text-white line-clamp-2 hover:text-isf-red transition-colors">
                    <router-link :to="art.to || `/artikel/${art.slug}`">
                      {{ art.title }}
                    </router-link>
                  </h4>
                  <p class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-2 line-clamp-2 leading-relaxed">
                    {{ art.summary }}
                  </p>
                </div>
                <div class="pt-4 mt-4 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between text-[11px] text-neutral-400 font-mono">
                  <span>{{ formatDate(art.publishedAt) }}</span>
                  <router-link :to="art.to || `/artikel/${art.slug}`" class="text-isf-red hover:underline font-bold">
                    Baca Lengkap →
                  </router-link>
                </div>
              </article>
            </div>
          </section>
        </div>

        <!-- ── TAB 5: KUTIPAN & KONTROVERSI ──────────────────────────────── -->
        <div v-else-if="activeTab === 'kontroversi'" class="space-y-10">
          <!-- Kumpulan Kutipan Kunci -->
          <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white mb-2 flex items-center gap-2">
              <IconQuote class="w-5 h-5 text-isf-red" />
              <span>Pernyataan Kunci & Kutipan Strategis</span>
            </h3>
            <p class="text-xs text-neutral-500 font-space-grotesk mb-6">
              Kutipan autentik yang mencerminkan posisi politik, pandangan hukum, atau sikap kebijakan tokoh.
            </p>

            <div v-if="!tabsData.kontroversi?.quotes?.length" class="py-8 text-center text-xs text-neutral-400">
              Belum ada kutipan penting terdaftar.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div
                v-for="(q, idx) in tabsData.kontroversi.quotes"
                :key="idx"
                class="p-5 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-[#FBF9F4] dark:bg-neutral-800/40 relative"
              >
                <p class="text-xs sm:text-sm font-space-grotesk italic text-neutral-800 dark:text-neutral-200 leading-relaxed mb-3">
                  "{{ q.text }}"
                </p>
                <div class="text-[11px] font-mono text-neutral-500">
                  <span class="font-bold text-neutral-700 dark:text-neutral-300">{{ q.context }}</span>
                  <span v-if="q.date"> • {{ q.date }}</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Timeline Kontroversi / Kasus Hukum -->
          <section class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
            <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white mb-2">
              Dinamika Kontroversi & Peristiwa Kritis
            </h3>
            <p class="text-xs text-neutral-500 font-space-grotesk mb-6">
              Catatan peristiwa yang memicu perdebatan publik, kritik lembaga, atau proses penegakan hukum.
            </p>

            <div v-if="!tabsData.kontroversi?.controversies?.length" class="py-8 text-center text-xs text-neutral-400">
              Tidak ada catatan kontroversi atau kasus hukum besar yang tercatat.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(c, idx) in tabsData.kontroversi.controversies"
                :key="idx"
                class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/60"
              >
                <div class="flex items-center justify-between gap-2 mb-1">
                  <span class="text-xs font-mono font-bold text-isf-red">{{ c.year }}</span>
                  <span v-if="c.status" class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300">
                    {{ c.status }}
                  </span>
                </div>
                <h4 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">{{ c.title }}</h4>
                <p class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk leading-relaxed">{{ c.description }}</p>
              </div>
            </div>
          </section>
        </div>

        <!-- ═══ 4. RELATED TOKOHS (TOKOH TERKAIT) ═════════════════════════════ -->
        <section v-if="relatedTokoh.length" class="pt-8 border-t border-neutral-200 dark:border-neutral-800">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-headline text-xl font-bold text-neutral-950 dark:text-white">
              Tokoh Lain di Rumpun {{ tokoh.role_category_label || tokoh.role_category }}
            </h3>
            <router-link :to="`/sosok/${tokoh.role_category}`" class="text-xs font-bold text-isf-red hover:underline font-space-grotesk">
              Lihat Semua →
            </router-link>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <SosokCard
              v-for="rel in relatedTokoh"
              :key="rel.id"
              :tokoh="rel"
            />
          </div>
        </section>
      </div>
    </main>

    <!-- ═══ REVIEW MODAL (#41) ═════════════════════════════════════════════ -->
    <div
      v-if="showReviewModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="closeReviewModal"
    >
      <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-neutral-900 p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-200 dark:border-neutral-800">
          <h3 class="font-headline text-lg font-bold text-neutral-950 dark:text-white">
            Ulas Kinerja: {{ tokoh?.name }}
          </h3>
          <button type="button" @click="closeReviewModal" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>

        <div v-if="reviewSuccess" class="p-3 rounded-lg bg-emerald-50 text-emerald-800 text-xs font-bold">
          {{ reviewSuccess }}
        </div>
        <div v-if="reviewError" class="p-3 rounded-lg bg-red-50 text-red-800 text-xs font-bold">
          {{ reviewError }}
        </div>

        <form class="space-y-4" @submit.prevent="handleReviewSubmit">
          <div>
            <label class="block text-xs font-bold font-space-grotesk mb-1">
              Beri Rating (1–5 Bintang)
            </label>
            <div class="flex items-center gap-2">
              <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="reviewRating = star"
                class="p-1 cursor-pointer transition-transform hover:scale-110"
              >
                <span :class="['text-2xl', star <= reviewRating ? 'text-amber-400' : 'text-neutral-300']">★</span>
              </button>
              <span class="text-xs font-mono font-bold text-neutral-500 ml-2">
                {{ reviewRating ? `${reviewRating} / 5` : 'Pilih bintang' }}
              </span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold font-space-grotesk mb-1">
              Tulis Catatan / Evaluasi Kebijakan
            </label>
            <textarea
              v-model="reviewComment"
              rows="4"
              class="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 p-3 text-xs font-space-grotesk focus:outline-none focus:ring-1 focus:ring-isf-red"
              placeholder="Berikan pandangan kritis Anda mengenai rekam jejak, integritas, dan capaian kebijakan tokoh..."
              required
            ></textarea>
          </div>

          <div class="flex items-center gap-2">
            <input
              id="is_anon"
              v-model="reviewIsAnonymous"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-isf-red focus:ring-isf-red"
            />
            <label for="is_anon" class="text-xs font-space-grotesk cursor-pointer select-none">
              Kirim sebagai anonim (sembunyikan nama Anda di daftar publik)
            </label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-neutral-800">
            <Button type="button" variant="outline" size="sm" @click="closeReviewModal">
              Batal
            </Button>
            <Button
              type="submit"
              size="sm"
              class="bg-isf-red hover:bg-red-700 text-white"
              :disabled="reviewSubmitting"
            >
              {{ reviewSubmitting ? 'Mengirim...' : 'Kirim Ulasan' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

