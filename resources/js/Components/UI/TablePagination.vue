<script setup>
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import Select from './Select.vue'
import SelectTrigger from './SelectTrigger.vue'
import SelectValue from './SelectValue.vue'
import SelectContent from './SelectContent.vue'
import SelectItem from './SelectItem.vue'

const props = defineProps({
  currentPage: {
    type: Number,
    default: 1,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 10,
  },
  total: {
    type: Number,
    default: 0,
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 25, 50, 75, 100],
  },
  class: {
    type: String,
    default: '',
  },
})

const emit = defineEmits([
  'update:currentPage',
  'update:perPage',
  'page-change',
  'per-page-change',
])

const computedTotalPages = computed(() => {
  if (props.totalPages > 1) return props.totalPages
  if (props.total > 0) {
    return Math.max(1, Math.ceil(props.total / props.perPage))
  }
  return 1
})

const startItem = computed(() => {
  if (props.total === 0) return 0
  return (props.currentPage - 1) * props.perPage + 1
})

const endItem = computed(() => {
  return Math.min(props.total, props.currentPage * props.perPage)
})

const visiblePages = computed(() => {
  const pages = []
  const total = computedTotalPages.value
  const current = props.currentPage

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

function goToPage(page) {
  if (page >= 1 && page <= computedTotalPages.value && page !== props.currentPage) {
    emit('update:currentPage', page)
    emit('page-change', page)
  }
}

function onPerPageChange(val) {
  const size = Number(val)
  emit('update:perPage', size)
  emit('per-page-change', size)
  emit('update:currentPage', 1)
  emit('page-change', 1)
}
</script>

<template>
  <div
    :class="cn(
      'flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 sm:px-4 sm:py-3 border-t border-border bg-card/60 text-xs text-muted-foreground select-none',
      props.class
    )"
  >
    <!-- Sebelah Kiri: Show 10, 25, 50, 75, 100 -->
    <div class="flex items-center gap-2">
      <span class="text-muted-foreground font-medium">Show</span>
      <Select :model-value="String(perPage)" @update:model-value="onPerPageChange">
        <SelectTrigger class="h-8 w-18 px-2.5 py-1 text-xs font-semibold bg-background border-input shadow-2xs focus:ring-1 focus:ring-ring">
          <SelectValue :placeholder="String(perPage)" />
        </SelectTrigger>
        <SelectContent class="min-w-18">
          <SelectItem
            v-for="opt in perPageOptions"
            :key="opt"
            :value="String(opt)"
            class="text-xs font-medium cursor-pointer"
          >
            {{ opt }}
          </SelectItem>
        </SelectContent>
      </Select>
      <span class="text-muted-foreground font-medium">entries</span>
      <span v-if="total > 0" class="text-muted-foreground/75 text-[11px] ml-1">
        (Menampilkan {{ startItem }} - {{ endItem }} dari {{ total }} data)
      </span>
    </div>

    <!-- Sebelah Kanan: Pagination buttons (Previous, Page Numbers, Next) -->
    <div class="flex items-center gap-1 self-end sm:self-auto">
      <button
        type="button"
        :disabled="currentPage <= 1"
        @click="goToPage(currentPage - 1)"
        class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-md text-xs font-medium border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
        aria-label="Previous Page"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="m15 18-6-6 6-6" />
        </svg>
        <span class="hidden sm:inline">Previous</span>
      </button>

      <div class="flex items-center gap-1">
        <button
          v-for="(page, idx) in visiblePages"
          :key="idx"
          type="button"
          :disabled="page === '...'"
          @click="page !== '...' && goToPage(page)"
          :class="[
            'h-8 min-w-[32px] px-2 rounded-md text-xs font-medium transition-colors cursor-pointer',
            page === currentPage
              ? 'bg-red-700 text-white font-semibold shadow-2xs'
              : (page === '...' ? 'cursor-default text-muted-foreground' : 'border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground')
          ]"
        >
          {{ page }}
        </button>
      </div>

      <button
        type="button"
        :disabled="currentPage >= computedTotalPages"
        @click="goToPage(currentPage + 1)"
        class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-md text-xs font-medium border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
        aria-label="Next Page"
      >
        <span class="hidden sm:inline">Next</span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="m9 18 6-6-6-6" />
        </svg>
      </button>
    </div>
  </div>
</template>

