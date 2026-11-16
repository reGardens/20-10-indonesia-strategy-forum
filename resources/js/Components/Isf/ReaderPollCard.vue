<script setup>
/**
 * ReaderPollCard.vue — Widget Interaktif Jajak Pendapat Pembaca (Reader Poll).
 *
 * Menampilkan:
 * - Keterangan tegas "Survei Persepsi Non-Ilmiah"
 * - Pertanyaan polling & deskripsi latar belakang isu
 * - Pilihan jawaban interaktif (radio) bila belum voting
 * - Visualisasi persentase bar progres jelas & rincian jumlah suara bila sudah voting
 * - Pembatasan 1 suara per perangkat (localStorage + server voter hash)
 * - Layout 2-kolom editorial (Konteks & Narasi di kiri, Form/Hasil di kanan)
 */
import { ref, computed, watch, onMounted } from 'vue'
import {
  IconCheck,
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
  embedded: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['voted'])

const selectedOption = ref('')
const submitting = ref(false)
const errorMsg = ref('')
const currentPoll = ref({ ...props.poll })

const storageKey = computed(() => `isf_reader_poll_voted_${props.poll?.id || currentPoll.value?.id}`)

const hasVotedLocal = ref(false)
const votedOptionId = ref(props.poll?.voted_option_id || null)

watch(
  () => props.poll,
  (newPoll) => {
    if (newPoll) {
      currentPoll.value = { ...newPoll }
      selectedOption.value = ''
      errorMsg.value = ''
      if (typeof window !== 'undefined') {
        const key = `isf_reader_poll_voted_${newPoll.id}`
        const saved = localStorage.getItem(key)
        hasVotedLocal.value = Boolean(saved)
        votedOptionId.value = saved || newPoll.voted_option_id || null
      }
    }
  },
  { immediate: true }
)

onMounted(() => {
  if (typeof window !== 'undefined' && props.poll?.id) {
    const saved = localStorage.getItem(storageKey.value)
    if (saved) {
      hasVotedLocal.value = true
      votedOptionId.value = saved
    }
  }
})

const hasVoted = computed(() => {
  return Boolean(props.poll?.has_voted || hasVotedLocal.value || currentPoll.value?.has_voted)
})

const isClosed = computed(() => {
  return props.poll?.status !== 'aktif' || Boolean(props.poll?.is_closed)
})

const totalVotes = computed(() => {
  return currentPoll.value?.total_votes || 0
})

const options = computed(() => {
  return currentPoll.value?.options || []
})

