<script setup>
/**
 * HukumView.vue — Halaman Landing Rubrik Hukum ISF (/hukum/ dan sub-kategori).
 *
 * Mengimplementasikan:
 * - Kumpulan artikel hukum dengan headline utama, artikel terbaru, dan pagination.
 * - Sub-kategori: /hukum/regulasi/, /hukum/putusan/, /hukum/kasus/, /hukum/glosarium/.
 * - Widget Khusus 1: Glosarium Hukum interaktif (pencarian, filter A-Z, anchor link per istilah).
 * - Widget Khusus 2: Timeline Kasus Besar (alur kronologis visual peradilan & peristiwa hukum).
 * - Widget Khusus 3: Opini Pakar Hukum dengan profil akademisi/advokat (nama, afiliasi, foto).
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
  ArtikelSkeleton,
  ImagePlaceholder,
  IsfLink,
  GlosariumHukum,
  TimelineKasus,
  OpiniPakarCard,
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
    { slug: 'regulasi', name: 'Regulasi', description: 'UU, Perpres, Perda baru dan kepatuhan regulasi.' },
    { slug: 'putusan', name: 'Putusan', description: 'Analisis Putusan MK, MA, dan peradilan umum.' },
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

// Reset dan reload saat subkategori berganti
watch(
  () => route.params?.subkategori,
  () => {
    currentPage.value = 1
    load()
    if (route.params?.subkategori === 'glosarium') {
      setTimeout(() => {
        const el = document.getElementById('isf-glosarium-hukum-section')
        if (el) el.scrollIntoView({ behavior: 'smooth' })
      }, 300)
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-[#FBF9F4] text-neutral-900 antialiased pb-20">
    <!-- Header Hero Rubrik Hukum -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-4 select-none">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <RouterLink to="/hukum" :class="!currentSubkategori ? 'text-white font-bold' : 'hover:text-white transition-colors'">
            Hukum
          </RouterLink>
          <template v-if="activeSubkategoriName">
            <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
            <span class="text-white font-bold">{{ activeSubkategoriName }}</span>
          </template>
        </nav>

        <!-- Rubrik Title & Kicker -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-neutral-800/80 pb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5">
              <span class="inline-block w-2 h-2 bg-isf-red rounded-full"></span>
              <span>Rubrik Kajian Yuridis & Regulasi</span>
            </div>
            <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
              HUKUM
            </h1>
            <p class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-2xl leading-relaxed">
              {{ rubrik.description }}
            </p>
          </div>

          <!-- Quick Navigation Pill Tabs -->
          <div class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1 shrink-0">
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
            <button
              type="button"
              @click="navigateSubkategori('glosarium')"
              :class="[
                'px-3.5 py-1.5 rounded-full text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer border border-amber-400/40',
                currentSubkategori === 'glosarium'
                  ? 'bg-amber-500 text-neutral-900 shadow-xs'
                  : 'bg-amber-400/10 text-amber-300 hover:bg-amber-400/20'
              ]"
            >
              Glosarium A–Z
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12">
      <!-- Loading Skeleton -->
      <div v-if="status === 'loading' && !data" class="py-12">
        <ArtikelSkeleton variant="rubrik" />
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="py-12">
        <ErrorMessage
          message="Gagal memuat konten rubrik Hukum. Silakan periksa koneksi internet Anda atau muat ulang halaman."
          :retry="load"
        />
      </div>

      <!-- Loaded Content -->
      <div v-else class="space-y-12 sm:space-y-16">
        <!-- ========================================================================= -->
        <!-- SECTION 1: HEADLINE UTAMA HUKUM (Hanya tampil di page 1 tanpa filter subkategori atau subkategori utama) -->
        <!-- ========================================================================= -->
        <section
          v-if="headline && currentPage === 1 && currentSubkategori !== 'glosarium'"
          class="isf-full-layer-section isf-full-layer-section--accent rounded-2xl bg-white border border-neutral-200/90 shadow-xs overflow-hidden"
        >
          <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex items-center gap-2 mb-4">
              <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-full"></span>
              <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
                Sorotan Utama Rubrik Hukum
              </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
              <!-- Headline Image (Col 7) -->
              <div class="lg:col-span-7">
                <a :href="headline.to" class="block aspect-[16/9] sm:aspect-[16/10] rounded-xl overflow-hidden group relative bg-neutral-900">
                  <img
                    v-if="headline.imageUrl"
                    :src="headline.imageUrl"
                    :alt="headline.imageAlt || headline.title"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                  />
                  <ImagePlaceholder v-else />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </a>
              </div>

              <!-- Headline Details (Col 5) -->
              <div class="lg:col-span-5 flex flex-col justify-between">
                <div>
                  <div class="flex items-center gap-2 text-xs font-mono text-neutral-500 mb-2">
                    <span v-if="headline.subcategory" class="font-bold text-isf-red uppercase tracking-wider">
                      {{ headline.subcategory }}
                    </span>
                    <span v-else class="font-bold text-isf-red uppercase tracking-wider">
                      {{ headline.category }}
                    </span>
                    <span>•</span>
                    <span v-if="headline.readingMinutes" class="inline-flex items-center gap-1">
                      <IconClock class="w-3.5 h-3.5" />
                      {{ headline.readingMinutes }} mnt baca
                    </span>
                  </div>

                  <a :href="headline.to" class="group">
                    <h2 class="font-headline text-2xl sm:text-3xl lg:text-3xl xl:text-4xl font-bold tracking-tight leading-tight group-hover:text-isf-red transition-colors text-neutral-900">
                      {{ headline.title }}
                    </h2>
                  </a>

                  <p v-if="headline.summary" class="text-xs sm:text-sm text-neutral-600 mt-3 line-clamp-4 leading-relaxed font-space-grotesk">
                    {{ headline.summary }}
                  </p>
                </div>

                <div class="mt-6 pt-4 border-t border-neutral-200/80 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-neutral-200 flex items-center justify-center text-neutral-600 text-xs font-bold">
                      <IconUser class="w-3.5 h-3.5" />
                    </div>
                    <span class="text-xs font-semibold text-neutral-700 font-space-grotesk">
                      {{ headline.author }}
                    </span>
                  </div>

                  <a
                    :href="headline.to"
                    class="inline-flex items-center gap-1 text-xs font-bold text-isf-red hover:underline"
                  >
                    <span>Baca Analisis Lengkap</span>
                    <IconArrowRight class="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ========================================================================= -->
        <!-- SECTION 2: OPINI PAKAR HUKUM (Cream Card Frame) -->
        <!-- ========================================================================= -->
        <section
          v-if="opiniPakar && opiniPakar.length > 0 && currentSubkategori !== 'glosarium'"
          class="isf-full-layer-section isf-full-layer-section--cream rounded-2xl bg-[#FBF9F4] border border-neutral-300/80 shadow-xs p-4 sm:p-6 lg:p-8"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-neutral-300/70">
            <div>
              <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-wider uppercase text-isf-red mb-1">
                <IconScale class="w-4 h-4" />
                <span>Kolom Dewan Pakar</span>
              </div>
              <h3 class="font-headline text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                Opini & Analisis Pakar Hukum
              </h3>
            </div>
            <p class="text-xs sm:text-sm text-neutral-600 max-w-md font-space-grotesk sm:text-right">
              Kajian mendalam dari akademisi hukum, advokat senior, dan praktisi peradilan terkemuka.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <OpiniPakarCard
              v-for="op in opiniPakar"
              :key="op.id || op.slug"
              :opinion="op"
              :is-dark="false"
            />
          </div>
        </section>

        <!-- ========================================================================= -->
        <!-- SECTION 3: TIMELINE KASUS HUKUM BESAR (Dark Navy Frame) -->
        <!-- ========================================================================= -->
        <section
          v-if="timelineKasus && timelineKasus.length > 0 && currentSubkategori !== 'glosarium'"
          class="isf-full-layer-section isf-full-layer-section--navy rounded-2xl shadow-md overflow-hidden"
        >
          <TimelineKasus
            :timelines="timelineKasus"
            :is-dark="true"
          />
        </section>

        <!-- ========================================================================= -->
        <!-- SECTION 4: GLOSARIUM HUKUM INTERAKTIF (White Frame) -->
        <!-- ========================================================================= -->
        <section class="isf-full-layer-section isf-full-layer-section--cream rounded-2xl shadow-xs overflow-hidden">
          <GlosariumHukum
            :terms="glosarium"
            :is-dark="false"
          />
        </section>

        <!-- ========================================================================= -->
        <!-- SECTION 5: DAFTAR ARTIKEL & ANALISIS HUKUM (Daftar Berita Redaksional) -->
        <!-- ========================================================================= -->
        <section
          v-if="currentSubkategori !== 'glosarium'"
          class="rounded-2xl bg-white border border-neutral-200/90 shadow-xs p-4 sm:p-6 lg:p-8"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-neutral-200/80">
            <div>
              <div class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red mb-1">
                {{ activeSubkategoriName ? `Subkategori: ${activeSubkategoriName}` : 'Arsip Artikel' }}
              </div>
              <h3 class="font-headline text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                Analisis & Berita Hukum Terkini
              </h3>
            </div>
            <div class="text-xs font-mono text-neutral-500 font-semibold">
              Total {{ meta.total }} artikel terbit
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="articles.length === 0" class="py-16 text-center">
            <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-3 bg-neutral-100 text-neutral-400">
              <IconBook class="w-6 h-6" />
            </div>
            <p class="font-headline text-base font-bold text-neutral-900">
              Belum ada artikel dalam kategori ini
            </p>
            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
              Silakan periksa kembali subkategori lain atau kembali ke beranda utama ISF.
            </p>
            <button
              type="button"
              @click="navigateSubkategori(null)"
              class="mt-4 px-4 py-2 rounded-lg text-xs font-bold bg-isf-red text-white hover:bg-red-700 transition-colors cursor-pointer"
            >
              Lihat Semua Artikel Hukum
            </button>
          </div>

          <!-- Article Cards Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <article
              v-for="art in articles"
              :key="art.slug"
              class="flex flex-col justify-between rounded-xl border border-neutral-200/80 p-4 transition-all hover:border-neutral-300 hover:shadow-xs group bg-[#FBF9F4]/40"
            >
              <div>
                <!-- Thumbnail -->
                <a :href="art.to" class="block aspect-[16/10] rounded-lg overflow-hidden mb-3.5 bg-neutral-900">
                  <img
                    v-if="art.imageUrl"
                    :src="art.imageUrl"
                    :alt="art.imageAlt || art.title"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                  />
                  <ImagePlaceholder v-else />
                </a>

                <!-- Meta Kicker & Subcategory -->
                <div class="flex items-center gap-2 text-[11px] font-mono text-neutral-500 mb-1.5">
                  <span class="font-bold text-isf-red uppercase tracking-wider">
                    {{ art.subcategory || art.category }}
                  </span>
                  <span>•</span>
                  <span>{{ art.readingMinutes || 3 }} mnt</span>
                </div>

                <!-- Title -->
                <a :href="art.to" class="group">
                  <h4 class="font-headline text-base sm:text-lg font-bold leading-snug tracking-tight group-hover:text-isf-red transition-colors text-neutral-900 line-clamp-2">
                    {{ art.title }}
                  </h4>
                </a>

                <!-- Summary -->
                <p v-if="art.summary" class="text-xs text-neutral-600 mt-2 line-clamp-2 leading-relaxed font-space-grotesk">
                  {{ art.summary }}
                </p>
              </div>

              <!-- Card Footer -->
              <div class="mt-4 pt-3 border-t border-neutral-200/60 flex items-center justify-between text-xs text-neutral-500 font-space-grotesk">
                <span class="truncate max-w-[160px]">{{ art.author }}</span>
                <span>{{ formatDate(art.publishedAt) }}</span>
              </div>
            </article>
          </div>

          <!-- Pagination Bar -->
          <div v-if="meta.lastPage > 1" class="mt-8 pt-6 border-t border-neutral-200 flex items-center justify-center">
            <Pagination
              :current-page="meta.currentPage"
              :total-pages="meta.lastPage"
              @page-change="onPageChange"
            />
          </div>
        </section>
      </div>
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
