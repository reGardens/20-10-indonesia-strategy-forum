<script setup>
import { Badge } from '@/Components/UI'

defineProps({
  label: { type: String, default: 'Tayang' }
})
</script>

<template>
  <Badge class="bg-emerald-600 text-white dark:bg-emerald-500 font-semibold text-[9px] px-1.5 py-0.5 rounded-full inline-flex items-center gap-1 shadow-xs">
    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
    {{ label }}
  </Badge>
</template>
