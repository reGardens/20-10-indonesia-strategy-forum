<script setup>
/**
 * SurveiDetailView.vue — Halaman Pengisian Survei & Hasil Sementara (/survei/:slug)
 * Didesain sederhana, jelas, dan ramah pengguna segala usia dengan identitas visual ISF.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconChevronRight,
  IconDownload,
  IconFileText,
  IconCheck,
  IconArrowLeft,
  IconArrowRight,
  IconClock,
  IconCircleCheck,
  IconLock,
  IconDeviceFloppy,
  IconSend,
  IconAlertCircle,
  IconChartBar,
} from '@tabler/icons-vue'
import {
  SectionHeading,
  SurveiCard,
  SurveiChart,
  TimelineMetodologi,
} from '@/Components/Isf'
import SurveyProgressBar from '@/Components/Isf/Survey/SurveyProgressBar.vue'
import SurveyQuestionScale from '@/Components/Isf/Survey/SurveyQuestionScale.vue'
import SurveyQuestionRadio from '@/Components/Isf/Survey/SurveyQuestionRadio.vue'
import SurveyQuestionCheckbox from '@/Components/Isf/Survey/SurveyQuestionCheckbox.vue'
import SurveyQuestionText from '@/Components/Isf/Survey/SurveyQuestionText.vue'
import SurveyDemographicsForm from '@/Components/Isf/Survey/SurveyDemographicsForm.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getSurveiDetail } from '@/api/services/isfPublicService'
import { saveSurveiDraft, submitSurvei } from '@/api/services/isfMemberSurveyService'
import { useIsfContent } from '@/Composables/useIsfContent'
import { useIsfSession } from '@/Composables/useIsfSession'

const route = useRoute()
const router = useRouter()
const { isLoggedIn } = useIsfSession()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'
const slug = computed(() => route.params.slug)

const { data, status, load } = useIsfContent(() => getSurveiDetail(slug.value))

const survey = computed(() => data.value?.survey || null)
const relatedSurveys = computed(() => data.value?.relatedSurveys || [])
const stats = computed(() => data.value?.stats || { total_member_respondents: 0 })

// State Formulir Survei
const answers = ref({})
const demographics = ref({
  age: '',
  domicile: '',
  profession: '',
})
const isCompleted = ref(false)
const isSaving = ref(false)
const isSubmitting = ref(false)
const lastSavedAt = ref('')
const submitError = ref('')
const validationErrors = ref({})

const questions = computed(() => survey.value?.questions || [])
const totalQuestions = computed(() => questions.value.length)

const progressPercentage = computed(() => {
  if (isCompleted.value) return 100
  if (totalQuestions.value === 0) return 0

  let answered = 0
  for (const q of questions.value) {
    const val = answers.value[q.id]
    if (val !== undefined && val !== null && val !== '') {
      if (Array.isArray(val)) {
        if (val.length > 0) answered++
      } else {
        answered++
      }
    }
  }

  let demoPoints = 0
  if (demographics.value.age) demoPoints += 0.33
  if (demographics.value.domicile) demoPoints += 0.33
  if (demographics.value.profession) demoPoints += 0.34

  return Math.min(100, Math.round(((answered + demoPoints) / (totalQuestions.value + 1)) * 100))
})

function saveToLocalStorage() {
  if (typeof localStorage === 'undefined' || !survey.value?.id) return
  try {
    localStorage.setItem(
      `isf_survey_draft_${survey.value.id}`,
      JSON.stringify({ answers: answers.value, demographics: demographics.value })
    )
  } catch {}
}

function loadFromLocalStorage() {
  if (typeof localStorage === 'undefined' || !survey.value?.id) return
  try {
    const raw = localStorage.getItem(`isf_survey_draft_${survey.value.id}`)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed.answers && Object.keys(answers.value).length === 0) {
        answers.value = parsed.answers
      }
      if (parsed.demographics && !demographics.value.age) {
        demographics.value = parsed.demographics
      }
    }
  } catch {}
}

let autoSaveTimeout = null
function triggerAutoSave() {
  if (isCompleted.value) return
  saveToLocalStorage()
  if (autoSaveTimeout) clearTimeout(autoSaveTimeout)
  autoSaveTimeout = setTimeout(() => {
    saveDraftSilent()
  }, 1500)
}

watch(
  () => [answers.value, demographics.value],
  () => {
    if (status.value === 'success' && !isCompleted.value && survey.value?.is_member_active) {
      triggerAutoSave()
    }
  },
  { deep: true }
)

async function saveDraftSilent() {
  if (isCompleted.value || !survey.value?.id || !isLoggedIn.value) return
  isSaving.value = true
  try {
    await saveSurveiDraft(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
    })
    lastSavedAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  } catch {} finally {
    isSaving.value = false
  }
}

async function saveDraftManual() {
  if (isCompleted.value || !survey.value?.id || !isLoggedIn.value) return
  isSaving.value = true
  submitError.value = ''
  try {
    await saveSurveiDraft(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
    })
    lastSavedAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    saveToLocalStorage()
  } catch (err) {
    submitError.value = err.response?.data?.message || 'Gagal menyimpan draft.'
  } finally {
    isSaving.value = false
  }
}

async function handleSubmit() {
  submitError.value = ''
  validationErrors.value = {}

  if (!isLoggedIn.value) {
    submitError.value = 'Silakan masuk terlebih dahulu.'
    return
  }

  let hasError = false
  for (const q of questions.value) {
    if (q.required) {
      const val = answers.value[q.id]
      if (val === undefined || val === null || val === '' || (Array.isArray(val) && val.length === 0)) {
        validationErrors.value[q.id] = 'Pertanyaan ini wajib diisi.'
        hasError = true
      }
    }
  }

  if (!demographics.value.age) {
    validationErrors.value['demographics.age'] = 'Silakan pilih usia Anda.'
    hasError = true
  }
  if (!demographics.value.domicile) {
    validationErrors.value['demographics.domicile'] = 'Silakan pilih domisili Anda.'
    hasError = true
  }
  if (!demographics.value.profession) {
    validationErrors.value['demographics.profession'] = 'Silakan pilih pekerjaan Anda.'
    hasError = true
  }

  if (hasError) {
    submitError.value = 'Mohon lengkapi pertanyaan wajib yang masih kosong di atas.'
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 300, behavior: 'smooth' })
    }
    return
  }

  isSubmitting.value = true
  try {
    await submitSurvei(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
    })
    isCompleted.value = true
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(`isf_survey_draft_${survey.value.id}`)
    }
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  } catch (err) {
    if (err.response?.status === 409 || err.response?.data?.message?.includes('sudah pernah mengisi')) {
      isCompleted.value = true
    } else {
      submitError.value = err.response?.data?.message || 'Gagal mengirim jawaban. Silakan coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}

watch(
  () => data.value,
  (val) => {
    if (val) {
      isCompleted.value = Boolean(val.is_completed)
      if (val.user_response) {
        answers.value = { ...(val.user_response.answers || {}) }
        demographics.value = {
          age: val.user_response.demographics?.age || '',
          domicile: val.user_response.demographics?.domicile || '',
          profession: val.user_response.demographics?.profession || '',
        }
      } else {
        loadFromLocalStorage()
      }
    }
  },
  { immediate: true }
)

const topicLabel = computed(() => {
  const map = { politik: 'Politik', ekonomi: 'Ekonomi', sosial: 'Sosial', hukum: 'Hukum' }
  return map[survey.value?.topic?.toLowerCase()] || survey.value?.topic || 'Umum'
})

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
  () => survey.value?.title,
  (title) => {
    if (typeof document !== 'undefined') {
      document.title = title ? `${title} · Survei · Indonesia Strategy Forum` : 'Survei · Indonesia Strategy Forum'
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20">
    <!-- ═══ HEADER HERO ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-10 pb-8">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-neutral-400 mb-4">
          <RouterLink to="/beranda" class="hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <RouterLink to="/survei" class="hover:text-white transition-colors">Survei</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60" />
          <span class="text-white font-bold truncate max-w-[220px] sm:max-w-md">{{ survey?.title || 'Survei' }}</span>
        </nav>

        <div v-if="survey">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase bg-isf-red text-white">
              {{ topicLabel }}
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-medium bg-white/10 text-neutral-200">
              <IconClock class="w-3.5 h-3.5" />
              {{ survey.estimated_minutes || 5 }} Menit
            </span>
          </div>

          <h1 class="font-headline text-2xl sm:text-3xl md:text-4xl font-black tracking-tight text-white leading-tight max-w-4xl">
            {{ survey.title }}
          </h1>

          <p v-if="survey.summary" class="text-neutral-300 text-sm sm:text-base mt-3 max-w-3xl leading-relaxed">
            {{ survey.summary }}
          </p>
        </div>
      </div>
    </header>

    <!-- ═══ SKELETON LOADER ═══ -->
    <main
      v-if="status === 'loading' && !data"
      class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 space-y-6 animate-pulse max-w-4xl mx-auto"
    >
      <div class="h-24 bg-neutral-100 rounded-lg border border-neutral-200"></div>
      <div class="h-64 bg-neutral-100 rounded-lg border border-neutral-200"></div>
    </main>

    <!-- ═══ ERROR STATE ═══ -->
    <div v-else-if="status === 'error' && !data" class="mx-auto max-w-3xl py-16 px-4">
      <ErrorMessage
        title="Survei Tidak Ditemukan"
        message="Survei yang Anda cari tidak tersedia."
        action-label="Kembali ke Daftar Survei"
        @retry="router.push('/survei')"
      />
    </div>

    <!-- ═══ KONTEN UTAMA ═══ -->
    <main v-else-if="survey" class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8">
      <!-- 1. JIKA SURVEI PARTISIPATIF AKTIF -->
      <div v-if="survey.is_member_active" class="max-w-3xl mx-auto space-y-6">
        <!-- Tombol Kembali -->
        <div>
          <RouterLink
            to="/survei"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-neutral-600 hover:text-isf-red transition-colors"
          >
            <IconArrowLeft class="w-4 h-4" />
            <span>Kembali ke Daftar Survei</span>
          </RouterLink>
        </div>

        <!-- A. AUTH GUARD: JIKA BELUM LOGIN -->
        <div
          v-if="!isLoggedIn"
          class="rounded-lg border border-neutral-300 bg-white p-8 text-center shadow-xs space-y-4"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-isf-red/10 text-isf-red">
            <IconLock class="h-7 w-7" />
          </div>
          <h2 class="font-headline text-2xl font-bold text-neutral-900">
            Silakan Masuk Terlebih Dahulu
          </h2>
          <p class="text-sm sm:text-base text-neutral-600 max-w-md mx-auto leading-relaxed">
            Untuk mengisi survei ini, silakan masuk ke akun Anda terlebih dahulu agar jawaban tersimpan dengan aman.
          </p>
          <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
            <a
              :href="`/login?redirect=${encodeURIComponent(route.fullPath)}`"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-md bg-isf-red hover:bg-red-700 text-white font-bold text-sm transition-colors"
            >
              <span>Masuk</span>
              <IconArrowRight class="w-4 h-4" />
            </a>
            <a
              href="/register"
              class="inline-flex items-center px-6 py-3 rounded-md border border-neutral-300 bg-neutral-50 hover:bg-neutral-100 text-neutral-800 font-semibold text-sm transition-colors"
            >
              Daftar Akun
            </a>
          </div>
        </div>

        <!-- B. HALAMAN TERIMA KASIH & HASIL SEMENTARA (JIKA SUDAH SELESAI) -->
        <div v-else-if="isCompleted" class="space-y-6">
          <div class="rounded-lg border border-neutral-300 bg-white p-8 text-center shadow-xs space-y-4">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
              <IconCircleCheck class="h-8 w-8" />
            </div>
            <h2 class="font-headline text-2xl sm:text-3xl font-bold text-neutral-900">
              Terima Kasih Atas Partisipasi Anda!
            </h2>
            <p class="text-sm sm:text-base text-neutral-600 max-w-lg mx-auto leading-relaxed">
              Jawaban Anda untuk survei <strong>"{{ survey.title }}"</strong> sudah kami terima dan simpan.
            </p>
            <div class="pt-2">
              <RouterLink
                to="/survei"
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-md bg-isf-red hover:bg-red-700 text-white font-bold text-sm transition-colors"
              >
                <IconArrowLeft class="w-4 h-4" />
                <span>Kembali ke Daftar Survei</span>
              </RouterLink>
            </div>
          </div>

          <!-- Hasil Sementara (Reward Member) -->
          <div v-if="survey.chart_data?.segments?.length > 0 || survey.headline_stat_value" class="rounded-lg border border-neutral-300 bg-white p-6 shadow-xs space-y-5">
            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
              <h3 class="font-headline text-lg font-bold text-neutral-900 flex items-center gap-2">
                <IconChartBar class="w-5 h-5 text-isf-red" />
                <span>Hasil Sementara</span>
              </h3>
              <span v-if="stats.total_member_respondents" class="text-xs text-neutral-500">
                {{ stats.total_member_respondents }} Responden
              </span>
            </div>

            <div v-if="survey.headline_stat_value" class="rounded-md bg-neutral-50 border border-neutral-200 p-4 flex items-center gap-4">
              <span class="font-headline text-3xl sm:text-4xl font-black text-isf-red shrink-0">
                {{ survey.headline_stat_value }}
              </span>
              <p class="text-sm font-medium text-neutral-800">
                {{ survey.headline_stat_label }}
              </p>
            </div>

            <SurveiChart
              v-if="survey.chart_data?.segments?.length > 0"
              :chart-data="survey.chart_data"
              :survey-title="survey.title"
              :institution="survey.institution"
            />
          </div>
        </div>

        <!-- C. FORMULIR PENGISIAN SURVEI -->
        <div v-else class="space-y-6">
          <SurveyProgressBar
            :estimated-minutes="survey.estimated_minutes || 5"
            :progress="progressPercentage"
            :is-saving="isSaving"
            :last-saved-at="lastSavedAt"
            :current-step="1"
            :total-steps="2"
          />

          <!-- Pengantar / Intro Singkat -->
          <div
            v-if="survey.editorial_intro"
            class="rounded-lg border-l-4 border-isf-red bg-[#FBF9F4] p-5 border border-neutral-200"
          >
            <p class="text-xs font-bold uppercase tracking-wider text-isf-red mb-1">Pengantar</p>
            <p class="text-sm sm:text-base text-neutral-800 leading-relaxed">
              {{ survey.editorial_intro }}
            </p>
          </div>

          <!-- Pesan Error Validasi -->
          <div
            v-if="submitError"
            class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900 flex items-start gap-3"
          >
            <IconAlertCircle class="h-5 w-5 shrink-0 text-isf-red mt-0.5" />
            <p class="text-sm font-semibold">{{ submitError }}</p>
          </div>

          <!-- Daftar Pertanyaan -->
          <div v-if="questions.length > 0" class="space-y-5">
            <template v-for="(q, idx) in questions" :key="q.id">
              <SurveyQuestionScale
                v-if="q.type === 'scale'"
                :question="q"
                :number="idx + 1"
                :error="validationErrors[q.id]"
                v-model="answers[q.id]"
              />
              <SurveyQuestionRadio
                v-else-if="q.type === 'radio'"
                :question="q"
                :number="idx + 1"
                :error="validationErrors[q.id]"
                v-model="answers[q.id]"
              />
              <SurveyQuestionCheckbox
                v-else-if="q.type === 'checkbox'"
                :question="q"
                :number="idx + 1"
                :error="validationErrors[q.id]"
                v-model="answers[q.id]"
              />
              <SurveyQuestionText
                v-else
                :question="q"
                :number="idx + 1"
                :error="validationErrors[q.id]"
                v-model="answers[q.id]"
              />
            </template>
          </div>

          <!-- Data Diri / Demografi -->
          <SurveyDemographicsForm
            v-model="demographics"
            :errors="validationErrors"
          />

          <!-- Tombol Simpan Draft & Kirim Jawaban -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-neutral-200">
            <button
              type="button"
              @click="saveDraftManual"
              :disabled="isSaving || isSubmitting"
              class="inline-flex items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-5 py-3 text-sm font-semibold text-neutral-800 hover:bg-neutral-50 transition-colors disabled:opacity-50 cursor-pointer"
            >
              <IconDeviceFloppy class="h-4 w-4 text-neutral-500" />
              <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Draft' }}</span>
            </button>

            <button
              type="button"
              @click="handleSubmit"
              :disabled="isSubmitting || isSaving"
              class="inline-flex items-center justify-center gap-2 rounded-md bg-isf-red px-8 py-3 text-sm font-bold text-white shadow-sm hover:bg-red-700 transition-colors disabled:opacity-50 cursor-pointer"
            >
              <IconSend class="h-4 w-4" />
              <span>{{ isSubmitting ? 'Mengirim...' : 'Kirim Jawaban' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 2. FALLBACK LAPORAN RISET (Jika bukan kuisioner partisipatif) -->
      <div v-else class="space-y-8 sm:space-y-12">
        <section class="bg-white rounded-lg border border-neutral-300 border-t-4 border-t-isf-red shadow-xs overflow-hidden">
          <div class="border-b border-neutral-200">
            <SectionHeading title="Temuan Utama Survei" size="lg" :class="sectionPad" />
          </div>
          <div :class="[sectionPad, 'py-6 flex flex-col md:flex-row md:items-center justify-between gap-6']">
            <div>
              <h2 class="font-headline text-2xl font-bold text-neutral-950">{{ survey.headline_stat_label }}</h2>
              <p v-if="survey.headline_stat_comparison" class="text-sm text-emerald-700 mt-1 flex items-center gap-1">
                <IconCheck class="w-4 h-4" />
                {{ survey.headline_stat_comparison }}
              </p>
            </div>
            <div class="bg-neutral-950 text-white rounded-xl p-6 text-center shrink-0 min-w-[180px]">
              <div class="font-headline font-black text-5xl text-isf-red">{{ survey.headline_stat_value }}</div>
              <span class="text-xs text-neutral-400">MoE {{ survey.margin_of_error }}</span>
            </div>
          </div>
        </section>

        <section class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
          <div class="border-b border-neutral-200">
            <SectionHeading title="Visualisasi Data Interaktif" size="lg" :class="sectionPad" />
          </div>
          <div :class="[sectionPad, 'py-6']">
            <SurveiChart :chart-data="survey.chart_data" :survey-title="survey.title" :institution="survey.institution" />
          </div>
        </section>

        <section class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden">
          <div class="border-b border-neutral-800 px-6 py-4">
            <h2 class="font-space-grotesk text-base font-bold text-white uppercase">Spesifikasi &amp; Metodologi Survei</h2>
          </div>
          <div :class="[sectionPad, 'py-6']">
            <TimelineMetodologi :survey="survey" :is-dark="true" />
          </div>
        </section>

        <section v-if="survey.expert_analysis" class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden">
          <div class="border-b border-neutral-800 px-6 py-4">
            <h2 class="font-space-grotesk text-base font-bold text-white uppercase">Analisis Pakar &amp; Implikasi Strategis Kebijakan</h2>
          </div>
          <div :class="[sectionPad, 'py-6 font-serif italic text-neutral-200 text-lg leading-relaxed']">
            "{{ survey.expert_analysis }}"
          </div>
        </section>

        <section v-if="survey.pdf_path" class="bg-white rounded-lg border border-neutral-300 p-6 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <IconFileText class="w-8 h-8 text-isf-red" />
            <div>
              <h4 class="font-headline font-bold text-base">Unduh Dokumen Laporan ({{ survey.pdf_size || 'PDF' }})</h4>
            </div>
          </div>
          <a :href="survey.pdf_path" target="_blank" download class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md bg-isf-red text-white font-bold text-sm">
            <IconDownload class="w-4 h-4" />
            <span>Unduh PDF</span>
          </a>
        </section>

        <section v-if="relatedSurveys && relatedSurveys.length > 0" class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
          <div class="border-b border-neutral-200">
            <SectionHeading title="Survei Terkait Lainnya" size="lg" :class="sectionPad" />
          </div>
          <div :class="[sectionPad, 'py-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6']">
            <SurveiCard v-for="rel in relatedSurveys" :key="rel.id" :survey="rel" theme="light" />
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
