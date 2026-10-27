<script setup>
import { ref, watch, computed } from 'vue'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

/**
 * DatePicker — Input tanggal dengan tampilan format Indonesia (DD/MM/YYYY).
 * Menghilangkan tampilan mm/dd/yyyy bawaan browser OS bahasa Inggris.
 *
 * v-model: string 'YYYY-MM-DD' (kompatibel dengan database dan validasi backend).
 */
const props = defineProps({
  modelValue: { type: String, default: '' },
  id: { type: String, default: '' },
  placeholder: { type: String, default: 'dd/mm/yyyy' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  class: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const hiddenDateInput = ref(null)
const displayText = ref('')

// Format YYYY-MM-DD -> DD/MM/YYYY untuk tampilan visual
function toDisplay(isoStr) {
  if (!isoStr) return ''
  const parts = isoStr.split('-')
  if (parts.length === 3 && parts[0].length === 4) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`
  }
  return isoStr
}

// Parse DD/MM/YYYY -> YYYY-MM-DD
function toIso(dispStr) {
  if (!dispStr) return ''
  const parts = dispStr.split('/')
  if (parts.length === 3 && parts[2].length === 4) {
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
  }
  return ''
}

watch(
  () => props.modelValue,
  (val) => {
    displayText.value = toDisplay(val)
  },
  { immediate: true }
)

function onTextInput(e) {
  let val = e.target.value.replace(/[^0-9/]/g, '')
  // Auto add slashes while typing (e.g. 27 -> 27/ -> 27/10/ -> 27/10/2026)
  if (val.length === 2 && !val.includes('/')) {
    val = val + '/'
  } else if (val.length === 5 && val.split('/').length === 2) {
    val = val + '/'
  }
  displayText.value = val

  const iso = toIso(val)
  if (iso) {
    emit('update:modelValue', iso)
  } else if (!val) {
    emit('update:modelValue', '')
  }
}

function onNativeDateChange(e) {
  const val = e.target.value
  displayText.value = toDisplay(val)
  emit('update:modelValue', val)
}

function openCalendar() {
  if (props.disabled) return
  if (hiddenDateInput.value?.showPicker) {
    try {
      hiddenDateInput.value.showPicker()
    } catch {
      hiddenDateInput.value.focus()
    }
  } else {
    hiddenDateInput.value?.focus()
  }
}
</script>

<template>
  <div class="relative w-full">
    <!-- Visual Text Input (Formatted DD/MM/YYYY) -->
    <input
      :id="id"
      type="text"
      :value="displayText"
      @input="onTextInput"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      maxlength="10"
      :class="[
        'w-full h-9 rounded-md border border-input bg-background pl-3 pr-9 text-xs text-foreground font-mono focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 transition-colors',
        props.class
      ]"
    />

    <!-- Calendar Icon Trigger -->
    <button
      type="button"
      @click="openCalendar"
      :disabled="disabled"
      tabindex="-1"
      class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground/70 hover:text-foreground transition-colors p-0.5 rounded focus:outline-none"
      title="Pilih tanggal dari kalender"
    >
      <IconComponent name="Calendar" :size="15" />
    </button>

    <!-- Hidden Native Date Picker for popup dialog -->
    <input
      ref="hiddenDateInput"
      type="date"
      :value="modelValue"
      @change="onNativeDateChange"
      class="sr-only"
      tabindex="-1"
      aria-hidden="true"
    />
  </div>
</template>
