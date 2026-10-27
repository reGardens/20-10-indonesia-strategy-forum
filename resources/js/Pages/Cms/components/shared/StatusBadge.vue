<script setup>
import { Badge } from '@/Components/UI'

const props = defineProps({
  status: { type: String, default: 'draf' }
})
</script>

<template>
  <Badge
    :class="[
      status === 'terbit'
        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
        : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20'
    ]"
    class="text-[9px] font-medium px-1.5 py-0.5 rounded-full capitalize"
  >
    <span
      :class="[
        'w-1.5 h-1.5 rounded-full mr-1 inline-block',
        status === 'terbit' ? 'bg-emerald-500' : 'bg-slate-400'
      ]"
    />
    {{ status === 'terbit' ? 'Terbit' : 'Draf' }}
  </Badge>
</template>
