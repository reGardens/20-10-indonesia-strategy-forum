<script setup>
/**
 * Detail Profil Penulis Opini Publik — /penulis/:slug
 * Menampilkan identitas penulis member, afiliasi, bio, dan daftar artikel terverifikasi yang telah terbit di ISF.
 */
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  IconArrowLeft,
  IconAward,
  IconBook,
  IconCalendarEvent,
  IconClock,
  IconMapPin,
  IconShare,
  IconUserCheck,
  IconPhoto,
} from '@tabler/icons-vue'
import {
  ArtikelSkeleton,
  CategoryLabel,
  ImagePlaceholder,
  IsfLink,
  SectionHeading,
} from '@/Components/Isf'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import { getPenulisDetail } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'

const route = useRoute()

const { data, status, load } = useIsfContent(() =>
  getPenulisDetail(route.params.slug)
)

const author = computed(() => data.value?.data ?? null)
const articles = computed(() => author.value?.articles ?? [])

watch(
  () => author.value?.name,
  (name) => {
    if (name && typeof document !== 'undefined') {
      document.title = `${name} · Profil Penulis · Indonesia Strategy Forum`
    }
  }
)

watch(
  () => route.params.slug,
  () => load(),
  { immediate: true }
)

function getInitials(name) {
  if (!name) return 'M'
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
}

function formatDate(val) {
  if (!val) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(val))
  } catch {
    return val
  }
}
</script>

