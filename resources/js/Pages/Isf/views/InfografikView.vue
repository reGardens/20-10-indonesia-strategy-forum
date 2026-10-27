<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { getInfografik } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'
import { formatDate } from '@/Utils/formatters'
import { CategoryLabel, ImagePlaceholder, IsfLink } from '@/Components/Isf'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'

const { data, status, load } = useIsfContent(() => getInfografik())
onMounted(load)

const selectedCategory = ref('semua')
const selectedImage = ref(null)

const categories = computed(() => {
  const items = data.value?.data || []
  const cats = new Set(items.map((i) => i.category).filter(Boolean))
  return ['semua', ...Array.from(cats)]
})

const filteredList = computed(() => {
  const items = data.value?.data || []
  if (selectedCategory.value === 'semua') return items
  return items.filter((i) => (i.category || '').toLowerCase() === selectedCategory.value.toLowerCase())
})

function openImage(item) {
  selectedImage.value = item
}

function closeImage() {
  selectedImage.value = null
}
</script>

<template>
  <div class="isf-mobile-gutter mx-auto w-full max-w-[1400px] px-4 py-8 lg:px-6 sm:py-10">
    <!-- Breadcrumb & Back -->
    <div class="mb-6 flex items-center justify-between border-b border-neutral-300 pb-3">
      <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">
        <router-link to="/beranda" class="hover:text-isf-red transition-colors">Beranda</router-link>
        <span>/</span>
        <span class="text-neutral-900 font-bold">Infografik</span>
      </div>
      <router-link
        to="/beranda"
        class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-isf-red hover:text-isf-red-dark transition-colors"
      >
        ← Kembali ke Beranda
      </router-link>
    </div>

    <!-- Page Header -->
    <header class="mb-8 max-w-3xl">
      <p class="isf-label text-isf-red uppercase tracking-widest font-bold">Pusat Visualisasi Data</p>
      <h1 class="isf-headline font-headline text-[32px] sm:text-[42px] leading-tight text-neutral-900 mt-1">
        Infografik Regulasi & Kebijakan
      </h1>
      <p class="mt-2 text-base sm:text-lg font-serif text-neutral-600 leading-relaxed">
        Visualisasi data komprehensif, peta regulasi nasional, dan intisari kebijakan strategis yang disajikan secara terstruktur dan berbasis fakta.
      </p>

      <!-- Category Filter Pills -->
      <div v-if="categories.length > 2" class="mt-6 flex flex-wrap gap-2">
        <button
          v-for="cat in categories"
          :key="cat"
          type="button"
          @click="selectedCategory = cat"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all"
          :class="
            selectedCategory === cat
              ? 'bg-isf-red text-white shadow-xs'
              : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
          "
        >
          {{ cat }}
        </button>
      </div>
    </header>

    <!-- Loading / Error states -->
    <div v-if="status === 'loading'" class="py-20">
      <LoadingIndicator text="Memuat koleksi infografik..." />
    </div>

    <div v-else-if="status === 'error'" class="py-16 max-w-xl mx-auto">
      <ErrorMessage
        message="Gagal memuat infografik. Periksa koneksi internet Anda dan coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- Empty State -->
    <div v-else-if="!filteredList.length" class="py-16 text-center border border-dashed border-neutral-300 rounded-lg">
      <p class="text-neutral-500 font-medium text-sm">Belum ada infografik yang dipublikasikan dalam kategori ini.</p>
    </div>

    <!-- Infographics Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">
      <article
        v-for="item in filteredList"
        :key="item.id || item.title"
        class="group flex flex-col justify-between border border-neutral-300 bg-white rounded-xs overflow-hidden shadow-2xs hover:shadow-md hover:border-neutral-400 transition-all cursor-pointer"
        @click="openImage(item)"
      >
        <div class="relative aspect-[4/3] bg-neutral-100 overflow-hidden">
          <img
            v-if="item.imageUrl"
            :src="item.imageUrl"
            :alt="item.alt || item.title"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            loading="lazy"
          />
          <ImagePlaceholder
            v-else
            :label="item.alt || item.title"
            aspect="photo"
            bare
            class="w-full h-full"
          />
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
            <span class="px-3 py-1.5 rounded-full bg-white/95 text-neutral-900 font-ui text-xs font-bold shadow-sm">
              🔍 Perbesar Visual
            </span>
          </div>
        </div>

        <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
          <div>
            <CategoryLabel :category="item.category" />
            <h2 class="isf-headline mt-1.5 text-[17px] sm:text-[18px] leading-snug font-bold text-neutral-900 group-hover:text-isf-red transition-colors">
              {{ item.title }}
            </h2>
          </div>
          <time
            v-if="item.publishedAt"
            :datetime="item.publishedAt"
            class="isf-byline block text-xs text-neutral-500 border-t border-neutral-200 pt-2.5"
          >
            {{ formatDate(item.publishedAt) }}
          </time>
        </div>
      </article>
    </div>

    <!-- Lightbox Modal for Full View -->
    <div
      v-if="selectedImage"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-xs p-4 sm:p-8"
      @click.self="closeImage"
    >
      <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col bg-neutral-950 rounded-xl overflow-hidden shadow-2xl border border-neutral-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800 bg-neutral-900/90 text-white">
          <div class="min-w-0 pr-4">
            <span class="text-[11px] font-bold text-isf-red uppercase tracking-wider">
              {{ selectedImage.category || 'Infografik' }}
            </span>
            <h3 class="text-base sm:text-lg font-bold truncate text-neutral-100">
              {{ selectedImage.title }}
            </h3>
          </div>
          <button
            type="button"
            @click="closeImage"
            class="h-9 w-9 rounded-full bg-neutral-800 hover:bg-neutral-700 text-neutral-300 hover:text-white flex items-center justify-center transition-colors text-lg font-bold shrink-0"
            aria-label="Tutup"
          >
            ✕
          </button>
        </div>
        <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-black/60">
          <img
            :src="selectedImage.imageUrl"
            :alt="selectedImage.title"
            class="max-w-full max-h-[75vh] object-contain rounded shadow-lg"
          />
        </div>
        <div class="px-6 py-3 bg-neutral-900/90 border-t border-neutral-800 text-xs text-neutral-400 flex items-center justify-between">
          <span>{{ selectedImage.alt || selectedImage.title }}</span>
          <span>{{ formatDate(selectedImage.publishedAt) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
