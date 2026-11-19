<script setup>
/**
 * IsfBannerCarousel — Horizontal banner carousel with circular arrow navigation (plain Vue, zero extra npm packages).
 * - Full horizontal slider with smooth CSS transition
 * - Circular arrow navigation buttons (Prev & Next) with ISF identity styling
 * - Takeout drag / swiping gestures to ensure clean vertical scrolling
 * - Keyboard navigation (Left/Right arrow keys)
 * - Auto-advance with pause on hover/focus; obeys prefers-reduced-motion
 * - Bottom slot features integrated IsfHotNewsTicker component
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { cn } from "@/lib/utils";
import ImagePlaceholder from "./ImagePlaceholder.vue";
import IsfLink from "./IsfLink.vue";
import IsfHotNewsTicker from "./IsfHotNewsTicker.vue";

const props = defineProps({
    // Each slide: { id, kicker, title, deck, to, imageLabel, imageUrl? }
    slides: {
        type: Array,
        required: true,
    },
    // Curated hot news items passed into the ticker
    hotNews: {
        type: Array,
        default: () => [],
    },
    interval: {
        type: Number,
        default: 5000,
    },
    autoplay: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: "Banner sorotan",
    },
    class: {
        type: String,
        default: "",
    },
});

const active = ref(0);
const userPaused = ref(false);
const hovered = ref(false);
const focused = ref(false);
const reducedMotion = ref(false);

const canRotate = computed(() => props.slides.length > 1);
const running = computed(
    () =>
        props.autoplay &&
        canRotate.value &&
        !userPaused.value &&
        !hovered.value &&
        !focused.value &&
        !reducedMotion.value,
);

function go(index) {
    const n = props.slides.length;
    if (!n) return;
    active.value = (index + n) % n;
}
function next() {
    go(active.value + 1);
}
function prev() {
    go(active.value - 1);
}

// Timer auto-advance
let timer = null;
function sync() {
    if (timer) window.clearInterval(timer);
    timer = null;
    if (running.value) timer = window.setInterval(next, props.interval);
}
watch([running, () => props.interval], sync);

function onFocusOut(event) {
    focused.value = event.currentTarget.contains(event.relatedTarget);
}

let mq = null;
function onMotionChange(event) {
    reducedMotion.value = event.matches;
    if (event.matches) userPaused.value = true;
}

onMounted(() => {
    mq = window.matchMedia?.("(prefers-reduced-motion: reduce)") || null;
    if (mq?.matches) {
        reducedMotion.value = true;
        userPaused.value = true;
    }
    mq?.addEventListener?.("change", onMotionChange);
    sync();
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
    timer = null;
    mq?.removeEventListener?.("change", onMotionChange);
});
</script>

<template>
    <section
        :class="
            cn(
                'isf-banner-carousel relative border border-neutral-300 bg-white overflow-hidden',
                props.class,
            )
        "
        aria-roledescription="carousel"
        :aria-label="label"
        @mouseenter="hovered = true"
        @mouseleave="hovered = false"
        @focusin="focused = true"
        @focusout="onFocusOut"
        @keydown.left.prevent="
            prev();
            sync();
        "
        @keydown.right.prevent="
            next();
            sync();
        "
    >
        <!-- Bar Navigasi Khusus Mobile: Diletakkan DI ATAS banner di sebelah kanan (garis panah saja) -->
        <div
            v-if="canRotate"
            class="flex sm:hidden items-center justify-end px-2 py-0.5 bg-transparent"
        >
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    data-testid="banner-prev-button"
                    aria-label="Slide sebelumnya"
                    class="banner-arrow-btn-mobile flex items-center justify-center p-1 text-neutral-700 hover:text-isf-red active:text-isf-red transition-colors bg-transparent border-0 cursor-pointer select-none"
                    style="background: transparent !important; border: none !important; outline: none !important;"
                    @click="
                        prev();
                        sync();
                    "
                >
                    <svg
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>
                <button
                    type="button"
                    data-testid="banner-next-button"
                    aria-label="Slide berikutnya"
                    class="banner-arrow-btn-mobile flex items-center justify-center p-1 text-neutral-700 hover:text-isf-red active:text-isf-red transition-colors bg-transparent border-0 cursor-pointer select-none"
                    style="background: transparent !important; border: none !important; outline: none !important;"
                    @click="
                        next();
                        sync();
                    "
                >
                    <svg
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Horizontal Slider Track (Clean without gesture interception) -->
        <div
            class="overflow-hidden relative w-full select-none"
            :aria-live="running ? 'off' : 'polite'"
        >
            <div
                class="flex w-full will-change-transform"
                :style="{
                    transform: `translateX(-${active * 100}%)`,
                    transition: 'transform 450ms cubic-bezier(0.25, 1, 0.5, 1)',
                }"
            >
                <div
                    v-for="(slide, i) in slides"
                    :key="slide.id"
                    role="group"
                    aria-roledescription="slide"
                    :aria-label="`${i + 1} dari ${slides.length}`"
                    :aria-hidden="i === active ? undefined : 'true'"
                    class="w-full min-w-full flex-shrink-0 relative select-none"
                >
                    <IsfLink
                        :to="slide.to || '#'"
                        :title="slide.imageLabel || slide.title || 'Buka tautan banner'"
                        :aria-label="`Buka banner: ${slide.imageLabel || slide.title || 'Banner Sorotan'}`"
                        class="group block relative overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2 select-none"
                    >
                        <ImagePlaceholder
                            :label="slide.imageLabel || slide.title || 'Banner Sorotan'"
                            :src="slide.imageUrl || ''"
                            :priority="i === 0"
                            aspect="banner"
                            bare
                            class="w-full pointer-events-none select-none transition-transform duration-300 ease-out group-hover:scale-[1.008]"
                        />
                    </IsfLink>
                </div>
            </div>

            <!-- Arrow Navigation Buttons Desktop (sm+) - Line icons, on sides -->
            <template v-if="canRotate">
                <button
                    type="button"
                    data-testid="banner-prev-button-desktop"
                    aria-label="Slide sebelumnya"
                    class="banner-arrow-btn hidden sm:flex absolute sm:left-3 md:sm:left-4 top-1/2 -translate-y-1/2 z-20 items-center justify-center p-2 text-white transition-all duration-200 cursor-pointer select-none bg-transparent border-0"
                    style="background: transparent !important; background-color: transparent !important; border: none !important; outline: none !important;"
                    @click="
                        prev();
                        sync();
                    "
                >
                    <svg
                        class="banner-arrow-icon w-8 h-8 md:w-9 md:h-9 text-white transition-all duration-200"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>

                <button
                    type="button"
                    data-testid="banner-next-button-desktop"
                    aria-label="Slide berikutnya"
                    class="banner-arrow-btn hidden sm:flex absolute sm:right-3 md:sm:right-4 top-1/2 -translate-y-1/2 z-20 items-center justify-center p-2 text-white transition-all duration-200 cursor-pointer select-none bg-transparent border-0"
                    style="background: transparent !important; background-color: transparent !important; border: none !important; outline: none !important;"
                    @click="
                        next();
                        sync();
                    "
                >
                    <svg
                        class="banner-arrow-icon w-8 h-8 md:w-9 md:h-9 text-white transition-all duration-200"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </template>
        </div>

        <!-- Slot Ticker Hot News -->
        <IsfHotNewsTicker :items="hotNews" />
    </section>
</template>

<style scoped>
.banner-arrow-btn {
    background: transparent !important;
    background-color: transparent !important;
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}
.banner-arrow-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.75)) drop-shadow(0 0 6px rgba(0, 0, 0, 0.5));
}
.banner-arrow-btn:hover .banner-arrow-icon {
    color: #E10600 !important;
    transform: scale(1.15);
    filter: drop-shadow(0 2px 6px rgba(225, 6, 0, 0.6)) drop-shadow(0 0 8px rgba(0, 0, 0, 0.6));
}
.banner-arrow-btn:active .banner-arrow-icon {
    transform: scale(0.95);
}
.banner-arrow-btn:focus-visible .banner-arrow-icon {
    color: #E10600 !important;
}
</style>
