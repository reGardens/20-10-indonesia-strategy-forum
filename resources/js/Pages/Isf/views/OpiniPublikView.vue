<script setup>
/**
 * OpiniPublikView.vue — Halaman Landing Rubrik Opini Publik ISF (/opini-publik & sub-kategori).
 *
 * Mengimplementasikan:
 * - Subkategori: Op-Ed (tamu/akademisi), Editorial ISF (pandangan resmi redaksi), Surat Pembaca.
 * - Fitur Khusus:
 *   1. Reader Poll (Polling isu hangat dengan label tegas "non-ilmiah", 1 vote/device, live result).
 *   2. Kutipan Publik (Kutipan tajam tokoh & netizen dengan sumber, tanggal, dan link ke tokoh).
 *   3. Surat Pembaca (Daftar kontribusi yang telah disetujui redaksi + modal form kirim surat).
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
  IconBuilding,
  IconHelpCircle,
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
  ArtikelSkeleton,
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
import { getOpiniPublik, getSuratPembaca } from '@/api/services/isfPublicService'
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
  if (currentSubkategori.value === 'polling') return 'Reader Poll'
  if (currentSubkategori.value === 'kutipan') return 'Kutipan Publik'
  const found = subkategoriList.value.find(s => s.slug === currentSubkategori.value)
  return found?.name || currentSubkategori.value
})

const headline = computed(() => data.value?.headline || null)
const articles = computed(() => data.value?.articles || [])
const meta = computed(() => data.value?.meta || { currentPage: 1, lastPage: 1, total: 0, perPage: 9 })
const readerPoll = computed(() => data.value?.readerPoll || null)
const kutipanPublik = computed(() => data.value?.kutipanPublik || [])
const suratPembacaList = computed(() => data.value?.suratPembaca || [])

const filteredQuotes = computed(() => {
  const list = kutipanPublik.value || []
  if (kutipanFilter.value === 'tokoh') {
    return list.filter(q => q.tokoh_tipe === 'tokoh' || (!q.tokoh_tipe && q.tokoh_id))
  }
  if (kutipanFilter.value === 'netizen') {
    return list.filter(q => q.tokoh_tipe === 'netizen' || (!q.tokoh_id && q.tokoh_tipe !== 'tokoh'))
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
  // Reload data to reflect newly submitted letters (even if pending, to update counts if applicable)
  load()
}

// Reset dan reload saat subkategori berganti
watch(
  () => route.params?.subkategori,
  () => {
    currentPage.value = 1
    load()
    if (route.params?.subkategori === 'polling') {
      setTimeout(() => {
        const el = document.getElementById('isf-reader-poll-section')
        if (el) el.scrollIntoView({ behavior: 'smooth' })
      }, 300)
    } else if (route.params?.subkategori === 'kutipan') {
      setTimeout(() => {
        const el = document.getElementById('isf-kutipan-publik-section')
        if (el) el.scrollIntoView({ behavior: 'smooth' })
      }, 300)
    } else if (route.params?.subkategori === 'surat-pembaca') {
      setTimeout(() => {
        const el = document.getElementById('isf-surat-pembaca-section')
        if (el) el.scrollIntoView({ behavior: 'smooth' })
      }, 300)
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-[#FBF9F4] text-neutral-900 antialiased pb-20">
    <!-- Header Hero Rubrik Opini Publik -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-4 select-none">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <RouterLink to="/opini-publik" :class="!currentSubkategori ? 'text-white font-bold' : 'hover:text-white transition-colors'">
            Opini Publik
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
              <span>Rubrik Diskursus, Pemikiran & Suara Publik</span>
            </div>
            <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white">
              OPINI PUBLIK
            </h1>
            <p class="text-neutral-300 text-sm sm:text-base max-w-2xl mt-3 font-normal leading-relaxed">
              {{ rubrik.description }}
            </p>
          </div>

          <!-- CTAs: Kirim Surat Pembaca -->
          <div class="flex items-center gap-3 shrink-0">
            <button
              type="button"
              @click="showSuratModal = true"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm tracking-wide shadow-md transition-all cursor-pointer"
            >
              <IconMail class="w-4 h-4 shrink-0" />
              <span>Kirim Surat Pembaca</span>
            </button>
          </div>
        </div>

        <!-- Subkategori Navigation Bar / Pill Tabs -->
        <div class="mt-6 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none text-xs sm:text-sm">
          <button
            type="button"
            @click="navigateSubkategori(null)"
            :class="[
              'px-4 py-2 rounded-full font-semibold transition-all whitespace-nowrap cursor-pointer',
              !currentSubkategori
                ? 'bg-isf-red text-white shadow-sm'
                : 'bg-neutral-800/90 text-neutral-300 hover:bg-neutral-700 hover:text-white'
            ]"
          >
            Semua Opini
          </button>
          <button
            v-for="sub in subkategoriList"
            :key="sub.slug"
            type="button"
            @click="navigateSubkategori(sub.slug)"
            :class="[
              'px-4 py-2 rounded-full font-semibold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5',
              currentSubkategori === sub.slug
                ? 'bg-isf-red text-white shadow-sm'
                : 'bg-neutral-800/90 text-neutral-300 hover:bg-neutral-700 hover:text-white'
            ]"
          >
            <span v-if="sub.slug === 'editorial'" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            {{ sub.name }}
          </button>

          <!-- Quick Jumps -->
          <a
            href="#isf-reader-poll-section"
            class="px-4 py-2 rounded-full font-semibold bg-neutral-800/90 text-neutral-300 hover:bg-neutral-700 hover:text-white transition-all whitespace-nowrap flex items-center gap-1.5"
          >
            <IconFlame class="w-3.5 h-3.5 text-amber-400" />
            Reader Poll
          </a>
          <a
            href="#isf-kutipan-publik-section"
            class="px-4 py-2 rounded-full font-semibold bg-neutral-800/90 text-neutral-300 hover:bg-neutral-700 hover:text-white transition-all whitespace-nowrap flex items-center gap-1.5"
          >
            <IconQuote class="w-3.5 h-3.5 text-sky-400" />
            Kutipan Publik
          </a>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 pt-8 sm:pt-10">
      <!-- Loading State -->
      <div v-if="status === 'loading'" class="py-12">
        <ArtikelSkeleton />
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="py-12">
        <ErrorMessage
          title="Gagal Memuat Opini Publik"
          message="Terjadi kendala saat mengambil data artikel dan diskursus opini. Silakan periksa koneksi Anda."
          action-label="Coba Lagi"
          @retry="load"
        />
      </div>

      <!-- Loaded Content -->
      <div v-else class="space-y-12 sm:space-y-16">
        <!-- ═══ 1. HEADLINE OPINI UTAMA ══════════════════════════════════════ -->
        <section v-if="headline && currentPage === 1" aria-labelledby="headline-opini-title">
          <div
            :class="[
              'rounded-2xl border overflow-hidden transition-all duration-300 shadow-md',
              headline.is_editorial || headline.subcategory === 'editorial'
                ? 'bg-neutral-900 border-neutral-800 text-white'
                : 'bg-white border-neutral-300 text-neutral-900'
            ]"
          >
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">
              <!-- Thumbnail Cover -->
              <div class="lg:col-span-7 relative overflow-hidden bg-neutral-950 min-h-[260px] sm:min-h-[360px] lg:min-h-[420px]">
                <img
                  v-if="headline.thumbnail || headline.cover_image"
                  :src="headline.thumbnail || headline.cover_image"
                  :alt="headline.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                />
                <ImagePlaceholder v-else class="w-full h-full min-h-[300px]" />

                <!-- Badges over image -->
                <div class="absolute top-4 left-4 flex flex-wrap gap-2">
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
                    Op-Ed Tamu
                  </span>
                </div>
              </div>

              <!-- Content Column -->
              <div class="lg:col-span-5 p-6 sm:p-8 lg:p-10 flex flex-col justify-between">
                <div>
                  <!-- Subkategori & Date -->
                  <div class="flex items-center gap-3 text-xs font-mono text-neutral-400 mb-3">
                    <span class="text-isf-red font-bold uppercase tracking-wider">
                      {{ headline.subcategory_name || 'Opini Utama' }}
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                      <IconClock class="w-3.5 h-3.5 shrink-0" />
                      {{ formatDate(headline.published_at || headline.created_at) }}
                    </span>
                  </div>

                  <!-- Headline Title -->
                  <h2 id="headline-opini-title" class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight mb-4">
                    <RouterLink
                      :to="`/artikel/${headline.slug}`"
                      :class="[
                        'hover:text-isf-red transition-colors',
                        headline.is_editorial ? 'text-white' : 'text-neutral-950'
                      ]"
                    >
                      {{ headline.title }}
                    </RouterLink>
                  </h2>

                  <!-- Excerpt -->
                  <p
                    :class="[
                      'text-sm sm:text-base leading-relaxed line-clamp-3 mb-6 font-newsreader italic',
                      headline.is_editorial ? 'text-neutral-300' : 'text-neutral-700'
                    ]"
                  >
                    "{{ headline.excerpt || headline.ringkasan || headline.title }}"
                  </p>
                </div>

                <!-- Author Details & CTA Button -->
                <div class="pt-6 border-t border-neutral-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <!-- Author / Editorial Signature -->
                  <div class="flex items-center gap-3">
                    <div
                      :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs font-space-grotesk border',
                        headline.is_editorial
                          ? 'bg-isf-red/20 text-isf-red border-isf-red/40'
                          : 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                      ]"
                    >
                      {{ (headline.author_name || (headline.is_editorial ? 'ISF' : 'OP')).substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-bold font-space-grotesk">
                        {{ headline.author_name || (headline.is_editorial ? 'Dewan Redaksi ISF' : 'Penulis Tamu') }}
                      </div>
                      <div class="text-[11px] text-neutral-400 font-space-grotesk">
                        {{ headline.author_affiliation || (headline.is_editorial ? 'Sikap Kebijakan Redaksi' : 'Kontributor Independen') }}
                      </div>
                    </div>
                  </div>

                  <!-- CTA Link -->
                  <RouterLink
                    :to="`/artikel/${headline.slug}`"
                    class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold font-space-grotesk text-isf-red hover:underline shrink-0"
                  >
                    <span>Baca Selengkapnya</span>
                    <IconArrowRight class="w-4 h-4" />
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ═══ 2. FITUR KHUSUS: READER POLL (POLLING INTERAKTIF) ═══════════ -->
        <section
          v-if="readerPoll"
          id="isf-reader-poll-section"
          aria-labelledby="reader-poll-heading"
          class="scroll-mt-20"
        >
          <div class="mb-4">
            <SectionHeading
              id="reader-poll-heading"
              title="Reader Poll: Suara Publik"
              size="lg"
            >
              <template #eyebrow>Jajak Pendapat Terkini</template>
            </SectionHeading>
          </div>
          <div class="max-w-3xl mx-auto">
            <ReaderPollCard :poll="readerPoll" />
          </div>
        </section>

        <!-- ═══ 3. FITUR KHUSUS: KUTIPAN PUBLIK (TOKOH & NETIZEN) ════════════ -->
        <section
          v-if="kutipanPublik && kutipanPublik.length > 0"
          id="isf-kutipan-publik-section"
          aria-labelledby="kutipan-publik-heading"
          class="scroll-mt-20"
        >
          <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
            <SectionHeading
              id="kutipan-publik-heading"
              title="Kutipan Publik Terpilih"
              size="lg"
            >
              <template #eyebrow>Pernyataan Kunci & Resonansi Sosial</template>
            </SectionHeading>

            <!-- Filter Tokoh vs Netizen -->
            <div class="inline-flex items-center gap-1 bg-white border border-neutral-300 rounded-lg p-1 text-xs font-semibold shadow-xs">
              <button
                type="button"
                @click="kutipanFilter = 'all'"
                :class="[
                  'px-3 py-1 rounded transition-colors',
                  kutipanFilter === 'all' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Semua ({{ kutipanPublik.length }})
              </button>
              <button
                type="button"
                @click="kutipanFilter = 'tokoh'"
                :class="[
                  'px-3 py-1 rounded transition-colors',
                  kutipanFilter === 'tokoh' ? 'bg-amber-600 text-white' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Tokoh Publik
              </button>
              <button
                type="button"
                @click="kutipanFilter = 'netizen'"
                :class="[
                  'px-3 py-1 rounded transition-colors',
                  kutipanFilter === 'netizen' ? 'bg-sky-600 text-white' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Suara Warga
              </button>
            </div>
          </div>

          <!-- Quotes Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <KutipanPublikCard
              v-for="quote in filteredQuotes"
              :key="quote.id"
              :quote="quote"
              theme="dark"
            />
          </div>
        </section>

        <!-- ═══ 4. FITUR KHUSUS: SURAT PEMBACA TAYANG ═══════════════════════ -->
        <section
          id="isf-surat-pembaca-section"
          aria-labelledby="surat-pembaca-heading"
          class="bg-white rounded-2xl border border-neutral-300 p-6 sm:p-8 lg:p-10 shadow-xs scroll-mt-20"
        >
          <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-neutral-200 pb-6 mb-6">
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
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs sm:text-sm font-bold font-space-grotesk transition-colors shrink-0 shadow-xs"
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
              class="border border-neutral-200 rounded-xl p-5 hover:border-neutral-400 transition-colors bg-[#FBF9F4]/40"
            >
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
                <div>
                  <div class="flex items-center gap-2 text-[11px] font-mono text-neutral-500 mb-1">
                    <span class="font-bold text-neutral-800">{{ letter.nama_pengirim }}</span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                      <IconMapPin class="w-3 h-3 text-neutral-400" />
                      {{ letter.kota_domisili }}
                    </span>
                    <span>•</span>
                    <span>{{ formatDate(letter.created_at) }}</span>
                  </div>
                  <h3 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 leading-snug">
                    {{ letter.judul_surat }}
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
                  {{ letter.isi_surat }}
                </p>
                <p v-else class="line-clamp-3">
                  {{ letter.isi_surat }}
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
              class="mt-4 px-4 py-2 rounded-lg bg-isf-red text-white text-xs font-bold font-space-grotesk hover:bg-red-700 transition-colors"
            >
              Kirim Surat Sekarang
            </button>
          </div>
        </section>

        <!-- ═══ 5. ARSIP ARTIKEL OPINI & EDITORIAL DENGAN PAGINATION ═══════ -->
        <section aria-labelledby="arsip-opini-title">
          <div class="flex items-center justify-between border-b border-neutral-300 pb-4 mb-6">
            <SectionHeading
              id="arsip-opini-title"
              :title="activeSubkategoriName ? `Daftar Tulisan: ${activeSubkategoriName}` : 'Arsip Artikel & Opini'"
              size="lg"
            >
              <template #eyebrow>Diskursus Lengkap</template>
            </SectionHeading>

            <span class="text-xs font-mono text-neutral-500">
              Total {{ meta.total }} tulisan
            </span>
          </div>

          <!-- Grid of Articles -->
          <div v-if="articles && articles.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <article
              v-for="item in articles"
              :key="item.id"
              class="group bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
            >
              <div>
                <!-- Thumbnail -->
                <div class="relative overflow-hidden bg-neutral-900 aspect-16/10">
                  <img
                    v-if="item.thumbnail || item.cover_image"
                    :src="item.thumbnail || item.cover_image"
                    :alt="item.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <ImagePlaceholder v-else class="w-full h-full" />

                  <!-- Category / Editorial badge -->
                  <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                    <span
                      v-if="item.is_editorial || item.subcategory === 'editorial'"
                      class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-isf-red text-white shadow-xs"
                    >
                      Editorial
                    </span>
                    <span
                      v-else-if="item.subcategory_name"
                      class="px-2 py-0.5 rounded text-[10px] font-semibold bg-neutral-900/80 backdrop-blur-xs text-white"
                    >
                      {{ item.subcategory_name }}
                    </span>
                  </div>
                </div>

                <!-- Body -->
                <div class="p-5">
                  <!-- Meta Date -->
                  <div class="flex items-center gap-2 text-[11px] font-mono text-neutral-500 mb-2">
                    <IconClock class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ formatDate(item.published_at || item.created_at) }}</span>
                  </div>

                  <!-- Title -->
                  <h3 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 group-hover:text-isf-red transition-colors leading-snug line-clamp-2 mb-2">
                    <RouterLink :to="`/artikel/${item.slug}`">
                      {{ item.title }}
                    </RouterLink>
                  </h3>

                  <!-- Excerpt -->
                  <p class="text-xs sm:text-sm text-neutral-600 line-clamp-2 leading-relaxed">
                    {{ item.excerpt || item.ringkasan || item.title }}
                  </p>
                </div>
              </div>

              <!-- Footer with Author / Affiliation -->
              <div class="p-5 pt-0 border-t border-neutral-100 flex items-center justify-between text-xs mt-3">
                <div class="min-w-0 flex-1">
                  <span class="block font-bold text-neutral-900 truncate">
                    {{ item.author_name || (item.is_editorial ? 'Dewan Redaksi ISF' : 'Penulis Op-Ed') }}
                  </span>
                  <span v-if="item.author_affiliation" class="block text-[11px] text-neutral-500 truncate">
                    {{ item.author_affiliation }}
                  </span>
                </div>
                <RouterLink
                  :to="`/artikel/${item.slug}`"
                  class="text-isf-red font-bold hover:underline shrink-0 ml-2"
                  aria-label="Baca tulisan"
                >
                  Baca →
                </RouterLink>
              </div>
            </article>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-16 bg-white rounded-xl border border-neutral-200">
            <IconFileText class="w-12 h-12 text-neutral-400 mx-auto mb-3" />
            <h4 class="font-bold text-neutral-800 text-base font-space-grotesk">Belum Ada Tulisan untuk Kategori Ini</h4>
            <p class="text-xs text-neutral-500 mt-1">
              Silakan periksa kategori lain atau kembali ke daftar seluruh opini publik.
            </p>
            <button
              type="button"
              @click="navigateSubkategori(null)"
              class="mt-4 px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition-colors"
            >
              Lihat Semua Opini
            </button>
          </div>

          <!-- Pagination -->
          <div v-if="meta.lastPage > 1" class="mt-8 flex justify-center">
            <Pagination
              :current-page="meta.currentPage"
              :last-page="meta.lastPage"
              @change="onPageChange"
            />
          </div>
        </section>
      </div>
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
.aspect-16\/10 {
  aspect-ratio: 16 / 10;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

