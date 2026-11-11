<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

const props = defineProps({
  startDate: { type: String, default: '' },
  endDate: { type: String, default: '' },
  occupiedRanges: { type: Array, default: () => [] },
  isBody: { type: Boolean, default: true },
  error: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih rentang tanggal penayangan...' }
})

const emit = defineEmits(['update:startDate', 'update:endDate'])

const pickerRef = ref(null)
const isOpen = ref(false)
const activeStep = ref('start') // 'start' | 'end'
const viewDate = ref(new Date())

function initViewDate() {
  if (props.startDate) {
    const d = new Date(props.startDate)
    if (!isNaN(d.getTime())) {
      viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1)
      return
    }
  }
  const today = new Date()
  viewDate.value = new Date(today.getFullYear(), today.getMonth(), 1)
}

initViewDate()

watch(
  () => props.startDate,
  (val) => {
    if (val && !isOpen.value) {
      const d = new Date(val)
      if (!isNaN(d.getTime())) {
        viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1)
      }
    }
  }
)

const currentYear = computed(() => viewDate.value.getFullYear())
const currentMonth = computed(() => viewDate.value.getMonth())

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const currentMonthName = computed(() => monthNames[currentMonth.value])

function prevMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
}

function nextMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
}

function formatDateShort(dateStr) {
  if (!dateStr) return '-'
  try {
    const parts = dateStr.split('-')
    if (parts.length === 3) {
      const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    }
    return dateStr
  } catch {
    return dateStr
  }
}

function toIso(year, month, day) {
  const m = String(month + 1).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return `${year}-${m}-${d}`
}

function getOccupyingAd(isoStr) {
  if (!props.isBody || !props.occupiedRanges.length) return null
  const target = new Date(isoStr)
  target.setHours(0, 0, 0, 0)

  for (const range of props.occupiedRanges) {
    if (!range.start_date) continue
    const s = new Date(range.start_date)
    s.setHours(0, 0, 0, 0)
    const e = range.end_date ? new Date(range.end_date) : new Date('2099-12-31')
    e.setHours(0, 0, 0, 0)

    if (target >= s && target <= e) {
      return range
    }
  }
  return null
}

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  const firstDayOfWeek = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const daysInPrevMonth = new Date(year, month, 0).getDate()

  const days = []

  // Prev month padding
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const day = daysInPrevMonth - i
    const iso = toIso(month === 0 ? year - 1 : year, month === 0 ? 11 : month - 1, day)
    days.push({
      day,
      iso,
      isCurrentMonth: false,
      isOccupied: Boolean(getOccupyingAd(iso)),
      occupyingAd: getOccupyingAd(iso)
    })
  }

  // Current month
  for (let i = 1; i <= daysInMonth; i++) {
    const iso = toIso(year, month, i)
    const occupyingAd = getOccupyingAd(iso)
    days.push({
      day: i,
      iso,
      isCurrentMonth: true,
      isOccupied: Boolean(occupyingAd),
      occupyingAd
    })
  }

  // Next month padding
  const remaining = 7 - (days.length % 7)
  if (remaining < 7) {
    for (let i = 1; i <= remaining; i++) {
      const iso = toIso(month === 11 ? year + 1 : year, month === 11 ? 0 : month + 1, i)
      days.push({
        day: i,
        iso,
        isCurrentMonth: false,
        isOccupied: Boolean(getOccupyingAd(iso)),
        occupyingAd: getOccupyingAd(iso)
      })
    }
  }

  return days
})

function isSelected(iso) {
  if (!props.startDate) return false
  if (props.startDate === iso || props.endDate === iso) return true
  if (props.endDate && iso > props.startDate && iso < props.endDate) return true
  return false
}

function handleDateClick(dayObj) {
  if (dayObj.isOccupied) return

  const clickedIso = dayObj.iso

  if (activeStep.value === 'start') {
    emit('update:startDate', clickedIso)
    if (props.endDate && props.endDate < clickedIso) {
      emit('update:endDate', '')
    }
    activeStep.value = 'end'
  } else {
    // Picking end date
    if (props.startDate && clickedIso < props.startDate) {
      emit('update:startDate', clickedIso)
      emit('update:endDate', '')
      activeStep.value = 'end'
      return
    }

    // Check if any occupied range is between props.startDate and clickedIso
    if (props.startDate && props.isBody) {
      const s = new Date(props.startDate)
      const e = new Date(clickedIso)
      let conflict = false

      for (const range of props.occupiedRanges) {
        const rS = new Date(range.start_date)
        const rE = range.end_date ? new Date(range.end_date) : new Date('2099-12-31')
        if (s <= rE && e >= rS) {
          conflict = true
          break
        }
      }

      if (conflict) {
        return
      }
    }

    emit('update:endDate', clickedIso)
    activeStep.value = 'start'
    // Tutup popover otomatis setelah rentang lengkap terpilih
    isOpen.value = false
  }
}