async function handleVote() {
  const pollId = currentPoll.value?.id || props.poll?.id
  if (!selectedOption.value || submitting.value || hasVoted.value || isClosed.value || !pollId) return

  submitting.value = true
  errorMsg.value = ''

  try {
    const res = await voteReaderPoll(pollId, selectedOption.value)
    if (res.poll) {
      currentPoll.value = res.poll
    } else if (res.data?.total_votes) {
      currentPoll.value = {
        ...currentPoll.value,
        total_votes: res.data.total_votes,
        has_voted: true,
      }
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
      embedded
        ? 'w-full'
        : [
            'rounded-2xl border transition-all p-5 sm:p-7 shadow-xs',
            isDark
              ? 'bg-[#0E1726] border-neutral-800 text-white'
              : 'bg-white border-neutral-200/90 text-neutral-900'
          ]
    ]"
  >
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-start">
      <!-- ═══ KOLOM KIRI: KONTEKS & NARASI ISU (lg:col-span-5) ═══ -->
      <div class="lg:col-span-5 flex flex-col justify-between">
        <div>
          <!-- Badge Non-Ilmiah & Status Polling -->
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-amber-500/15 text-amber-300 border border-amber-500/30">
              <IconInfoCircle class="w-3.5 h-3.5 shrink-0" />
              <span>Survei Persepsi Non-Ilmiah</span>
            </span>

            <span
              :class="[
                'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-mono font-medium',
                !isClosed
                  ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                  : 'bg-neutral-700/40 text-neutral-400 border border-neutral-600/30'
              ]"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="!isClosed ? 'bg-emerald-400 animate-pulse' : 'bg-neutral-400'"></span>
              <span>{{ !isClosed ? 'Polling Aktif' : 'Polling Selesai' }}</span>
            </span>
          </div>

          <!-- Question & Context -->
          <h3
            :class="[
              'font-headline text-xl sm:text-2xl lg:text-3xl font-bold tracking-tight leading-snug',
              isDark || embedded ? 'text-white' : 'text-neutral-900'
            ]"
          >
            {{ currentPoll.question }}
          </h3>
          <p
            v-if="currentPoll.description"
            class="text-xs sm:text-sm mt-3 leading-relaxed font-space-grotesk"
            :class="isDark || embedded ? 'text-neutral-300' : 'text-neutral-600'"
          >
            {{ currentPoll.description }}
          </p>
        </div>

        <!-- Meta Info & Disclaimer -->
        <div class="mt-6 pt-5 border-t" :class="isDark || embedded ? 'border-neutral-800' : 'border-neutral-200'">
          <div class="flex items-center gap-6 text-xs font-mono">
            <div>
              <div class="text-[10px] uppercase text-neutral-400 tracking-wider">Total Partisipan</div>
              <div class="text-xl font-bold font-mono mt-0.5" :class="isDark || embedded ? 'text-white' : 'text-neutral-900'">
                {{ totalVotes }} pemilih
              </div>
            </div>
            <div>
              <div class="text-[10px] uppercase text-neutral-400 tracking-wider">Ketentuan</div>
              <div class="text-xs font-space-grotesk mt-0.5" :class="isDark || embedded ? 'text-neutral-300' : 'text-neutral-700'">
                1 Suara / Perangkat
              </div>
            </div>
          </div>
          <p class="text-[11px] text-neutral-400 italic mt-3 font-space-grotesk">
            Bukan survei acak / sampling statistik ilmiah, melainkan cerminan aspirasi pembaca ISF.
          </p>
        </div>
      </div>

      <!-- ═══ KOLOM KANAN: FORM VOTING / VISUALISASI HASIL (lg:col-span-7) ═══ -->
      <div
        class="lg:col-span-7 rounded-xl p-5 sm:p-6"
        :class="isDark || embedded ? 'bg-[#0E1726]/80 border border-neutral-800/90 shadow-md' : 'bg-neutral-50 border border-neutral-200'"
      >
        <!-- Error Alert (if any) -->
        <div
          v-if="errorMsg"
          class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2 font-space-grotesk"
        >
          <IconAlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>

        <!-- Mode 1: Voting Form (Belum Memilih & Polling Aktif) -->
        <div v-if="!hasVoted && !isClosed" class="space-y-3 font-space-grotesk">
          <div
            v-for="opt in options"
            :key="opt.id || opt.key"
            @click="selectedOption = String(opt.id || opt.key)"
            :class="[
              'flex items-center gap-3.5 p-3.5 sm:p-4 rounded-xl border transition-all cursor-pointer select-none',
              selectedOption === String(opt.id || opt.key)
                ? (isDark || embedded ? 'bg-isf-red/20 border-isf-red text-white ring-1 ring-isf-red' : 'bg-red-50 border-isf-red text-neutral-900 ring-1 ring-isf-red')
                : (isDark || embedded ? 'bg-neutral-900/60 border-neutral-800 hover:border-neutral-700 text-neutral-200' : 'bg-white border-neutral-200 hover:border-neutral-300 text-neutral-800')
            ]"
          >
            <div
              :class="[
                'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors',
                selectedOption === String(opt.id || opt.key)
                  ? 'border-isf-red bg-isf-red'
                  : (isDark || embedded ? 'border-neutral-600' : 'border-neutral-400')
              ]"
            >
              <div v-if="selectedOption === String(opt.id || opt.key)" class="w-1.5 h-1.5 rounded-full bg-white"></div>
            </div>
            <span class="text-xs sm:text-sm font-medium leading-snug flex-1">
              {{ opt.label }}
            </span>
          </div>

          <!-- Action Button -->
          <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span class="text-[11px] text-neutral-400 font-mono">
              Hasil langsung tampil setelah memilih.
            </span>
            <button
              type="button"
              :disabled="!selectedOption || submitting"
              @click="handleVote"
              :class="[
                'inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg text-xs sm:text-sm font-space-grotesk font-bold transition-all shadow-md cursor-pointer',
                selectedOption && !submitting
                  ? 'bg-isf-red text-white hover:bg-red-700 hover:shadow-lg'
                  : 'bg-neutral-700 text-neutral-400 cursor-not-allowed'
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
          <div v-if="hasVoted" class="flex items-center gap-2 text-xs font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-500/20 p-2.5 rounded-lg mb-3">
            <IconCheck class="w-4 h-4 shrink-0" />
            <span>Suara Anda telah dicatat. Berikut rekapitulasi perolehan suara terkini:</span>
          </div>
          <div v-else-if="isClosed" class="flex items-center gap-2 text-xs font-semibold text-neutral-400 bg-neutral-800/40 border border-neutral-700/30 p-2.5 rounded-lg mb-3">
            <IconLock class="w-4 h-4 shrink-0" />
            <span>Periode polling telah berakhir. Berikut rekapitulasi hasil akhir:</span>
          </div>

          <!-- Bar Results -->
          <div class="space-y-3">
            <div
              v-for="opt in options"
              :key="opt.id || opt.key"
              class="p-3.5 rounded-xl border transition-all"
              :class="[
                votedOptionId === String(opt.id || opt.key)
                  ? (isDark || embedded ? 'border-isf-red/70 bg-isf-red/10' : 'border-isf-red/50 bg-red-50/50')
                  : (isDark || embedded ? 'border-neutral-800 bg-neutral-900/60' : 'border-neutral-200/80 bg-white')
              ]"
            >
              <div class="flex items-center justify-between gap-3 text-xs sm:text-sm mb-2">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <span v-if="votedOptionId === String(opt.id || opt.key)" class="text-isf-red font-bold text-xs" title="Pilihan Anda">
                    ✓
                  </span>
                  <span
                    class="font-medium truncate"
                    :class="votedOptionId === String(opt.id || opt.key) ? 'text-isf-red font-bold' : (isDark || embedded ? 'text-neutral-200' : 'text-neutral-800')"
                  >
                    {{ opt.label }}
                  </span>
                </div>
                <div class="text-right shrink-0 font-mono flex items-center gap-2">
                  <span class="text-xs text-neutral-400 font-normal">({{ opt.votes || 0 }} suara)</span>
                  <span class="font-bold text-sm" :class="isDark || embedded ? 'text-white' : 'text-neutral-900'">
                    {{ opt.percentage || 0 }}%
                  </span>
                </div>
              </div>

              <!-- Clear Progress Bar -->
              <div class="w-full h-2.5 rounded-full overflow-hidden" :class="isDark || embedded ? 'bg-neutral-800' : 'bg-neutral-200'">
                <div
                  class="h-full rounded-full transition-all duration-700"
                  :class="votedOptionId === String(opt.id || opt.key) ? 'bg-isf-red' : (isDark || embedded ? 'bg-blue-500' : 'bg-blue-600')"
                  :style="{ width: `${opt.percentage || 0}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
