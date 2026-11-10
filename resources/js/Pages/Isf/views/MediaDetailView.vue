<script setup>
/**
 * Halaman Detail Embed Video & Artikel Ulasan Pendamping
 * Referensi Desain: EU Reporter Editorial Article
 * (https://www.eureporter.co/world/japan/2025/12/28/japan-should-face-up-to-history-and-contribute-more-to-regional-peace/)
 */
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  IconArrowLeft,
  IconBrandWhatsapp,
  IconBrandX,
  IconCheck,
  IconCopy,
  IconExternalLink
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  ImagePlaceholder
} from '@/Components/Isf'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { formatDate } from '@/Utils/formatters'
import { getMediaDetail } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()
const copied = ref(false)
const activeSidebarTab = ref('terkini')

const { data, status, load } = useIsfContent(() => getMediaDetail(route.params.id))

watch(
  () => route.params.id,
  () => {
    copied.value = false
    load()
  },
  { immediate: true }
)

const media = computed(() => data.value?.data || null)
const relatedVideos = computed(() => data.value?.related || [])
const recentArticles = computed(() => data.value?.recentArticles || [])

/**
 * Ekstraksi ID video YouTube dari berbagai format URL atau tag <iframe>
 */
function extractYouTubeId(url) {
  if (!url) return ''
  let cleaned = String(url).trim()

  const iframeMatch = cleaned.match(/src=["']([^"']+)["']/i)
  if (iframeMatch && iframeMatch[1]) {
    cleaned = iframeMatch[1].trim()
  }

  if (/^[a-zA-Z0-9_-]{11}$/.test(cleaned)) {
    return cleaned
  }

  const vMatch = cleaned.match(/[?&]v=([a-zA-Z0-9_-]{11})(?:[&?]|$)/)
  if (vMatch && vMatch[1]) {
    return vMatch[1]
  }

  const pathMatch = cleaned.match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|live\/|v\/|watch\/))([a-zA-Z0-9_-]{11})/)
  if (pathMatch && pathMatch[1]) {
    return pathMatch[1]
  }

  return ''
}

/**
 * Ekstraksi ID video TikTok dari berbagai format URL atau tag <iframe>
 */
function extractTikTokId(url) {
  if (!url) return ''
  let cleaned = String(url).trim()

  const iframeMatch = cleaned.match(/src=["']([^"']+)["']/i)
  if (iframeMatch && iframeMatch[1]) {
    cleaned = iframeMatch[1].trim()
  }

  if (/^\d{15,22}$/.test(cleaned)) {
    return cleaned
  }

  const match = cleaned.match(/(?:video\/|v\/|embed\/v2\/|embed\/)(\d{15,22})/)
  if (match && match[1]) {
    return match[1]
  }

  return ''
}

/**
 * Ekstraksi ID postingan/reel Instagram dari berbagai format URL atau tag <iframe>
 */
function extractInstagramId(url) {
  if (!url) return ''
  let cleaned = String(url).trim()

  const iframeMatch = cleaned.match(/src=["']([^"']+)["']/i)
  if (iframeMatch && iframeMatch[1]) {
    cleaned = iframeMatch[1].trim()
  }

  const match = cleaned.match(/(?:p|reel|reels|tv)\/([A-Za-z0-9_-]+)/)
  if (match && match[1]) {
    return match[1]
  }

  return ''
}

/**
 * Deteksi platform (youtube, tiktok, instagram) berdasarkan kecocokan URL & kategori
 */
const platform = computed(() => {
  const url = (media.value?.mediaUrl || '').toLowerCase()
  const cat = (media.value?.category || '').toLowerCase()

  // 1. Cek dari karakteristik URL aktual terlebih dahulu
  if (url.includes('youtube.com') || url.includes('youtu.be') || Boolean(extractYouTubeId(media.value?.mediaUrl))) {
    return 'youtube'
  }
  if (url.includes('tiktok.com') || Boolean(extractTikTokId(media.value?.mediaUrl))) {
    return 'tiktok'
  }
  if (url.includes('instagram.com') || Boolean(extractInstagramId(media.value?.mediaUrl))) {
    return 'instagram'
  }

  // 2. Fallback berdasarkan label kategori
  if (cat.includes('tiktok')) return 'tiktok'
  if (cat.includes('instagram') || cat.includes('reels')) return 'instagram'
  return 'youtube'
})

