<script setup>
import { computed } from 'vue'
import ImagePlaceholder from '@/Components/Isf/ImagePlaceholder.vue'
import { IconUser, IconTrendingUp, IconAward, IconArrowRight } from '@tabler/icons-vue'

const props = defineProps({
  tokoh: {
    type: Object,
    required: true,
  },
  featured: {
    type: Boolean,
    default: false,
  },
})

const targetUrl = computed(() => {
  return props.tokoh.to || `/sosok/${props.tokoh.slug}`
})

const categoryBadgeColor = computed(() => {
  const cat = (props.tokoh.role_category || '').toLowerCase()
  switch (cat) {
    case 'eksekutif':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-900'
    case 'legislatif':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-900'
    case 'yudikatif':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-900'
    case 'pakar':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900'
    case 'militer':
      return 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border-red-200 dark:border-red-900'
    case 'pengusaha':
      return 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-900'
    default:
      return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300 border-neutral-200 dark:border-neutral-700'
  }
})
</script>

<template>
  <div
    :class="[
      'group rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden flex flex-col transition-all duration-300 hover:shadow-lg hover:border-neutral-400 dark:hover:border-neutral-700',
      featured ? 'md:flex-row' : ''
    ]"
  >
    <!-- Photo Area -->
    <div :class="['relative overflow-hidden bg-neutral-100 dark:bg-neutral-800', featured ? 'md:w-2/5 aspect-[4/3] md:aspect-auto' : 'aspect-[4/3]']">
      <img
        v-if="tokoh.photo_url"
        :src="tokoh.photo_url"
        :alt="tokoh.photo_alt || tokoh.name"
        class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-105"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center p-6 text-neutral-400">
        <IconUser class="w-16 h-16 opacity-40" />
      </div>

      <!-- Top Badges Overlay -->
      <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
        <span :class="['px-2.5 py-1 rounded-md text-[11px] font-bold font-mono uppercase tracking-wider border shadow-xs', categoryBadgeColor]">
          {{ tokoh.role_category_label || tokoh.role_category || 'Tokoh' }}
        </span>

        <span
          v-if="tokoh.influence_score || tokoh.skor_value"
          class="px-2.5 py-1 rounded-md bg-[#0B1220]/90 backdrop-blur-xs text-white text-[11px] font-mono font-bold flex items-center gap-1 shadow-xs border border-white/10"
        >
          <IconAward class="w-3.5 h-3.5 text-amber-400 shrink-0" />
          <span>Skor {{ tokoh.influence_score || tokoh.skor_value }}</span>
        </span>
      </div>

      <div
        v-if="tokoh.is_featured || tokoh.is_influential_weekly"
        class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-isf-red text-white text-[10px] font-mono font-bold tracking-wider uppercase shadow-xs"
      >
        Sosok Pekan Ini
      </div>
    </div>

    <!-- Content Area -->
    <div class="p-5 flex-1 flex flex-col justify-between">
      <div>
        <h3 class="font-headline text-lg sm:text-xl font-bold text-neutral-950 dark:text-white group-hover:text-isf-red transition-colors line-clamp-2 leading-snug min-h-[3rem]">
          <router-link :to="targetUrl">
            {{ tokoh.name }}
          </router-link>
        </h3>
        <!-- 1 baris deskripsi: Jabatan & Ringkasan Analisis ISF -->
        <p class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-1.5 line-clamp-2 leading-relaxed min-h-[2.5rem]">
          <span v-if="tokoh.position" class="font-medium text-neutral-900 dark:text-neutral-200">{{ tokoh.position }}</span>
          <span v-if="tokoh.position && (tokoh.summary_analysis || tokoh.teaser)" class="text-neutral-400 mx-1.5">•</span>
          <span v-if="tokoh.summary_analysis || tokoh.teaser" class="text-neutral-500">{{ tokoh.summary_analysis || tokoh.teaser }}</span>
        </p>

        <!-- Issues Tags -->
        <div v-if="tokoh.issues && tokoh.issues.length" class="flex flex-wrap gap-1.5 mt-3.5">
          <span
            v-for="isu in tokoh.issues"
            :key="isu"
            class="px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-[10px] font-mono text-neutral-600 dark:text-neutral-400 capitalize"
          >
            #{{ isu }}
          </span>
        </div>
      </div>

      <!-- Footer Action -->
      <div class="pt-4 mt-4 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between">
        <span class="text-[11px] text-neutral-400 font-mono">
          Rekam Jejak & Ulasan
        </span>
        <router-link
          :to="targetUrl"
          class="inline-flex items-center gap-1 text-xs font-bold text-isf-red hover:text-red-700 transition-colors font-space-grotesk"
        >
          <span>Eksplorasi Profil</span>
          <IconArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
        </router-link>
      </div>
    </div>
  </div>
</template>

