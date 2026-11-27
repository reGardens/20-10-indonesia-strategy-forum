<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  IconArrowLeft,
  IconClock,
  IconDeviceFloppy,
  IconSend,
  IconAlertCircle,
  IconCheck,
  IconCircleCheck,
  IconLock,
} from '@tabler/icons-vue'
import {
  getSurveiDetail,
  saveSurveiDraft,
  submitSurvei,
} from '@/api/services/isfMemberSurveyService'
import { useIsfSession } from '@/Composables/useIsfSession'
import SurveyProgressBar from '@/Components/Isf/Survey/SurveyProgressBar.vue'
import SurveyQuestionScale from '@/Components/Isf/Survey/SurveyQuestionScale.vue'
import SurveyQuestionRadio from '@/Components/Isf/Survey/SurveyQuestionRadio.vue'
import SurveyQuestionCheckbox from '@/Components/Isf/Survey/SurveyQuestionCheckbox.vue'
import SurveyQuestionText from '@/Components/Isf/Survey/SurveyQuestionText.vue'
import SurveyDemographicsForm from '@/Components/Isf/Survey/SurveyDemographicsForm.vue'

const route = useRoute()
const router = useRouter()
const { session, isLoggedIn, ensureLoaded } = useIsfSession()

const surveyId = computed(() => route.params.id)

const survey = ref(null)
const answers = ref({})
const demographics = ref({
  age: '',
  domicile: '',
  profession: '',
})
const currentStep = ref(1)
const isCompleted = ref(false)
const completedAt = ref('')

const loading = ref(true)
const isSaving = ref(false)
const isSubmitting = ref(false)
const lastSavedAt = ref('')
const submitError = ref('')
const validationErrors = ref({})

// Debounce timer for auto-save
let autoSaveTimeout = null

async function loadData() {
  loading.value = true
  submitError.value = ''
  validationErrors.value = {}

  // 1. Auth Guard: Pastikan login
  await ensureLoaded()
  if (!isLoggedIn.value) {
    const target = encodeURIComponent(route.fullPath)
    router.replace(`/login?redirect=${target}`)
    return
  }

  try {
    const res = await getSurveiDetail(surveyId.value)
    survey.value = res.survey
    isCompleted.value = Boolean(res.is_completed)

    if (res.response) {
      answers.value = res.response.answers || {}
      demographics.value = {
        age: res.response.demographics?.age || '',
        domicile: res.response.demographics?.domicile || '',
        profession: res.response.demographics?.profession || '',
      }
      currentStep.value = res.response.current_step || 1
      completedAt.value = res.response.completed_at || ''
    } else {
      // Coba load fallback dari local storage jika ada
      loadFromLocalStorage()
    }
  } catch (err) {
    console.error('Failed to load survey form:', err)
    submitError.value = 'Gagal memuat formulir survei. Silakan refresh halaman.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const questions = computed(() => survey.value?.questions || [])
const totalQuestions = computed(() => questions.value.length)

// Hitung persentase progress
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

  // Tambahkan poin kelengkapan jika demografi sudah diisi
  let demoPoints = 0
  if (demographics.value.age) demoPoints += 0.33
  if (demographics.value.domicile) demoPoints += 0.33
  if (demographics.value.profession) demoPoints += 0.34

  const totalPossible = totalQuestions.value + 1
  const currentTotal = answered + demoPoints
  return Math.min(100, Math.round((currentTotal / totalPossible) * 100))
})

// Auto-Save Trigger
function triggerAutoSave() {
  if (isCompleted.value) return

  // Simpan ke local storage segera
  saveToLocalStorage()

  if (autoSaveTimeout) clearTimeout(autoSaveTimeout)
  autoSaveTimeout = setTimeout(() => {
    saveDraftSilent()
  }, 1500)
}

watch(
  () => [answers.value, demographics.value],
  () => {
    if (!loading.value && !isCompleted.value) {
      triggerAutoSave()
    }
  },
  { deep: true }
)

async function saveDraftSilent() {
  if (isCompleted.value || !survey.value?.id) return
  isSaving.value = true
  try {
    const res = await saveDraftSurvei(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
      current_step: currentStep.value,
    })
    lastSavedAt.value = res.saved_at || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  } catch (err) {
    console.warn('Auto-save silent failed, data remains in localStorage:', err)
  } finally {
    isSaving.value = false
  }
}

// Tombol manual "Simpan Draft"
async function handleManualSaveDraft() {
  if (isCompleted.value) return
  isSaving.value = true
  submitError.value = ''
  try {
    const res = await saveSurveiDraft(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
      current_step: currentStep.value,
    })
    lastSavedAt.value = res.saved_at || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    alert('Draf jawaban Anda berhasil disimpan ke server. Anda dapat melanjutkannya kapan saja.')
  } catch (err) {
    submitError.value = err.response?.data?.message || 'Gagal menyimpan draf ke server.'
  } finally {
    isSaving.value = false
  }
}