const isYouTube = computed(() => platform.value === 'youtube')
const isTikTok = computed(() => platform.value === 'tiktok')
const isInstagram = computed(() => platform.value === 'instagram')

/**
 * Ekstraksi URL embed native langsung tanpa wrapper/overlay tambahan.
 */
const embedUrl = computed(() => {
  const raw = (media.value?.mediaUrl || '').trim()

  // YouTube: embed resmi dengan rel=0
  if (platform.value === 'youtube') {
    let videoId = extractYouTubeId(raw)
    // Fallback ke video resmi EU Reporter / ISF Roundtable jika URL kosong atau generic
    if (!videoId) {
      videoId = 'aBxlmrCPjBA'
    }
    return `https://www.youtube.com/embed/${videoId}?rel=0`
  }

  // TikTok: player vertikal resmi v2
  if (platform.value === 'tiktok') {
    let videoId = extractTikTokId(raw)
    if (videoId) {
      return `https://www.tiktok.com/embed/v2/${videoId}`
    }
    return `https://www.tiktok.com/embed/v2/7200000000000000000`
  }

  // Instagram: embed responsif untuk reel / post
  if (platform.value === 'instagram') {
    let igId = extractInstagramId(raw)
    if (igId) {
      return `https://www.instagram.com/p/${igId}/embed`
    }
  }

  // Fallback jika URL sudah berformat embed generik
  if (raw && raw.includes('/embed/')) {
    return raw
  }

  return ''
})

/**
 * Thumbnail URL otomatis dengan resolusi YouTube fallback jika imageUrl belum diisi.
 */
function getThumbnailUrl(item) {
  if (item?.imageUrl) return item.imageUrl
  const ytid = extractYouTubeId(item?.mediaUrl)
  if (ytid) {
    return `https://i.ytimg.com/vi/${ytid}/hqdefault.jpg`
  }
  const cat = (item?.category || '').toLowerCase()
  if (cat.includes('youtube') || (item?.mediaUrl || '').includes('youtube.com')) {
    return 'https://i.ytimg.com/vi/aBxlmrCPjBA/hqdefault.jpg'
  }
  return ''
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2500)
  } catch {
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

function shareTwitter() {
  const text = encodeURIComponent(`${media.value?.title || 'Video Analisis'} — Indonesia Strategy Forum`)
  const url = encodeURIComponent(window.location.href)
  window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'noopener,noreferrer')
}

