<script setup>
/**
 * IsfTopRankCarousel.vue
 *
 * Modul Top Rank Artikel 1 s/d 3 of the Week di Beranda publik (Issue #93).
 * Diposisikan di kolom kiri tepat di bawah widget ISF Insight.
 *
 * Desain & Tata Letak:
 * - Styling container dan header diselaraskan persis dengan ISF Insight Slider:
 *   Header (.isf-col-head), counter 1/3, tombol prev/next persegi rounded dengan SVG.
 * - Badge peringkat:
 *     - Top 1: Gold
 *     - Top 2: Red
 *     - Top 3: Silver
 * - Card: Judul, deskripsi ringkas, nama penulis, dan tanggal menyatu di dalam bagian bawah image (overlay gradient).
 *   Tinggi card proporsional dan tidak berantakan/sesak di kolom beranda.
 * - Carousel: Auto-slide timer, jeda saat hover, dan dot/pill indicator di bawah card selaras ISF Insight.
 */
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import IsfLink from '@/Components/Isf/IsfLink.vue'
import { formatDate } from '@/Utils/formatters'

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    interval: {
        type: Number,
        default: 8000,
    },
    cellPad: {
        type: String,
        default: 'lg:px-4 xl:px-[30px]',
    },
    class: {
        type: String,
        default: '',
    },
})

const defaultDummyItems = [
    {
        rank: 1,
        title: 'Konsolidasi Parlemen: Peta Koalisi Pembahasan Agenda Legislasi Prioritas 2026',
        summary: 'Telaah mendalam arah kebijakan pembangunan dan stabilitas regulasi nasional di Senayan.',
        author: 'Tim Riset ISF',
        imageUrl: 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=600&auto=format&fit=crop',
        publishedAt: '24 Nov 2026',
        to: '/beranda',
    },
    {
        rank: 2,
        title: 'Pembaruan KUHP Nasional: Standardisasi Penilaian Risiko Pidana Korporasi',
        summary: 'Pedoman operasional kepatuhan bagi organ perseroan guna memitigasi risiko delik korporasi.',
        author: 'Dewan Pakar Hukum',
        imageUrl: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=600&auto=format&fit=crop',
        publishedAt: '23 Nov 2026',
        to: '/beranda',
    },
    {
        rank: 3,
        title: 'Kebijakan Fiskal Hijau: Skema Insentif Pajak & Transisi Energi Bersih 2026',
        summary: 'Pemerintah mematangkan peta jalan insentif perpajakan bagi industri pelopor energi baru terbarukan.',
        author: 'Analis Kebijakan Publik',
        imageUrl: 'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?q=80&w=600&auto=format&fit=crop',
        publishedAt: '22 Nov 2026',
        to: '/beranda',
    },
]

const slides = computed(() => {
    if (Array.isArray(props.items) && props.items.length > 0) {
        return props.items.map((it, idx) => {
            const rawRank = it.rank ?? (idx + 1)
            const parsedRank = Number(rawRank)
            return {
                ...it,
                rank: !isNaN(parsedRank) && parsedRank > 0 ? parsedRank : idx + 1,
                title: it.title || '',
                summary: it.summary || '',
                author: it.author || it.author_name || 'Redaksi ISF',
                imageUrl: it.image_url || it.imageUrl || '',
                publishedAt: it.published_at || it.publishedAt || '',
                to: it.to || it.url || '/beranda',
            }
        })
    }
    return defaultDummyItems
})

const activeIndex = ref(0)
const isHovered = ref(false)
const totalSlides = computed(() => slides.value.length)
const canSlide = computed(() => totalSlides.value > 1)
const currentSlide = computed(() => slides.value[activeIndex.value] || slides.value[0])

const rankNum = computed(() => {
    const raw = currentSlide.value?.rank
    const parsed = Number(raw)
    return !isNaN(parsed) && parsed > 0 ? parsed : activeIndex.value + 1
})

function next() {
    if (totalSlides.value <= 1) return
    activeIndex.value = (activeIndex.value + 1) % totalSlides.value
}

function prev() {
    if (totalSlides.value <= 1) return
    activeIndex.value = (activeIndex.value - 1 + totalSlides.value) % totalSlides.value
}

function goTo(idx) {
    if (idx >= 0 && idx < totalSlides.value) {
        activeIndex.value = idx
    }
}

// Auto slide timer
let timer = null
function startTimer() {
    stopTimer()
    if (canSlide.value && !isHovered.value) {
        timer = window.setInterval(next, props.interval)
    }
}
function stopTimer() {
    if (timer) {
        window.clearInterval(timer)
        timer = null
    }
}

watch([isHovered, canSlide], startTimer)

onMounted(startTimer)
onBeforeUnmount(stopTimer)

// Touch swipe support for mobile
let touchStartX = 0
let touchEndX = 0

function handleTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX
}

function handleTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenX
    const diff = touchStartX - touchEndX
    if (Math.abs(diff) > 40) {
        if (diff > 0) next()
        else prev()
    }
}
</script>

