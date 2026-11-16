<script setup>
/**
 * ReaderPollCard.vue — Widget Interaktif Reader Poll (Polling Opini Pembaca).
 *
 * Menampilkan:
 * - Keterangan tegas "Jajak Pendapat Non-Ilmiah"
 * - Pertanyaan polling & deskripsi latar belakang isu
 * - Pilihan jawaban interaktif (radio) bila belum voting
 * - Visualisasi persentase bar progres & rincian jumlah suara bila sudah voting
 * - Pembatasan 1 suara per perangkat (localStorage + server voter hash)
 */
import { ref, computed, onMounted } from 'vue'
import {
  IconCheck,
  IconChartPie,
  IconClock,
  IconAlertCircle,
  IconLock,
  IconSend,
  IconInfoCircle,
} from '@tabler/icons-vue'
import { voteReaderPoll } from '@/api/services/isfPublicService'

const props = defineProps({
  poll: {
    type: Object,
    required: true,
  },
  isDark: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['voted'])

const selectedOption = ref('')
const submitting = ref(false)
const errorMsg = ref('')
const currentPoll = ref({ ...props.poll })

const storageKey = computed(() => `isf_reader_poll_voted_${props.poll.id}`)

const hasVotedLocal = ref(false)
const votedOptionId = ref(props.poll.voted_option_id || null)

onMounted(() => {
  if (typeof window !== 'undefined') {
    const saved = localStorage.getItem(storageKey.value)
    if (saved) {
      hasVotedLocal.value = true
      votedOptionId.value = saved
    }
  }
})

const hasVoted = computed(() => {
  return Boolean(props.poll.has_voted || hasVotedLocal.value || currentPoll.value.has_voted)
})

const isClosed = computed(() => {
  return props.poll.status !== 'aktif' || Boolean(props.poll.is_closed)
})

const totalVotes = computed(() => {
  return currentPoll.value.total_votes || 0
})

const options = computed(() => {
  return currentPoll.value.options || []
})

async function handleVote() {
  if (!selectedOption.value || submitting.value || hasVoted.value || isClosed.value) return

  submitting.value = true
  errorMsg.value = ''

  try {
    const res = await voteReaderPoll(props.poll.id, selectedOption.value)
    if (res.poll) {
      currentPoll.value = res.poll
    }
    hasVotedLocal.value = true
    votedOptionId.value = selectedOption.value
    if (typeof window !== 'undefined') {
      localStorage.setItem(storageKey.value, selectedOption.value)
    }
    emit('voted', currentPoll.value)
  } catch (err) {
    if (err.response?.status === 422) {
      errorMsg.value = err.response.data?.message || 'Anda sudah memberikan suara pada polling ini.'
      hasVotedLocal.value = true
      if (err.response.data?.poll) {
        currentPoll.value = err.response.data.poll
      }
    } else {
      errorMsg.value = 'Gagal mengirim suara. Silakan coba beberapa saat lagi.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div
    :class="[
      'rounded-2xl border transition-all p-5 sm:p-7 shadow-xs',
      isDark
        ? 'bg-[#0E1726] border-neutral-800 text-white'
        : 'bg-white border-neutral-200/90 text-neutral-900'
    ]"
  >
    <!-- Top Header: Badge Non-Ilmiah & Status -->
    <div class="flex flex-wrap items-center justify-between gap-2.5 mb-4 pb-3 border-b" :class="isDark ? 'border-neutral-800' : 'border-neutral-100'">
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
          <IconInfoCircle class="w-3.5 h-3.5 shrink-0" />
          <span>Survei Persepsi Non-Ilmiah</span>
        </span>
      </div>

      <div class="flex items-center gap-2 text-xs font-mono font-medium" :class="isDark ? 'text-neutral-400' : 'text-neutral-500'">
        <span
          :class="[
            'inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider',
            !isClosed
              ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-400 border border-emerald-600/30'
              : 'bg-neutral-600/15 text-neutral-600 dark:text-neutral-400 border border-neutral-600/30'
          ]"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="!isClosed ? 'bg-emerald-500 animate-pulse' : 'bg-neutral-400'"></span>
          <span>{{ !isClosed ? 'Polling Aktif' : 'Polling Selesai' }}</span>
        </span>
        <span>•</span>
        <span>{{ totalVotes }} suara</span>
      </div>
    </div>

    <!-- Question & Context -->
    <div class="mb-6">
      <h3
        :class="[
          'font-headline text-lg sm:text-xl md:text-2xl font-bold tracking-tight leading-snug',
          isDark ? 'text-white' : 'text-neutral-900'
        ]"
      >
        {{ currentPoll.question }}
      </h3>
      <p
        v-if="currentPoll.description"
        class="text-xs sm:text-sm mt-2 leading-relaxed font-space-grotesk"
        :class="isDark ? 'text-neutral-400' : 'text-neutral-600'"
      >
        {{ currentPoll.description }}
      </p>
    </div>

    <!-- Error Alert (if any) -->
    <div
      v-if="errorMsg"
      class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 text-xs flex items-center gap-2 font-space-grotesk"
    >
      <IconAlertCircle class="w-4 h-4 shrink-0" />
      <span>{{ errorMsg }}</span>
    </div>

    <!-- Mode 1: Voting Form (Belum Memilih & Polling Aktif) -->
    <div v-if="!hasVoted && !isClosed" class="space-y-3 font-space-grotesk">
      <div
        v-for="opt in options"
        :key="opt.id"
        @click="selectedOption = String(opt.id)"
        :class="[
          'flex items-center gap-3.5 p-3 sm:p-3.5 rounded-xl border transition-all cursor-pointer select-none',
          selectedOption === String(opt.id)
            ? (isDark ? 'bg-isf-red/20 border-isf-red text-white' : 'bg-red-50 border-isf-red text-neutral-900')
            : (isDark ? 'bg-neutral-900/60 border-neutral-800 hover:border-neutral-700 text-neutral-200' : 'bg-neutral-50/70 border-neutral-200 hover:border-neutral-300 text-neutral-800')
        ]"
      >
        <div
          :class="[
            'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors',
            selectedOption === String(opt.id)
              ? 'border-isf-red bg-isf-red'
              : (isDark ? 'border-neutral-600' : 'border-neutral-400')
          ]"
        >
          <div v-if="selectedOption === String(opt.id)" class="w-1.5 h-1.5 rounded-full bg-white"></div>
        </div>
        <span class="text-xs sm:text-sm font-medium leading-snug flex-1">
          {{ opt.label }}
        </span>
      </div>

      <!-- Action Button -->
      <div class="pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-[11px] text-neutral-500 font-mono">
          1 suara per perangkat. Hasil tampil seketika setelah memilih.
        </p>
        <button
          type="button"
          :disabled="!selectedOption || submitting"
          @click="handleVote"
          :class="[
            'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-space-grotesk font-bold transition-all shadow-xs cursor-pointer',
            selectedOption && !submitting
              ? 'bg-isf-red text-white hover:bg-red-700 hover:shadow-sm'
              : 'bg-neutral-300 text-neutral-500 cursor-not-allowed dark:bg-neutral-800 dark:text-neutral-600'
          ]"
        >
          <IconSend v-if="!submitting" class="w-4 h-4" />
          <span v-else class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ submitting ? 'Mencatat Suara...' : 'Kirim Suara Anda' }}</span>
        </button>
      </div>
    </div>

    <!-- Mode 2: Results Display (Sudah Memilih atau Polling Selesai) -->
    <div v-else class="space-y-4 font-space-grotesk">
      <div v-if="hasVoted" class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-2">
        <IconCheck class="w-4 h-4" />
        <span>Suara Anda telah tercatat. Berikut rekapitulasi hasil polling publik saat ini:</span>
      </div>
      <div v-else-if="isClosed" class="flex items-center gap-2 text-xs font-semibold text-neutral-500 mb-2">
        <IconLock class="w-4 h-4" />
        <span>Periode polling telah berakhir. Berikut hasil akhir aspirasi publik:</span>
      </div>

      <!-- Bar Results -->
      <div class="space-y-3">
        <div
          v-for="opt in options"
          :key="opt.id"
          class="p-3 rounded-xl border relative overflow-hidden transition-all"
          :class="[
            votedOptionId === String(opt.id)
              ? (isDark ? 'border-isf-red/70 bg-isf-red/10' : 'border-isf-red/50 bg-red-50/50')
              : (isDark ? 'border-neutral-800 bg-neutral-900/40' : 'border-neutral-200/80 bg-neutral-50/40')
          ]"
        >
          <!-- Background progress fill -->
          <div
            class="absolute top-0 bottom-0 left-0 transition-all duration-700 opacity-20"
            :class="votedOptionId === String(opt.id) ? 'bg-isf-red' : (isDark ? 'bg-blue-500' : 'bg-neutral-500')"
            :style="{ width: `${opt.percentage || 0}%` }"
          ></div>

          <div class="relative flex items-center justify-between gap-3 text-xs sm:text-sm">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <span v-if="votedOptionId === String(opt.id)" class="text-isf-red font-bold text-xs" title="Pilihan Anda">
                ✓
              </span>
              <span
                class="font-medium truncate"
                :class="votedOptionId === String(opt.id) ? 'text-isf-red font-bold' : (isDark ? 'text-neutral-200' : 'text-neutral-800')"
              >
                {{ opt.label }}
              </span>
            </div>
            <div class="text-right shrink-0 font-mono flex items-center gap-2">
              <span class="text-xs text-neutral-500 font-normal">({{ opt.votes || 0 }} suara)</span>
              <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-neutral-900'">
                {{ opt.percentage || 0 }}%
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-3 border-t flex items-center justify-between text-xs text-neutral-500 font-mono" :class="isDark ? 'border-neutral-800' : 'border-neutral-100'">
        <span>Total partisipan: {{ totalVotes }} pemilih</span>
        <span class="text-[11px] italic">Bukan metode survei acak / sampling ilmiah</span>
      </div>
    </div>
  </div>
</template>