function clearRange() {
  emit('update:startDate', '')
  emit('update:endDate', '')
  activeStep.value = 'start'
}

function onManualStartChange(e) {
  emit('update:startDate', e.target.value)
  if (e.target.value && props.endDate && props.endDate < e.target.value) {
    emit('update:endDate', '')
  }
}

function onManualEndChange(e) {
  emit('update:endDate', e.target.value)
}

const displayRangeText = computed(() => {
  if (props.startDate && props.endDate) {
    return `${formatDateShort(props.startDate)} – ${formatDateShort(props.endDate)}`
  }
  if (props.startDate) {
    return `${formatDateShort(props.startDate)} – (Pilih Tanggal Selesai)`
  }
  return ''
})

function onDocClick(e) {
  if (pickerRef.value && !pickerRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
})
</script>

<template>
  <div ref="pickerRef" class="relative w-full">
    <!-- Single Unified Date Range Input Bar (Menggantikan 2 input terpisah) -->
    <div
      data-testid="date-range-trigger"
      @click="isOpen = !isOpen"
      :class="[
        'w-full h-10 px-3 py-2 rounded-lg border bg-background flex items-center justify-between text-xs cursor-pointer transition-all select-none',
        isOpen ? 'ring-1 ring-ring border-red-500/60 shadow-sm' : 'border-input hover:border-neutral-600',
        error ? 'border-red-500' : ''
      ]"
    >
      <div class="flex items-center gap-2.5 min-w-0">
        <IconComponent name="Calendar" :size="16" class="text-neutral-400 shrink-0" />
        <span v-if="displayRangeText" class="font-mono font-medium text-foreground truncate">
          {{ displayRangeText }}
        </span>
        <span v-else class="text-muted-foreground truncate">
          {{ placeholder }}
        </span>
      </div>

      <div class="flex items-center gap-1.5 shrink-0 ml-2">
        <button
          v-if="startDate || endDate"
          type="button"
          @click.stop="clearRange"
          class="p-1 rounded text-muted-foreground hover:text-red-400 hover:bg-neutral-800 transition cursor-pointer"
          title="Hapus Rentang Tanggal"
        >
          <IconComponent name="X" :size="13" />
        </button>
        <IconComponent
          name="ChevronDown"
          :size="14"
          :class="['text-muted-foreground transition-transform duration-200', isOpen ? 'rotate-180 text-foreground' : '']"
        />
      </div>
    </div>

    <!-- Error message jika bentrok -->
    <div v-if="error" class="p-2 mt-1.5 rounded-md bg-red-500/10 border border-red-500/30 flex items-start gap-1.5 text-xs text-red-400">
      <IconComponent name="AlertCircle" :size="14" class="shrink-0 mt-0.5" />
      <span>{{ error }}</span>
    </div>

    <!-- Popover Dropdown Date Range Picker (Hanya muncul saat input diklik, tidak memenuhi halaman di bawah) -->
    <div
      v-if="isOpen"
      class="absolute top-full left-0 z-50 mt-1.5 w-full sm:w-[350px] p-3.5 rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl space-y-3"
    >
      <!-- Mode & Status Pemilihan -->
      <div class="flex items-center justify-between pb-2 border-b border-neutral-800 text-[11px]">
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
          <span class="font-semibold text-neutral-200">
            {{ activeStep === 'start' ? 'Pilih Tanggal Mulai Tayang' : 'Pilih Tanggal Selesai Tayang' }}
          </span>
        </div>
        <button
          type="button"
          @click="activeStep = activeStep === 'start' ? 'end' : 'start'"
          class="text-[10px] text-red-400 hover:underline cursor-pointer font-mono"
        >
          {{ activeStep === 'start' ? 'Ubah Selesai' : 'Ubah Mulai' }}
        </button>
      </div>

      <!-- Quick Date Inputs di dalam Popover -->
      <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
        <div>
          <label for="pop_start_date" class="text-[10px] text-muted-foreground block mb-0.5">Mulai:</label>
          <input
            id="pop_start_date"
            type="date"
            :value="startDate"
            @change="onManualStartChange"
            @focus="activeStep = 'start'"
            class="w-full h-7 rounded border border-neutral-700 bg-neutral-950 px-2 text-[11px] text-neutral-200"
          />
        </div>
        <div>
          <label for="pop_end_date" class="text-[10px] text-muted-foreground block mb-0.5">Selesai:</label>
          <input
            id="pop_end_date"
            type="date"
            :value="endDate"
            :min="startDate || undefined"
            @change="onManualEndChange"
            @focus="activeStep = 'end'"
            class="w-full h-7 rounded border border-neutral-700 bg-neutral-950 px-2 text-[11px] text-neutral-200"
          />
        </div>
      </div>

      <!-- Occupied Notice (Khusus Iklan Body) -->
      <div
        v-if="isBody && occupiedRanges.length > 0"
        class="p-2 rounded bg-neutral-950 border border-neutral-800 text-[10px] text-neutral-400 space-y-1"
      >
        <span class="font-semibold text-amber-400 flex items-center gap-1">
          <IconComponent name="Info" :size="12" />
          <span>Jadwal iklan body lain terpakai (terdisable):</span>
        </span>
        <div class="flex flex-wrap gap-1">
          <span
            v-for="r in occupiedRanges"
            :key="r.id"
            class="px-1.5 py-0.5 rounded bg-neutral-900 border border-neutral-800 text-rose-300 font-mono"
          >
            {{ formatDateShort(r.start_date) }}–{{ r.end_date ? formatDateShort(r.end_date) : '...' }}
          </span>
        </div>
      </div>

      <!-- Month Navigation -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-xs font-bold text-neutral-200 font-mono">
          {{ currentMonthName }} {{ currentYear }}
        </span>
        <div class="flex items-center gap-1">
          <button
            type="button"
            @click="prevMonth"
            class="p-1 rounded text-neutral-400 hover:text-white hover:bg-neutral-800 transition cursor-pointer"
          >
            <IconComponent name="ChevronLeft" :size="14" />
          </button>
          <button
            type="button"
            @click="nextMonth"
            class="p-1 rounded text-neutral-400 hover:text-white hover:bg-neutral-800 transition cursor-pointer"
          >
            <IconComponent name="ChevronRight" :size="14" />
          </button>
        </div>
      </div>

      <!-- Weekdays -->
      <div class="grid grid-cols-7 gap-1 text-center">
        <span
          v-for="d in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']"
          :key="d"
          class="text-[9px] font-semibold text-muted-foreground uppercase font-mono"
        >
          {{ d }}
        </span>
      </div>

      <!-- Calendar Days Grid -->
      <div class="grid grid-cols-7 gap-1">
        <button
          v-for="cell in calendarDays"
          :key="cell.iso"
          type="button"
          :disabled="cell.isOccupied"
          @click="handleDateClick(cell)"
          :title="cell.isOccupied ? `Terpakai: ${cell.occupyingAd?.title || 'Iklan Lain'}` : cell.iso"
          :class="[
            'h-7 rounded text-[11px] font-mono transition-all relative flex items-center justify-center select-none',
            cell.isOccupied
              ? 'bg-neutral-950 text-neutral-600 line-through cursor-not-allowed border border-dashed border-rose-500/30'
              : isSelected(cell.iso)
                ? 'bg-red-600 text-white font-bold'
                : cell.isCurrentMonth
                  ? 'text-neutral-200 hover:bg-neutral-800 hover:text-red-400 cursor-pointer'
                  : 'text-neutral-600 hover:bg-neutral-800/40 cursor-pointer'
          ]"
        >
          <span>{{ cell.day }}</span>
        </button>
      </div>

      <!-- Footer Actions -->
      <div class="flex items-center justify-between pt-2 border-t border-neutral-800 text-[11px]">
        <button
          type="button"
          @click="clearRange"
          class="text-neutral-400 hover:text-red-400 transition cursor-pointer"
        >
          Hapus Rentang
        </button>
        <button
          type="button"
          @click="isOpen = false"
          class="px-3 py-1 rounded bg-neutral-800 hover:bg-neutral-700 text-neutral-200 font-medium transition cursor-pointer"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>
