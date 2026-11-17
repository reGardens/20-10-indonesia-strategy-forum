<script setup>
/**
 * KebijakanPublikView.vue — Halaman Landing Rubrik Kebijakan Publik ISF (/kebijakan-publik dan sub-kategori sektoral).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik:
 *   Breadcrumb responsif, kicker badge, serif heading, dan quick pill tabs.
 * - Subkategori sektoral: Ekonomi & Fiskal, Kesehatan, Pendidikan, Pertahanan, Lingkungan, Policy Brief, Monitoring Janji.
 * - Widget 1: Policy Brief (ringkasan eksekutif, rekomendasi kunci kebijakan, unduh PDF resmi).
 *   Tinggi kartu bersifat independen (self-start) sehingga saat ringkasan 1 kartu dibuka, kartu lain tidak ikut memanjang.
 * - Widget 2: Data & Statistik Kebijakan (grafik interaktif APBN / BPS / data sektoral berbasis SimpleBarChart).
 * - Widget 3: Monitoring Kebijakan (pelacakan program pemerintah: Janji vs Realisasi, progres %, status, sumber) dengan FilterSelect terpadu.
 * - Section 5: Arsip Artikel (Analisis & Berita Kebijakan Terkini) dengan layout 2-kolom bergaris persis seperti Politik, deskripsi line-clamp-2 + elipsis, dan tampil di semua filter.
 * - Skeleton loader desktop dan mobile yang presisi mencerminkan struktur konten.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IconClock,
  IconChevronRight,
  IconAlertCircle,
  IconExternalLink,
} from '@tabler/icons-vue'
import {
  ImagePlaceholder,
  IsfLink,
  PolicyBriefCard,
  MonitoringKebijakan,
  SimpleBarChart,
  SectionHeading,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getKebijakanPublik } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const linkClass =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2'

// Subkategori dari URL parameter (ekonomi, kesehatan, pendidikan, pertahanan, lingkungan, policy-brief, monitoring)
const currentSubkategori = computed(() => {
  return route.params.subkategori || null
})

const currentPage = ref(1)

// Fetch data rubrik kebijakan publik
const { data, status, load } = useIsfContent(() => {
  const param = ['policy-brief', 'monitoring', 'data-statistik', 'isf-insight', 'database-peraturan'].includes(currentSubkategori.value)
    ? null
    : currentSubkategori.value
  return getKebijakanPublik(param, currentPage.value, 8)
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Kebijakan Publik',
  label: 'Kebijakan Publik',
  description: 'Analisis sektoral & kajian strategis kebijakan pemerintah, evaluasi regulasi, data fiskal APBN, dan pelacakan program nasional.',
  subkategori: [
    { slug: 'ekonomi', name: 'Ekonomi & Fiskal', description: 'Kebijakan fiskal, APBN, moneter, inflasi, dan stabilitas makroekonomi.' },
    { slug: 'kesehatan', name: 'Kesehatan', description: 'Sistem kesehatan nasional, BPJS, ketahanan farmasi, dan layanan medis.' },
    { slug: 'pendidikan', name: 'Pendidikan', description: 'Kurikulum, anggaran pendidikan 20%, riset teknologi, dan SDM.' },
    { slug: 'pertahanan', name: 'Pertahanan', description: 'Modernisasi alutsista, ketahanan siber, diplomasi pertahanan, dan perbatasan.' },
    { slug: 'lingkungan', name: 'Lingkungan', description: 'Transisi energi terbarukan, dekarbonisasi industri, kehutanan, dan tata kelola iklim.' },
  ]
})

const subkategoriList = computed(() => rubrik.value?.subkategori || [])

const activeSubkategoriName = computed(() => {
  if (!currentSubkategori.value) return null
  if (currentSubkategori.value === 'policy-brief') return 'Policy Brief'
  if (currentSubkategori.value === 'monitoring') return 'Monitoring Kebijakan'
  if (currentSubkategori.value === 'data-statistik' || currentSubkategori.value === 'isf-insight') return 'ISF Insight (Grafik)'
  if (currentSubkategori.value === 'database-peraturan') return 'Database Peraturan'
  const found = subkategoriList.value.find(s => s.slug === currentSubkategori.value)
  return found?.name || currentSubkategori.value
})

const headline = computed(() => data.value?.headline || null)
const articles = computed(() => data.value?.articles || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 8 })
const policyBriefs = computed(() => data.value?.policyBriefs || [])
const dataGrafik = computed(() => data.value?.dataGrafik || [])
const databasePeraturans = computed(() => data.value?.databasePeraturans || [])
const monitoringData = computed(() => data.value?.monitoringKebijakan || [])

function navigateSubkategori(slug) {
  currentPage.value = 1
  if (!slug) {
    router.push({ path: '/kebijakan-publik' })
  } else {
    router.push({ path: `/kebijakan-publik/${slug}` })
  }
}

function onPageChange(page) {
  currentPage.value = page
  load()
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
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
</script>

<template>
  <div class="isf-content-layer min-h-screen bg-white text-neutral-900 pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK (NAVY EDITORIAL) ═══════════════════════════ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-6 sm:pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink
            to="/kebijakan-publik"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              !currentSubkategori ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Kebijakan Publik
          </RouterLink>
          <template v-if="activeSubkategoriName">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-white font-bold whitespace-nowrap shrink-0">{{ activeSubkategoriName }}</span>
          </template>
        </nav>

        <!-- Rubrik Title & Deskripsi + Filter Pills -->
        <div>
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>RUBRIK ANALISIS KEBIJAKAN & REGULASI PUBLIK</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
            KEBIJAKAN PUBLIK
          </h1>
          <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
            {{ rubrik.description }}
          </p>

          <!-- Filter Pills Kategori Kebijakan Publik (Di dalam hero header navy di bawah deskripsi) -->
          <div class="mt-6 flex items-center gap-2 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1" role="tablist" aria-label="Filter Subkategori Kebijakan Publik">
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
              @click="navigateSubkategori('policy-brief')"
              :class="[
                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer border',
                currentSubkategori === 'policy-brief'
                  ? 'bg-isf-red text-white border-isf-red shadow-xs'
                  : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
              ]"
            >
              Policy Brief
            </button>
            <button
              type="button"
              @click="navigateSubkategori('data-statistik')"
              :class="[
                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer border',
                currentSubkategori === 'data-statistik' || currentSubkategori === 'isf-insight'
                  ? 'bg-isf-red text-white border-isf-red shadow-xs'
                  : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
              ]"
            >
              ISF Insight (Grafik)
            </button>
            <button
              type="button"
              @click="navigateSubkategori('database-peraturan')"
              :class="[
                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer border',
                currentSubkategori === 'database-peraturan'
                  ? 'bg-isf-red text-white border-isf-red shadow-xs'
                  : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60'
              ]"
            >
              Database Peraturan
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan isi konten di Desktop & Mobile) ═══ -->
    <main v-if="status === 'loading' && !data" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat konten rubrik Kebijakan Publik">
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

      <!-- Section 2 Skeleton: Policy Brief -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-44 bg-neutral-300 rounded"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 sm:p-6">
          <div v-for="n in 3" :key="n" class="bg-white rounded-xl border border-neutral-200 p-5 space-y-3">
            <div class="h-5 w-24 bg-neutral-200 rounded-full"></div>
            <div class="h-6 w-full bg-neutral-300 rounded"></div>
            <div class="h-4 w-3/4 bg-neutral-200 rounded"></div>
            <div class="h-16 w-full bg-neutral-100 rounded"></div>
            <div class="h-9 w-full bg-neutral-200 rounded mt-4"></div>
          </div>
        </div>
      </div>

      <!-- Section 3 Skeleton: Monitoring Kebijakan Navy -->
      <div class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-800 pb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
            <div class="h-4 w-48 bg-neutral-700 rounded"></div>
          </div>
          <div class="h-8 w-36 bg-neutral-800 rounded-lg"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="n in 4" :key="n" class="bg-neutral-900 rounded-lg border border-neutral-800 p-4 space-y-3">
            <div class="h-4 w-28 bg-neutral-800 rounded"></div>
            <div class="h-5 w-full bg-neutral-700 rounded"></div>
            <div class="h-2 w-full bg-neutral-800 rounded-full"></div>
          </div>
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
        message="Gagal memuat konten rubrik Kebijakan Publik. Periksa koneksi Anda lalu coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: SOROTAN UTAMA (HEADLINE) ═════════════════════════ -->
      <section
        v-if="headline && (!currentSubkategori || ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'].includes(currentSubkategori))"
        aria-labelledby="headline-kebijakan"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="headline-kebijakan"
            title="Sorotan Kebijakan Publik"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Analisis Strategis Kebijakan</template>
          </SectionHeading>
        </div>

        <IsfLink
          :to="headline.to"
          :class="[
            linkClass,
            'group grid grid-cols-1 gap-4 py-4 sm:gap-6 sm:py-5 lg:grid-cols-12 lg:gap-8 lg:py-6',
            sectionPad
          ]"
        >
          <ImagePlaceholder
            :label="headline.imageAlt || headline.title"
            :src="headline.imageUrl || ''"
            aspect="news"
            priority
            class="lg:col-span-7"
          />
          <div class="min-w-0 lg:col-span-5 flex flex-col justify-between">
            <div>
              <span class="isf-kicker text-isf-red font-bold text-[11px] sm:text-xs">
                {{ headline.category }}{{ headline.subcategory ? ` · ${headline.subcategory}` : '' }}
              </span>
              <h2 class="isf-headline mt-1.5 block text-[20px] sm:text-[24px] md:text-[28px] lg:text-[30px] leading-[1.18] group-hover:text-isf-red transition-colors">
                {{ headline.title }}
              </h2>
              <p v-if="headline.summary" class="mt-2 sm:mt-3 block font-serif text-[14px] sm:text-[16px] leading-[1.5] text-neutral-600 line-clamp-3 sm:line-clamp-5">
                {{ headline.summary }}
              </p>
            </div>

            <div class="isf-byline mt-3 sm:mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 pt-3 border-t border-neutral-200 text-[11px] sm:text-xs">
              <strong>{{ headline.author }}</strong>
              <time v-if="headline.publishedAt" :datetime="headline.publishedAt">
                {{ formatDate(headline.publishedAt) }}
              </time>
              <span v-if="headline.readingMinutes" class="inline-flex items-center gap-1 text-neutral-500">
                <IconClock class="h-3.5 w-3.5" aria-hidden="true" />
                {{ headline.readingMinutes }} menit baca
              </span>
            </div>
          </div>
        </IsfLink>
      </section>

      <!-- ═══ SECTION 2: POLICY BRIEF & REKOMENDASI KEBIJAKAN ═════ -->
      <section
        id="isf-policy-brief-section"
        v-if="policyBriefs && policyBriefs.length > 0 && (!currentSubkategori || currentSubkategori === 'policy-brief')"
        aria-labelledby="policy-brief-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="policy-brief-title"
            title="Policy Brief & Rekomendasi Kebijakan"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Risalah Riset & Dokumen Resmi</template>
          </SectionHeading>
        </div>

        <!-- Grid dengan items-start agar saat 1 kartu dibuka, kartu lain tidak ikut memanjang -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start" :class="[sectionPad, 'py-5 sm:py-7']">
          <PolicyBriefCard
            v-for="brief in policyBriefs"
            :key="brief.id || brief.slug"
            :brief="brief"
            :is-dark="false"
          />
        </div>
      </section>

      <!-- ═══ SECTION 3: ISF INSIGHT (GRAFIK) ═════════════ -->
      <section
        v-if="dataGrafik && dataGrafik.length > 0 && (!currentSubkategori || currentSubkategori === 'data-statistik' || currentSubkategori === 'isf-insight')"
        aria-labelledby="data-grafik-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="data-grafik-title"
            title="ISF Insight (Grafik)"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Data & Statistik Kebijakan Strategis</template>
          </SectionHeading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" :class="[sectionPad, 'py-5 sm:py-7']">
          <div
            v-for="chart in dataGrafik"
            :key="chart.id"
            class="flex flex-col justify-between p-4 rounded-xl border border-neutral-200/90 bg-white hover:border-neutral-300 shadow-2xs hover:shadow-xs transition"
          >
            <SimpleBarChart
              :title="chart.title"
              :description="chart.description"
              :data="chart.data || []"
              :unit="chart.unit || ''"
              :source-title="chart.source_title || 'Kemenkeu / BPS RI'"
              :source-url="chart.source_url || '#'"
              :compact="true"
            />
            <div v-if="chart.author_name" class="mt-3 pt-2 border-t border-neutral-100 text-[11px] text-neutral-500 font-mono">
              Analis: {{ chart.author_name }} {{ chart.author_role ? `(${chart.author_role})` : '' }}
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 5: ARSIP ARTIKEL TERBARU + PAGINATION ═ -->
      <section
        v-if="!currentSubkategori || ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'].includes(currentSubkategori)"
        aria-labelledby="daftar-artikel-kebijakan"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="daftar-artikel-kebijakan"
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

        <!-- 2 Kolom Artikel Bergaris: responsive grid with strict line-clamp-2 for description (Persis seperti Politik) -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2">
          <IsfLink
            v-for="item in articles"
            :key="item.slug"
            :to="item.to"
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
              <!-- Batasi deskripsi hanya 2 baris saja lalu beri elipsis (...) -->
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

        <!-- Pagination -->
        <div v-if="meta.lastPage > 1" :class="['py-5 sm:py-6 flex justify-center border-t border-neutral-200', sectionPad]">
          <Pagination
            :current-page="meta.currentPage"
            :total-pages="meta.lastPage"
            @update:current-page="onPageChange"
          />
        </div>
      </section>

      <!-- ═══ SECTION AKHIR: TENTANG DATABASE PERATURAN, DISCLAIMER & LINK TERKAIT (SUMBER PERATURAN.BPK.GO.ID) ═══ -->
      <section
        v-if="!currentSubkategori || currentSubkategori === 'database-peraturan'"
        aria-labelledby="database-peraturan-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="database-peraturan-title"
            title="Database Peraturan & Rekomendasi Kebijakan"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Rujukan Resmi Regulasi & Dokumen Publik</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-6 sm:py-8 space-y-6']">
          <!-- Tentang Database Peraturan -->
          <div class="space-y-2">
            <h4 class="font-space-grotesk text-sm sm:text-base font-bold text-neutral-900 flex items-center gap-2">
              <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
              <span>Tentang Database Peraturan & Risalah Kebijakan ISF</span>
            </h4>
            <p class="text-xs sm:text-sm font-serif text-neutral-700 leading-relaxed max-w-4xl">
              Basis data peraturan dan rekomendasi kebijakan Indonesia Strategy Forum (ISF) menghimpun dokumen perundang-undangan, policy brief strategis, serta analisis dampak kebijakan publik. Repositori ini dirancang untuk memudahkan para pengambil kebijakan, akademisi, dan publik dalam mengakses rujukan hukum yang valid, akurat, dan terstruktur.
            </p>
          </div>

          <!-- Disclaimer Resmi -->
          <div class="rounded-xl p-4 sm:p-5 bg-amber-50/70 border border-amber-200/80 space-y-2">
            <div class="flex items-center gap-2 text-xs font-mono font-bold uppercase tracking-wider text-amber-900">
              <IconAlertCircle class="w-4 h-4 text-amber-700 shrink-0" />
              <span>Disclaimer Resmi & Batasan Tanggung Jawab</span>
            </div>
            <p class="text-xs text-amber-950 font-serif leading-relaxed">
              Seluruh naskah peraturan perundang-undangan dan dokumen kebijakan publik dalam platform ini bersumber dari portal resmi JDIH Badan Pemeriksa Keuangan Republik Indonesia (<a href="https://peraturan.bpk.go.id" target="_blank" rel="noopener noreferrer" class="underline font-bold text-amber-900 hover:text-amber-700">peraturan.bpk.go.id</a>), JDIHN Nasional, serta kementerian/lembaga negara terkait. Data disediakan untuk keperluan riset, transparansi publik, dan analisis independen non-komersial. Naskah yang berkekuatan hukum tetap adalah dokumen autentik yang termuat dalam Lembaran Negara Republik Indonesia dan Berita Negara Republik Indonesia.
            </p>
          </div>

          <!-- Tautan Rujukan Resmi -->
          <div class="pt-2 border-t border-neutral-200/80">
            <h5 class="text-xs font-space-grotesk font-bold uppercase tracking-wider text-neutral-500 mb-3">
              Tautan Terkait & Sumber Resmi:
            </h5>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <a
                href="https://peraturan.bpk.go.id"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center justify-between p-3 rounded-lg border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 hover:border-neutral-300 transition-all text-xs"
              >
                <div class="min-w-0 pr-2">
                  <div class="font-bold text-neutral-900 group-hover:text-isf-red transition-colors truncate">
                    JDIH BPK RI
                  </div>
                  <div class="text-[11px] text-neutral-500 truncate">peraturan.bpk.go.id</div>
                </div>
                <IconExternalLink class="w-4 h-4 text-neutral-400 group-hover:text-isf-red shrink-0" />
              </a>

              <a
                href="https://jdihn.go.id"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center justify-between p-3 rounded-lg border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 hover:border-neutral-300 transition-all text-xs"
              >
                <div class="min-w-0 pr-2">
                  <div class="font-bold text-neutral-900 group-hover:text-isf-red transition-colors truncate">
                    JDIHN Nasional
                  </div>
                  <div class="text-[11px] text-neutral-500 truncate">jdihn.go.id</div>
                </div>
                <IconExternalLink class="w-4 h-4 text-neutral-400 group-hover:text-isf-red shrink-0" />
              </a>

              <a
                href="https://setkab.go.id"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center justify-between p-3 rounded-lg border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 hover:border-neutral-300 transition-all text-xs"
              >
                <div class="min-w-0 pr-2">
                  <div class="font-bold text-neutral-900 group-hover:text-isf-red transition-colors truncate">
                    Sekretariat Kabinet RI
                  </div>
                  <div class="text-[11px] text-neutral-500 truncate">setkab.go.id</div>
                </div>
                <IconExternalLink class="w-4 h-4 text-neutral-400 group-hover:text-isf-red shrink-0" />
              </a>
            </div>
          </div>
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
