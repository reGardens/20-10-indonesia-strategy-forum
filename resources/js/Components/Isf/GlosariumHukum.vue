<script setup>
/**
 * GlosariumHukum.vue — Kamus Terminologi & Asas Hukum Interaktif
 *
 * Mengimplementasikan:
 * - Toolbar pencarian instan dan filter A-Z responsif.
 * - Tampilan default 4 kartu utama dengan permainan warna identitas ISF (Navy, Crimson Red, Amber, Slate).
 * - Konten lengkap muncul otomatis ketika abjad diklik atau dilakukan pencarian.
 * - CTA link jangkar dihapus sesuai instruksi.
 */
import { computed, ref } from 'vue'
import {
  IconSearch,
  IconBook,
  IconScale,
  IconX,
  IconArrowRight,
  IconInfoCircle,
} from '@tabler/icons-vue'

const props = defineProps({
  /** Array of legal glossary terms: { id, istilah, slug, abjad, definisi, dasar_hukum, istilah_terkait } */
  terms: {
    type: Array,
    default: () => [],
  },
  /** Active selected letter (optional sync) */
  initialLetter: {
    type: String,
    default: '',
  },
  /** Dark mode / Navy container styling */
  isDark: {
    type: Boolean,
    default: false,
  },
})

const searchQuery = ref('')
const selectedLetter = ref(props.initialLetter || '')

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

// Palet warna identitas ISF untuk memperkaya tampilan kartu glosarium agar tidak polos
const cardThemes = [
  {
    border: 'border-t-4 border-t-isf-red',
    badge: 'bg-isf-red text-white shadow-xs',
    kicker: 'text-isf-red',
    iconColor: 'text-isf-red',
    titleHover: 'group-hover:text-isf-red',
  },
  {
    border: 'border-t-4 border-t-[#0B1220]',
    badge: 'bg-[#0B1220] text-amber-400 shadow-xs border border-neutral-700',
    kicker: 'text-[#0B1220] dark:text-amber-400',
    iconColor: 'text-[#0B1220] dark:text-amber-400',
    titleHover: 'group-hover:text-amber-700 dark:group-hover:text-amber-400',
  },
  {
    border: 'border-t-4 border-t-amber-600',
    badge: 'bg-amber-600 text-white shadow-xs',
    kicker: 'text-amber-600',
    iconColor: 'text-amber-600',
    titleHover: 'group-hover:text-amber-700',
  },
  {
    border: 'border-t-4 border-t-slate-700',
    badge: 'bg-slate-800 text-white shadow-xs',
    kicker: 'text-slate-700 dark:text-slate-300',
    iconColor: 'text-slate-700 dark:text-slate-300',
    titleHover: 'group-hover:text-slate-900',
  },
]

function getTheme(idx) {
  return cardThemes[idx % cardThemes.length]
}

// Map terms by uppercase first letter
const availableLettersSet = computed(() => {
  const set = new Set()
  props.terms.forEach((t) => {
    const letter = (t.abjad || t.istilah?.[0] || '').toUpperCase()
    if (letter) set.add(letter)
  })
  return set
})

// Whether active filtering is engaged
const isFiltering = computed(() => {
  return Boolean(
    searchQuery.value.trim() ||
    (selectedLetter.value && selectedLetter.value !== '' && selectedLetter.value !== 'SEMUA')
  )
})

// Filtered terms based on letter and search query
const filteredTerms = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  const letter = selectedLetter.value

  return props.terms.filter((term) => {
    // Filter letter
    if (letter && letter !== 'SEMUA') {
      const termLetter = (term.abjad || term.istilah?.[0] || '').toUpperCase()
      if (termLetter !== letter) return false
    }

    // Filter search query
    if (q) {
      const name = (term.istilah || '').toLowerCase()
      const def = (term.definisi || '').toLowerCase()
      const law = (term.dasar_hukum || '').toLowerCase()
      const related = Array.isArray(term.istilah_terkait)
        ? term.istilah_terkait.join(' ').toLowerCase()
        : ''
      return (
        name.includes(q) ||
        def.includes(q) ||
        law.includes(q) ||
        related.includes(q)
      )
    }

    return true
  })
})

// Displayed terms: default 4 cards if not filtering, otherwise all filtered terms
const displayedTerms = computed(() => {
  if (!isFiltering.value) {
    return props.terms.slice(0, 4)
  }
  return filteredTerms.value
})

function selectLetter(letter) {
  if (selectedLetter.value === letter) {
    selectedLetter.value = ''
  } else {
    selectedLetter.value = letter
  }
}

function clearFilters() {
  searchQuery.value = ''
  selectedLetter.value = ''
}

