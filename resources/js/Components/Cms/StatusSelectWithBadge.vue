<script setup>
import { computed } from 'vue'
import { STATUSES } from '@/Pages/Cms/Isf/options'
import {
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue
} from '@/Components/UI'

const props = defineProps({
  modelValue: { type: String, default: 'draf' },
  label: { type: String, default: 'Status' },
  required: { type: Boolean, default: false },
  id: { type: String, default: 'status' }
})

const emit = defineEmits(['update:modelValue'])

const badgeClass = computed(() =>
  props.modelValue === 'terbit'
    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
    : 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20'
)
const dotClass = computed(() =>
  props.modelValue === 'terbit' ? 'bg-emerald-500' : 'bg-slate-400'
)
const badgeLabel = computed(() =>
  props.modelValue === 'terbit' ? 'Terbit' : 'Draf'
)
</script>

<template>
  <div class="space-y-1.5">
    <div class="flex items-center gap-2">
      <label :for="id" class="text-xs font-medium text-foreground">
        {{ label }} <span v-if="required" class="text-red-500">*</span>
      </label>
      <!-- Status badge beside the label (mini badge) -->
      <span :class="['inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold', badgeClass]">
        <span :class="['w-1.5 h-1.5 rounded-full inline-block', dotClass]" />
        {{ badgeLabel }}
      </span>
    </div>

    <Select
      :model-value="modelValue"
      @update:model-value="emit('update:modelValue', $event)"
    >
      <SelectTrigger :id="id" class="h-9 text-xs font-medium">
        <SelectValue :placeholder="label" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem
          v-for="s in STATUSES"
          :key="s.value"
          :value="s.value"
          class="text-xs font-medium"
        >
          <span class="inline-flex items-center gap-2">
            <span
              :class="[
                'w-1.5 h-1.5 rounded-full',
                s.value === 'terbit' ? 'bg-emerald-500' : 'bg-slate-400'
              ]"
            />
            {{ s.label }}
          </span>
        </SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>
