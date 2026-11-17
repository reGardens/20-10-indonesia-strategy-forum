<script setup>
/**
 * HukumView.vue — Halaman Landing Rubrik Hukum ISF (/hukum/ dan sub-kategori).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik:
 *   Breadcrumb, kicker badge, serif heading, dan quick pill tabs.
 * - Kumpulan artikel hukum dengan headline utama, artikel terbaru (line-clamp-2 dengan elipsis), dan pagination.
 * - Sub-kategori: /hukum/regulasi/, /hukum/putusan/, /hukum/kasus/, /hukum/glosarium/.
 * - Tampil di semua filter: arsip artikel tetap dimunculkan di seluruh tab filter.
 * - Widget Khusus 1: Timeline Kasus Hukum Besar dengan alur vertikal minimalis bergaya shadcn UI.
 * - Widget Khusus 2: Opini Pakar Hukum dengan profil akademisi/advokat dalam frame cream serasi.
 * - Widget Khusus 3: Glosarium Hukum interaktif (pencarian, filter A-Z, anchor link per istilah).
 * - Skeleton loader desktop dan mobile yang presisi mencerminkan struktur konten.
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
  IconScale,
  IconBook,
} from '@tabler/icons-vue'
import {
  ImagePlaceholder,
  IsfLink,
  GlosariumHukum,
  TimelineKasus,
  SectionHeading,
} from '@/Components/Isf'
import Pagination from '@/Components/UI/Pagination.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getHukum } from '@/api/services/isfPublicService'
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

// Fetch data rubrik hukum
const { data, status, load } = useIsfContent(() => {
  return getHukum(currentSubkategori.value, currentPage.value, 9)
})

const rubrik = computed(() => data.value?.rubrik || {
  title: 'Hukum',
  label: 'Hukum',
  description: 'Analisis yuridis, putusan pengadilan, kepatuhan regulasi, dan kasus hukum nasional.',
  subkategori: [
    { slug: 'regulasi', name: 'Regulasi & Perundang-undangan', description: 'UU, Perpres, Perda baru dan kepatuhan regulasi.' },
    { slug: 'putusan', name: 'Putusan Peradilan', description: 'Analisis Putusan MK, MA, dan peradilan umum.' },
    { slug: 'kasus', name: 'Kasus Hukum', description: 'Kasus hukum besar, tipikor, dan proses peradilan.' },
  ]
})

const subkategoriList = computed(() => rubrik.value?.subkategori || [])
const activeSubkategoriName = computed(() => {
  if (!currentSubkategori.value) return null
  if (currentSubkategori.value === 'glosarium') return 'Glosarium Hukum'
  const found = subkategoriList.value.find(s => s.slug === currentSubkategori.value)
  return found?.name || currentSubkategori.value
})

const headline = computed(() => data.value?.headline || null)
const articles = computed(() => data.value?.articles || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })
const opiniPakar = computed(() => data.value?.opiniPakar || [])
const timelineKasus = computed(() => data.value?.timelineKasus || [])
const glosarium = computed(() => data.value?.glosarium || [])

function navigateSubkategori(slug) {
  currentPage.value = 1
  if (!slug) {
    router.push({ path: '/hukum' })
  } else {
    router.push({ path: `/hukum/${slug}` })
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
            to="/hukum"
            :class="[
              'whitespace-nowrap shrink-0 transition-colors',
              !currentSubkategori ? 'text-white font-bold' : 'hover:text-white'
            ]"
          >
            Hukum
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
            <span>RUBRIK KAJIAN YURIDIS & REGULASI</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
            HUKUM
          </h1>
          <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed">
            {{ rubrik.description }}
          </p>

          <!-- Filter Pills Kategori Hukum (Di dalam hero header navy di bawah deskripsi) -->
          <div class="mt-6 flex items-center gap-2 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1" role="tablist" aria-label="Filter Subkategori Hukum">
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
              @click="navigateSubkategori('glosarium')"
              :class="[
                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer border',
                currentSubkategori === 'glosarium'
                  ? 'bg-amber-500 text-neutral-900 border-amber-600 shadow-xs'
                  : 'bg-[#1a2333] text-amber-300/90 hover:bg-[#253247] hover:text-amber-200 border border-amber-500/40'
              ]"
            >
              Glosarium A–Z
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan isi konten di Desktop & Mobile) ═══ -->
    <main v-if="status === 'loading' && !data" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat konten rubrik Hukum">
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

      <!-- Section 2 Skeleton: Timeline Kasus Navy -->
      <div class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6">
        <div class="border-b border-neutral-800 pb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
            <div class="h-4 w-48 bg-neutral-700 rounded"></div>
          </div>
          <div class="h-3 w-32 bg-neutral-800 rounded"></div>
        </div>
        <div class="space-y-4 py-2">
          <div class="h-5 w-64 bg-neutral-800 rounded"></div>
          <div class="h-20 w-full bg-neutral-800/60 rounded"></div>
          <div class="h-16 w-full bg-neutral-800/40 rounded"></div>
        </div>
      </div>

      <!-- Section 3 Skeleton: Opini Pakar -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 p-4">
          <div class="h-4 w-44 bg-neutral-300 rounded"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
          <div v-for="i in 4" :key="i" class="space-y-3 p-3 bg-white/60 rounded border border-neutral-200">
            <div class="h-12 w-12 rounded-full bg-neutral-300"></div>
            <div class="h-4 w-3/4 bg-neutral-300 rounded"></div>
            <div class="h-3 w-1/2 bg-neutral-200 rounded"></div>
            <div class="h-3 w-full bg-neutral-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Section 5 Skeleton: Artikel Grid 2 Kolom -->
      <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
        <div class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4">
          <div class="h-4 w-32 bg-neutral-200 rounded"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2">
          <div v-for="i in 4" :key="i" class="p-4 border-b border-neutral-200 flex gap-4">
            <div class="w-24 sm:w-36 aspect-[4/3] bg-neutral-200 rounded shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-16 bg-neutral-200 rounded"></div>
              <div class="h-5 w-full bg-neutral-300 rounded"></div>
              <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
              <div class="h-3 w-1/3 bg-neutral-200 rounded mt-2"></div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Error state -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        message="Gagal memuat konten rubrik Hukum. Periksa koneksi Anda lalu coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: SOROTAN UTAMA (HEADLINE) ═════════════════════════ -->
      <section
        v-if="headline && (!currentSubkategori || ['regulasi', 'putusan'].includes(currentSubkategori))"
        aria-labelledby="headline-hukum"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="headline-hukum"
            title="Sorotan Hukum"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Laporan Khusus & Analisis Yuridis</template>
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

      <!-- ═══ SECTION 2: WIDGET TIMELINE KASUS HUKUM BESAR (NAVY) ═══════════ -->
      <section
        v-if="timelineKasus && timelineKasus.length > 0 && (!currentSubkategori || currentSubkategori === 'kasus')"
        aria-labelledby="timeline-kasus-title"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
      >
        <div class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
              Alur Peristiwa Yuridis
            </span>
            <span class="text-neutral-600">•</span>
            <h2 id="timeline-kasus-title" class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider">
              Timeline Kasus Hukum Besar
            </h2>
          </div>
          <span class="text-[11px] font-mono text-neutral-400">
            Perkara Peradilan & Peristiwa Konstitusi
          </span>
        </div>

        <div :class="[sectionPad, 'py-4 sm:py-6']">
          <TimelineKasus :timelines="timelineKasus" :is-dark="true" />
        </div>
      </section>

      <!-- ═══ SECTION 3: WIDGET OPINI PAKAR HUKUM ═══════════════════ -->
      <section
        v-if="opiniPakar && opiniPakar.length > 0 && !currentSubkategori"
        aria-labelledby="opini-pakar-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="opini-pakar-title"
            title="Opini & Analisis Pakar Hukum"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Kajian & Analisis Akademisi</template>
          </SectionHeading>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200">
          <router-link
            v-for="op in opiniPakar"
            :key="op.id || op.slug"
            :to="op.to || ('/artikel/hukum/' + op.slug)"
            class="group p-3 sm:p-5 flex flex-col justify-between hover:bg-neutral-50/80 transition-colors block cursor-pointer"
          >
            <div>
              <!-- Avatar & Pakar Info -->
              <div class="flex items-start gap-3 sm:block mb-2 sm:mb-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden bg-neutral-200 shrink-0 border border-neutral-200 shadow-2xs group-hover:ring-2 group-hover:ring-isf-red transition-all">
                  <img
                    v-if="op.expert_avatar"
                    :src="op.expert_avatar"
                    :alt="op.expert_name"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-neutral-100 text-neutral-400">
                    <IconUser class="w-8 h-8 sm:w-10 sm:h-10" />
                  </div>
                </div>

                <div class="min-w-0 flex-1 sm:mt-2.5">
                  <span class="text-[10px] font-space-grotesk font-bold uppercase tracking-wider text-isf-red block">
                    Dewan Pakar
                  </span>
                  <h4 class="font-space-grotesk font-bold text-sm sm:text-base text-neutral-900 mt-0.5 line-clamp-1 group-hover:text-isf-red transition-colors">
                    {{ op.expert_name }}
                  </h4>
                  <p class="text-xs text-neutral-500 font-medium line-clamp-1 mt-0.5">
                    {{ op.expert_title }}
                  </p>
                </div>
              </div>

              <!-- Judul Opini & Ringkasan -->
              <h5 class="font-headline text-sm sm:text-base font-bold text-neutral-900 mt-2 line-clamp-2 leading-snug group-hover:text-isf-red transition-colors">
                {{ op.title }}
              </h5>
              <p v-if="op.summary" class="text-xs font-serif text-neutral-600 line-clamp-2 sm:line-clamp-3 mt-1.5 leading-relaxed">
                {{ op.summary }}
              </p>
            </div>

            <!-- Tautan Baca Opini Lengkap -->
            <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-neutral-200">
              <div
                class="inline-flex items-center gap-1.5 text-xs font-space-grotesk font-bold text-isf-red group-hover:text-isf-red-dark transition-colors"
              >
                <span>Baca Opini Lengkap</span>
                <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
              </div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- ═══ SECTION 4: ARTIKEL TERBARU + PAGINATION ═ -->
      <section
        v-if="!currentSubkategori || ['regulasi', 'putusan'].includes(currentSubkategori)"
        aria-labelledby="daftar-artikel-hukum"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="daftar-artikel-hukum"
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

      <!-- ═══ SECTION 5: GLOSARIUM HUKUM INTERAKTIF (DI SECTION AKHIR DI BAWAH ARTIKEL TERBARU) ═══ -->
      <section
        id="isf-glosarium-hukum-section"
        v-if="!currentSubkategori || currentSubkategori === 'glosarium'"
        aria-labelledby="glosarium-title"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="glosarium-title"
            title="Glosarium Hukum"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Kamus Istilah & Terminologi A–Z</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-4 sm:py-6']">
          <GlosariumHukum
            :terms="glosarium"
            :is-dark="false"
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
