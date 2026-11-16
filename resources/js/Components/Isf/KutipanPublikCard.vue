<script setup>
/**
 * KutipanPublikCard.vue — Komponen kartu kutipan tajam dari tokoh publik atau netizen
 * terkait isu strategis nasional dalam rubrik Opini Publik ISF.
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { IconQuote, IconExternalLink, IconUserCheck, IconMessageCircle2 } from '@tabler/icons-vue'

const props = defineProps({
  quote: {
    type: Object,
    required: true,
  },
  theme: {
    type: String,
    default: 'dark', // 'dark' | 'light'
    validator: (v) => ['dark', 'light'].includes(v),
  },
})

const isTokoh = computed(() => {
  return props.quote?.tokoh_tipe === 'tokoh' || (!props.quote?.tokoh_tipe && props.quote?.tokoh_id)
})

const formattedDate = computed(() => {
  if (!props.quote?.tanggal_kutipan) return ''
  try {
    const d = new Date(props.quote.tanggal_kutipan)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(d)
  } catch (e) {
    return props.quote.tanggal_kutipan
  }
})

const tokohSlug = computed(() => {
  return props.quote?.tokoh?.slug || null
})

const avatarUrl = computed(() => {
  return props.quote?.tokoh?.foto_url || null
})
</script>

<template>
  <div
    :class="[
      'relative flex flex-col justify-between rounded-xl border p-5 transition-all duration-300 hover:shadow-lg',
      theme === 'dark'
        ? 'bg-neutral-900/90 border-neutral-800 hover:border-neutral-700 text-neutral-100'
        : 'bg-white border-neutral-200 hover:border-neutral-300 text-neutral-900 shadow-xs'
    ]"
  >
    <!-- Top Row: Badge & Issue Tag -->
    <div class="flex items-center justify-between gap-2 mb-3">
      <!-- Author Type Badge -->
      <span
        v-if="isTokoh"
        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide uppercase font-space-grotesk bg-amber-500/10 text-amber-400 border border-amber-500/20"
      >
        <IconUserCheck class="w-3.5 h-3.5 shrink-0" />
        Tokoh Publik
      </span>
      <span
        v-else
        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide uppercase font-space-grotesk bg-sky-500/10 text-sky-400 border border-sky-500/20"
      >
        <IconMessageCircle2 class="w-3.5 h-3.5 shrink-0" />
        Suara Netizen
      </span>

      <!-- Topic Pill -->
      <span
        v-if="quote.isu_terkait"
        class="text-[11px] font-mono text-neutral-400 truncate max-w-[140px] sm:max-w-[180px]"
        :title="quote.isu_terkait"
      >
        #{{ quote.isu_terkait }}
      </span>
    </div>

    <!-- Quote Body with Decorative Icon -->
    <div class="relative my-2">
      <IconQuote
        :class="[
          'w-8 h-8 opacity-20 absolute -top-3 -left-2 rotate-180 pointer-events-none',
          theme === 'dark' ? 'text-isf-red' : 'text-isf-red'
        ]"
      />
      <blockquote
        class="relative font-newsreader italic text-base sm:text-lg leading-relaxed pl-4 pr-1 text-neutral-200 line-clamp-5"
        :class="{ 'text-neutral-800': theme === 'light' }"
      >
        "{{ quote.kutipan }}"
      </blockquote>
    </div>

    <!-- Footer: Author Info & Source Link -->
    <div
      :class="[
        'mt-4 pt-4 border-t flex items-center justify-between gap-3',
        theme === 'dark' ? 'border-neutral-800/80' : 'border-neutral-100'
      ]"
    >
      <div class="flex items-center gap-3 min-w-0">
        <!-- Author Avatar or Placeholder -->
        <component
          :is="tokohSlug ? RouterLink : 'div'"
          :to="tokohSlug ? `/tokoh/${tokohSlug}` : undefined"
          class="shrink-0 group/avatar block"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            :alt="quote.tokoh_nama"
            class="w-10 h-10 rounded-full object-cover border border-neutral-700 group-hover/avatar:border-isf-red transition-colors"
          />
          <div
            v-else
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs font-space-grotesk border',
              isTokoh
                ? 'bg-amber-500/20 text-amber-300 border-amber-500/30'
                : 'bg-sky-500/20 text-sky-300 border-sky-500/30'
            ]"
          >
            {{ (quote.tokoh_nama || '?').substring(0, 2).toUpperCase() }}
          </div>
        </component>

        <!-- Author Name and Title -->
        <div class="min-w-0 flex-1">
          <component
            :is="tokohSlug ? RouterLink : 'div'"
            :to="tokohSlug ? `/tokoh/${tokohSlug}` : undefined"
            :class="[
              'block font-space-grotesk font-bold text-xs sm:text-sm truncate',
              tokohSlug
                ? 'hover:text-isf-red transition-colors underline-offset-2 hover:underline'
                : '',
              theme === 'dark' ? 'text-white' : 'text-neutral-900'
            ]"
          >
            {{ quote.tokoh_nama }}
          </component>
          <p class="text-[11px] text-neutral-400 font-space-grotesk truncate">
            {{ quote.tokoh_jabatan || (isTokoh ? 'Tokoh Publik' : 'Warga / Netizen') }}
          </p>
        </div>
      </div>

      <!-- Source Link & Date -->
      <div class="text-right shrink-0">
        <a
          v-if="quote.sumber_url"
          :href="quote.sumber_url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1 text-[11px] font-space-grotesk text-isf-red hover:text-red-400 font-medium transition-colors"
          :title="`Buka sumber: ${quote.sumber_nama || 'Tautan'}`"
        >
          <span class="max-w-[90px] sm:max-w-[120px] truncate">{{ quote.sumber_nama || 'Sumber' }}</span>
          <IconExternalLink class="w-3 h-3 shrink-0" />
        </a>
        <span v-else-if="quote.sumber_nama" class="text-[11px] text-neutral-400 font-space-grotesk block truncate max-w-[110px]">
          {{ quote.sumber_nama }}
        </span>
        <span v-if="formattedDate" class="text-[10px] text-neutral-500 font-mono block mt-0.5">
          {{ formattedDate }}
        </span>
      </div>
    </div>
  </div>
</template>

