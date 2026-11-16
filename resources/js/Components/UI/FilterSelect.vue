<script setup>
/**
 * FilterSelect.vue — Komponen Select Dropdown Standar Tunggal untuk Filter Kategori & Status
 * Digunakan secara terpadu di seluruh CMS dan Landing Page publik ISF.
 */
import { computed } from 'vue'

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

function getOptionValue(opt) {
  if (opt === null || opt === undefined) return ''
  if (typeof opt !== 'object') return String(opt)
  return opt.value !== undefined ? opt.value : (opt.slug !== undefined ? opt.slug : (opt.id !== undefined ? opt.id : ''))
}

function getOptionLabel(opt) {
  if (opt === null || opt === undefined) return ''
  if (typeof opt !== 'object') return String(opt)
  return opt.label !== undefined ? opt.label : (opt.name !== undefined ? opt.name : (opt.title !== undefined ? opt.title : ''))
}

function handleChange(e) {
  const val = e.target.value
  emit('update:modelValue', val)
  emit('change', val)
}
</script>

<template>
  <div :class="['relative inline-block', $props.class]">
    <select
      :id="id"
      :name="name"
      :value="modelValue"
      :disabled="disabled"
      :aria-label="ariaLabel"
      @change="handleChange"
      :class="[
        'w-full rounded-md border pl-2.5 pr-8 font-medium transition-colors focus:outline-none focus:ring-1 appearance-none cursor-pointer disabled:cursor-not-allowed disabled:opacity-50',
        size === 'sm' ? 'h-8 text-xs' : (size === 'md' ? 'h-9 text-xs' : 'h-10 text-sm'),
        isDark
          ? 'bg-neutral-900 border-neutral-700 text-neutral-100 focus:ring-isf-red focus:border-isf-red'
          : 'bg-background border-input text-foreground focus:ring-ring focus:border-ring'
      ]"
    >
      <option v-if="placeholder !== ''" value="">
        {{ placeholder }}
      </option>
      <slot>
        <option
          v-for="(opt, idx) in options"
          :key="idx"
          :value="getOptionValue(opt)"
        >
          {{ getOptionLabel(opt) }}
        </option>
      </slot>
    </select>
    <div
      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5"
      :class="isDark ? 'text-neutral-400' : 'text-muted-foreground'"
    >
      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
  </div>
</template>

