<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  IconChartBar,
  IconClock,
  IconArrowRight,
  IconCheck,
  IconEdit,
  IconFilter,
  IconBuilding,
  IconCalendar,
} from '@tabler/icons-vue'
import { getSurveiKatalog } from '@/api/services/isfMemberSurveyService'

const surveys = ref([])
const loading = ref(true)
const activeFilter = ref('semua') // semua | belum_diisi | draft | selesai

async function load() {
  loading.value = true
  try {
    surveys.value = await getSurveiKatalog()
  } catch (e) {
    console.error('Failed to load member surveys:', e)
  } finally {
    loading.value = false
  }
}

onMounted(load)

const filteredSurveys = computed(() => {
  if (activeFilter.value === 'semua') return surveys.value
  return surveys.value.filter((s) => s.member_status === activeFilter.value)
})

const counts = computed(() => {
  const all = surveys.value
  return {
    semua: all.length,
    belum_diisi: all.filter((s) => s.member_status === 'belum_diisi').length,
    draft: all.filter((s) => s.member_status === 'draft').length,
    selesai: all.filter((s) => s.member_status === 'selesai').length,
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Halaman -->
    <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-xs">
      <div class="max-w-3xl">
        <div class="flex items-center gap-2 mb-2">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-isf-red/10 px-3 py-1 font-ui text-xs font-bold uppercase tracking-wider text-isf-red">
            Area Khusus Member ISF
          </span>
        </div>
        <h1 class="font-heading text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-neutral-100">
          Katalog Survei Kebijakan & Opini Publik
        </h1>
        <p class="font-body text-sm sm:text-base text-neutral-600 dark:text-neutral-300 mt-2 leading-relaxed">
          Sebagai anggota terverifikasi Indonesia Strategy Forum, suara dan telaah Anda menjadi basis pemodelan rekomendasi kebijakan publik. Jawaban tersimpan secara aman dan dilindungi dari pemilihan ganda (satu akun, satu suara).
        </p>
      </div>

      <!-- Filter Tab Status: Belum Diisi, Sedang Diisi (Draft), Selesai -->
      <div class="mt-6 flex flex-wrap gap-2 border-t border-neutral-100 dark:border-neutral-800 pt-5">
        <button
          type="button"
          @click="activeFilter = 'semua'"
          class="rounded-xl px-4 py-2 font-ui text-xs font-semibold transition-all"
          :class="[
            activeFilter === 'semua'
              ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900'
              : 'border border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'
          ]"
        >
          Semua ({{ counts.semua }})
        </button>

        <button
          type="button"
          @click="activeFilter = 'belum_diisi'"
          class="rounded-xl px-4 py-2 font-ui text-xs font-semibold transition-all"
          :class="[
            activeFilter === 'belum_diisi'
              ? 'bg-isf-red text-white'
              : 'border border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'
          ]"
        >
          Belum Diisi ({{ counts.belum_diisi }})
        </button>

        <button
          type="button"
          @click="activeFilter = 'draft'"
          class="rounded-xl px-4 py-2 font-ui text-xs font-semibold transition-all"
          :class="[
            activeFilter === 'draft'
              ? 'bg-amber-600 text-white'
              : 'border border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'
          ]"
        >
          Sedang Diisi / Draft ({{ counts.draft }})
        </button>

        <button
          type="button"
          @click="activeFilter = 'selesai'"
          class="rounded-xl px-4 py-2 font-ui text-xs font-semibold transition-all"
          :class="[
            activeFilter === 'selesai'
              ? 'bg-emerald-600 text-white'
              : 'border border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'
          ]"
        >
          Selesai ({{ counts.selesai }})
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div v-for="i in 4" :key="i" class="h-64 animate-pulse rounded-2xl bg-neutral-100 dark:bg-neutral-800"></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="filteredSurveys.length === 0"
      class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-12 text-center"
    >
      <IconChartBar class="h-12 w-12 text-neutral-400 mx-auto mb-3" />
      <h3 class="font-heading text-lg font-bold text-neutral-800 dark:text-neutral-200">
        Tidak ada survei untuk kategori filter ini
      </h3>
      <p class="font-ui text-xs text-neutral-500 dark:text-neutral-400 mt-1">
        Silakan pilih tab filter lainnya di atas.
      </p>
    </div>

    <!-- Daftar Kartu Survei -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div
        v-for="survey in filteredSurveys"
        :key="survey.id"
        class="flex flex-col justify-between rounded-2xl border p-6 transition-all shadow-xs hover:shadow-md"
        :class="[
          survey.member_status === 'selesai'
            ? 'border-emerald-500/30 bg-emerald-50/10 dark:border-emerald-900/30 dark:bg-emerald-950/5'
            : survey.member_status === 'draft'
            ? 'border-amber-500/30 bg-amber-50/10 dark:border-amber-900/30 dark:bg-amber-950/5'
            : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900'
        ]"
      >
        <div>
          <!-- Header Card: Topik & Status Badge -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="rounded-md bg-neutral-100 dark:bg-neutral-800 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
              {{ survey.topic || 'Kebijakan Publik' }}
            </span>

            <!-- Status Badge: Belum Diisi | Sedang Diisi (Draft) | Selesai -->
            <span
              v-if="survey.member_status === 'selesai'"
              class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 font-ui text-[11px] font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
            >
              <IconCheck class="h-3.5 w-3.5" />
              Selesai
            </span>
            <span
              v-else-if="survey.member_status === 'draft'"
              class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 font-ui text-[11px] font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
            >
              <IconEdit class="h-3 w-3" />
              Sedang Diisi (Draft {{ survey.progress }}%)
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 font-ui text-[11px] font-semibold text-red-800 dark:bg-red-950/50 dark:text-red-300"
            >
              Belum Diisi
            </span>
          </div>

          <!-- Title -->
          <h2 class="font-heading text-lg font-bold text-neutral-900 dark:text-neutral-100 leading-snug line-clamp-2">
            {{ survey.title }}
          </h2>

          <!-- Editorial Intro Snippet -->
          <p class="font-body text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 mt-2.5 line-clamp-2 leading-relaxed">
            {{ survey.editorial_intro || 'Partisipasi survei panel strategis ISF untuk pemetaan persepsi kebijakan nasional.' }}
          </p>

          <!-- Metadata List -->
          <div class="mt-4 flex flex-wrap items-center gap-4 text-xs font-ui text-neutral-500 dark:text-neutral-400 border-t border-neutral-100 dark:border-neutral-800 pt-3">
            <span class="flex items-center gap-1.5">
              <IconClock class="h-3.5 w-3.5 text-neutral-400" />
              Estimasi: {{ survey.estimated_minutes }} Menit
            </span>
            <span class="flex items-center gap-1.5">
              <IconChartBar class="h-3.5 w-3.5 text-neutral-400" />
              {{ survey.total_questions }} Pertanyaan
            </span>
            <span v-if="survey.institution" class="flex items-center gap-1.5">
              <IconBuilding class="h-3.5 w-3.5 text-neutral-400" />
              {{ survey.institution }}
            </span>
          </div>
        </div>

        <!-- Footer Action -->
        <div class="mt-6 pt-4 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3">
          <div class="text-xs font-ui text-neutral-500">
            <span v-if="survey.member_status === 'selesai' && survey.completed_at">
              Diisi pada {{ survey.completed_at }}
            </span>
            <span v-else-if="survey.member_status === 'draft'">
              Tersimpan otomatis
            </span>
            <span v-else>
              Satu akun, satu suara
            </span>
          </div>

          <div>
            <!-- Tombol jika Selesai: Lihat Hasil Reward -->
            <RouterLink
              v-if="survey.member_status === 'selesai'"
              :to="`/member/survei/${survey.id}/selesai`"
              class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-600 bg-emerald-50 px-4 py-2 font-ui text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 transition-all"
            >
              <span>Lihat Hasil / Grafik</span>
              <IconArrowRight class="h-3.5 w-3.5" />
            </RouterLink>

            <!-- Tombol jika Draft: Lanjutkan Draft -->
            <RouterLink
              v-else-if="survey.member_status === 'draft'"
              :to="`/member/survei/${survey.id}`"
              class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500 bg-amber-500 px-4 py-2 font-ui text-xs font-bold text-white hover:bg-amber-600 shadow-xs transition-all"
            >
              <span>Lanjutkan Draft</span>
              <IconArrowRight class="h-3.5 w-3.5" />
            </RouterLink>

            <!-- Tombol jika Belum Diisi: Mulai Isi -->
            <RouterLink
              v-else
              :to="`/member/survei/${survey.id}`"
              class="inline-flex items-center gap-1.5 rounded-xl bg-isf-red px-4 py-2 font-ui text-xs font-bold text-white hover:bg-red-700 shadow-xs transition-all"
            >
              <span>Mulai Isi Survei</span>
              <IconArrowRight class="h-3.5 w-3.5" />
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
