<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import {
  IconSearch,
  IconBook,
  IconScale,
  IconLink,
  IconCheck,
  IconChevronDown,
  IconChevronUp,
  IconX,
  IconArrowRight,
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
const selectedLetter = ref(props.initialLetter || 'SEMUA')
const expandedTermSlug = ref(null)
const copiedSlug = ref(null)

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

// Map terms by uppercase first letter
const availableLettersSet = computed(() => {
  const set = new Set()
  props.terms.forEach((t) => {
    const letter = (t.abjad || t.istilah?.[0] || '').toUpperCase()
    if (letter) set.add(letter)
  })
  return set
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

function selectLetter(letter) {
  selectedLetter.value = letter
}

function clearFilters() {
  searchQuery.value = ''
  selectedLetter.value = 'SEMUA'
}

function toggleExpand(slug) {
  expandedTermSlug.value = expandedTermSlug.value === slug ? null : slug
}

function handleRelatedClick(relatedTerm) {
  searchQuery.value = relatedTerm
  selectedLetter.value = 'SEMUA'
  // Auto scroll to glossary container
  const el = document.getElementById('isf-glosarium-hukum-section')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

async function copyAnchorLink(slug) {
  const url = `${window.location.origin}${window.location.pathname}#istilah-${slug}`
  try {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(url)
    } else {
      const ta = document.createElement('textarea')
      ta.value = url
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
    copiedSlug.value = slug
    setTimeout(() => {
      if (copiedSlug.value === slug) copiedSlug.value = null
    }, 2000)
  } catch {
    // fallback
  }
}

// On mount: check window.location.hash to open anchor term
onMounted(() => {
  if (typeof window !== 'undefined' && window.location.hash) {
    const hash = window.location.hash.replace('#istilah-', '').replace('#', '')
    if (hash) {
      const found = props.terms.find((t) => t.slug === hash)
      if (found) {
        expandedTermSlug.value = found.slug
        selectedLetter.value = (found.abjad || found.istilah?.[0] || '').toUpperCase()
        setTimeout(() => {
          const el = document.getElementById(`istilah-${found.slug}`)
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }, 300)
      }
    }
  }
})
</script>

<template>
  <div
    id="isf-glosarium-hukum-section"
    :class="[
      'rounded-xl border transition-colors',
      isDark
        ? 'bg-[#0B1220] border-neutral-800 text-white'
        : 'bg-white border-neutral-200/80 text-neutral-900 shadow-xs'
    ]"
  >
    <!-- Header Box & Search Bar -->
    <div class="p-4 sm:p-6 border-b" :class="isDark ? 'border-neutral-800/80' : 'border-neutral-200/80'">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded text-[11px] font-space-grotesk font-bold uppercase tracking-wider bg-isf-red/10 text-isf-red border border-isf-red/20 mb-2">
            <IconBook class="w-3.5 h-3.5 shrink-0" />
            <span>Kamus Istilah Interaktif</span>
          </div>
          <h3 class="font-headline text-xl sm:text-2xl font-bold tracking-tight">
            Glosarium Hukum Nasional
          </h3>
          <p class="text-xs sm:text-sm mt-1" :class="isDark ? 'text-neutral-400' : 'text-neutral-600'">
            Panduan terminologi yuridis, asas hukum, doktrin peradilan, dan rujukan undang-undang resmi.
          </p>
        </div>

        <!-- Search Input -->
        <div class="w-full md:w-80 shrink-0">
          <div class="relative flex items-center">
            <IconSearch
              class="w-4 h-4 absolute left-3 pointer-events-none"
              :class="isDark ? 'text-neutral-500' : 'text-neutral-400'"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari istilah, asas, atau UU..."
              aria-label="Cari istilah glosarium hukum"
              class="w-full pl-9 pr-9 py-2 rounded-lg text-xs sm:text-sm font-space-grotesk border transition-all outline-none focus:ring-2 focus:ring-isf-red"
              :class="[
                isDark
                  ? 'bg-neutral-900/90 border-neutral-700 text-white placeholder-neutral-500 focus:border-isf-red'
                  : 'bg-neutral-50 border-neutral-300 text-neutral-900 placeholder-neutral-400 focus:bg-white focus:border-isf-red'
              ]"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              aria-label="Bersihkan pencarian"
              class="absolute right-2.5 p-1 rounded-full text-neutral-400 hover:text-white transition-colors"
            >
              <IconX class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Alphabet Filter Bar A - Z -->
      <div class="mt-4 pt-3 border-t" :class="isDark ? 'border-neutral-800/80' : 'border-neutral-100'">
        <div class="flex items-center gap-1 overflow-x-auto isf-no-scrollbar pb-1">
          <button
            type="button"
            @click="selectLetter('SEMUA')"
            :class="[
              'px-2.5 py-1 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
              selectedLetter === 'SEMUA'
                ? 'bg-isf-red text-white shadow-xs'
                : isDark
                  ? 'bg-neutral-900/60 text-neutral-400 hover:text-white hover:bg-neutral-800'
                  : 'bg-neutral-100 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-200'
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
              'w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded text-xs font-mono font-bold transition-all shrink-0',
              selectedLetter === char
                ? 'bg-isf-red text-white shadow-xs ring-2 ring-isf-red/30'
                : availableLettersSet.has(char)
                  ? isDark
                    ? 'bg-neutral-900/80 text-neutral-200 hover:text-white hover:bg-neutral-800 cursor-pointer'
                    : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200 cursor-pointer'
                  : isDark
                    ? 'bg-transparent text-neutral-700 cursor-not-allowed'
                    : 'bg-transparent text-neutral-300 cursor-not-allowed'
            ]"
            :title="availableLettersSet.has(char) ? `Filter huruf ${char}` : `Belum ada istilah dengan awalan ${char}`"
          >
            {{ char }}
          </button>
        </div>
      </div>
    </div>

    <!-- Active Filter Info (if filtered) -->
    <div
      v-if="searchQuery || (selectedLetter && selectedLetter !== 'SEMUA')"
      class="px-4 sm:px-6 py-2.5 flex items-center justify-between text-xs font-space-grotesk border-b"
      :class="isDark ? 'bg-neutral-900/40 border-neutral-800/80 text-neutral-300' : 'bg-neutral-50/80 border-neutral-200/60 text-neutral-600'"
    >
      <div class="flex items-center gap-2 truncate">
        <span>Menampilkan <strong>{{ filteredTerms.length }}</strong> istilah</span>
        <span v-if="selectedLetter !== 'SEMUA'" class="inline-flex items-center gap-1 font-mono text-[11px] px-1.5 py-0.5 rounded bg-isf-red/10 text-isf-red font-bold">
          Huruf {{ selectedLetter }}
        </span>
        <span v-if="searchQuery" class="inline-flex items-center gap-1 text-[11px] px-1.5 py-0.5 rounded bg-neutral-500/10 text-neutral-400">
          "{{ searchQuery }}"
        </span>
      </div>
      <button
        type="button"
        @click="clearFilters"
        class="text-[11px] font-semibold text-isf-red hover:underline shrink-0 ml-2"
      >
        Reset Filter
      </button>
    </div>

    <!-- Terms List Container -->
    <div class="p-4 sm:p-6">
      <div v-if="filteredTerms.length === 0" class="py-12 text-center">
        <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-3 bg-neutral-500/10 text-neutral-400">
          <IconBook class="w-6 h-6" />
        </div>
        <p class="font-headline text-base font-bold" :class="isDark ? 'text-white' : 'text-neutral-900'">
          Istilah tidak ditemukan
        </p>
        <p class="text-xs mt-1" :class="isDark ? 'text-neutral-400' : 'text-neutral-600'">
          Tidak ada istilah yang cocok dengan kriteria pencarian atau huruf yang dipilih.
        </p>
        <button
          type="button"
          @click="clearFilters"
          class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-space-grotesk font-semibold bg-isf-red text-white shadow-xs hover:bg-red-700 transition-colors"
        >
          Lihat Semua Istilah
        </button>
      </div>

      <!-- Accordion Grid / List -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
        <div
          v-for="term in filteredTerms"
          :key="term.id || term.slug"
          :id="`istilah-${term.slug}`"
          :class="[
            'rounded-lg border transition-all duration-200 overflow-hidden',
            expandedTermSlug === term.slug
              ? isDark
                ? 'bg-neutral-900/90 border-isf-red/70 shadow-md'
                : 'bg-white border-isf-red/60 shadow-md ring-1 ring-isf-red/10'
              : isDark
                ? 'bg-neutral-900/40 border-neutral-800 hover:border-neutral-700'
                : 'bg-neutral-50/60 border-neutral-200/80 hover:border-neutral-300'
          ]"
        >
          <!-- Term Header (Always Clickable) -->
          <div
            @click="toggleExpand(term.slug)"
            class="p-3.5 sm:p-4 cursor-pointer flex items-start justify-between gap-3 select-none"
          >
            <div class="min-w-0 flex items-start gap-2.5">
              <span
                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-mono font-bold text-xs shrink-0 shadow-2xs"
                :class="isDark ? 'bg-neutral-800 text-amber-400 border border-neutral-700' : 'bg-amber-50 text-amber-700 border border-amber-200/60'"
              >
                {{ (term.abjad || term.istilah?.[0] || 'A').toUpperCase() }}
              </span>
              <div class="min-w-0">
                <h4 class="font-space-grotesk font-bold text-sm sm:text-base tracking-tight leading-snug truncate" :class="isDark ? 'text-white' : 'text-neutral-900'">
                  {{ term.istilah }}
                </h4>
                <p
                  v-if="expandedTermSlug !== term.slug"
                  class="text-xs mt-1 line-clamp-2 leading-relaxed"
                  :class="isDark ? 'text-neutral-400' : 'text-neutral-600'"
                >
                  {{ term.definisi }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-1 shrink-0 mt-0.5">
              <button
                type="button"
                @click.stop="copyAnchorLink(term.slug)"
                :title="copiedSlug === term.slug ? 'Tautan disalin!' : 'Salin tautan langsung ke istilah ini'"
                class="p-1 rounded text-neutral-400 hover:text-white transition-colors cursor-pointer"
              >
                <IconCheck v-if="copiedSlug === term.slug" class="w-4 h-4 text-emerald-400" />
                <IconLink v-else class="w-4 h-4 hover:text-isf-red" />
              </button>
              <component
                :is="expandedTermSlug === term.slug ? IconChevronUp : IconChevronDown"
                class="w-4 h-4 text-neutral-400"
              />
            </div>
          </div>

          <!-- Expanded Body Details -->
          <div
            v-if="expandedTermSlug === term.slug"
            class="px-3.5 sm:px-4 pb-4 pt-1 border-t transition-all font-space-grotesk"
            :class="isDark ? 'border-neutral-800/80 bg-neutral-950/40' : 'border-neutral-100 bg-neutral-50/50'"
          >
            <!-- Full Definition -->
            <div class="mt-2 text-xs sm:text-sm leading-relaxed" :class="isDark ? 'text-neutral-200' : 'text-neutral-700'">
              {{ term.definisi }}
            </div>

            <!-- Legal Basis (Dasar Hukum) -->
            <div v-if="term.dasar_hukum" class="mt-3 p-2.5 rounded-lg border text-xs flex items-start gap-2" :class="isDark ? 'bg-neutral-900 border-neutral-800 text-neutral-300' : 'bg-white border-neutral-200 text-neutral-800'">
              <IconScale class="w-4 h-4 text-isf-red shrink-0 mt-0.5" />
              <div>
                <span class="block font-bold text-[10px] uppercase tracking-wider text-isf-red">Dasar Hukum & Rujukan:</span>
                <span class="leading-normal">{{ term.dasar_hukum }}</span>
              </div>
            </div>

            <!-- Related Terms (Istilah Terkait) -->
            <div v-if="term.istilah_terkait && term.istilah_terkait.length > 0" class="mt-3 flex items-center flex-wrap gap-1.5">
              <span class="text-[11px] font-semibold text-neutral-400 mr-1">Istilah Terkait:</span>
              <button
                v-for="rel in term.istilah_terkait"
                :key="rel"
                type="button"
                @click="handleRelatedClick(rel)"
                class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border transition-all cursor-pointer"
                :class="isDark ? 'bg-neutral-800/80 border-neutral-700 text-neutral-300 hover:border-isf-red hover:text-white' : 'bg-neutral-100 border-neutral-200 text-neutral-700 hover:border-isf-red hover:text-isf-red'"
              >
                <span>{{ rel }}</span>
                <IconArrowRight class="w-3 h-3 opacity-60" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.isf-no-scrollbar::-webkit-scrollbar {
  display: none;
}
.isf-no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

