<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import {
  IconCircleCheck,
  IconChartBar,
  IconArrowLeft,
  IconShare,
  IconCheck,
  IconUsers,
  IconBuilding,
} from '@tabler/icons-vue'
import { getSurveiResults } from '@/api/services/isfMemberSurveyService'
import SurveiChart from '@/Components/Isf/SurveiChart.vue'

const route = useRoute()
const surveyId = computed(() => route.params.id)

const data = ref(null)
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    data.value = await getSurveiResults(surveyId.value)
  } catch (e) {
    console.error('Failed to load survey results:', e)
  } finally {
    loading.value = false
  }
}

onMounted(load)

const survey = computed(() => data.value?.survey || {})
const stats = computed(() => data.value?.stats || {})
const userResponse = computed(() => data.value?.user_response)
const chartData = computed(() => survey.value?.chart_data || { segments: [] })
</script>

<template>
  <div class="space-y-8 max-w-4xl mx-auto pb-16">
    <!-- Tombol Navigasi Atas -->
    <div class="flex items-center justify-between">
      <RouterLink
        to="/member/survei"
        class="inline-flex items-center gap-1.5 font-ui text-xs font-semibold text-neutral-600 dark:text-neutral-400 hover:text-isf-red transition-colors"
      >
        <IconArrowLeft class="h-4 w-4" />
        Kembali ke Katalog Survei
      </RouterLink>

      <RouterLink
        to="/member/dashboard"
        class="font-ui text-xs font-semibold text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200"
      >
        Ke Dasbor Member →
      </RouterLink>
    </div>

    <!-- Hero Ucapan Terima Kasih -->
    <div class="rounded-3xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-8 sm:p-12 text-center shadow-xs">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 mb-6">
        <IconCircleCheck class="h-10 w-10" />
      </div>

      <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-3 py-1 font-ui text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-3">
        Suara Berhasil Dicatat
      </span>

      <h1 class="font-heading text-2xl sm:text-4xl font-bold text-neutral-900 dark:text-neutral-100 leading-tight">
        Terima Kasih Atas Partisipasi Anda!
      </h1>

      <p class="font-body text-sm sm:text-base text-neutral-600 dark:text-neutral-300 max-w-2xl mx-auto mt-3 leading-relaxed">
        Jawaban dan telaah Anda pada survei <strong class="text-neutral-900 dark:text-neutral-100 font-bold">"{{ survey.title }}"</strong> telah diverifikasi dan masuk ke dalam database riset panel kebijakan Indonesia Strategy Forum.
      </p>

      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <RouterLink
          to="/member/survei"
          class="rounded-xl bg-isf-red px-6 py-2.5 font-ui text-xs sm:text-sm font-bold text-white hover:bg-red-700 shadow-xs transition-all"
        >
          Lihat Survei Lainnya
        </RouterLink>
        <RouterLink
          to="/member/dashboard"
          class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-6 py-2.5 font-ui text-xs sm:text-sm font-semibold text-neutral-800 dark:text-neutral-200 hover:bg-neutral-100 transition-all"
        >
          Kembali ke Dashboard
        </RouterLink>
      </div>
    </div>

    <!-- REWARD: Tampilkan Grafik Hasil Sementara Khusus Member -->
    <div class="space-y-6">
      <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-800 pb-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-isf-red/10 text-isf-red">
              <IconChartBar class="h-4 w-4" />
            </span>
            <h2 class="font-heading text-xl font-bold text-neutral-900 dark:text-neutral-100">
              Hasil Sementara & Distribusi Opini
            </h2>
          </div>
          <p class="font-ui text-xs text-neutral-500 dark:text-neutral-400 mt-1">
            Reward khusus anggota: Anda dapat memantau pergeseran sentimen dan distribusi jawaban panel secara berkala.
          </p>
        </div>

        <div v-if="stats.total_member_respondents" class="hidden sm:flex items-center gap-2 rounded-xl bg-neutral-100 dark:bg-neutral-800 px-3 py-1.5 text-xs font-ui text-neutral-700 dark:text-neutral-300">
          <IconUsers class="h-4 w-4 text-isf-red" />
          <span><strong>{{ stats.total_member_respondents }}</strong> Responden Terverifikasi</span>
        </div>
      </div>

      <!-- Highlight Stat Card -->
      <div v-if="survey.headline_stat_value" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xs md:col-span-1 flex flex-col justify-between">
          <span class="text-xs font-ui uppercase tracking-wider text-neutral-500">Temuan Kunci Sementara</span>
          <div class="my-4">
            <span class="font-heading text-4xl sm:text-5xl font-extrabold text-isf-red">
              {{ survey.headline_stat_value }}
            </span>
            <p class="font-ui text-xs sm:text-sm text-neutral-700 dark:text-neutral-300 font-medium mt-2 leading-snug">
              {{ survey.headline_stat_label }}
            </p>
          </div>
          <span class="text-[11px] font-ui text-neutral-400">
            *Dianalisis dari sampel representatif nasional & panel member
          </span>
        </div>

        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xs md:col-span-2">
          <span class="text-xs font-ui uppercase tracking-wider text-neutral-500">Ringkasan Analisis ISF</span>
          <p class="font-body text-sm text-neutral-700 dark:text-neutral-300 mt-2 leading-relaxed">
            {{ survey.summary || 'Survei ini memetakan konsistensi arah kebijakan strategis dengan tingkat keyakinan 95% dan margin of error ±2.45%.' }}
          </p>
          <div v-if="survey.expert_analysis" class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800 text-xs text-neutral-600 dark:text-neutral-400">
            <strong class="text-neutral-900 dark:text-neutral-100">Catatan Analis:</strong>
            {{ survey.expert_analysis }}
          </div>
        </div>
      </div>

      <!-- Komponen Grafik Reusable (SurveiChart.vue) -->
      <SurveiChart
        v-if="chartData.segments && chartData.segments.length > 0"
        :chart-data="chartData"
        :survey-title="survey.title"
        :institution="survey.institution || 'Indonesia Strategy Forum'"
      />
    </div>
  </div>
</template>
