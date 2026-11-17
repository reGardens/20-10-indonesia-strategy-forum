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
import { computed, ref, watch } from 'vue'
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
  IconX,
} from '@tabler/icons-vue'
import {
  ImagePlaceholder,
  IsfLink,
  SectionHeading,
  ReaderPollCard,
  KutipanPublikCard,
  SuratPembacaForm,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getOpiniPublik } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const linkClass =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2'

// Subkategori dari URL parameter (op-ed, editorial, surat-pembaca, polling, kutipan)
const currentSubkategori = computed(() => {
  return route.params.subkategori || null
})

const currentPage = ref(1)
const showSuratModal = ref(false)
const kutipanFilter = ref('all') // 'all' | 'tokoh' | 'netizen'
const expandedLetterId = ref(null)

// Fetch data rubrik opini publik
const { data, status, load } = useIsfContent(() => {
  const param = ['polling', 'kutipan'].includes(currentSubkategori.value)
    ? null
    : currentSubkategori.value
  return getOpiniPublik(param, currentPage.value, 9)
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Opini Publik',
  label: 'Opini Publik',
  description: 'Wadah dialektika pemikiran, analisis kritis akademisi, sikap resmi dewan redaksi, serta ruang aspirasi warga bagi kemajuan bangsa.',
  subkategori: [
    { slug: 'op-ed', name: 'Op-Ed & Pakar', description: 'Tulisan opini mendalam dari akademisi, analis, dan kontributor tamu independen.' },
    { slug: 'editorial', name: 'Editorial ISF', description: 'Pandangan resmi dan sikap editorial dewan redaksi ISF atas isu strategis nasional.' },
    { slug: 'surat-pembaca', name: 'Surat Pembaca', description: 'Aspirasi, kritik konstruktif, dan masukan masyarakat luas yang telah dikurasi redaksi.' },
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
const articles = computed(() => data.value?.articles || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })
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
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function toggleLetter(id) {
  expandedLetterId.value = expandedLetterId.value === id ? null : id
}

function onSuratSubmitted() {
  load()
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
                v-for="sub in subkategoriList"
                :key="sub.slug"
                type="button"
                @click="navigateSubkategori(sub.slug)"
                :class="[
                  'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                  currentSubkategori === sub.slug
                    ? 'bg-isf-red text-white'
                    : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
                ]"
              >
                {{ sub.name }}
              </button>
              <button
                type="button"
                @click="navigateSubkategori('polling')"
                :class="[
                  'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5 border',
                  currentSubkategori === 'polling'
                    ? 'bg-isf-red text-white border-isf-red'
                    : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
                ]"
              >
                <IconFlame class="w-3.5 h-3.5 text-amber-500" />
                <span>Jajak Pendapat</span>
              </button>
              <button
                type="button"
                @click="navigateSubkategori('kutipan')"
                :class="[
                  'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5 border',
                  currentSubkategori === 'kutipan'
                    ? 'bg-isf-red text-white border-isf-red'
                    : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
                ]"
              >
                <IconQuote class="w-3.5 h-3.5 text-sky-400" />
                <span>Kutipan Publik</span>
              </button>
            </div>

            <!-- CTA Kirim Surat Pembaca (Di kanan header navy) -->
            <button
              type="button"
              @click="showSuratModal = true"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded text-xs font-space-grotesk font-bold bg-isf-red hover:bg-red-700 text-white transition-all shrink-0 shadow-xs cursor-pointer"
            >
              <IconMail class="w-3.5 h-3.5" />
              <span>Kirim Surat</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan isi konten di Desktop & Mobile) ═══ -->
    <main v-if="status === 'loading' && !data" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat konten rubrik Opini Publik">
      <!-- Section 1 Skeleton: Headline -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-40 bg-neutral-200 rounded"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 p-4 sm:p-6">
          <div class="lg:col-span-7 aspect-[16/10] bg-neutral-200 rounded-md"></div>
          <div class="lg:col-span-5 flex flex-col justify-between space-y-4">
            <div class="space-y-3">
              <div class="h-3 w-28 bg-neutral-200 rounded"></div>
              <div class="h-7 w-full bg-neutral-300 rounded"></div>
              <div class="h-7 w-4/5 bg-neutral-300 rounded"></div>
              <div class="h-4 w-full bg-neutral-200 rounded mt-2"></div>
              <div class="h-4 w-5/6 bg-neutral-200 rounded"></div>
            </div>
            <div class="pt-3 border-t border-neutral-200 flex gap-3">
              <div class="h-3 w-24 bg-neutral-200 rounded"></div>
              <div class="h-3 w-20 bg-neutral-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2 Skeleton: Reader Poll Navy -->
      <div class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-800 pb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
            <div class="h-4 w-44 bg-neutral-700 rounded"></div>
          </div>
        </div>
        <div class="max-w-3xl mx-auto h-48 bg-neutral-900 rounded-xl border border-neutral-800"></div>
      </div>

      <!-- Section 3 Skeleton: Kutipan Publik -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-300 pb-4 flex items-center justify-between">
          <div class="h-4 w-44 bg-neutral-300 rounded"></div>
          <div class="h-7 w-32 bg-neutral-300 rounded-lg"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div v-for="n in 3" :key="n" class="h-40 bg-neutral-200 rounded-xl"></div>
        </div>
      </div>

      <!-- Section 4 Skeleton: Surat Pembaca White -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden p-4 sm:p-6 space-y-4">
        <div class="border-b border-neutral-200 pb-4 flex items-center justify-between">
          <div class="h-4 w-44 bg-neutral-300 rounded"></div>
          <div class="h-8 w-36 bg-neutral-200 rounded-lg"></div>
        </div>
        <div class="space-y-3">
          <div v-for="n in 2" :key="n" class="h-28 bg-neutral-100 rounded-xl border border-neutral-200"></div>
        </div>
      </div>

      <!-- Section 5 Skeleton: Artikel Terbaru Grid -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-32 bg-neutral-300 rounded"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2">
          <div v-for="n in 4" :key="n" class="grid grid-cols-[6rem_1fr] sm:grid-cols-[10rem_1fr] gap-3 sm:gap-4 p-4 border-b border-neutral-200">
            <div class="aspect-[4/3] bg-neutral-200 rounded"></div>
            <div class="space-y-2">
              <div class="h-3 w-16 bg-neutral-200 rounded"></div>
              <div class="h-4 w-full bg-neutral-300 rounded"></div>
              <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
            </div>
          </div>
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
      <!-- ═══ SECTION 1: SOROTAN UTAMA (HEADLINE) ═════════════════════════ -->
      <section
        v-if="headline && currentPage === 1 && (!currentSubkategori || ['op-ed', 'editorial'].includes(currentSubkategori))"
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
            <template #eyebrow>Laporan & Diskursus Utama</template>
          </SectionHeading>
        </div>

        <IsfLink
          :to="headline.to || `/artikel/${headline.slug}`"
          :class="[
            linkClass,
            'group grid grid-cols-1 gap-4 py-4 sm:gap-6 sm:py-5 lg:grid-cols-12 lg:gap-8 lg:py-6',
            sectionPad
          ]"
        >
          <!-- Thumbnail Cover -->
          <div class="lg:col-span-7 relative overflow-hidden rounded-md bg-neutral-900 aspect-[16/10]">
            <img
              v-if="headline.imageUrl || headline.thumbnail || headline.cover_image"
              :src="headline.imageUrl || headline.thumbnail || headline.cover_image"
              :alt="headline.imageAlt || headline.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <ImagePlaceholder v-else class="w-full h-full" />

            <!-- Badges over image -->
            <div class="absolute top-3 left-3 flex flex-wrap gap-2">
              <span
                v-if="headline.is_editorial || headline.subcategory === 'editorial'"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-isf-red text-white shadow-md border border-red-500"
              >
                <IconAward class="w-3.5 h-3.5 shrink-0" />
                Editorial Resmi ISF
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-neutral-900/90 backdrop-blur-md text-amber-400 border border-amber-500/30"
              >
                <IconUser class="w-3.5 h-3.5 shrink-0" />
                Op-Ed Pakar
              </span>
            </div>
          </div>

          <!-- Content Column -->
          <div class="min-w-0 lg:col-span-5 flex flex-col justify-between">
            <div>
              <span class="isf-kicker text-isf-red font-bold text-[11px] sm:text-xs">
                {{ headline.subcategory ? `OPINI · ${headline.subcategory.toUpperCase()}` : 'OPINI PUBLIK' }}
              </span>
              <h2 class="font-headline text-xl sm:text-2xl lg:text-3xl font-bold leading-tight text-neutral-950 group-hover:text-isf-red transition-colors mt-2 mb-3">
                {{ headline.title }}
              </h2>
              <p class="font-newsreader italic text-neutral-700 text-sm sm:text-base leading-relaxed line-clamp-3 mb-4">
                "{{ headline.summary || headline.excerpt || headline.title }}"
              </p>
            </div>

            <!-- Author & Byline Details -->
            <div class="pt-4 border-t border-neutral-200 flex items-center justify-between gap-4">
              <div class="flex items-center gap-2.5 min-w-0">
                <div
                  :class="[
                    'w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs font-space-grotesk border shrink-0',
                    headline.is_editorial
                      ? 'bg-isf-red/20 text-isf-red border-isf-red/40'
                      : 'bg-amber-500/20 text-amber-600 border-amber-500/40'
                  ]"
                >
                  {{ (headline.author || headline.author_name || (headline.is_editorial ? 'ISF' : 'OP')).substring(0, 2).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="text-xs sm:text-sm font-bold font-space-grotesk text-neutral-900 truncate">
                    {{ headline.author || headline.author_name || (headline.is_editorial ? 'Dewan Redaksi ISF' : 'Penulis Op-Ed') }}
                  </div>
                  <div class="text-[11px] text-neutral-500 font-space-grotesk flex items-center gap-1.5 truncate">
                    <time v-if="headline.publishedAt" :datetime="headline.publishedAt">{{ formatDate(headline.publishedAt) }}</time>
                    <span v-if="headline.readingMinutes">• {{ headline.readingMinutes }} mnt baca</span>
                  </div>
                </div>
              </div>

              <!-- Read Link -->
              <span class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold font-space-grotesk text-isf-red group-hover:underline shrink-0">
                <span>Baca</span>
                <IconArrowRight class="w-4 h-4" />
              </span>
            </div>
          </div>
        </IsfLink>
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

      <!-- ═══ SECTION 4: FITUR KHUSUS: SURAT PEMBACA TERKURASI ═════════════ -->
      <section
        v-if="!currentSubkategori || currentSubkategori === 'surat-pembaca'"
        id="isf-surat-pembaca-section"
        aria-labelledby="surat-pembaca-heading"
        class="bg-white rounded-lg border border-neutral-300 p-4 sm:p-6 lg:p-8 shadow-xs scroll-mt-20"
      >
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-200 pb-4 mb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
              <span>Kontribusi Masyarakat</span>
            </div>
            <h2 id="surat-pembaca-heading" class="font-headline text-2xl sm:text-3xl font-black text-neutral-950">
              Surat Pembaca Terkurasi
            </h2>
            <p class="text-neutral-600 text-xs sm:text-sm mt-1">
              Aspirasi dan refleksi pembaca yang telah melalui proses kurasi dan penyuntingan oleh Dewan Redaksi ISF.
            </p>
          </div>

          <button
            type="button"
            @click="showSuratModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs sm:text-sm font-bold font-space-grotesk transition-colors shrink-0 shadow-xs cursor-pointer self-start sm:self-auto"
          >
            <IconMail class="w-4 h-4 text-isf-red" />
            <span>Tulis Surat Pembaca</span>
          </button>
        </div>

        <!-- Letters List -->
        <div v-if="suratPembacaList && suratPembacaList.length > 0" class="space-y-4">
          <article
            v-for="letter in suratPembacaList"
            :key="letter.id"
            class="border border-neutral-200 rounded-xl p-5 hover:border-neutral-400 transition-colors bg-neutral-50/60"
          >
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
              <div>
                <div class="flex items-center gap-2 text-[11px] font-mono text-neutral-500 mb-1">
                  <span class="font-bold text-neutral-800">{{ letter.sender_name }}</span>
                  <span>•</span>
                  <span class="flex items-center gap-1">
                    <IconMapPin class="w-3 h-3 text-neutral-400" />
                    {{ letter.sender_city }}
                  </span>
                  <span>•</span>
                  <span>{{ formatDate(letter.published_at) }}</span>
                </div>
                <h3 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 leading-snug">
                  {{ letter.title }}
                </h3>
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
                {{ expandedLetterId === letter.id ? 'Tutup Isi Lengkap' : 'Baca Surat Lengkap →' }}
              </button>
              <span class="text-[10px] font-mono text-neutral-400">
                Status: Terverifikasi
              </span>
            </div>
          </article>
        </div>

        <!-- Empty Letters State -->
        <div v-else class="text-center py-10 bg-neutral-50 rounded-xl border border-dashed border-neutral-300">
          <IconMail class="w-10 h-10 text-neutral-400 mx-auto mb-2" />
          <h4 class="font-bold text-neutral-800 text-sm font-space-grotesk">Belum Ada Surat Pembaca yang Ditayangkan</h4>
          <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
            Jadilah yang pertama mengirimkan pandangan dan aspirasi Anda untuk kebijakan publik Indonesia.
          </p>
          <button
            type="button"
            @click="showSuratModal = true"
            class="mt-4 px-4 py-2 rounded-lg bg-isf-red text-white text-xs font-bold font-space-grotesk hover:bg-red-700 transition-colors cursor-pointer"
          >
            Kirim Surat Sekarang
          </button>
        </div>
      </section>

      <!-- ═══ SECTION 5: DAFTAR ARTIKEL TERBARU + PAGINATION (Format Identik dengan Politik) ═══ -->
      <section
        v-if="!currentSubkategori || ['op-ed', 'editorial'].includes(currentSubkategori)"
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

        <!-- 2 Kolom Artikel Bergaris: responsive grid with strict line-clamp-2 for description -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2">
          <IsfLink
            v-for="item in articles"
            :key="item.slug"
            :to="item.to || `/artikel/${item.slug}`"
            :class="[
              linkClass,
              'group grid min-w-0 grid-cols-[6rem_minmax(0,1fr)] sm:grid-cols-[12rem_minmax(0,1fr)] lg:grid-cols-[10rem_minmax(0,1fr)] xl:grid-cols-[14rem_minmax(0,1fr)] items-start gap-3 sm:gap-5 border-b border-neutral-200 px-3 py-3.5 sm:px-4 sm:py-5 xl:px-[30px] lg:[&:nth-child(odd)]:border-r last:border-b-0 lg:last:border-b'
            ]"
          >
            <ImagePlaceholder
              :label="item.imageAlt || item.title"
              :src="item.imageUrl || item.thumbnail || item.cover_image || ''"
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
              <!-- Batasi deskripsi hanya 2 baris saja lalu beri elipsis (...) -->
              <p v-if="item.summary || item.excerpt" class="mt-1.5 block font-serif text-[13px] sm:text-[14px] leading-[1.45] text-neutral-600 line-clamp-2">
                {{ item.summary || item.excerpt }}
              </p>
              <div class="isf-byline mt-2 sm:mt-3 flex items-center gap-2 text-[11px] sm:text-xs">
                <time v-if="item.publishedAt" :datetime="item.publishedAt">
                  {{ formatDate(item.publishedAt) }}
                </time>
                <span v-if="item.readingMinutes" class="text-neutral-400">• {{ item.readingMinutes }} mnt</span>
                <span v-if="item.author || item.author_name" class="text-neutral-500 truncate max-w-[120px] sm:max-w-none">• {{ item.author || item.author_name }}</span>
              </div>
            </div>
          </IsfLink>
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

    <!-- ═══ MODAL KIRIM SURAT PEMBACA ═══════════════════════════════════════ -->
    <div
      v-if="showSuratModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs overflow-y-auto animate-fadeIn"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-surat-title"
    >
      <div class="relative w-full max-w-2xl my-8">
        <SuratPembacaForm
          :is-modal="true"
          @close="showSuratModal = false"
          @submitted="onSuratSubmitted"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
.animate-fadeIn {
  animation: fadeIn 0.2s ease-out forwards;
}
.isf-no-scrollbar::-webkit-scrollbar {
  display: none;
}
.isf-no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
