<script setup>
/**
 * PolitikView.vue — Halaman Landing Rubrik Politik ISF (/politik/ dan sub-kategori).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Hukum:
 *   Breadcrumb, kicker badge, serif heading, dan quick pill tabs.
 * - Kumpulan artikel politik dengan headline utama, artikel terbaru (line-clamp-2 dengan elipsis), dan pagination.
 * - Sub-kategori: /politik/pemilu/, /politik/parlemen/, /politik/pemerintahan/, /politik/partai/.
 * - Fallback cerdas: seluruh filter subkategori tetap memunculkan artikel terbaru sehingga tidak kosong.
 * - Widget Khusus 1: Infografis Interaktif Peta Kursi Parlemen dengan chart model simpel & label Dinamika Legislasi.
 * - Widget Khusus 2: Profil Tokoh Politik pilihan CMS dengan navigasi aktif ke rubrik Sosok (/sosok/:slug).
 * - Skeleton loader desktop dan mobile yang 100% presisi mencerminkan struktur konten.
 * - Identitas visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif editorial headline, Space Grotesk UI.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IconArrowLeft,
  IconClock,
  IconUser,
  IconArrowRight,
  IconChevronRight,
  IconBuildingBank
} from '@tabler/icons-vue'
import {
  ImagePlaceholder,
  IsfLink,
  PetaKursiParlemen,
  SectionHeading,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getPolitik } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const linkClass =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2'

// Subkategori dari URL parameter (jika ada)
const currentSubkategori = computed(() => {
  return route.params.subkategori || null
})

const currentPage = ref(1)

// Fetch data rubrik politik
const { data, status, load } = useIsfContent(() => {
  return getPolitik(currentSubkategori.value, currentPage.value, 9)
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Politik',
  label: 'Politik',
  description: 'Dinamika politik nasional, parlemen, partai, pemilu, dan lembaga negara.',
  subkategori: [
    { slug: 'pemilu', name: 'Pemilu & Pilkada', description: 'Isu dinamika Pemilu, Pilpres, Pilkada, dan kepemiluan.' },
    { slug: 'parlemen', name: 'DPR/DPD/MPR', description: 'Kinerja legislasi, rapat komisi, dan dinamika parlemen.' },
    { slug: 'pemerintahan', name: 'Eksekutif', description: 'Kebijakan kementerian, kabinet, dan lembaga eksekutif.' },
    { slug: 'partai', name: 'Dinamika Partai', description: 'Perkembangan internal, suksesi, dan peta koalisi partai politik.' },
  ]
})

const subkategoriList = computed(() => rubrik.value?.subkategori || [])
const activeSubkategoriName = computed(() => {
  if (!currentSubkategori.value) return null
  const found = subkategoriList.value.find(s => s.slug === currentSubkategori.value)
  return found?.name || currentSubkategori.value
})

const headline = computed(() => data.value?.headline || null)
const articles = computed(() => data.value?.articles || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })
const petaKursi = computed(() => data.value?.petaKursi || {})
const tokohPolitik = computed(() => data.value?.tokohPolitik || [])

function navigateSubkategori(slug) {
  currentPage.value = 1
  if (!slug) {
    router.push({ path: '/politik' })
  } else {
    router.push({ path: `/politik/${slug}` })
  }
}

function onPageChange(page) {
  currentPage.value = page
  load()
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Reset dan reload saat subkategori berganti
watch(
  () => route.params.subkategori,
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
      const pageTitle = subName ? `${subName} · Politik · Indonesia Strategy Forum` : `${title || 'Politik'} · Indonesia Strategy Forum`
      document.title = pageTitle
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-[#FBF9F4] text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO RUBRIK POLITIK (Gaya Editorial Navy identik dengan Hukum) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-6 sm:pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0">
          <router-link to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</router-link>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <router-link
            to="/politik"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              !currentSubkategori ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Politik
          </router-link>
          <template v-if="activeSubkategoriName">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
            <span class="text-white font-bold whitespace-nowrap shrink-0">{{ activeSubkategoriName }}</span>
          </template>
        </nav>

        <!-- Rubrik Title & Quick Pill Tabs (di bawah Deskripsi, tanpa divider ganda/gap kosong) -->
        <div class="space-y-4">
          <div>
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
              <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
              <span>RUBRIK DINAMIKA POLITIK & PARLEMEN</span>
            </div>
            <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
              POLITIK
            </h1>
            <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
              {{ rubrik.description }}
            </p>
          </div>

          <!-- Quick Navigation Pill Tabs di Bawah Deskripsi -->
          <div class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1" role="tablist" aria-label="Filter Subkategori Politik">
            <button
              type="button"
              @click="navigateSubkategori(null)"
              :class="[
                'px-3.5 py-1.5 rounded-full text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                !currentSubkategori
                  ? 'bg-isf-red text-white shadow-xs'
                  : 'bg-white/10 text-neutral-300 hover:bg-white/20 hover:text-white'
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
                'px-3.5 py-1.5 rounded-full text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                currentSubkategori === sub.slug
                  ? 'bg-isf-red text-white shadow-xs'
                  : 'bg-white/10 text-neutral-300 hover:bg-white/20 hover:text-white'
              ]"
            >
              {{ sub.name }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan isi konten di Desktop & Mobile) ═══ -->
    <main v-if="status === 'loading' && !data" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat konten rubrik Politik">
      <!-- Section 1 Skeleton: Headline -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-36 bg-neutral-200 rounded"></div>
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

      <!-- Section 2 Skeleton: Peta Kursi Parlemen Navy -->
      <div class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-800 pb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
            <div class="h-4 w-44 bg-neutral-700 rounded"></div>
          </div>
          <div class="h-8 w-36 bg-neutral-800 rounded-lg"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
          <div class="lg:col-span-6 flex items-center justify-center">
            <div class="w-56 h-56 sm:w-64 sm:h-64 rounded-full border-8 border-neutral-800 flex items-center justify-center">
              <div class="w-32 h-32 rounded-full bg-neutral-900 border border-neutral-800"></div>
            </div>
          </div>
          <div class="lg:col-span-6 space-y-2">
            <div v-for="n in 6" :key="n" class="h-10 w-full bg-neutral-900 rounded-lg border border-neutral-800"></div>
          </div>
        </div>
      </div>

      <!-- Section 3 Skeleton: Profil Tokoh Politik Cream -->
      <div class="bg-[#FBF9F4] rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-300 bg-white/60 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-40 bg-neutral-300 rounded"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200">
          <div v-for="n in 4" :key="n" class="p-4 sm:p-5 space-y-3">
            <div class="aspect-square w-full rounded-md bg-neutral-200"></div>
            <div class="h-3 w-20 bg-neutral-200 rounded"></div>
            <div class="h-4 w-3/4 bg-neutral-300 rounded"></div>
            <div class="h-3 w-1/2 bg-neutral-200 rounded"></div>
            <div class="h-3 w-full bg-neutral-200 rounded"></div>
            <div class="pt-3 border-t border-neutral-200 h-4 w-28 bg-neutral-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Section 4 Skeleton: Artikel Terbaru Grid -->
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
        message="Gagal memuat konten rubrik Politik. Periksa koneksi Anda lalu coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: SOROTAN UTAMA (HEADLINE) ═════════════════════════ -->
      <section
        v-if="headline"
        aria-labelledby="headline-politik"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="headline-politik"
            title="Sorotan Politik"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Laporan Khusus</template>
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

      <!-- ═══ SECTION 2: WIDGET PETA KURSI PARLEMEN (NAVY) ═════════════════ -->
      <section
        v-if="petaKursi?.items?.length"
        aria-labelledby="peta-kursi-parlemen-title"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
      >
        <div class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
              Data Dinamika Legislasi
            </span>
            <span class="text-neutral-600">•</span>
            <h2 id="peta-kursi-parlemen-title" class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider">
              Peta Kursi Parlemen
            </h2>
          </div>
          <span class="text-[11px] font-mono text-neutral-400">
            Sumber: KPU & Sekretariat Parlemen RI
          </span>
        </div>

        <div :class="[sectionPad, 'py-3 sm:py-5']">
          <PetaKursiParlemen :initial-data="petaKursi" />
        </div>
      </section>

      <!-- ═══ SECTION 3: WIDGET PROFIL TOKOH POLITIK (CREAM) ═══════════════ -->
      <section
        v-if="tokohPolitik.length > 0"
        aria-labelledby="tokoh-politik-title"
        class="bg-[#FBF9F4] rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-300 bg-white/60">
          <SectionHeading
            id="tokoh-politik-title"
            title="Profil Tokoh Politik"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Aktor & Pembuat Kebijakan</template>
          </SectionHeading>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200">
          <router-link
            v-for="tokoh in tokohPolitik"
            :key="tokoh.id"
            :to="tokoh.to || ('/sosok/' + tokoh.slug)"
            class="group p-3 sm:p-5 flex flex-col justify-between hover:bg-neutral-50/80 transition-colors block cursor-pointer"
          >
            <div>
              <!-- Responsive Avatar layout -->
              <div class="flex items-start gap-3 sm:block mb-2 sm:mb-3">
                <div class="w-16 h-16 sm:w-full sm:h-auto sm:aspect-square rounded-md overflow-hidden bg-neutral-200 shrink-0 border border-neutral-200 shadow-2xs group-hover:shadow-md transition-shadow">
                  <img
                    v-if="tokoh.photoUrl"
                    :src="tokoh.photoUrl"
                    :alt="tokoh.photoAlt || tokoh.name"
                    class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-neutral-100 text-neutral-400">
                    <IconUser class="w-8 h-8 sm:w-12 sm:h-12" />
                  </div>
                </div>

                <div class="min-w-0 flex-1 sm:mt-2">
                  <span class="text-[10px] font-space-grotesk font-bold uppercase tracking-wider text-isf-red block">
                    Tokoh Politik
                  </span>
                  <h3 class="font-space-grotesk font-bold text-sm sm:text-base text-neutral-900 mt-0.5 line-clamp-1 group-hover:text-isf-red transition-colors">
                    {{ tokoh.name }}
                  </h3>
                  <p class="text-xs text-neutral-500 font-medium line-clamp-1 mt-0.5">
                    {{ tokoh.position }}
                  </p>
                </div>
              </div>

              <p v-if="tokoh.teaser" class="text-xs font-serif text-neutral-600 line-clamp-2 sm:line-clamp-3 leading-relaxed">
                {{ tokoh.teaser }}
              </p>

              <!-- Mini Stats jika ada -->
              <div v-if="tokoh.stats?.length" class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-neutral-200 grid grid-cols-2 gap-2 text-left">
                <div v-for="(st, idx) in tokoh.stats.slice(0, 2)" :key="idx">
                  <div class="text-[10px] text-neutral-400 truncate">{{ st.label }}</div>
                  <div class="text-xs font-bold text-neutral-900 font-mono">{{ st.value }}</div>
                </div>
              </div>
            </div>

            <!-- Tautan profil yang mengaktifkan menu Sosok -->
            <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-neutral-200">
              <div
                class="inline-flex items-center gap-1.5 text-xs font-space-grotesk font-bold text-isf-red group-hover:text-isf-red-dark transition-colors"
              >
                <span>Lihat Profil Sosok</span>
                <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
              </div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- ═══ SECTION 4: DAFTAR ARTIKEL TERBARU + PAGINATION ═══════════════ -->
      <section
        aria-labelledby="daftar-artikel-politik"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="daftar-artikel-politik"
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
