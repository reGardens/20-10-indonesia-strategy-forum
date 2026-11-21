<script setup>
/**
 * FilterSelect.vue — Komponen Select Dropdown Standar Tunggal untuk Filter Kategori & Status
 * Berbasis Radix Vue / Shadcn UI modern.
 */
import { computed } from 'vue'
import Select from './Select.vue'
import SelectTrigger from './SelectTrigger.vue'
import SelectValue from './SelectValue.vue'
import SelectContent from './SelectContent.vue'
import SelectItem from './SelectItem.vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'sm', // 'sm' (h-8), 'md' (h-9), 'lg' (h-10)
  },
  isDark: {
    type: Boolean,
    default: false,
  },
  class: {
    type: String,
    default: '',
  },
  ariaLabel: {
    type: String,
    default: 'Filter pilihan',
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const EMPTY_VALUE = '__ALL__'

const internalValue = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    return EMPTY_VALUE
  }
  return String(props.modelValue)
})

function getOptionValue(opt) {
  if (opt === null || opt === undefined) return EMPTY_VALUE
  if (typeof opt !== 'object') {
    const s = String(opt)
    return s === '' ? EMPTY_VALUE : s
  }
  const raw = opt.value !== undefined ? opt.value : (opt.slug !== undefined ? opt.slug : (opt.id !== undefined ? opt.id : ''))
  const str = String(raw)
  return str === '' ? EMPTY_VALUE : str
}

function getOptionLabel(opt) {
  if (opt === null || opt === undefined) return ''
  if (typeof opt !== 'object') return String(opt)
  return opt.label !== undefined ? opt.label : (opt.name !== undefined ? opt.name : (opt.title !== undefined ? opt.title : ''))
}

const emptyLabel = computed(() => {
  if (props.placeholder) return props.placeholder
  const emptyOpt = (props.options || []).find((opt) => getOptionValue(opt) === EMPTY_VALUE)
  return emptyOpt ? getOptionLabel(emptyOpt) : 'Select Kategori'
})

// Filter out any option that evaluates to EMPTY_VALUE so it is never duplicated
const sanitizedOptions = computed(() => {
  if (!Array.isArray(props.options)) return []
  return props.options.filter((opt) => getOptionValue(opt) !== EMPTY_VALUE)
})

const triggerClass = computed(() => [
  'w-full font-medium transition-colors cursor-pointer',
  props.size === 'sm' ? 'h-8 text-xs' : (props.size === 'md' ? 'h-9 text-xs' : 'h-10 text-sm'),
  props.isDark
    ? 'bg-neutral-900 border-neutral-700 text-neutral-100 hover:bg-neutral-800'
    : 'bg-background border-input text-foreground hover:bg-accent/40'
])

function handleSelect(val) {
  const finalVal = val === EMPTY_VALUE ? '' : val
  emit('update:modelValue', finalVal)
  emit('change', finalVal)
}
</script>

<template>
  <div :class="['relative inline-block min-w-[130px]', $props.class]">
    <Select
      :model-value="internalValue"
      :disabled="disabled"
      @update:model-value="handleSelect"
    >
      <SelectTrigger :id="id" :class="triggerClass" :aria-label="ariaLabel">
        <SelectValue :placeholder="emptyLabel" />
      </SelectTrigger>
      <SelectContent :class="isDark ? 'bg-neutral-900 border-neutral-700 text-neutral-100' : ''">
        <SelectItem
          :value="EMPTY_VALUE"
          class="text-xs font-medium cursor-pointer"
        >
          {{ emptyLabel }}
        </SelectItem>
        <SelectItem
          v-for="(opt, idx) in sanitizedOptions"
          :key="idx"
          :value="getOptionValue(opt)"
          class="text-xs font-medium cursor-pointer"
        >
          {{ getOptionLabel(opt) }}
        </SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>

