<script setup>
/**
 * SurveiDetailView.vue — Halaman Detail Analisis Survei Opini Publik Interaktif (/survei/:slug)
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik Politik:
 *   Breadcrumb responsif (whitespace-nowrap & truncate), red kicker badge, serif headline, institusi, dan action button.
 * - Seksi 1: Headline Statistic Temuan Kunci dengan border-t-4 border-t-isf-red.
 * - Seksi 2: Visualisasi Data Interaktif (SurveiChart) dengan pemilahan segmen dan tabel alternatif aksesibel.
 * - Seksi 3: Linimasa Alur & Metodologi Riset Ilmiah bergaya shadcn UI (TimelineMetodologi: 5 tahap terstruktur, hairline vertical track, node dot indicator).
 * - Seksi 4: Analisis Pakar & Implikasi Kebijakan Strategis dalam frame Navy khas ISF.
 * - Seksi 5: Callout Unduh Dokumen Laporan Lengkap (PDF).
 * - Seksi 6: Survei Terkait Lainnya dalam grid SurveiCard.
 * - Tata letak 100% responsif di mobile, tablet, dan desktop tanpa clipping.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconChevronRight,
  IconBuilding,
  IconCalendar,
  IconDownload,
  IconFileText,
  IconMapPin,
  IconAward,
  IconCheck,
  IconShare,
  IconArrowLeft,
  IconArrowRight,
} from '@tabler/icons-vue'
import {
  SectionHeading,
  SurveiCard,
  SurveiChart,
  TimelineMetodologi,
} from '@/Components/Isf'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSurveiDetail } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'

const slug = computed(() => route.params.slug)

// Fetch detail survei
const { data, status, load } = useIsfContent(() => {
  return getSurveiDetail(slug.value)
})

const survey = computed(() => data.value?.survey || null)
const relatedSurveys = computed(() => data.value?.relatedSurveys || [])

const topicLabel = computed(() => {
  const map = {
    politik: 'Politik',
    ekonomi: 'Ekonomi',
    sosial: 'Sosial',
    hukum: 'Hukum',
  }
  return map[survey.value?.topic?.toLowerCase()] || survey.value?.topic || 'Isu Nasional'
})

const regionLabel = computed(() => {
  const map = {
    nasional: 'Nasional',
    jawa: 'Pulau Jawa',
    sumatera: 'Sumatera',
    kalimantan: 'Kalimantan',
    sulawesi: 'Sulawesi',
    bali_nusra: 'Bali & Nusra',
    maluku_papua: 'Maluku & Papua',
  }
  return map[survey.value?.region?.toLowerCase()] || survey.value?.region || 'Nasional'
})

function shareUrl() {
  if (typeof navigator !== 'undefined' && navigator.share) {
    navigator.share({
      title: survey.value?.title,
      text: survey.value?.summary,
      url: window.location.href,
    }).catch(() => {})
  } else if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
    alert('Tautan survei berhasil disalin ke papan klip!')
  }
}

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

// Update document title
watch(
  () => survey.value?.title,
  (title) => {
    if (typeof document !== 'undefined') {
      document.title = title
        ? `${title} · Survei · Indonesia Strategy Forum`
        : 'Detail Survei · Indonesia Strategy Forum'
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ 1. HEADER HERO DETAIL SURVEI (Gaya Editorial Navy identik dengan Politik) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8 sm:pb-10">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 sm:mb-6 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink to="/survei" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Survei Opini Publik</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <span class="text-white font-bold truncate max-w-[180px] sm:max-w-md">{{ survey?.title || 'Detail Analisis Survei' }}</span>
        </nav>

        <div v-if="survey">
          <!-- Kicker Badge -->
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-3">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>LAPORAN ANALISIS RISET EMPIRIS</span>
          </div>

          <!-- Badges Bar -->
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span
              v-if="survey.is_isf"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-isf-red text-white shadow-xs"
            >
              <IconAward class="w-3.5 h-3.5 shrink-0" />
              Riset In-House ISF
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-white/10 text-neutral-200 border border-white/15">
              {{ topicLabel }}
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/10 text-neutral-300 border border-white/15">
              <IconMapPin class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
              {{ regionLabel }}
            </span>
          </div>

          <!-- Institution & Release Period -->
          <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs sm:text-sm text-neutral-400 mb-4 font-space-grotesk">
            <span class="flex items-center gap-1.5 font-bold text-white">
              <IconBuilding class="w-4 h-4 text-isf-red shrink-0" />
              {{ survey.institution }}
            </span>
            <span>•</span>
            <span class="flex items-center gap-1.5">
              <IconCalendar class="w-4 h-4 text-neutral-400 shrink-0" />
              Periode Lapangan: {{ survey.survey_period }}
            </span>
            <span>•</span>
            <span>Rilis: {{ survey.release_date }}</span>
          </div>

          <!-- Survey Title -->
          <h1 class="font-headline text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight max-w-4xl mb-4 sm:mb-6">
            {{ survey.title }}
          </h1>

          <!-- Summary Intro -->
          <p class="text-neutral-300 text-sm sm:text-base md:text-lg font-serif leading-relaxed max-w-3xl">
            {{ survey.summary }}
          </p>

          <!-- Top CTAs (Share & PDF Download) -->
          <div class="mt-6 sm:mt-8 flex flex-wrap items-center gap-3 pt-6 border-t border-neutral-800/80">
            <a
              v-if="survey.pdf_path"
              :href="survey.pdf_path"
              target="_blank"
              download
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm shadow-md transition-all cursor-pointer"
            >
              <IconDownload class="w-4 h-4 shrink-0" />
              <span>Unduh Laporan Lengkap ({{ survey.pdf_size || 'PDF' }})</span>
            </a>

            <button
              type="button"
              @click="shareUrl"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 text-neutral-200 text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
            >
              <IconShare class="w-4 h-4 shrink-0" />
              <span>Bagikan Survei</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. SKELETON LOADER (Presisi mencerminkan struktur detail konten) ═══ -->
    <main
      v-if="status === 'loading' && !data"
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12 animate-pulse"
      aria-busy="true"
      aria-label="Memuat laporan detail survei"
    >
      <!-- Section 1 Skeleton: Headline Stat Callout -->
      <div class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red p-6 sm:p-8 space-y-4">
        <div class="h-4 w-40 bg-neutral-200 rounded"></div>
        <div class="h-10 w-3/4 bg-neutral-300 rounded"></div>
        <div class="h-4 w-1/2 bg-neutral-200 rounded"></div>
      </div>

      <!-- Section 2 Skeleton: Chart -->
      <div class="bg-white rounded-lg border border-neutral-300 p-6 sm:p-8 space-y-4">
        <div class="h-5 w-48 bg-neutral-300 rounded"></div>
        <div class="h-64 bg-neutral-100 rounded-lg"></div>
      </div>

      <!-- Section 3 Skeleton: Timeline -->
      <div class="bg-white rounded-lg border border-neutral-300 p-6 sm:p-8 space-y-6">
        <div class="h-5 w-52 bg-neutral-300 rounded"></div>
        <div class="space-y-4">
          <div v-for="n in 3" :key="n" class="h-20 bg-neutral-100 rounded-lg"></div>
        </div>
      </div>
    </main>

    <!-- Error State -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Laporan Survei Tidak Ditemukan"
        message="Laporan hasil survei yang Anda cari tidak tersedia atau belum dipublikasikan."
        action-label="Kembali ke Daftar Survei"
        @retry="router.push('/survei')"
      />
    </div>

    <!-- ═══ 3. LOADED CONTENT VIEW ═════════════════════════════════════════ -->
    <main v-else-if="survey" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
      <!-- ═══ SECTION 1: HEADLINE STATISTIC TEMUAN KUNCI ═════════════════════ -->
      <section
        aria-labelledby="headline-stat-heading"
        class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="headline-stat-heading"
            title="Temuan Utama Survei"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Headline Statistic & Toleransi Kesalahan</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="max-w-2xl">
              <span class="text-xs font-mono font-bold tracking-widest text-isf-red uppercase block mb-1.5">
                Indikator Persepsi Publik
              </span>
              <h2 class="font-headline text-2xl sm:text-3xl font-bold text-neutral-950 leading-snug">
                {{ survey.headline_stat_label }}
              </h2>
              <p
                v-if="survey.headline_stat_comparison"
                class="text-xs sm:text-sm text-emerald-700 font-mono font-medium mt-2 flex items-center gap-1.5"
              >
                <IconCheck class="w-4 h-4 shrink-0" />
                <span>{{ survey.headline_stat_comparison }}</span>
              </p>
              <div class="mt-4 pt-3 border-t border-neutral-200 flex flex-wrap items-center gap-4 text-xs font-mono text-neutral-500">
                <span>Ukuran Sampel: <strong class="text-neutral-900">{{ survey.sample_size?.toLocaleString('id-ID') }} Responden</strong></span>
                <span>•</span>
                <span>Margin of Error: <strong class="text-neutral-900">{{ survey.margin_of_error }}</strong></span>
                <span>•</span>
                <span>Tingkat Kepercayaan: <strong class="text-neutral-900">{{ survey.confidence_level || '95%' }}</strong></span>
              </div>
            </div>

            <!-- Big Stat Figure Box -->
            <div class="bg-neutral-950 text-white rounded-xl p-5 sm:p-7 text-center shrink-0 min-w-[200px] border border-neutral-800 shadow-sm">
              <span class="text-[11px] font-mono text-neutral-400 block mb-1 uppercase tracking-wider">
                Angka Persentase
              </span>
              <div class="font-headline font-black text-5xl sm:text-6xl text-isf-red tracking-tight leading-none my-1">
                {{ survey.headline_stat_value }}
              </div>
              <span class="text-[11px] text-neutral-400 font-mono block mt-1">
                Margin of Error {{ survey.margin_of_error }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 2: VISUALISASI DATA INTERAKTIF ═════════════════════════ -->
      <section
        aria-labelledby="visualisasi-data-heading"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="visualisasi-data-heading"
            title="Visualisasi Data Interaktif"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Eksplorasi Jawaban Per Segmen</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <SurveiChart
            :chart-data="survey.chart_data"
            :survey-title="survey.title"
            :institution="survey.institution"
          />
        </div>
      </section>

      <!-- ═══ SECTION 3: LINIMASA ALUR & METODOLOGI RISET (SHADCN TIMELINE - HITAM IDENTITAS ISF) ══ -->
      <section
        aria-labelledby="metodologi-heading"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
      >
        <div class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
              Transparansi Ilmiah & Kontrol Mutu
            </span>
            <span class="text-neutral-600">•</span>
            <h2 id="metodologi-heading" class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider">
              Spesifikasi & Metodologi Survei
            </h2>
          </div>
          <span class="text-[11px] font-mono text-neutral-400">
            Standar Penjaminan Mutu Riset ISF
          </span>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <!-- Komponen Timeline Metodologi Shadcn Modern & Minimalis (Hitam Identitas ISF) -->
          <TimelineMetodologi
            :survey="survey"
            :is-dark="true"
          />
        </div>
      </section>

      <!-- ═══ SECTION 4: ANALISIS PAKAR & IMPLIKASI STRATEGIS (NAVY) ══════════ -->
      <section
        v-if="survey.expert_analysis"
        aria-labelledby="analisis-pakar-heading"
        class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
      >
        <div class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red">
              Telaah Peneliti ISF
            </span>
            <span class="text-neutral-600">•</span>
            <h2 id="analisis-pakar-heading" class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider">
              Analisis Pakar & Implikasi Strategis Kebijakan
            </h2>
          </div>
          <span class="text-[11px] font-mono text-neutral-400">
            Kajian Strategis Nasional
          </span>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-7 lg:py-8 space-y-6']">
          <div class="font-serif italic text-neutral-200 text-base sm:text-lg lg:text-xl leading-relaxed max-w-4xl">
            <p class="whitespace-pre-line">
              "{{ survey.expert_analysis }}"
            </p>
          </div>

          <div class="pt-6 border-t border-neutral-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-isf-red/20 text-isf-red border border-isf-red/30 flex items-center justify-center font-bold text-xs font-space-grotesk">
              ISF
            </div>
            <div>
              <div class="text-xs sm:text-sm font-bold text-white font-space-grotesk">
                Divisi Riset Kebijakan Publik & Opini Publik
              </div>
              <div class="text-[11px] text-neutral-400 font-space-grotesk">
                Indonesia Strategy Forum (ISF)
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ SECTION 5: UNDUH DOKUMEN LAPORAN LENGKAP (PDF CALLOUT) ══════════ -->
      <section v-if="survey.pdf_path" aria-label="Unduh Dokumen Laporan Lengkap">
        <div class="bg-white rounded-lg border-2 border-dashed border-neutral-300 p-5 sm:p-7 flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-isf-red/10 text-isf-red flex items-center justify-center shrink-0 border border-isf-red/20">
              <IconFileText class="w-6 h-6 sm:w-8 sm:h-8" />
            </div>
            <div>
              <h4 class="font-headline font-bold text-base sm:text-lg text-neutral-950">
                Unduh Dokumen Laporan Lengkap Survei
              </h4>
              <p class="text-xs sm:text-sm text-neutral-600 mt-0.5">
                Berisi tabulasi silang (crosstabs), kuesioner lengkap, dan rincian per provinsi (Format: {{ survey.pdf_size || 'PDF' }}).
              </p>
            </div>
          </div>

          <a
            :href="survey.pdf_path"
            target="_blank"
            download
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm shadow-md transition-all shrink-0 cursor-pointer"
          >
            <IconDownload class="w-4 h-4 shrink-0" />
            <span>Unduh Berkas PDF</span>
          </a>
        </div>
      </section>

      <!-- ═══ SECTION 6: SURVEI TERKAIT LAINNYA ══════════════════════════════ -->
      <section
        v-if="relatedSurveys && relatedSurveys.length > 0"
        aria-labelledby="related-surveys-heading"
        class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
      >
        <div class="border-b border-neutral-200">
          <SectionHeading
            id="related-surveys-heading"
            title="Survei Terkait Lainnya"
            size="lg"
            :class="sectionPad"
          >
            <template #eyebrow>Riset Sejenis</template>
          </SectionHeading>
        </div>

        <div :class="[sectionPad, 'py-5 sm:py-6 lg:py-8']">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SurveiCard
              v-for="rel in relatedSurveys"
              :key="rel.id"
              :survey="rel"
              theme="light"
            />
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
