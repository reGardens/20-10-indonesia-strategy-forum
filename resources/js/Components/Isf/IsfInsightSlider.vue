<script setup>
/**
 * IsfInsightSlider — Politico Poll Style Insight Carousel.
 * Meniru tata letak visual "The Politico Poll":
 * - Header: Red circle badge + "ISF INSIGHT" + navigasi panah
 * - Konten: Headline, deskripsi naratif + link "Baca selengkapnya", profil analis (avatar, nama, jabatan)
 * - Grafik batang horizontal presisi dengan persentase/angka di dalam bar
 * - Footer: Gelombang grafis mini equalizer khas visual poll + atribusi sumber
 * - Carousel: Mendukung banyak insight, auto-advance, jeda saat hover, dan dot pagination
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import IsfLink from "./IsfLink.vue";

const props = defineProps({
    insights: {
        type: Array,
        required: true,
    },
    interval: {
        type: Number,
        default: 7500,
    },
    cellPad: {
        type: String,
        default: "",
    },
    class: {
        type: String,
        default: "",
    },
});

const activeIndex = ref(0);
const isPaused = ref(false);
const isHovered = ref(false);

const currentInsight = computed(() => {
    if (!props.insights?.length) return null;
    return props.insights[activeIndex.value] || props.insights[0];
});

const totalSlides = computed(() => props.insights?.length || 0);
const canSlide = computed(() => totalSlides.value > 1);

function next() {
    if (!canSlide.value) return;
    activeIndex.value = (activeIndex.value + 1) % totalSlides.value;
}

function prev() {
    if (!canSlide.value) return;
    activeIndex.value =
        (activeIndex.value - 1 + totalSlides.value) % totalSlides.value;
}

function goTo(index) {
    activeIndex.value = index;
}

// Timer auto-slide
let timer = null;
function startTimer() {
    stopTimer();
    if (canSlide.value && !isPaused.value && !isHovered.value) {
        timer = window.setInterval(next, props.interval);
    }
}
function stopTimer() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

watch([isPaused, isHovered, canSlide], startTimer);

onMounted(startTimer);
onBeforeUnmount(stopTimer);

// Kalkulasi nilai bar chart untuk slide aktif
const activeBars = computed(() => {
    const rawData = currentInsight.value?.data || [];
    const maxVal = rawData.reduce(
        (max, item) => Math.max(max, Number(item.value) || 0),
        0,
    );
    return rawData.map((item, index) => {
        const val = Number(item.value) || 0;
        const pct = maxVal > 0 ? (val / maxVal) * 100 : 0;
        const rawUnit = (currentInsight.value?.unit || "").trim();
        const unit = (rawUnit.toLowerCase() === "persen" || rawUnit.toLowerCase() === "percent" || rawUnit === "%") ? "%" : rawUnit;
        const formattedUnit = unit ? (unit === "%" ? " %" : ` ${unit}`) : "";
        return {
            label: item.label,
            value: val,
            display: `${val}${formattedUnit}`,
            pct: Math.min(Math.max(pct, 14), 100),
            isTop: index === 0,
        };
    });
});
</script>

<template>
    <div
        v-if="currentInsight"
        class="isf-insight-module relative"
        :class="props.class"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <!-- Single Column Header: Red Circle + ISF Insight + Slide Nav Controls -->
        <div :class="['isf-col-head flex items-center justify-between', cellPad]">
            <p id="insight-title" class="isf-label">
                ISF Insight
            </p>

            <!-- Controls (Prev / Next & Counter) jika ada > 1 slide -->
            <div v-if="canSlide" class="flex items-center gap-1.5 text-neutral-500">
                <span class="font-ui text-[11px] font-medium text-neutral-600 mr-1">
                    {{ activeIndex + 1 }}/{{ totalSlides }}
                </span>
                <button
                    type="button"
                    class="flex h-6 w-6 items-center justify-center rounded border border-neutral-300 bg-white text-neutral-700 hover:border-isf-red hover:text-isf-red transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-isf-red"
                    aria-label="Insight sebelumnya"
                    @click="prev"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button
                    type="button"
                    class="flex h-6 w-6 items-center justify-center rounded border border-neutral-300 bg-white text-neutral-700 hover:border-isf-red hover:text-isf-red transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-isf-red"
                    aria-label="Insight berikutnya"
                    @click="next"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Card: Politico Poll Styled Box -->
        <div :class="['py-4', cellPad]">
            <figure
                data-testid="insight-chart"
                class="isf-chart-compact rounded-[4px] border border-neutral-300 bg-[#FAFAFA] p-4 sm:p-5 transition-all"
            >
                <figcaption>
                    <!-- Title -->
                    <h3
                        class="font-ui text-[16px] sm:text-[17px] font-bold leading-snug text-neutral-950"
                    >
                        {{ currentInsight.title }}
                    </h3>

                    <!-- Description + Inline "Baca selengkapnya" link -->
                    <p
                        v-if="currentInsight.description"
                        class="mt-2 font-body text-[13px] leading-relaxed text-neutral-700"
                    >
                        {{ currentInsight.description }}
                        <IsfLink
                            v-if="currentInsight.readMoreUrl"
                            :to="currentInsight.readMoreUrl"
                            class="ml-1 inline font-semibold text-isf-red hover:underline focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-isf-red"
                        >
                            Baca selengkapnya.
                        </IsfLink>
                    </p>
                </figcaption>

            <!-- Author / Analyst Byline Box (seperti pada Politico Poll) -->
            <div
                v-if="currentInsight.author"
                class="mt-3.5 flex items-center gap-3 border-t border-neutral-200 pt-3"
            >
                <img
                    v-if="currentInsight.author.avatar"
                    :src="currentInsight.author.avatar"
                    :alt="currentInsight.author.name"
                    class="h-9 w-9 rounded-full object-cover border border-neutral-300 shadow-xs shrink-0"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-neutral-700 font-ui font-bold text-[12px] shrink-0"
                >
                    {{ (currentInsight.author.name || "ISF").slice(0, 2).toUpperCase() }}
                </div>
                <div class="min-w-0">
                    <p class="font-ui text-[13px] font-bold leading-tight text-neutral-900 truncate">
                        {{ currentInsight.author.name }}
                    </p>
                    <p class="font-ui text-[11px] leading-tight text-neutral-500 truncate mt-0.5">
                        {{ currentInsight.author.role }}
                    </p>
                </div>
            </div>

            <!-- Horizontal Bar Chart -->
            <div class="mt-4 space-y-2 border-t border-neutral-200 pt-3.5">
                <div
                    v-for="bar in activeBars"
                    :key="bar.label"
                    class="flex items-center gap-2"
                >
                    <!-- Label Kiri -->
                    <span
                        class="w-28 sm:w-32 shrink-0 truncate font-ui text-[10px] sm:text-[10.5px] font-medium text-neutral-800"
                        :title="bar.label"
                    >
                        {{ bar.label }}
                    </span>

                    <!-- Bar Track & Fill -->
                    <div class="relative h-5 w-full bg-neutral-200/80 rounded-xs overflow-hidden flex items-center">
                        <div
                            class="flex h-full items-center justify-start px-2 font-ui text-[10px] font-bold text-white transition-all duration-500 ease-out whitespace-nowrap text-left"
                            :class="bar.isTop ? 'bg-isf-red' : 'bg-neutral-800'"
                            :style="{ width: `${bar.pct}%`, minWidth: '42px' }"
                        >
                            {{ bar.display }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer: Politico-style equalizer graphic + attribution -->
            <div class="mt-4 border-t border-neutral-200 pt-3">
                <!-- Baris 1: Logo Equalizer + ISF INSIGHT (rapi satu baris) -->
                <div class="flex items-center gap-2">
                    <!-- Equalizer Wave Graphic -->
                    <div class="flex items-end gap-[2px] h-3.5 shrink-0" aria-hidden="true">
                        <span class="w-[2px] h-1.5 bg-neutral-400 rounded-xs"></span>
                        <span class="w-[2px] h-2.5 bg-neutral-400 rounded-xs"></span>
                        <span class="w-[2px] h-3 bg-neutral-500 rounded-xs"></span>
                        <span class="w-[2px] h-2 bg-neutral-400 rounded-xs"></span>
                        <span class="w-[2px] h-3.5 bg-isf-red rounded-xs"></span>
                        <span class="w-[2px] h-2.5 bg-isf-red rounded-xs"></span>
                        <span class="w-[2px] h-1.5 bg-neutral-400 rounded-xs"></span>
                    </div>

                    <!-- Brand text: ISF INSIGHT -->
                    <span class="font-ui font-black text-[12px] tracking-wider text-neutral-900 uppercase whitespace-nowrap">
                        ISF <span class="font-normal text-neutral-600">INSIGHT</span>
                    </span>
                </div>

                <!-- Baris 2: Sumber Atribusi (di bawah logo seperti pada referensi Politico) -->
                <div class="mt-1 font-ui text-[10.5px] text-neutral-500 truncate">
                    <a
                        v-if="currentInsight.sourceUrl"
                        :href="currentInsight.sourceUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hover:text-isf-red hover:underline"
                    >
                        {{ currentInsight.sourceTitle || currentInsight.signature || "Data Riset ISF" }}
                    </a>
                    <span v-else>
                        {{ currentInsight.signature || currentInsight.sourceTitle || "Data Riset ISF" }}
                    </span>
                </div>
            </div>

            <!-- Screen reader accessible table for WCAG & test assertions -->
            <div class="sr-only">
                <table>
                    <caption>{{ currentInsight.title }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Kategori</th>
                            <th scope="col">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bar in activeBars" :key="bar.label">
                            <td>{{ bar.label }}</td>
                            <td>{{ bar.display }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </figure>

        <!-- Dot Pagination Indicator jika > 1 slide -->
        <div
            v-if="canSlide"
            class="mt-2.5 flex items-center justify-center gap-1.5"
            role="tablist"
            aria-label="Pilihan insight"
        >
            <button
                v-for="(_, idx) in insights"
                :key="idx"
                type="button"
                role="tab"
                :aria-selected="idx === activeIndex"
                :aria-label="`Buka slide insight ${idx + 1}`"
                class="h-1.5 transition-all duration-300 rounded-full"
                :class="
                    idx === activeIndex
                        ? 'w-6 bg-isf-red'
                        : 'w-2 bg-neutral-300 hover:bg-neutral-400'
                "
                @click="goTo(idx)"
            ></button>
        </div>
    </div>
</div>
</template>