<template>
    <section
        aria-labelledby="top-rank-title"
        :class="['isf-top-rank-module relative border-t border-neutral-300 pt-5', props.class]"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd"
    >
        <!-- Single Column Header (sama persis dengan format ISF Insight) -->
        <div :class="['isf-col-head flex items-center justify-between', cellPad]">
            <p id="top-rank-title" class="isf-label">
                <span>TOP ARTIKEL</span>
            </p>

            <!-- Controls (Prev / Next & Counter) selaras ISF Insight -->
            <div v-if="canSlide" class="flex items-center gap-1.5 text-neutral-500">
                <span class="font-ui text-[11px] font-medium text-neutral-600 mr-1">
                    {{ activeIndex + 1 }}/{{ totalSlides }}
                </span>
                <button
                    type="button"
                    class="flex h-6 w-6 items-center justify-center rounded border border-neutral-300 bg-white text-neutral-700 hover:border-isf-red hover:text-isf-red transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-isf-red cursor-pointer"
                    aria-label="Peringkat sebelumnya"
                    @click="prev"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button
                    type="button"
                    class="flex h-6 w-6 items-center justify-center rounded border border-neutral-300 bg-white text-neutral-700 hover:border-isf-red hover:text-isf-red transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-isf-red cursor-pointer"
                    aria-label="Peringkat berikutnya"
                    @click="next"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Card: Konten menyatu di dalam bagian bawah image (Rasio 4x3 sewajarnya card) -->
        <div :class="['py-4', cellPad]">
            <div
                v-if="currentSlide"
                class="relative w-full aspect-[4/3] overflow-hidden rounded-[4px] border border-neutral-300 bg-neutral-900 shadow-xs"
            >
                <IsfLink
                    :to="currentSlide.to"
                    class="group relative block w-full h-full overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                >
                    <!-- Background Image yang mengisi seluruh card -->
                    <img
                        v-if="currentSlide.imageUrl"
                        :src="currentSlide.imageUrl"
                        :alt="currentSlide.title"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105 opacity-90"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="h-full w-full bg-neutral-800 flex items-center justify-center text-neutral-500"
                    >
                        <span class="font-ui text-xs">ISF Top Artikel</span>
                    </div>

                    <!-- Top-Left Badge: Top 1 Gold, Top 2 Red, Top 3 Silver -->
                    <div class="absolute top-2.5 left-2.5 z-10 select-none">
                        <!-- Top 1: Gold -->
                        <span
                            v-if="rankNum === 1"
                            class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 text-neutral-950 shadow-md border border-amber-300 font-space-grotesk"
                        >
                            <span>★</span>
                            <span>TOP 1</span>
                        </span>
                        <!-- Top 2: Red -->
                        <span
                            v-else-if="rankNum === 2"
                            class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider bg-isf-red text-white shadow-md border border-red-500 font-space-grotesk"
                        >
                            <span>★</span>
                            <span>TOP 2</span>
                        </span>
                        <!-- Top 3: Silver -->
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider bg-gradient-to-r from-slate-200 via-neutral-200 to-slate-300 text-neutral-800 shadow-md border border-neutral-300 font-space-grotesk"
                        >
                            <span>★</span>
                            <span>TOP 3</span>
                        </span>
                    </div>

                    <!-- Overlay Gradient & Konten Menyatu di Bagian Dalam Bawah Image -->
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent flex flex-col justify-end p-3 sm:p-4 text-white"
                    >
                        <!-- Judul Artikel -->
                        <h3 class="font-ui text-[13.5px] sm:text-[14.5px] font-bold leading-snug text-white group-hover:text-amber-200 transition-colors line-clamp-2">
                            {{ currentSlide.title }}
                        </h3>

                        <!-- Deskripsi Ringkas (1 baris agar image tetap terlihat luas) -->
                        <p
                            v-if="currentSlide.summary"
                            class="mt-1 font-serif text-[11px] sm:text-[11.5px] leading-snug text-neutral-200 line-clamp-1"
                        >
                            {{ currentSlide.summary }}
                        </p>

                        <!-- Nama Penulis dan Tanggal di Dasar Bagian Dalam Image -->
                        <div class="mt-2 pt-1.5 border-t border-white/20 flex items-center justify-between text-[10px] sm:text-[10.5px] text-neutral-300 font-ui">
                            <span class="truncate font-medium max-w-[65%] text-neutral-200">
                                {{ currentSlide.author }}
                            </span>
                            <span v-if="currentSlide.publishedAt" class="shrink-0 text-neutral-400 font-mono text-[10px]">
                                {{ formatDate(currentSlide.publishedAt) }}
                            </span>
                        </div>
                    </div>
                </IsfLink>
            </div>

            <!-- Dot / Pill Carousel Indicators Selaras ISF Insight -->
            <div
                v-if="canSlide"
                class="mt-2.5 flex items-center justify-center gap-1.5"
                role="tablist"
                aria-label="Pilihan peringkat Top Artikel"
            >
                <button
                    v-for="(_, idx) in slides"
                    :key="idx"
                    type="button"
                    role="tab"
                    :aria-selected="idx === activeIndex"
                    :aria-label="`Buka slide Top Artikel ${idx + 1}`"
                    class="h-1.5 transition-all duration-300 rounded-full cursor-pointer"
                    :class="
                        idx === activeIndex
                            ? 'w-6 bg-isf-red'
                            : 'w-2 bg-neutral-300 hover:bg-neutral-400'
                    "
                    @click="goTo(idx)"
                ></button>
            </div>
        </div>
    </section>
</template>