function shareWhatsApp() {
  const text = encodeURIComponent(`*${media.value?.title || 'Video Analisis'}*\n\nSimak ulasan lengkapnya di Indonesia Strategy Forum:\n${window.location.href}`)
  window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <div class="isf-mobile-gutter mx-auto max-w-[1240px] px-4 py-6 sm:py-8 lg:py-10">
    <!-- State 1: Loading Skeleton -->
    <ArtikelSkeleton v-if="status === 'loading'" variant="tokoh" />

    <!-- State 2: Error Message -->
    <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
      <ErrorMessage
        message="Gagal memuat tayangan media sorotan. Silakan periksa koneksi Anda dan coba lagi."
        retryable
        @retry="load"
      />
    </div>

    <!-- State 3: Not Found -->
    <div v-else-if="status === 'not-found' || !media" class="py-16 text-center">
      <h1 class="isf-headline font-headline text-2xl text-neutral-900 font-bold">
        Tayangan Media Tidak Ditemukan
      </h1>
      <p class="mt-2 text-neutral-600 text-sm">
        Video atau artikel ulasan yang Anda cari mungkin telah diperbarui atau dinonaktifkan.
      </p>
      <router-link
        to="/beranda"
        class="mt-5 inline-flex items-center gap-1.5 font-semibold text-isf-red underline underline-offset-2 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
      >
        <IconArrowLeft :size="16" />
        Kembali ke Beranda
      </router-link>
    </div>

    <!-- State 4: Ready (Struktur Halaman Artikel ala EU Reporter) -->
    <article v-else class="grid grid-cols-1 gap-10 lg:grid-cols-12">
      <!-- MAIN / LEFT COLUMN (Col 8) -->
      <div class="space-y-6 lg:col-span-8">
        <!-- Post Header (mvp-post-head) -->
        <header>
          <!-- Category Kicker (mvp-post-cat) -->
          <div class="mb-2">
            <router-link
              to="/beranda"
              class="text-xs font-bold uppercase tracking-wider text-isf-red font-space-grotesk hover:underline"
            >
              {{ media.category || 'Sorotan' }}
            </router-link>
          </div>

          <!-- Headline Title (mvp-post-title) -->
          <h1 class="isf-headline font-headline text-[30px] sm:text-[38px] lg:text-[44px] font-bold text-neutral-900 leading-[1.15] tracking-tight">
            {{ media.title }}
          </h1>

          <!-- Top Share Bar -->
          <div class="mt-4 py-2.5 border-y border-neutral-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold uppercase tracking-wider text-neutral-700 font-mono">SHARE:</span>
              
              <button
                type="button"
                @click="copyLink"
                title="Salin Tautan"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 transition"
              >
                <IconCheck v-if="copied" :size="14" class="text-emerald-600" />
                <IconCopy v-else :size="14" />
                <span>{{ copied ? 'Tersalin' : 'Salin' }}</span>
              </button>

              <button
                type="button"
                @click="shareWhatsApp"
                title="Bagikan ke WhatsApp"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-emerald-50 hover:text-emerald-600 text-neutral-700 transition"
              >
                <IconBrandWhatsapp :size="14" />
                <span class="hidden sm:inline">WhatsApp</span>
              </button>

              <button
                type="button"
                @click="shareTwitter"
                title="Bagikan ke X"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-neutral-100 text-neutral-700 transition"
              >
                <IconBrandX :size="14" />
                <span class="hidden sm:inline">X (Twitter)</span>
              </button>
            </div>

            <a
              v-if="media.mediaUrl"
              :href="media.mediaUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-xs font-semibold text-neutral-500 hover:text-isf-red transition"
            >
              <span>Buka Asli</span>
              <IconExternalLink :size="13" />
            </a>
          </div>

          <!-- Author Info Wrap (mvp-author-info-wrap) -->
          <div class="mt-4 flex items-center gap-3">
            <div class="h-11 w-11 rounded-full bg-neutral-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
              ISF
            </div>
            <div class="text-xs text-neutral-600 leading-snug">
              <p>
                <span class="text-neutral-400">Published on</span>
                <time v-if="media.publishedAt" :datetime="media.publishedAt" class="font-semibold text-neutral-800 ml-1">
                  {{ formatDate(media.publishedAt) }}
                </time>
              </p>
              <p class="mt-0.5">
                <span class="text-neutral-400">By</span>
                <span class="font-bold text-neutral-900 ml-1">
                  {{ media.author || 'Tim Redaksi & Dewan Pakar ISF' }}
                </span>
              </p>
            </div>
          </div>
        </header>

        <!-- Featured Media Container (mvp-post-feat-img-video) -->
        <div class="overflow-hidden rounded-xs bg-black">
          <!-- YouTube: 16:9 Aspect Video Embed Langsung -->
          <div v-if="isYouTube && embedUrl" class="aspect-video w-full bg-black">
            <iframe
              :src="embedUrl"
              :title="media.title"
              class="h-full w-full border-0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerpolicy="strict-origin-when-cross-origin"
              allowfullscreen
            ></iframe>
          </div>

          <!-- TikTok: Player Vertikal 9:16 Responsif Langsung -->
          <div v-else-if="isTikTok && embedUrl" class="py-6 flex items-center justify-center bg-neutral-950">
            <div class="w-full max-w-[340px] h-[580px] rounded-lg overflow-hidden bg-black">
              <iframe
                :src="embedUrl"
                :title="media.title"
                class="h-full w-full border-0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
              ></iframe>
            </div>
          </div>

          <!-- Instagram: Frame Post / Reel Responsif Langsung -->
          <div v-else-if="isInstagram && embedUrl" class="py-6 flex items-center justify-center bg-neutral-950">
            <div class="w-full max-w-[460px] h-[600px] rounded-lg overflow-hidden bg-white">
              <iframe
                :src="embedUrl"
                :title="media.title"
                class="h-full w-full border-0"
                allowtransparency="true"
                scrolling="no"
                allow="encrypted-media"
                allowfullscreen
              ></iframe>
            </div>
          </div>

          <!-- Generic Embed Fallback -->
          <div v-else-if="embedUrl" class="aspect-video w-full bg-black">
            <iframe
              :src="embedUrl"
              :title="media.title"
              class="h-full w-full border-0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            ></iframe>
          </div>

          <!-- Fallback if no embed player -->
          <div v-else class="relative aspect-video w-full bg-neutral-100 flex items-center justify-center text-center p-6">
            <img
              v-if="getThumbnailUrl(media)"
              :src="getThumbnailUrl(media)"
              :alt="media.title"
              class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="relative z-10 max-w-md space-y-2">
              <h3 class="text-neutral-900 font-bold text-base line-clamp-2">
                {{ media.title }}
              </h3>
              <a
                v-if="media.mediaUrl"
                :href="media.mediaUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-xs bg-neutral-900 text-white px-4 py-2 text-xs font-bold hover:bg-neutral-800 transition shadow"
              >
                <span>Buka di {{ media.category || 'Platform Asli' }}</span>
                <IconExternalLink :size="14" />
              </a>
            </div>
          </div>
        </div>

        <!-- Article Content Body (mvp-content-main) -->
        <div class="font-serif text-[17px] leading-relaxed text-neutral-800 space-y-5 pt-2">
          <!-- Content HTML body -->
          <div
            v-if="media.content"
            class="prose prose-neutral max-w-none prose-p:text-neutral-800 prose-p:leading-relaxed prose-p:font-serif prose-headings:font-serif prose-headings:font-bold prose-headings:text-neutral-900 prose-blockquote:border-isf-red prose-blockquote:bg-neutral-50/70"
            v-html="media.content"
          ></div>

          <!-- Fallback summary if no HTML content -->
          <p v-else-if="media.summary" class="italic text-[18px] text-neutral-800 border-l-4 border-isf-red pl-4 py-1.5 bg-neutral-50/80">
            {{ media.summary }}
          </p>
        </div>

        <!-- Bottom Share Bar (SHARE THIS ARTICLE) -->
        <div class="pt-8 mt-10 border-t border-neutral-200">
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-700 font-mono mb-3">
            SHARE THIS ARTICLE:
          </p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="copyLink"
              title="Salin Tautan"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 transition"
            >
              <IconCheck v-if="copied" :size="14" class="text-emerald-600" />
              <IconCopy v-else :size="14" />
              <span>{{ copied ? 'Tersalin' : 'Salin Tautan' }}</span>
            </button>
            <button
              type="button"
              @click="shareWhatsApp"
              title="Bagikan ke WhatsApp"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-emerald-50 hover:text-emerald-600 text-neutral-700 transition"
            >
              <IconBrandWhatsapp :size="15" />
              <span>WhatsApp</span>
            </button>
            <button
              type="button"
              @click="shareTwitter"
              title="Bagikan ke X"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-xs border border-neutral-300 bg-white hover:bg-neutral-100 text-neutral-700 transition"
            >
              <IconBrandX :size="15" />
              <span>X (Twitter)</span>
            </button>
          </div>
        </div>
      </div>

      <!-- SIDEBAR / RIGHT COLUMN (Col 4 - mvp-side-wrap) -->
      <aside class="space-y-8 lg:col-span-4">
        <!-- Back to Beranda Link -->
        <div>
          <router-link
            to="/beranda"
            class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-neutral-600 hover:text-isf-red transition-colors"
          >
            <IconArrowLeft :size="15" />
            Kembali ke Beranda
          </router-link>
        </div>

        <!-- Sidebar Tabbed Widget (mvp_tabber_widget: Terkini / Video) -->
        <section class="border border-neutral-300 bg-white rounded-xs overflow-hidden shadow-2xs">
          <!-- Widget Tab Buttons -->
          <div class="flex items-center border-b border-neutral-300 bg-neutral-100">
            <button
              type="button"
              @click="activeSidebarTab = 'terkini'"
              :class="[
                'flex-1 py-2.5 text-xs font-bold uppercase tracking-wider text-center transition-colors cursor-pointer border-b-2 -mb-[1px]',
                activeSidebarTab === 'terkini'
                  ? 'border-isf-red bg-white text-neutral-900 font-extrabold'
                  : 'border-transparent text-neutral-500 hover:text-neutral-900'
              ]"
            >
              Terkini
            </button>
            <button
              type="button"
              @click="activeSidebarTab = 'video'"
              :class="[
                'flex-1 py-2.5 text-xs font-bold uppercase tracking-wider text-center transition-colors cursor-pointer border-b-2 -mb-[1px]',
                activeSidebarTab === 'video'
                  ? 'border-isf-red bg-white text-neutral-900 font-extrabold'
                  : 'border-transparent text-neutral-500 hover:text-neutral-900'
              ]"
            >
              Video Lainnya
            </button>
          </div>

          <!-- Tab Content: Terkini (Articles with 72x72 Thumbnails) -->
          <div v-if="activeSidebarTab === 'terkini'" class="p-4">
            <div v-if="recentArticles.length === 0" class="text-xs text-neutral-500 py-3 text-center">
              Belum ada artikel terkini.
            </div>
            <ul v-else class="divide-y divide-neutral-200">
              <li
                v-for="art in recentArticles"
                :key="art.id"
                class="py-3 first:pt-0 last:pb-0"
              >
                <router-link
                  :to="art.to || '/beranda'"
                  class="group flex items-start gap-3"
                >
                  <!-- Square Thumbnail (80x80 style) -->
                  <div class="w-[72px] h-[72px] shrink-0 rounded-xs overflow-hidden bg-neutral-100 border border-neutral-200">
                    <img
                      v-if="art.imageUrl"
                      :src="art.imageUrl"
                      :alt="art.title"
                      class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <ImagePlaceholder
                      v-else
                      :label="art.title"
                      bare
                      class="h-full w-full"
                    />
                  </div>

                  <!-- Text: Category, Date, Headline -->
                  <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex items-center gap-1.5 text-[10.5px]">
                      <span class="font-bold uppercase tracking-wider text-isf-red font-space-grotesk">
                        {{ art.category || 'Kebijakan' }}
                      </span>
                      <span class="text-neutral-300">•</span>
                      <time v-if="art.published_at" class="text-neutral-500 font-mono">
                        {{ formatDate(art.published_at) }}
                      </time>
                    </div>
                    <h4 class="font-headline text-[13.5px] leading-snug font-bold text-neutral-900 group-hover:text-isf-red transition-colors line-clamp-2">
                      {{ art.title }}
                    </h4>
                  </div>
                </router-link>
              </li>
            </ul>
          </div>

          <!-- Tab Content: Video Lainnya -->
          <div v-else class="p-4">
            <div v-if="relatedVideos.length === 0" class="text-xs text-neutral-500 py-3 text-center">
              Belum ada video lainnya.
            </div>
            <ul v-else class="divide-y divide-neutral-200">
              <li
                v-for="rel in relatedVideos"
                :key="rel.id"
                class="py-3 first:pt-0 last:pb-0"
              >
                <router-link
                  :to="'/media/' + rel.id"
                  class="group flex items-start gap-3"
                >
                  <!-- 16:9 Thumbnail -->
                  <div class="relative w-24 shrink-0 aspect-video rounded-xs overflow-hidden bg-neutral-100 border border-neutral-300">
                    <img
                      v-if="getThumbnailUrl(rel)"
                      :src="getThumbnailUrl(rel)"
                      :alt="rel.title"
                      class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <ImagePlaceholder
                      v-else
                      :label="rel.title"
                      aspect="video"
                      bare
                      class="h-full w-full"
                    />
                    <span
                      class="absolute bottom-1 left-1 rounded bg-black/80 px-1 py-0.2 text-[8px] font-bold text-white uppercase tracking-wider"
                    >
                      {{ rel.category || 'Video' }}
                    </span>
                  </div>

                  <!-- Text -->
                  <div class="min-w-0 flex-1 space-y-1">
                    <h4 class="text-xs font-bold text-neutral-900 leading-snug group-hover:text-isf-red transition-colors line-clamp-2">
                      {{ rel.title }}
                    </h4>
                    <time v-if="rel.publishedAt" class="text-[10px] text-neutral-500 block font-mono">
                      {{ formatDate(rel.publishedAt) }}
                    </time>
                  </div>
                </router-link>
              </li>
            </ul>
          </div>
        </section>

        <!-- Executive Briefing Box -->
        <div class="rounded-xs border border-neutral-300 bg-neutral-900 p-5 text-white space-y-3 shadow-sm">
          <div class="flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
            <h4 class="text-xs font-bold tracking-wider uppercase text-neutral-300">
              Executive Briefing ISF
            </h4>
          </div>
          <p class="text-xs text-neutral-300 leading-relaxed">
            Dapatkan ringkasan intelijen kebijakan, telaah draf regulasi, dan analisis perundang-undangan langsung di email Anda setiap pekan.
          </p>
          <router-link
            to="/register"
            class="inline-block w-full text-center rounded-xs bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 transition shadow-xs"
          >
            Daftar Akses Anggota
          </router-link>
        </div>
      </aside>
    </article>
  </div>
</template>
