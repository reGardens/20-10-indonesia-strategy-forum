<script setup>
/**
 * OpiniPublikView.vue — Halaman Landing Rubrik Opini Publik ISF (/opini-publik & sub-kategori).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan Politik & Hukum:
 *   Breadcrumb responsif, kicker badge, serif heading, dan quick pill tabs.
 * - Subkategori: Op-Ed (tamu/akademisi), Editorial ISF (pandangan resmi redaksi), Surat Pembaca.
 * - Fitur Khusus:
 *   1. Section 1: Sorotan Opini Publik (Headline utama bergaya editorial 12-kolom).
 *   2. Section 2: Reader Poll (Navy frame interaktif isu hangat dengan label non-ilmiah).
 *   3. Section 3: Kutipan Publik (Cream frame kutipan tajam tokoh & netizen).
 *   4. Section 4: Surat Pembaca (Daftar kontribusi masyarakat terkurasi + modal form kirim surat).
 *   5. Section 5: Artikel Terbaru (Grid 2-kolom bergaris pembatas, line-clamp-2 deskripsi, tampil di semua filter).
 * - Skeleton loader desktop dan mobile yang presisi mencerminkan struktur konten.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconArrowLeft,
  IconClock,
  IconUser,
  IconArrowRight,
  IconChevronRight,
  IconFileText,
  IconQuote,
  IconMail,
  IconSend,
  IconCheck,
  IconMapPin,
  IconFlame,
  IconAward,
  IconShieldCheck,
  IconPhoto,
  IconX,
} from '@tabler/icons-vue'
import {
  ImagePlaceholder,
  IsfLink,
  SectionHeading,
  ReaderPollCard,
  KutipanPublikCard,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getOpiniPublik } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'
import { useIsfSession } from '@/Composables/useIsfSession'

const route = useRoute()
const router = useRouter()
const { isLoggedIn, ensureLoaded } = useIsfSession()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const linkClass =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2'

// Subkategori dari URL parameter (op-ed, editorial, surat-pembaca, polling, kutipan)
const currentSubkategori = computed(() => {
  return route.params.subkategori || null
})

const currentPage = ref(1)
const kutipanFilter = ref('all') // 'all' | 'tokoh' | 'netizen'
const expandedLetterId = ref(null)

// Fetch data rubrik opini publik
const { data, status, load } = useIsfContent(() => {
  const param = ['polling', 'kutipan'].includes(currentSubkategori.value)
    ? null
    : currentSubkategori.value
  return getOpiniPublik(param, currentPage.value, 12)
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Opini Publik',
  label: 'Opini Publik',
  description: 'Wadah pemikiran kritis, pandangan resmi redaksi, dan suara kontribusi publik.',
  subkategori: [
    { slug: 'op-ed', name: 'Op-Ed & Analisis Pakar', description: 'Tulisan gagasan dan analisis mendalam dari akademisi, intelektual, dan pakar independen.' },
    { slug: 'editorial', name: 'Editorial ISF', description: 'Sikap, refleksi, dan pandangan resmi Dewan Redaksi Indonesia Strategy Forum terhadap isu strategis bangsa.' },
    { slug: 'surat-pembaca', name: 'Surat Pembaca', description: 'Kontribusi pemikiran, catatan kritis, tanggapan, dan aspirasi masyarakat dari berbagai penjuru tanah air.' },
  ]
})

const subkategoriList = computed(() => rubrik.value?.subkategori || [])

const activeSubkategoriName = computed(() => {
  if (!currentSubkategori.value) return null
  if (currentSubkategori.value === 'polling') return 'Jajak Pendapat Pembaca'
  if (currentSubkategori.value === 'kutipan') return 'Kutipan Publik'
  const found = subkategoriList.value.find(s => s.slug === currentSubkategori.value)
  return found?.name || currentSubkategori.value
})

const headline = computed(() => data.value?.headline || null)
const articles = computed(() => {
  const list = data.value?.articles || []
  if (headline.value && currentPage.value === 1 && list.length === 11) {
    const isDup = list.some(a => (a.id && a.id === headline.value.id) || (a.slug && a.slug === headline.value.slug))
    if (!isDup) {
      return [...list, headline.value]
    }
  }
  return list
})
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 12 })
const activePollIndex = ref(0)
const readerPolls = computed(() => {
  if (data.value?.readerPolls && data.value.readerPolls.length > 0) {
    return data.value.readerPolls
  }
  return data.value?.readerPoll ? [data.value.readerPoll] : []
})
const readerPoll = computed(() => data.value?.readerPoll || null)
const selectedPoll = computed(() => {
  return readerPolls.value[activePollIndex.value] || data.value?.readerPoll || null
})
const kutipanPublik = computed(() => data.value?.kutipanPublik || [])
const suratPembacaList = computed(() => data.value?.suratPembaca || [])

function generateAuthorSlug(name) {
  if (!name) return 'member-isf'
  return String(name)
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'member-isf'
}

// Sorotan Opini Publik Berisi Artikel Kontribusi Member (Grid 4x3 = 12 Cards per Page + Pagination)
const sorotanCurrentPage = ref(1)
const sorotanPerPage = 12

const sorotanMemberCards = computed(() => {
  if (data.value?.sorotanMember && data.value.sorotanMember.length > 0) {
    return data.value.sorotanMember
  }
  const memberArts = (data.value?.articles || []).filter(a => a.is_by_member)
  if (memberArts.length > 0) {
    return memberArts
  }
  const fromLetters = (data.value?.suratPembaca || []).map(l => ({
    title: l.title,
    slug: l.slug,
    category: l.category || 'Opini',
    subcategory: l.subcategory || 'Artikel Member',
    summary: l.summary || (l.body ? l.body.slice(0, 130) : ''),
    author: l.sender_name,
    author_name: l.sender_name,
    author_slug: generateAuthorSlug(l.sender_name),
    author_affiliation: l.author_role || l.sender_city || 'Member Terverifikasi',
    author_avatar: l.author_avatar,
    imageUrl: l.image_url,
    readingMinutes: l.reading_minutes || 5,
    to: `/artikel/${l.slug}`,
    publishedAt: l.published_at_formatted || l.published_at,
    is_by_member: true,
  }))
  if (fromLetters.length > 0) {
    return fromLetters
  }
  return articles.value || []
})

const paginatedSorotanCards = computed(() => {
  const start = (sorotanCurrentPage.value - 1) * sorotanPerPage
  return sorotanMemberCards.value.slice(start, start + sorotanPerPage)
})

const sorotanTotalPages = computed(() => {
  return Math.max(1, Math.ceil(sorotanMemberCards.value.length / sorotanPerPage))
})

function onSorotanPageChange(page) {
  sorotanCurrentPage.value = page
  if (typeof window !== 'undefined') {
    const el = document.getElementById('headline-opini')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    } else {
      window.scrollTo({ top: 380, behavior: 'smooth' })
    }
  }
}

const filteredQuotes = computed(() => {
  const list = kutipanPublik.value || []
  if (kutipanFilter.value === 'tokoh') {
    return list.filter(q => q.author_type === 'tokoh' || q.tokoh_tipe === 'tokoh' || q.tokoh_id || q.tokoh)
  }
  if (kutipanFilter.value === 'netizen') {
    return list.filter(q => q.author_type === 'netizen' || q.tokoh_tipe === 'netizen' || (!q.tokoh_id && !q.tokoh))
  }
  return list
})

// CTA 1 hingga 5 sesuai instruksi untuk menggantikan tab Op-Ed ke kanan (tetap ditampilkan, belum difungsikan)
const ctaList = [
  { label: 'CTA 1' },
  { label: 'CTA 2' },
  { label: 'CTA 3' },
  { label: 'CTA 4' },
  { label: 'CTA 5' },
]

function handleBuatArtikelClick() {
  if (isLoggedIn.value) {
    window.location.href = '/cms/artikel'
  } else {
    window.location.href = '/register?redirect=/cms/artikel'
  }
}

function handleDaftarBuatArtikel() {
  handleBuatArtikelClick()
}

onMounted(async () => {
  await ensureLoaded()
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem('isf_popup_buat_artikel_open')
  }
})

function navigateSubkategori(slug) {
  currentPage.value = 1
  if (!slug) {
    router.push({ path: '/opini-publik' })
  } else {
    router.push({ path: `/opini-publik/${slug}` })
  }
}

function onPageChange(page) {
  currentPage.value = page
  load()
  if (typeof window !== 'undefined') {
    const el = document.getElementById('daftar-artikel-opini')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    } else {
      window.scrollTo({ top: 380, behavior: 'smooth' })
    }
  }
}

function toggleLetter(id) {
  expandedLetterId.value = expandedLetterId.value === id ? null : id
}


// Reset dan reload saat subkategori berganti (tanpa click-to-scroll sesuai instruksi)
watch(
  () => route.params?.subkategori,
  () => {
    currentPage.value = 1
    load()
  },
  { immediate: true }
)

// Update document title
watch(
  [() => rubrik.value?.title, activeSubkategoriName],
  ([title, subName]) => {
    if (typeof document !== 'undefined') {
      const pageTitle = subName
        ? `${subName} · Opini Publik · Indonesia Strategy Forum`
        : `${title || 'Opini Publik'} · Indonesia Strategy Forum`
      document.title = pageTitle
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK OPINI PUBLIK (Gaya Editorial Navy identik dengan Politik & Hukum) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-6 sm:pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink
            to="/opini-publik"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              !currentSubkategori ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Opini Publik
          </RouterLink>
          <template v-if="activeSubkategoriName">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-white font-bold whitespace-nowrap shrink-0 truncate max-w-[160px] sm:max-w-none">{{ activeSubkategoriName }}</span>
          </template>
        </nav>

        <!-- Rubrik Title & Deskripsi + Filter Pills -->
        <div>
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>RUBRIK DISKURSUS, OPINI & SUARA PUBLIK</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
            OPINI PUBLIK
          </h1>
          <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
            {{ rubrik.description }}
          </p>

          <!-- Filter Pills Kategori Opini Publik & CTA Kirim Surat (Di dalam hero header navy di bawah deskripsi) -->
          <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1" role="tablist" aria-label="Filter Subkategori Opini Publik">
              <button
                type="button"
                @click="navigateSubkategori(null)"
                :class="[
                  'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                  !currentSubkategori
                    ? 'bg-isf-red text-white'
                    : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
                ]"
              >
                Semua Artikel
              </button>
              <button
                v-for="(cta, cIdx) in ctaList"
                :key="cIdx"
                type="button"
                class="px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 bg-[#1a2333] text-neutral-300 border border-neutral-700/60 cursor-default opacity-85 select-none"
                :title="cta.label"
              >
                <span>{{ cta.label }}</span>
              </button>
            </div>

            <!-- CTA Buat Artikel: Jika belum login: "Daftar untuk Buat Artikel" -> /register; Jika sudah login: "Buat Artikel" -> /cms/artikel -->
            <button
              type="button"
              @click="handleBuatArtikelClick"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded text-xs font-space-grotesk font-bold bg-isf-red hover:bg-red-700 text-white transition-all shrink-0 shadow-xs cursor-pointer"
            >
              <IconFileText class="w-3.5 h-3.5" />
              <span>{{ isLoggedIn ? 'Buat Artikel' : 'Daftar untuk Buat Artikel' }}</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi 1:1 mencerminkan bentuk asli setiap section di Desktop & Mobile) ═══ -->
    <main v-if="status === 'loading' && !data" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat konten rubrik Opini Publik">
      <!-- Section 1 Skeleton: Sorotan Utama (4 Cards Grid Berisi Artikel Member) -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="space-y-1.5">
            <div class="h-3 w-32 bg-neutral-200 rounded"></div>
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 bg-isf-red rounded-xs"></div>
              <div class="h-5 sm:h-6 w-48 sm:w-56 bg-neutral-300 rounded"></div>
            </div>
          </div>
        </div>

        <div class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="i in 4"
              :key="i"
              class="flex flex-col justify-between bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden"
            >
              <!-- Image 16:10 -->
              <div class="relative aspect-[16/10] bg-neutral-200">
              </div>

              <!-- Content -->
              <div class="p-4 sm:p-4.5 flex-1 flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <div class="h-3 w-14 bg-neutral-200 rounded"></div>
                    <div class="h-3 w-12 bg-neutral-200 rounded"></div>
                  </div>
                  <div class="h-4.5 w-full bg-neutral-300 rounded"></div>
                  <div class="h-4.5 w-4/5 bg-neutral-300 rounded"></div>
                  <div class="h-3 w-full bg-neutral-200 rounded mt-1"></div>
                  <div class="h-3 w-3/4 bg-neutral-200 rounded"></div>
                </div>

                <!-- Author Row -->
                <div class="pt-3 border-t border-neutral-200 flex items-center justify-between gap-2 mt-auto">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-neutral-300 shrink-0"></div>
                    <div class="space-y-1">
                      <div class="h-3 w-20 bg-neutral-300 rounded"></div>
                      <div class="h-2.5 w-14 bg-neutral-200 rounded"></div>
                    </div>
                  </div>
                  <div class="w-7 h-7 rounded-full bg-neutral-200 shrink-0"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2 Skeleton: Jajak Pendapat Pembaca -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-6 lg:p-8 space-y-6">
        <div class="border-b border-neutral-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="h-3 w-44 bg-neutral-200 rounded"></div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
              <div class="h-6 w-60 bg-neutral-300 rounded"></div>
            </div>
          </div>
          <div class="flex gap-2">
            <div class="h-7 w-20 bg-neutral-200 rounded-full"></div>
            <div class="h-7 w-20 bg-neutral-200 rounded-full"></div>
          </div>
        </div>

        <!-- Poll Card Inside Light Frame -->
        <div class="max-w-3xl mx-auto bg-neutral-50 rounded-xl border border-neutral-200 p-5 sm:p-7 space-y-6">
          <div class="space-y-2">
            <div class="h-3 w-24 bg-neutral-200 rounded"></div>
            <div class="h-6 w-full bg-neutral-300 rounded"></div>
            <div class="h-6 w-3/4 bg-neutral-300 rounded"></div>
            <div class="h-3.5 w-5/6 bg-neutral-200 rounded mt-1"></div>
          </div>
          <!-- Option Bars -->
          <div class="space-y-3">
            <div v-for="opt in 3" :key="opt" class="h-12 w-full bg-white rounded-lg border border-neutral-200 flex items-center px-4 justify-between">
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full border border-neutral-300 bg-neutral-200"></div>
                <div class="h-3.5 w-44 bg-neutral-200 rounded"></div>
              </div>
              <div class="h-3.5 w-10 bg-neutral-200 rounded"></div>
            </div>
          </div>
          <!-- Vote Button & Disclaimer -->
          <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="h-9 w-32 bg-isf-red/70 rounded-lg"></div>
            <div class="h-3 w-56 bg-neutral-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Section 3 Skeleton: Kutipan Publik -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-300 pb-4 mb-6">
          <div class="space-y-1">
            <div class="h-3 w-44 bg-neutral-200 rounded"></div>
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 bg-isf-red rounded-xs"></div>
              <div class="h-6 w-52 bg-neutral-300 rounded"></div>
            </div>
          </div>
          <div class="flex items-center gap-1.5 p-1 bg-white border border-neutral-200 rounded-lg">
            <div class="h-6 w-20 bg-neutral-300 rounded"></div>
            <div class="h-6 w-24 bg-neutral-200 rounded"></div>
            <div class="h-6 w-24 bg-neutral-200 rounded"></div>
          </div>
        </div>

        <!-- 3 Quote Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="q in 3"
            :key="q"
            class="bg-[#FBF9F4] rounded-xl border border-neutral-300/80 p-5 sm:p-6 flex flex-col justify-between space-y-4"
          >
            <div class="space-y-3">
              <div class="w-6 h-6 rounded bg-neutral-300"></div>
              <div class="space-y-2">
                <div class="h-4 w-full bg-neutral-300 rounded"></div>
                <div class="h-4 w-full bg-neutral-300 rounded"></div>
                <div class="h-4 w-3/5 bg-neutral-300 rounded"></div>
              </div>
              <div class="h-3 w-28 bg-neutral-200 rounded mt-2"></div>
            </div>
            <div class="pt-4 border-t border-neutral-200/80 flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-neutral-300 shrink-0"></div>
              <div class="space-y-1.5 flex-1">
                <div class="h-3.5 w-24 bg-neutral-300 rounded"></div>
                <div class="h-2.5 w-16 bg-neutral-200 rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 4 Skeleton: Artikel by Member Terkurasi -->
      <div class="bg-white rounded-lg border border-neutral-300 p-4 sm:p-6 lg:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-200 pb-4 mb-6">
          <div class="space-y-1">
            <div class="h-3 w-48 bg-neutral-200 rounded"></div>
            <div class="h-6 w-64 bg-neutral-300 rounded"></div>
            <div class="h-3.5 w-96 max-w-full bg-neutral-200 rounded mt-1"></div>
          </div>
          <div class="h-9 w-36 bg-neutral-200 rounded-lg shrink-0"></div>
        </div>

        <!-- 2 Letter Cards Stack -->
        <div class="space-y-4">
          <div
            v-for="l in 2"
            :key="l"
            class="border border-neutral-200 rounded-xl p-5 bg-neutral-50/60 space-y-3"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3 flex-1">
                <div class="w-11 h-11 rounded-lg bg-neutral-300 shrink-0"></div>
                <div class="space-y-2 flex-1">
                  <div class="flex items-center gap-2">
                    <div class="h-3 w-24 bg-neutral-300 rounded"></div>
                    <div class="h-3 w-16 bg-neutral-200 rounded"></div>
                    <div class="h-3 w-20 bg-neutral-200 rounded"></div>
                  </div>
                  <div class="h-5 w-4/5 bg-neutral-300 rounded"></div>
                </div>
              </div>
              <div class="h-5 w-28 bg-emerald-500/15 rounded-full shrink-0"></div>
            </div>
            <div class="space-y-1.5 pt-1">
              <div class="h-3.5 w-full bg-neutral-200 rounded"></div>
              <div class="h-3.5 w-5/6 bg-neutral-200 rounded"></div>
            </div>
            <div class="pt-3 border-t border-neutral-200/80 flex items-center justify-between">
              <div class="h-3 w-32 bg-neutral-300 rounded"></div>
              <div class="h-3 w-24 bg-neutral-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 5 Skeleton: Artikel Terbaru + Pagination -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="space-y-1">
            <div class="h-3 w-32 bg-neutral-200 rounded"></div>
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 bg-isf-red rounded-xs"></div>
              <div class="h-6 w-44 bg-neutral-300 rounded"></div>
            </div>
          </div>
        </div>

        <!-- 4 Columns Grid of Articles (Grid 12 Cards 4x3) -->
        <div class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="n in 4"
              :key="n"
              class="p-4 bg-white rounded-xl border border-neutral-200 space-y-3"
            >
              <!-- Thumbnail -->
              <div class="aspect-[16/10] bg-neutral-200 rounded-md"></div>
              <!-- Text Content -->
              <div class="space-y-2 min-w-0">
                <div class="h-3 w-20 bg-neutral-200 rounded"></div>
                <div class="h-4.5 w-full bg-neutral-300 rounded"></div>
                <div class="h-3 w-3/4 bg-neutral-200 rounded"></div>
                <div class="flex items-center gap-2 pt-1 border-t border-neutral-100">
                  <div class="h-2.5 w-16 bg-neutral-200 rounded"></div>
                  <div class="h-2.5 w-12 bg-neutral-200 rounded"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Skeleton -->
        <div class="py-5 sm:py-6 flex justify-center border-t border-neutral-200 gap-1.5">
          <div class="h-8 w-20 bg-neutral-200 rounded"></div>
          <div class="h-8 w-8 bg-neutral-300 rounded"></div>
          <div class="h-8 w-8 bg-neutral-200 rounded"></div>
          <div class="h-8 w-8 bg-neutral-200 rounded"></div>
          <div class="h-8 w-20 bg-neutral-200 rounded"></div>
        </div>
      </div>
    </main>

    <!-- Error state -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Gagal Memuat Opini Publik"
        message="Gagal memuat konten rubrik Opini Publik. Periksa koneksi Anda lalu coba lagi."
        action-label="Coba Lagi"
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: SOROTAN UTAMA (4x3 CARDS ARTIKEL BY MEMBER + PAGINATION) ═════════════════════ -->
      <section
        v-if="(!currentSubkategori || ['op-ed', 'editorial'].includes(currentSubkategori)) && sorotanMemberCards.length > 0"
        aria-labelledby="headline-opini"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="headline-opini"
            title="Sorotan Opini Publik"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Artikel Kontribusi Member Terverifikasi</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6']">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <article
              v-for="(card, cIdx) in paginatedSorotanCards"
              :key="card.slug || cIdx"
              class="group flex flex-col justify-between bg-neutral-50 hover:bg-neutral-100/90 rounded-xl border border-neutral-200/90 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-isf-red/40"
            >
              <!-- Card Image / Thumbnail (Aspect 16:10, full-bleed without black bar) -->
              <IsfLink :to="card.to || `/artikel/${card.slug}`" class="block relative aspect-[16/10] overflow-hidden bg-neutral-200">
                <img
                  v-if="card.imageUrl || card.thumbnail || card.cover_image"
                  :src="card.imageUrl || card.thumbnail || card.cover_image"
                  :alt="card.imageAlt || card.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
                <!-- Clean neutral placeholder filling 100% of container without black bars -->
                <div
                  v-else
                  class="w-full h-full flex flex-col items-center justify-center bg-neutral-200 text-neutral-400 group-hover:bg-neutral-300/60 transition-colors select-none"
                >
                  <IconPhoto class="w-10 h-10 stroke-[1.25] text-neutral-400 group-hover:text-neutral-500 transition-colors" />
                </div>
              </IsfLink>

              <!-- Card Content -->
              <div class="p-4 sm:p-4.5 flex-1 flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                  <div class="flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                    <span class="text-isf-red font-bold uppercase tracking-wide">
                      {{ card.subcategory ? card.subcategory.toUpperCase() : 'OPINI' }}
                    </span>
                    <span class="flex items-center gap-1">
                      <IconClock class="w-3 h-3" />
                      {{ card.readingMinutes || 5 }} mnt
                    </span>
                  </div>

                  <h3 class="font-headline font-bold text-sm sm:text-base leading-snug text-neutral-900 group-hover:text-isf-red transition-colors line-clamp-2">
                    <IsfLink :to="card.to || `/artikel/${card.slug}`">
                      {{ card.title }}
                    </IsfLink>
                  </h3>

                  <p v-if="card.summary || card.excerpt" class="text-neutral-600 text-xs leading-relaxed line-clamp-2 font-serif">
                    {{ card.summary || card.excerpt }}
                  </p>
                </div>

                <!-- Author Profile Footer -->
                <div class="pt-3 border-t border-neutral-200/80 flex items-center justify-between gap-2 mt-auto">
                  <IsfLink
                    :to="`/penulis/${card.author_slug || generateAuthorSlug(card.author || card.author_name)}`"
                    class="group/author flex items-center gap-2 min-w-0 hover:opacity-85 transition-opacity"
                    :title="`Kunjungi profil ${card.author || card.author_name || 'penulis'}`"
                  >
                    <div class="w-7 h-7 rounded-full overflow-hidden bg-neutral-200 shrink-0 border border-neutral-300">
                      <img
                        v-if="card.author_avatar"
                        :src="card.author_avatar"
                        :alt="card.author || card.author_name"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center bg-neutral-800 text-white font-bold text-[10px]">
                        {{ (card.author || card.author_name || 'M')[0] }}
                      </div>
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs font-semibold text-neutral-900 truncate font-space-grotesk group-hover/author:text-isf-red transition-colors">
                        {{ card.author || card.author_name || 'Member ISF' }}
                      </p>
                      <p class="text-[10px] text-neutral-500 truncate">
                        {{ card.author_affiliation || 'Member Terverifikasi' }}
                      </p>
                    </div>
                  </IsfLink>

                  <!-- Button Arrow di samping nama penulis -> mengarah ke detail profile penulis -->
                  <IsfLink
                    :to="`/penulis/${card.author_slug || generateAuthorSlug(card.author || card.author_name)}`"
                    class="w-7 h-7 rounded-full flex items-center justify-center text-isf-red hover:bg-isf-red/10 transition-colors shrink-0"
                    :title="`Kunjungi profil ${card.author || card.author_name || 'penulis'}`"
                    :aria-label="`Kunjungi profil ${card.author || card.author_name || 'penulis'}`"
                  >
                    <IconArrowRight class="w-4 h-4" />
                  </IsfLink>
                </div>
              </div>
            </article>
          </div>
        </div>

        <!-- Pagination Sorotan Opini Publik (4 Kolom x 3 Baris) -->
        <div
          v-if="sorotanMemberCards.length > 0"
          :class="['py-5 sm:py-6 flex justify-center border-t border-neutral-200', sectionPad]"
        >
          <Pagination
            :current-page="sorotanCurrentPage"
            :total-pages="sorotanTotalPages"
            @update:current-page="onSorotanPageChange"
          />
        </div>
      </section>

      <!-- ═══ SECTION 2: FITUR KHUSUS: JAJAK PENDAPAT PEMBACA (NAVY FRAME) ════════════ -->
      <section
        v-if="selectedPoll && (!currentSubkategori || currentSubkategori === 'polling')"
        id="isf-reader-poll-section"
        aria-labelledby="jajak-pendapat-heading"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-6 lg:p-8 scroll-mt-20 isf-full-layer-section--navy"
      >
        <div class="border-b border-neutral-800 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <SectionHeading
            id="jajak-pendapat-heading"
            title="Jajak Pendapat Pembaca"
            size="lg"
          >
            <template #eyebrow>Suara & Persepsi Publik Non-Ilmiah</template>
          </SectionHeading>

          <!-- Poll Switcher jika ada lebih dari 1 polling aktif di CMS -->
          <div v-if="readerPolls.length > 1" class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar">
            <button
              v-for="(p, idx) in readerPolls"
              :key="p.id"
              type="button"
              @click="activePollIndex = idx"
              :class="[
                'px-3.5 py-1.5 rounded-full text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5',
                activePollIndex === idx
                  ? 'bg-isf-red text-white shadow-xs'
                  : 'bg-white/10 text-neutral-300 hover:bg-white/20 hover:text-white'
              ]"
            >
              <span>Isu {{ idx + 1 }}</span>
              <span v-if="p.status === 'aktif'" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            </button>
          </div>
        </div>

        <ReaderPollCard :poll="selectedPoll" :embedded="true" :is-dark="true" />
      </section>

      <!-- ═══ SECTION 3: FITUR KHUSUS: KUTIPAN PUBLIK ═════════ -->
      <section
        v-if="kutipanPublik && kutipanPublik.length > 0 && (!currentSubkategori || currentSubkategori === 'kutipan')"
        id="isf-kutipan-publik-section"
        aria-labelledby="kutipan-publik-heading"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-4 sm:p-6 lg:p-8 scroll-mt-20"
      >
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-300 pb-4 mb-6">
          <SectionHeading
            id="kutipan-publik-heading"
            title="Kutipan Publik Terpilih"
            size="lg"
          >
            <template #eyebrow>Pernyataan Kunci & Resonansi Tokoh</template>
          </SectionHeading>

          <!-- Filter Tokoh vs Netizen -->
          <div class="inline-flex items-center gap-1 bg-white border border-neutral-300 rounded-lg p-1 text-xs font-semibold shadow-xs self-start sm:self-auto">
            <button
              type="button"
              @click="kutipanFilter = 'all'"
              :class="[
                'px-3 py-1 rounded transition-colors cursor-pointer',
                kutipanFilter === 'all' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Semua ({{ kutipanPublik.length }})
            </button>
            <button
              type="button"
              @click="kutipanFilter = 'tokoh'"
              :class="[
                'px-3 py-1 rounded transition-colors cursor-pointer',
                kutipanFilter === 'tokoh' ? 'bg-amber-600 text-white' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Tokoh Publik
            </button>
            <button
              type="button"
              @click="kutipanFilter = 'netizen'"
              :class="[
                'px-3 py-1 rounded transition-colors cursor-pointer',
                kutipanFilter === 'netizen' ? 'bg-sky-600 text-white' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Suara Warga
            </button>
          </div>
        </div>

        <!-- Quotes Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 items-start">
          <KutipanPublikCard
            v-for="quote in filteredQuotes"
            :key="quote.id"
            :quote="quote"
            theme="light"
          />
        </div>
      </section>

      <!-- ═══ SECTION 4: FITUR KHUSUS: ARTIKEL BY MEMBER TERKURASI ═════════════ -->
      <section
        v-if="!currentSubkategori || currentSubkategori === 'surat-pembaca'"
        id="isf-surat-pembaca-section"
        aria-labelledby="surat-pembaca-heading"
        class="bg-white rounded-lg border border-neutral-300 p-4 sm:p-6 lg:p-8 shadow-xs scroll-mt-20"
      >
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-200 pb-4 mb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
              <span>Kontribusi Anggota & Komunitas</span>
            </div>
            <h2 id="surat-pembaca-heading" class="font-headline text-2xl sm:text-3xl font-black text-neutral-950">
              Artikel by Member Terkurasi
            </h2>
            <p class="text-neutral-600 text-xs sm:text-sm mt-1">
              Gagasan, analisis mendalam, dan refleksi pemikiran member yang telah melalui proses kurasi dan penyuntingan Dewan Redaksi ISF.
            </p>
          </div>

          <button
            type="button"
            @click="handleBuatArtikelClick"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs sm:text-sm font-bold font-space-grotesk transition-colors shrink-0 shadow-xs cursor-pointer self-start sm:self-auto"
          >
            <IconFileText class="w-4 h-4 text-isf-red" />
            <span>{{ isLoggedIn ? 'Buat Artikel' : 'Daftar untuk Buat Artikel' }}</span>
          </button>
        </div>

        <!-- Letters List -->
        <div v-if="suratPembacaList && suratPembacaList.length > 0" class="space-y-4">
          <article
            v-for="letter in suratPembacaList"
            :key="letter.id"
            class="border border-neutral-200 rounded-xl p-5 hover:border-neutral-400 transition-colors bg-neutral-50/60"
          >
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-2">
              <div class="flex items-start gap-3">
                <!-- 1:1 Author Avatar -->
                <div v-if="letter.author_avatar" class="w-11 h-11 rounded-lg overflow-hidden border border-neutral-300 bg-neutral-200 shrink-0 mt-0.5">
                  <img :src="letter.author_avatar" :alt="letter.sender_name" class="w-full h-full object-cover" />
                </div>
                <div>
                  <div class="flex items-center gap-2 text-[11px] font-mono text-neutral-500 mb-1 flex-wrap">
                    <span class="font-bold text-neutral-800">{{ letter.sender_name }}</span>
                    <span v-if="letter.author_role" class="text-neutral-600">({{ letter.author_role }})</span>
                    <span>•</span>
                    <span v-if="letter.sender_city" class="flex items-center gap-1">
                      <IconMapPin class="w-3 h-3 text-neutral-400" />
                      {{ letter.sender_city }}
                    </span>
                    <span v-if="letter.sender_city">•</span>
                    <span>{{ formatDate(letter.published_at) }}</span>
                  </div>
                  <h3 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 leading-snug">
                    {{ letter.title }}
                  </h3>
                </div>
              </div>

              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-700 border border-emerald-500/30 shrink-0 self-start">
                <IconShieldCheck class="w-3 h-3 text-emerald-600" />
                Diverifikasi Redaksi
              </span>
            </div>

            <!-- Letter Body with Expand/Collapse -->
            <div class="mt-3 text-xs sm:text-sm text-neutral-700 leading-relaxed font-newsreader">
              <p v-if="expandedLetterId === letter.id" class="whitespace-pre-line">
                {{ letter.body }}
              </p>
              <p v-else class="line-clamp-3">
                {{ letter.body }}
              </p>
            </div>

            <div class="mt-3 flex items-center justify-between pt-3 border-t border-neutral-200/80">
              <button
                type="button"
                @click="toggleLetter(letter.id)"
                class="text-xs font-bold font-space-grotesk text-isf-red hover:underline cursor-pointer"
              >
                {{ expandedLetterId === letter.id ? 'Tutup Isi Lengkap' : 'Baca Artikel Lengkap →' }}
              </button>
              <span class="text-[10px] font-mono text-neutral-400">
                Status: Terverifikasi
              </span>
            </div>
          </article>
        </div>

        <!-- Empty Letters State -->
        <div v-else class="text-center py-10 bg-neutral-50 rounded-xl border border-dashed border-neutral-300">
          <IconFileText class="w-10 h-10 text-neutral-400 mx-auto mb-2" />
          <h4 class="font-bold text-neutral-800 text-sm font-space-grotesk">Belum Ada Artikel by Member yang Ditayangkan</h4>
          <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
            Jadilah yang pertama mengirimkan analisis dan gagasan Anda untuk dipublikasikan di ISF.
          </p>
          <button
            type="button"
            @click="handleBuatArtikelClick"
            class="mt-4 px-4 py-2 rounded-lg bg-isf-red text-white text-xs font-bold font-space-grotesk hover:bg-red-700 transition-colors cursor-pointer"
          >
            {{ isLoggedIn ? 'Buat Artikel' : 'Daftar untuk Buat Artikel' }}
          </button>
        </div>
      </section>

      <!-- ═══ SECTION 5: DAFTAR ARTIKEL TERBARU + PAGINATION (Format Identik dengan Politik) ═══ -->
      <section
        aria-labelledby="daftar-artikel-opini"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="daftar-artikel-opini"
            title="Artikel Terbaru"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>
              {{ meta.total }} Total Artikel{{ activeSubkategoriName ? ` (${activeSubkategoriName})` : '' }}
            </template>
          </SectionHeading>
        </div>

        <!-- Empty state jika benar-benar tidak ada artikel -->
        <div v-if="articles.length === 0 && !headline" class="py-16 text-center" :class="sectionPad">
          <p class="text-neutral-500 text-sm font-space-grotesk">
            Belum ada artikel yang diterbitkan untuk kategori ini.
          </p>
        </div>

        <!-- 4 Kolom Artikel (Grid 12 Cards 4x3) -->
        <div v-else class="p-4 sm:p-6 lg:p-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <article
              v-for="(item, idx) in articles"
              :key="item.slug || item.id || idx"
              class="group flex flex-col justify-between bg-white hover:bg-neutral-50/80 rounded-xl border border-neutral-200 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-isf-red/40"
            >
              <IsfLink :to="item.to || `/artikel/${item.slug}`" class="block relative aspect-[16/10] overflow-hidden bg-neutral-200">
                <ImagePlaceholder
                  :label="item.imageAlt || item.title"
                  :src="item.imageUrl || item.thumbnail || item.cover_image || ''"
                  aspect="photo"
                  bare
                  :priority="idx === 0"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
              </IsfLink>
              <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
                <div>
                  <span class="isf-kicker text-isf-red block font-bold text-[11px] sm:text-xs">
                    {{ item.category }}{{ item.subcategory ? ` · ${item.subcategory}` : '' }}
                  </span>
                  <h3 class="isf-headline mt-1.5 text-[15px] sm:text-[16px] leading-[1.3] group-hover:text-isf-red transition-colors line-clamp-2">
                    <IsfLink :to="item.to || `/artikel/${item.slug}`">
                      {{ item.title }}
                    </IsfLink>
                  </h3>
                  <p v-if="item.summary || item.excerpt" class="mt-1.5 font-serif text-[12px] sm:text-[13px] leading-[1.4] text-neutral-600 line-clamp-2">
                    {{ item.summary || item.excerpt }}
                  </p>
                </div>
                <div class="isf-byline pt-3 border-t border-neutral-100 flex items-center justify-between text-[11px] sm:text-xs text-neutral-500 mt-auto">
                  <span class="truncate font-medium text-neutral-700 max-w-[55%]">
                    {{ item.author || item.author_name || 'Tim Riset ISF' }}
                  </span>
                  <time v-if="item.publishedAt" :datetime="item.publishedAt" class="shrink-0 text-neutral-500">
                    {{ formatDate(item.publishedAt) }}
                  </time>
                </div>
              </div>
            </article>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta.total > 0" :class="['py-5 sm:py-6 flex justify-center border-t border-neutral-200', sectionPad]">
          <Pagination
            :current-page="meta.currentPage"
            :total-pages="Math.max(1, meta.lastPage)"
            @update:current-page="onPageChange"
          />
        </div>
      </section>
    </main>
  </div>
</template>