<template>
  <div class="isf-mobile-gutter w-full px-4 py-8 sm:px-6 sm:py-10 md:px-8 xl:px-12">
    <!-- Back to Opini Publik Link -->
    <div class="mb-6 flex items-center justify-between">
      <IsfLink
        to="/opini-publik"
        class="inline-flex items-center gap-2 text-xs font-semibold text-neutral-600 hover:text-isf-red transition-colors"
      >
        <IconArrowLeft class="w-4 h-4" />
        Kembali ke Opini Publik
      </IsfLink>
      <div class="text-[11px] font-mono text-neutral-400">
        Rubrik Opini & Aspirasi Warga
      </div>
    </div>

    <!-- Loading State: Presisi 1:1 Mengikuti Bentuk Asli Halaman -->
    <div v-if="status === 'loading'" class="space-y-10 sm:space-y-12 animate-pulse" aria-busy="true" aria-label="Memuat profil penulis">
      <!-- Author Header Skeleton -->
      <div class="bg-white rounded-xl border border-neutral-300 border-t-4 border-t-isf-red p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 sm:gap-6">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-neutral-200 shrink-0"></div>
          <div class="space-y-3 flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <div class="h-5 w-36 bg-neutral-200 rounded-full"></div>
              <div class="h-5 w-24 bg-neutral-200 rounded-full"></div>
            </div>
            <div class="h-7 w-64 bg-neutral-300 rounded"></div>
            <div class="h-4 w-44 bg-neutral-200 rounded"></div>
            <div class="space-y-1.5 pt-1">
              <div class="h-3 w-full bg-neutral-200 rounded"></div>
              <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
            </div>
          </div>
          <div class="sm:text-right border-t sm:border-t-0 sm:border-l border-neutral-200 pt-4 sm:pt-0 sm:pl-6 w-full sm:w-auto flex sm:flex-col items-center sm:items-end justify-between space-y-1">
            <div class="h-3 w-20 bg-neutral-200 rounded"></div>
            <div class="h-8 w-12 bg-neutral-300 rounded"></div>
            <div class="h-3 w-16 bg-neutral-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Articles Grid Skeleton -->
      <div class="space-y-6">
        <div class="border-b border-neutral-200 pb-3">
          <div class="h-3 w-44 bg-neutral-200 rounded mb-1.5"></div>
          <div class="h-6 w-56 bg-neutral-300 rounded"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="i in 3"
            :key="i"
            class="flex flex-col justify-between bg-white rounded-xl border border-neutral-200 overflow-hidden"
          >
            <div class="relative aspect-[16/10] bg-neutral-200">
              <div class="absolute top-2.5 left-2.5 h-5 w-20 bg-neutral-300 rounded-full"></div>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <div class="h-3 w-16 bg-neutral-200 rounded"></div>
                  <div class="h-3 w-12 bg-neutral-200 rounded"></div>
                </div>
                <div class="h-5 w-full bg-neutral-300 rounded"></div>
                <div class="h-5 w-4/5 bg-neutral-300 rounded"></div>
                <div class="h-3 w-full bg-neutral-200 rounded mt-1"></div>
                <div class="h-3 w-3/4 bg-neutral-200 rounded"></div>
              </div>
              <div class="pt-3 border-t border-neutral-200 flex items-center justify-between">
                <div class="h-3 w-24 bg-neutral-200 rounded"></div>
                <div class="h-3 w-20 bg-neutral-300 rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="status === 'error' || !author" class="py-12">
      <ErrorMessage
        message="Profil penulis tidak ditemukan atau belum memiliki naskah artikel yang disetujui untuk tayang."
        @retry="load"
      />
      <div class="mt-4 text-center">
        <IsfLink
          to="/opini-publik"
          class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold rounded-lg bg-isf-red text-white hover:bg-isf-red/90 transition-colors shadow-sm"
        >
          Lihat Opini Publik
        </IsfLink>
      </div>
    </div>

    <!-- Author Profile Content -->
    <div v-else class="space-y-10 sm:space-y-12">
      <!-- ═══ AUTHOR HEADER PROFILE CARD ═══ -->
      <section class="bg-white rounded-xl border border-neutral-300 border-t-4 border-t-isf-red p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 sm:gap-6">
          <!-- 1:1 Square Avatar -->
          <div class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden border-2 border-neutral-200 bg-neutral-900 shrink-0 shadow-sm">
            <img
              v-if="author.avatar"
              :src="author.avatar"
              :alt="author.name"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              class="w-full h-full flex items-center justify-center font-bold text-2xl bg-gradient-to-br from-neutral-800 to-neutral-950 text-white font-space-grotesk"
            >
              {{ getInitials(author.name) }}
            </div>
            <!-- Verification Tag -->
            <div class="absolute bottom-1 right-1 bg-isf-red text-white p-1 rounded-full shadow">
              <IconUserCheck class="w-3.5 h-3.5" />
            </div>
          </div>

          <!-- Author Info -->
          <div class="space-y-2 flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-isf-red/10 text-isf-red border border-isf-red/20">
                <IconAward class="w-3.5 h-3.5" />
                Member Terverifikasi ISF
              </span>
              <span
                v-if="author.city"
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-neutral-100 text-neutral-600 border border-neutral-200"
              >
                <IconMapPin class="w-3.5 h-3.5" />
                {{ author.city }}
              </span>
            </div>

            <h1 class="font-headline font-bold text-2xl sm:text-3xl text-neutral-900 leading-tight">
              {{ author.name }}
            </h1>

            <p class="text-xs sm:text-sm text-neutral-600 font-medium font-space-grotesk">
              {{ author.role || 'Member Komunitas Indonesia Strategy Forum' }}
            </p>

            <p class="text-xs text-neutral-500 font-serif leading-relaxed max-w-2xl pt-1">
              {{ author.bio }}
            </p>
          </div>

          <!-- Total Articles Pill Counter -->
          <div class="sm:text-right border-t sm:border-t-0 sm:border-l border-neutral-200 pt-4 sm:pt-0 sm:pl-6 w-full sm:w-auto flex sm:flex-col items-center sm:items-end justify-between">
            <span class="text-[11px] font-mono text-neutral-400 uppercase tracking-wider block">Total Karya</span>
            <span class="font-space-grotesk text-2xl sm:text-3xl font-extrabold text-neutral-900 leading-none">
              {{ author.total_articles || articles.length }}
            </span>
            <span class="text-[10px] text-neutral-500 font-serif mt-1 block">Artikel Terbit</span>
          </div>
        </div>
      </section>

      <!-- ═══ ARTICLES LIST SECTION ═══ -->
      <section class="space-y-6">
        <div class="border-b border-neutral-200 pb-3 flex items-center justify-between">
          <SectionHeading
            title="Daftar Karya & Analisis"
            size="md"
          >
            <template #eyebrow>Artikel Kontribusi yang Telah Melalui Kurasi Dewan Redaksi</template>
          </SectionHeading>
        </div>

        <div v-if="articles.length === 0" class="py-12 text-center bg-white rounded-xl border border-neutral-200 p-8">
          <IconBook class="w-10 h-10 text-neutral-300 mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-700">Belum ada artikel yang ditayangkan.</p>
          <p class="text-xs text-neutral-500 mt-1">Artikel baru yang diajukan oleh penulis sedang dalam tahap peninjauan redaksi.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article
            v-for="(card, cIdx) in articles"
            :key="card.slug || cIdx"
            class="group flex flex-col justify-between bg-white hover:bg-neutral-50/80 rounded-xl border border-neutral-200/90 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-isf-red/40"
          >
            <!-- Card Image (Aspect 16:10, full-bleed without black bar) -->
            <IsfLink :to="card.to || `/artikel/${card.slug}`" class="block relative aspect-[16/10] overflow-hidden bg-neutral-200">
              <img
                v-if="card.imageUrl || card.thumbnail"
                :src="card.imageUrl || card.thumbnail"
                :alt="card.imageAlt || card.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <!-- Clean neutral placeholder without black bars -->
              <div
                v-else
                class="w-full h-full flex flex-col items-center justify-center bg-neutral-200 text-neutral-400 group-hover:bg-neutral-300/60 transition-colors select-none"
              >
                <IconPhoto class="w-10 h-10 stroke-[1.25] text-neutral-400 group-hover:text-neutral-500 transition-colors" />
              </div>

              <!-- Badge By Member -->
              <div class="absolute top-2.5 left-2.5">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-isf-red text-white shadow-sm">
                  <IconAward class="w-3 h-3" />
                  By Member
                </span>
              </div>
            </IsfLink>

            <!-- Card Body -->
            <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
              <div class="space-y-2">
                <div class="flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                  <span class="text-isf-red font-bold uppercase tracking-wide">
                    {{ card.subcategory ? card.subcategory.toUpperCase() : 'OPINI' }}
                  </span>
                  <span class="flex items-center gap-1">
                    <IconClock class="w-3 h-3" />
                    {{ card.readingMinutes || 5 }} mnt
                  </span>
                </div>

                <h3 class="font-headline font-bold text-base leading-snug text-neutral-900 group-hover:text-isf-red transition-colors line-clamp-2">
                  <IsfLink :to="card.to || `/artikel/${card.slug}`">
                    {{ card.title }}
                  </IsfLink>
                </h3>

                <p v-if="card.summary || card.excerpt" class="text-neutral-600 text-xs leading-relaxed line-clamp-3 font-serif">
                  {{ card.summary || card.excerpt }}
                </p>
              </div>

              <!-- Footer with Date & CTA -->
              <div class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 mt-auto">
                <span class="text-[11px] font-mono">
                  {{ formatDate(card.publishedAt) }}
                </span>
                <IsfLink
                  :to="card.to || `/artikel/${card.slug}`"
                  class="text-isf-red font-semibold hover:underline inline-flex items-center gap-1"
                >
                  Baca Artikel →
                </IsfLink>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