function handleRelatedClick(relatedTerm) {
  searchQuery.value = relatedTerm
  selectedLetter.value = ''
  const el = document.getElementById('isf-glosarium-hukum-section')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
  <div id="isf-glosarium-hukum-section" class="space-y-5">
    <!-- Screen-reader accessible title -->
    <div class="sr-only">
      <h3>Glosarium Hukum Nasional</h3>
    </div>

    <!-- 1. Sleek Search & Alphabet Toolbar -->
    <div class="space-y-3.5">
      <!-- Search Input Bar -->
      <div class="relative w-full">
        <IconSearch
          class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
          :class="isDark ? 'text-neutral-500' : 'text-neutral-400'"
        />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari istilah, asas hukum, atau pasal rujukan (misal: Kasasi, Inkracht, Actus Reus, KUHP)..."
          aria-label="Cari istilah glosarium hukum"
          class="w-full pl-10 pr-10 py-2.5 rounded-lg text-xs sm:text-sm font-space-grotesk border transition-all outline-none focus:ring-2 focus:ring-isf-red/20 focus:border-isf-red"
          :class="[
            isDark
              ? 'bg-neutral-900 border-neutral-700 text-white placeholder-neutral-500 focus:bg-neutral-950'
              : 'bg-neutral-50 border-neutral-300 text-neutral-900 placeholder-neutral-400 focus:bg-white'
          ]"
        />
        <button
          v-if="searchQuery"
          type="button"
          @click="searchQuery = ''"
          aria-label="Bersihkan pencarian"
          class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-white transition-colors cursor-pointer"
        >
          <IconX class="w-4 h-4" />
        </button>
      </div>

      <!-- Alphabet Filter Bar A–Z -->
      <div class="flex items-center gap-1.5 overflow-x-auto isf-no-scrollbar pb-1">
        <button
          type="button"
          @click="clearFilters"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
            !selectedLetter
              ? 'bg-isf-navy text-white shadow-xs ring-1 ring-neutral-700'
              : isDark
                ? 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700'
                : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
          ]"
        >
          Semua ({{ terms.length }})
        </button>

        <button
          v-for="char in alphabet"
          :key="char"
          type="button"
          :disabled="!availableLettersSet.has(char)"
          @click="selectLetter(char)"
          :class="[
            'w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg text-xs font-mono font-bold transition-all shrink-0',
            selectedLetter === char
              ? 'bg-isf-red text-white shadow-xs ring-2 ring-isf-red/30'
              : availableLettersSet.has(char)
                ? isDark
                  ? 'bg-neutral-800 text-neutral-200 hover:bg-neutral-700 cursor-pointer'
                  : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200 cursor-pointer'
                : isDark
                  ? 'opacity-25 text-neutral-600 cursor-not-allowed'
                  : 'opacity-30 text-neutral-400 cursor-not-allowed'
          ]"
          :title="availableLettersSet.has(char) ? `Filter huruf ${char}` : `Belum ada istilah dengan awalan ${char}`"
        >
          {{ char }}
        </button>
      </div>
    </div>

    <!-- Info Banner Default State (Sebelum Filter / Search Digunakan) -->
    <div
      v-if="!isFiltering"
      class="flex items-center justify-between py-2 px-3.5 rounded-lg text-xs font-space-grotesk border"
      :class="isDark ? 'bg-neutral-900 border-neutral-800 text-neutral-300' : 'bg-amber-50/70 border-amber-200/80 text-amber-800'"
    >
      <div class="flex items-center gap-2">
        <IconInfoCircle class="w-4 h-4 shrink-0 text-amber-600" />
        <span>Menampilkan <strong>4 istilah utama</strong> sebagai ringkasan awal. Klik huruf abjad A–Z atau ketik di pencarian untuk membuka seluruh kamus.</span>
      </div>
    </div>

    <!-- Active Filter Status Chip (jika sedang memfilter) -->
    <div
      v-else
      class="flex items-center justify-between py-2 px-3.5 rounded-lg text-xs font-space-grotesk border"
      :class="isDark ? 'bg-neutral-900 border-neutral-800 text-neutral-300' : 'bg-neutral-50 border-neutral-200 text-neutral-600'"
    >
      <div class="flex items-center gap-2 truncate">
        <span>Menemukan <strong>{{ filteredTerms.length }}</strong> istilah</span>
        <span v-if="selectedLetter && selectedLetter !== 'SEMUA'" class="px-2 py-0.5 rounded bg-isf-red text-white font-mono text-[10px] font-bold">
          Huruf {{ selectedLetter }}
        </span>
        <span v-if="searchQuery" class="px-2 py-0.5 rounded text-[11px]" :class="isDark ? 'bg-neutral-800' : 'bg-neutral-200'">
          "{{ searchQuery }}"
        </span>
      </div>
      <button
        type="button"
        @click="clearFilters"
        class="text-xs font-bold text-isf-red hover:underline shrink-0 ml-2 cursor-pointer"
      >
        Reset Filter
      </button>
    </div>

    <!-- 2. Empty State -->
    <div
      v-if="displayedTerms.length === 0"
      class="py-12 text-center rounded-xl border border-dashed p-8"
      :class="isDark ? 'border-neutral-800' : 'border-neutral-300'"
    >
      <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-3" :class="isDark ? 'bg-neutral-800 text-neutral-400' : 'bg-neutral-100 text-neutral-400'">
        <IconBook class="w-6 h-6" />
      </div>
      <p class="font-headline text-base font-bold" :class="isDark ? 'text-white' : 'text-neutral-900'">
        Istilah Tidak Ditemukan
      </p>
      <p class="text-xs mt-1 max-w-sm mx-auto" :class="isDark ? 'text-neutral-400' : 'text-neutral-500'">
        Tidak ada istilah yang cocok dengan kriteria pencarian atau huruf yang dipilih.
      </p>
      <button
        type="button"
        @click="clearFilters"
        class="mt-4 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-isf-red text-white hover:bg-red-700 transition-colors cursor-pointer"
      >
        Kembali ke Ringkasan Utama
      </button>
    </div>

    <!-- 3. Clean Definition Cards Grid dengan Permainan Warna Identitas ISF -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="(term, idx) in displayedTerms"
        :key="term.id || term.slug || idx"
        :id="`istilah-${term.slug}`"
        class="rounded-xl border p-4 sm:p-5 flex flex-col justify-between transition-all duration-200 group shadow-xs hover:shadow-md"
        :class="[
          getTheme(idx).border,
          isDark
            ? 'bg-neutral-900/80 border-neutral-800 hover:border-neutral-700 hover:bg-neutral-900'
            : 'bg-white border-neutral-200/90 hover:border-neutral-300'
        ]"
      >
        <div>
          <!-- Header: Huruf Badge + Istilah (Tanpa CTA copy link) -->
          <div class="flex items-start gap-3 mb-2.5">
            <span
              class="w-7 h-7 rounded-lg flex items-center justify-center font-mono font-bold text-xs shrink-0"
              :class="getTheme(idx).badge"
            >
              {{ (term.abjad || term.istilah?.[0] || 'A').toUpperCase() }}
            </span>
            <div class="min-w-0 flex-1">
              <h4
                class="font-space-grotesk font-bold text-sm sm:text-base tracking-tight leading-snug transition-colors"
                :class="[isDark ? 'text-white' : 'text-neutral-900', getTheme(idx).titleHover]"
              >
                {{ term.istilah }}
              </h4>
            </div>
          </div>

          <!-- Definisi -->
          <p
            class="text-xs sm:text-sm font-space-grotesk leading-relaxed mt-2"
            :class="isDark ? 'text-neutral-300' : 'text-neutral-600'"
          >
            {{ term.definisi }}
          </p>

          <!-- Dasar Hukum & Rujukan Resmi -->
          <div
            v-if="term.dasar_hukum"
            class="mt-3 pt-2.5 border-t flex items-start gap-2 text-xs font-space-grotesk"
            :class="isDark ? 'border-neutral-800 text-neutral-400' : 'border-neutral-100 text-neutral-500'"
          >
            <IconScale class="w-3.5 h-3.5 shrink-0 mt-0.5" :class="getTheme(idx).iconColor" />
            <span class="text-[11px] leading-relaxed">
              <strong :class="isDark ? 'text-neutral-200' : 'text-neutral-700'">Dasar Hukum:</strong> {{ term.dasar_hukum }}
            </span>
          </div>
        </div>

        <!-- Istilah Terkait (Jika Ada) -->
        <div
          v-if="term.istilah_terkait && term.istilah_terkait.length > 0"
          class="mt-3 pt-2.5 border-t flex items-center flex-wrap gap-1.5"
          :class="isDark ? 'border-neutral-800' : 'border-neutral-100'"
        >
          <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-neutral-400 mr-1">Terkait:</span>
          <button
            v-for="rel in term.istilah_terkait"
            :key="rel"
            type="button"
            @click="handleRelatedClick(rel)"
            class="inline-flex items-center gap-1 text-[11px] font-space-grotesk font-medium px-2 py-0.5 rounded-md transition-colors cursor-pointer"
            :class="[
              isDark
                ? 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700 hover:text-white'
                : 'bg-neutral-100 text-neutral-700 hover:bg-isf-red/10 hover:text-isf-red'
            ]"
          >
            <span>{{ rel }}</span>
            <IconArrowRight class="w-3 h-3 opacity-50" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