// Validasi Form
function validateForm() {
  const errs = {}

  for (const q of questions.value) {
    if (q.required) {
      const val = answers.value[q.id]
      if (val === undefined || val === null || val === '') {
        errs[q.id] = `Pertanyaan wajib diisi.`
      } else if (Array.isArray(val) && val.length === 0) {
        errs[q.id] = `Pilih minimal satu opsi.`
      }
    }
  }

  if (!demographics.value.age) {
    errs['demographics.age'] = 'Kelompok usia wajib dipilih.'
  }
  if (!demographics.value.domicile) {
    errs['demographics.domicile'] = 'Domisili wilayah wajib dipilih.'
  }
  if (!demographics.value.profession) {
    errs['demographics.profession'] = 'Latar belakang profesi wajib dipilih.'
  }

  validationErrors.value = errs
  return Object.keys(errs).length === 0
}

// Tombol "Kirim Jawaban" (Submit)
async function handleSubmit() {
  if (isCompleted.value) return
  submitError.value = ''

  if (!validateForm()) {
    submitError.value = 'Mohon lengkapi semua isian wajib sebelum mengirim jawaban.'
    // Scroll ke error pertama
    window.scrollTo({ top: 300, behavior: 'smooth' })
    return
  }

  isSubmitting.value = true
  try {
    const res = await submitSurvei(survey.value.id, {
      answers: answers.value,
      demographics: demographics.value,
    })

    // Bersihkan draft local storage
    clearLocalStorage()

    // Redirect ke Halaman Terima Kasih / Hasil
    router.push(`/member/survei/${survey.value.id}/selesai`)
  } catch (err) {
    console.error('Submit error:', err)
    if (err.response?.status === 422) {
      submitError.value = err.response.data?.message || 'Validasi gagal.'
      if (err.response.data?.errors) {
        validationErrors.value = err.response.data.errors
      }
    } else {
      submitError.value = 'Terjadi kesalahan sistem saat mengirim jawaban. Silakan coba beberapa saat lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}

// Local Storage Helpers
function getStorageKey() {
  return `isf_survey_draft_${surveyId.value}`
}

function saveToLocalStorage() {
  try {
    localStorage.setItem(
      getStorageKey(),
      JSON.stringify({
        answers: answers.value,
        demographics: demographics.value,
        timestamp: Date.now(),
      })
    )
  } catch (_) {}
}

function loadFromLocalStorage() {
  try {
    const raw = localStorage.getItem(getStorageKey())
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed.answers) answers.value = parsed.answers
      if (parsed.demographics) demographics.value = parsed.demographics
    }
  } catch (_) {}
}

function clearLocalStorage() {
  try {
    localStorage.removeItem(getStorageKey())
  } catch (_) {}
}
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Tombol Kembali ke Katalog -->
    <div class="flex items-center justify-between">
      <RouterLink
        to="/member/survei"
        class="inline-flex items-center gap-1.5 font-ui text-xs font-semibold text-neutral-600 dark:text-neutral-400 hover:text-isf-red dark:hover:text-isf-red transition-colors"
      >
        <IconArrowLeft class="h-4 w-4" />
        Kembali ke Katalog Survei
      </RouterLink>

      <span class="font-ui text-xs text-neutral-500">
        ID Survei: #{{ surveyId }}
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="h-32 animate-pulse rounded-2xl bg-neutral-100 dark:bg-neutral-800"></div>
      <div class="h-64 animate-pulse rounded-2xl bg-neutral-100 dark:bg-neutral-800"></div>
    </div>

    <template v-else-if="survey">
      <!-- Banner Jika Sudah Pernah Mengisi (Pencegahan Double-Voting) -->
      <div
        v-if="isCompleted"
        class="rounded-2xl border border-emerald-500/40 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20 p-5 sm:p-6"
      >
        <div class="flex items-start gap-3.5">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
            <IconCircleCheck class="h-5 w-5" />
          </div>
          <div class="flex-1">
            <h3 class="font-heading text-base font-bold text-emerald-950 dark:text-emerald-100">
              Survei Ini Telah Anda Selesaikan
            </h3>
            <p class="font-ui text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 mt-1">
              Jawaban Anda telah dicatat pada sistem pada {{ completedAt }}. Sesuai prinsip independensi ISF (satu akun, satu suara), Anda tidak dapat mengirimkan suara berulang.
            </p>
            <div class="mt-4">
              <RouterLink
                :to="`/member/survei/${survey.id}/selesai`"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 font-ui text-xs font-bold text-white hover:bg-emerald-800 transition-all shadow-xs"
              >
                <span>Lihat Hasil & Visualisasi Grafik Sementara</span>
                →
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Header Judul Survei & Intro Editorial ISF -->
      <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
        <div class="flex flex-wrap items-center gap-2 mb-3">
          <span class="rounded-md bg-neutral-100 dark:bg-neutral-800 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
            {{ survey.topic || 'Kebijakan Publik' }}
          </span>
          <span class="text-xs font-ui text-neutral-500 dark:text-neutral-400">
            Lembaga: {{ survey.institution || 'ISF Research' }}
          </span>
        </div>

        <h1 class="font-heading text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-neutral-100 leading-tight">
          {{ survey.title }}
        </h1>

        <!-- Konteks (Intro): 1 Paragraf Pengantar Pembeda ISF vs Polling Medsos -->
        <div class="mt-4 rounded-xl border-l-4 border-isf-red bg-neutral-50 dark:bg-neutral-800/50 p-4 sm:p-5">
          <h2 class="font-ui text-xs font-bold uppercase tracking-wider text-isf-red mb-1.5 flex items-center gap-1.5">
            Pengantar & Konteks Analisis
          </h2>
          <p class="font-body text-sm sm:text-base text-neutral-700 dark:text-neutral-300 leading-relaxed">
            {{ survey.editorial_intro || 'Berdasarkan pemantauan kebijakan ISF, survei ini dirancang untuk menangkap parameter telaah berbasis kepakaran dari para anggota terverifikasi. Masukan Anda akan diproses secara komprehensif untuk penyusunan rekomendasi strategis.' }}
          </p>
        </div>
      </div>

      <!-- Sticky / Top Progress Bar -->
      <SurveyProgressBar
        :current-step="currentStep"
        :total-steps="2"
        :estimated-minutes="survey.estimated_minutes || 5"
        :progress="progressPercentage"
        :is-saving="isSaving"
        :last-saved-at="lastSavedAt"
      />

      <!-- Alert Pesan Error Global -->
      <div
        v-if="submitError"
        class="rounded-xl border border-red-300 bg-red-50 dark:border-red-900 dark:bg-red-950/40 p-4 text-xs sm:text-sm text-red-800 dark:text-red-300 flex items-center gap-2"
      >
        <IconAlertCircle class="h-5 w-5 shrink-0 text-red-600" />
        <span>{{ submitError }}</span>
      </div>

      <!-- Form Pertanyaan -->
      <div class="space-y-6">
        <template v-for="(q, idx) in questions" :key="q.id">
          <!-- Tipe: Skala Linier (1-10) Likert Scale -->
          <SurveyQuestionScale
            v-if="q.type === 'scale'"
            :question="q"
            v-model="answers[q.id]"
            :number="idx + 1"
            :error="validationErrors[q.id] || validationErrors[`answers.${q.id}`]"
          />

          <!-- Tipe: Pilihan Ganda (Radio) -->
          <SurveyQuestionRadio
            v-else-if="q.type === 'radio'"
            :question="q"
            v-model="answers[q.id]"
            :number="idx + 1"
            :error="validationErrors[q.id] || validationErrors[`answers.${q.id}`]"
          />

          <!-- Tipe: Ceklis (Checkbox) -->
          <SurveyQuestionCheckbox
            v-else-if="q.type === 'checkbox'"
            :question="q"
            v-model="answers[q.id]"
            :number="idx + 1"
            :error="validationErrors[q.id] || validationErrors[`answers.${q.id}`]"
          />

          <!-- Tipe: Jawaban Singkat / Text Area -->
          <SurveyQuestionText
            v-else-if="q.type === 'text'"
            :question="q"
            v-model="answers[q.id]"
            :number="idx + 1"
            :error="validationErrors[q.id] || validationErrors[`answers.${q.id}`]"
          />
        </template>

        <!-- Bagian Data Demografi (Wajib untuk analisis segmen data) -->
        <SurveyDemographicsForm
          v-model="demographics"
          :errors="validationErrors"
        />
      </div>

      <!-- Sticky / Bottom Action Bar: Simpan Draft & Kirim Jawaban -->
      <div
        v-if="!isCompleted"
        class="sticky bottom-4 z-20 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 p-4 sm:p-5 shadow-lg backdrop-blur-md flex flex-col sm:flex-row items-center justify-between gap-4"
      >
        <div class="flex items-center gap-2 text-xs font-ui text-neutral-500">
          <IconLock class="h-4 w-4 text-neutral-400" />
          <span>Satu Akun, Satu Suara · Dilindungi Pencegahan Double-Voting</span>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
          <!-- Tombol "Simpan Draft" -->
          <button
            type="button"
            @click="handleManualSaveDraft"
            :disabled="isSaving || isSubmitting"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-5 py-2.5 font-ui text-xs sm:text-sm font-semibold text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-all disabled:opacity-50"
          >
            <IconDeviceFloppy class="h-4 w-4 text-neutral-500" />
            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Draft' }}</span>
          </button>

          <!-- Tombol "Kirim Jawaban" (Submit) -->
          <button
            type="button"
            @click="handleSubmit"
            :disabled="isSubmitting || isSaving"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-isf-red px-6 py-2.5 font-ui text-xs sm:text-sm font-bold text-white hover:bg-red-700 shadow-sm transition-all disabled:opacity-50"
          >
            <IconSend class="h-4 w-4" />
            <span>{{ isSubmitting ? 'Mengirim Jawaban...' : 'Kirim Jawaban' }}</span>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
