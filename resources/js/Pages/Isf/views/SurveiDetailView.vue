<script setup>
/**
 * SurveiDetailView.vue — Halaman Detail Survei Opini Publik Interaktif (/survei/:slug)
 *
 * Mengimplementasikan:
 * - Headline Statistic: Angka metrik utama temuan survei & komparasi tren.
 * - Visualisasi Data Interaktif: Grafik batang/segmen yang bisa difilter (usia, gender, wilayah) + tabel alternatif pembaca layar.
 * - Metodologi Riset: Sampel, margin of error, periode, metode penarikan sampel, dan lembaga pelaksana.
 * - Downloadable PDF: Unduh berkas laporan riset lengkap + info ukuran file.
 * - Analisis Pakar: Ulasan mendalam peneliti ISF terkait hasil survei.
 * - Related Surveys: Survei relevan sebelumnya.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconArrowLeft,
  IconChevronRight,
  IconBuilding,
  IconCalendar,
  IconUsers,
  IconScale,
  IconDownload,
  IconFileText,
  IconMapPin,
  IconAward,
  IconCheck,
  IconShare,
  IconInfoCircle,
  IconShieldCheck,
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  SectionHeading,
  SurveiCard,
  SurveiChart,
} from '@/Components/Isf'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSurveiDetail } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const router = useRouter()

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
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-[#FBF9F4] text-neutral-900 antialiased pb-20">
    <!-- Header Hero Detail Survei (Navy) -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-10">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-6 select-none flex-wrap">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <RouterLink to="/survei" class="hover:text-white transition-colors">Survei Opini Publik</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <span class="text-white font-bold truncate max-w-[200px] sm:max-w-md">{{ survey?.title || 'Detail Survei' }}</span>
        </nav>

        <div v-if="survey">
          <!-- Badges Bar -->
          <div class="flex flex-wrap items-center gap-2 mb-4">
            <span
              v-if="survey.is_isf"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-isf-red text-white shadow-xs"
            >
              <IconAward class="w-3.5 h-3.5 shrink-0" />
              Riset In-House ISF
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-neutral-800 border border-neutral-700 text-amber-400 font-mono">
              {{ topicLabel }}
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-800 border border-neutral-700 text-neutral-300">
              <IconMapPin class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
              {{ regionLabel }}
            </span>
          </div>

          <!-- Institution & Release Period -->
          <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-neutral-400 mb-3 font-space-grotesk">
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
          <h1 class="font-headline text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight text-white leading-tight max-w-4xl mb-6">
            {{ survey.title }}
          </h1>

          <!-- Summary Intro -->
          <p class="text-neutral-300 text-sm sm:text-base md:text-lg font-normal leading-relaxed max-w-3xl">
            {{ survey.summary }}
          </p>

          <!-- Top CTAs (Share & PDF) -->
          <div class="mt-8 flex flex-wrap items-center gap-3 pt-6 border-t border-neutral-800/80">
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
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-200 text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
            >
              <IconShare class="w-4 h-4 shrink-0" />
              <span>Bagikan Survei</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 pt-8 sm:pt-10">
      <!-- Loading State -->
      <div v-if="status === 'loading'" class="py-12">
        <ArtikelSkeleton variant="artikel" />
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="py-12">
        <ErrorMessage
          title="Laporan Survei Tidak Ditemukan"
          message="Laporan hasil survei yang Anda cari tidak tersedia atau belum dipublikasikan."
          action-label="Kembali ke Daftar Survei"
          @retry="router.push('/survei')"
        />
      </div>

      <!-- Loaded Content -->
      <div v-else-if="survey" class="space-y-12 sm:space-y-16">
        <!-- ═══ 1. HEADLINE STATISTIC CALLOUT ═════════════════════════════════ -->
        <section aria-labelledby="headline-stat-heading">
          <div class="bg-white rounded-2xl border border-neutral-300 p-6 sm:p-8 lg:p-10 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="max-w-2xl">
              <span class="text-xs font-mono font-bold tracking-widest text-isf-red uppercase block mb-1">
                Headline Statistic Temuan Kunci
              </span>
              <h2 id="headline-stat-heading" class="font-headline text-2xl sm:text-3xl font-bold text-neutral-950 leading-snug">
                {{ survey.headline_stat_label }}
              </h2>
              <p
                v-if="survey.headline_stat_comparison"
                class="text-xs sm:text-sm text-emerald-700 font-mono font-medium mt-2 flex items-center gap-1.5"
              >
                <IconCheck class="w-4 h-4 shrink-0" />
                {{ survey.headline_stat_comparison }}
              </p>
            </div>

            <!-- Big Stat Figure -->
            <div class="bg-neutral-900 text-white rounded-xl p-6 sm:p-8 text-center shrink-0 min-w-[200px] border border-neutral-800">
              <span class="text-[11px] font-mono text-neutral-400 block mb-1 uppercase tracking-wider">
                Angka Persentase
              </span>
              <div class="font-headline font-black text-5xl sm:text-6xl text-isf-red tracking-tight leading-none">
                {{ survey.headline_stat_value }}
              </div>
              <span class="text-[11px] text-neutral-400 block mt-2">
                Margin of Error {{ survey.margin_of_error }}
              </span>
            </div>
          </div>
        </section>

        <!-- ═══ 2. VISUALISASI DATA INTERAKTIF (DENGAN TABEL ALTERNATIF) ══════ -->
        <section aria-labelledby="visualisasi-data-heading">
          <div class="mb-4">
            <SectionHeading
              id="visualisasi-data-heading"
              title="Visualisasi Data Interaktif"
              size="lg"
            >
              <template #eyebrow>Eksplorasi Jawaban Per Segmen</template>
            </SectionHeading>
          </div>

          <SurveiChart
            :chart-data="survey.chart_data"
            :survey-title="survey.title"
            :institution="survey.institution"
          />
        </section>

        <!-- ═══ 3. METODOLOGI RISET LENGKAP ═══════════════════════════════════ -->
        <section aria-labelledby="metodologi-heading">
          <div class="bg-white rounded-2xl border border-neutral-300 p-6 sm:p-8 lg:p-10 shadow-xs">
            <div class="border-b border-neutral-200 pb-4 mb-6">
              <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
                <IconShieldCheck class="w-4 h-4 shrink-0" />
                <span>Transparansi Ilmiah</span>
              </div>
              <h3 id="metodologi-heading" class="font-headline text-2xl font-bold text-neutral-950">
                Spesifikasi & Metodologi Survei
              </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
              <!-- Item 1: Sampel -->
              <div class="p-4 rounded-xl bg-[#FBF9F4] border border-neutral-200">
                <div class="flex items-center gap-2 text-xs text-neutral-500 font-semibold mb-1">
                  <IconUsers class="w-4 h-4 text-isf-red" />
                  <span>Ukuran Sampel Responden</span>
                </div>
                <div class="font-headline font-bold text-xl text-neutral-900">
                  {{ survey.sample_size?.toLocaleString('id-ID') }} Responden
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                  Warga negara berusia 17 tahun ke atas atau sudah menikah.
                </p>
              </div>

              <!-- Item 2: Margin of Error -->
              <div class="p-4 rounded-xl bg-[#FBF9F4] border border-neutral-200">
                <div class="flex items-center gap-2 text-xs text-neutral-500 font-semibold mb-1">
                  <IconScale class="w-4 h-4 text-isf-red" />
                  <span>Margin of Error (Toleransi Kesalahan)</span>
                </div>
                <div class="font-headline font-bold text-xl text-neutral-900">
                  {{ survey.margin_of_error }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                  Pada tingkat kepercayaan {{ survey.confidence_level || '95%' }}.
                </p>
              </div>

              <!-- Item 3: Periode Lapangan -->
              <div class="p-4 rounded-xl bg-[#FBF9F4] border border-neutral-200">
                <div class="flex items-center gap-2 text-xs text-neutral-500 font-semibold mb-1">
                  <IconCalendar class="w-4 h-4 text-isf-red" />
                  <span>Waktu Pengumpulan Data</span>
                </div>
                <div class="font-space-grotesk font-bold text-base text-neutral-900">
                  {{ survey.survey_period }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                  Rilis Publik: {{ survey.release_date }}
                </p>
              </div>

              <!-- Item 4: Metode Penarikan Sampel -->
              <div class="p-4 rounded-xl bg-[#FBF9F4] border border-neutral-200 sm:col-span-2">
                <div class="text-xs text-neutral-500 font-semibold mb-1">
                  Metode Penarikan Sampel (Sampling)
                </div>
                <div class="font-space-grotesk font-bold text-sm text-neutral-900">
                  {{ survey.sampling_method }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                  Distribusi proporsional menurut wilayah, gender, dan strata sosial ekonomi.
                </p>
              </div>

              <!-- Item 5: Metode Wawancara & Lembaga -->
              <div class="p-4 rounded-xl bg-[#FBF9F4] border border-neutral-200">
                <div class="text-xs text-neutral-500 font-semibold mb-1">
                  Teknik Wawancara & Pelaksana
                </div>
                <div class="font-space-grotesk font-bold text-sm text-neutral-900 truncate">
                  {{ survey.institution }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                  {{ survey.interview_method || 'Wawancara Tatap Muka Terstruktur' }}
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- ═══ 4. ANALISIS PAKAR & CATATAN KEBIJAKAN STRATEGIS ════════════════ -->
        <section
          v-if="survey.expert_analysis"
          aria-labelledby="analisis-pakar-heading"
        >
          <div class="bg-neutral-900 rounded-2xl border border-neutral-800 p-6 sm:p-8 lg:p-10 text-white shadow-md">
            <div class="border-b border-neutral-800 pb-4 mb-6">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/30 mb-2">
                <span>Telaah Peneliti ISF</span>
              </div>
              <h3 id="analisis-pakar-heading" class="font-headline text-2xl sm:text-3xl font-bold text-white">
                Analisis Pakar & Implikasi Strategis Kebijakan
              </h3>
            </div>

            <div class="text-neutral-300 text-sm sm:text-base leading-relaxed space-y-4 font-newsreader italic text-lg max-w-4xl">
              <p class="whitespace-pre-line leading-relaxed">
                "{{ survey.expert_analysis }}"
              </p>
            </div>

            <div class="mt-8 pt-6 border-t border-neutral-800 flex items-center gap-3">
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

        <!-- ═══ 5. DOWNLOAD PDF CALLOUT ═════════════════════════════════════════ -->
        <section v-if="survey.pdf_path">
          <div class="bg-[#FBF9F4] rounded-2xl border-2 border-dashed border-neutral-300 p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-isf-red/10 text-isf-red flex items-center justify-center shrink-0 border border-isf-red/20">
                <IconFileText class="w-8 h-8" />
              </div>
              <div>
                <h4 class="font-headline font-bold text-lg text-neutral-950">
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
              class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-isf-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm shadow-md transition-all shrink-0 cursor-pointer"
            >
              <IconDownload class="w-4 h-4 shrink-0" />
              <span>Unduh Berkas PDF</span>
            </a>
          </div>
        </section>

        <!-- ═══ 6. RELATED SURVEYS ═════════════════════════════════════════════ -->
        <section
          v-if="relatedSurveys && relatedSurveys.length > 0"
          aria-labelledby="related-surveys-heading"
        >
          <div class="border-b border-neutral-300 pb-4 mb-6">
            <SectionHeading
              id="related-surveys-heading"
              title="Survei Terkait Lainnya"
              size="lg"
            >
              <template #eyebrow>Riset Sejenis</template>
            </SectionHeading>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SurveiCard
              v-for="rel in relatedSurveys"
              :key="rel.id"
              :survey="rel"
              theme="light"
            />
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

