<script setup>
/**
 * IsfHotNewsTicker — Interactive text carousel ticker for Hot News.
 * Positioned in the slot below the banner.
 * - Vertical slide transition (up / down) matching natural ticker movement
 * - Gesture support: swipe up / drag up for next, swipe down / drag down for prev
 *   (also supports swipe left/right gracefully)
 * - Real-time vertical drag feedback (translateY)
 * - Indikator posisi berita di pojok kanan (titik, dapat diklik)
 * - Auto-advances every `interval` ms (pauses on hover, focus, drag, reduced-motion)
 * - Clickable headline with drag-safe click suppression
 * - Aligned with ISF editorial styling (Politico-inspired)
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { cn } from "@/lib/utils";
import IsfLink from "./IsfLink.vue";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    interval: {
        type: Number,
        default: 5000,
    },
    class: {
        type: String,
        default: "",
    },
});

const DEFAULT_NEWS = [
    {
        id: 1,
        title: "Pemerintah Matangkan Kebijakan Hilirisasi Energi Hijau dan Insentif Fiskal 2026",
        to: "/beranda",
        category: "Kebijakan Publik",
        published_at: "10 Nov 2026",
        status: "terbit",
    },
    {
        id: 2,
        title: "DPR dan Kementerian Keuangan Bahas Penyesuaian Tarif PPh Badan Sektor Prioritas",
        to: "/beranda",
        category: "Politik",
        published_at: "10 Nov 2026",
        status: "terbit",
    },
    {
        id: 3,
        title: "Bank Indonesia Pertahankan BI-Rate: Stabilitas Moneter Hadapi Volatilitas Global",
        to: "/beranda",
        category: "Kebijakan Publik",
        published_at: "09 Nov 2026",
        status: "terbit",
    },
];

const activeIndex = ref(0);
const isHovered = ref(false);
const isFocused = ref(false);
const userPaused = ref(false);
const reducedMotion = ref(false);
const slideDirection = ref("next");

// Drag & Swipe gesture state (Vertical)
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const dragDeltaY = ref(0);
const dragDeltaX = ref(0);
const hasDragged = ref(false);
const trackRef = ref(null);

const activeItems = computed(() => {
    const list =
        Array.isArray(props.items) && props.items.length > 0
            ? props.items.filter((item) => (item.status ?? "terbit") === "terbit")
            : DEFAULT_NEWS;
    return list.length > 0 ? list : DEFAULT_NEWS;
});

const totalItems = computed(() => activeItems.value.length);
const canRotate = computed(() => totalItems.value > 1);

const currentItem = computed(() => {
    if (totalItems.value === 0) return null;
    return activeItems.value[activeIndex.value % totalItems.value];
});

function go(index, dir = "next") {
    if (!canRotate.value) return;
    slideDirection.value = dir;
    const n = totalItems.value;
    activeIndex.value = (index + n) % n;
}

function next() {
    go(activeIndex.value + 1, "next");
}

function prev() {
    go(activeIndex.value - 1, "prev");
}

/** Lompat ke berita tertentu dari indikator; arah animasi mengikuti posisi. */
function goTo(index) {
    if (index === activeIndex.value) return;
    go(index, index > activeIndex.value ? "next" : "prev");
    startTimer();
}

// Timer
let timer = null;
const isRunning = computed(
    () =>
        canRotate.value &&
        !userPaused.value &&
        !isHovered.value &&
        !isFocused.value &&
        !isDragging.value &&
        !reducedMotion.value,
);

function startTimer() {
    stopTimer();
    if (isRunning.value) {
        timer = window.setInterval(next, props.interval);
    }
}

function stopTimer() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

watch(isRunning, (running) => {
    if (running) startTimer();
    else stopTimer();
});

// Mouse drag handlers (Vertical)
function onMouseDown(e) {
    if (e.button !== 0 || !canRotate.value) return;
    isDragging.value = true;
    hasDragged.value = false;
    dragStartY.value = e.clientY;
    dragStartX.value = e.clientX;
    dragDeltaY.value = 0;
    dragDeltaX.value = 0;
    stopTimer();
    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
}

function onMouseMove(e) {
    if (!isDragging.value) return;
    const deltaY = e.clientY - dragStartY.value;
    const deltaX = e.clientX - dragStartX.value;
    if (Math.abs(deltaY) > 5 || Math.abs(deltaX) > 5) {
        hasDragged.value = true;
    }
    dragDeltaY.value = deltaY;
    dragDeltaX.value = deltaX;
}

function onMouseUp() {
    if (!isDragging.value) return;
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    isDragging.value = false;

    const threshold = 22;
    // Primary check: vertical drag (drag up -> next; drag down -> prev)
    if (Math.abs(dragDeltaY.value) >= Math.abs(dragDeltaX.value)) {
        if (dragDeltaY.value < -threshold) {
            next();
        } else if (dragDeltaY.value > threshold) {
            prev();
        }
    } else {
        // Fallback: horizontal drag (drag left -> next; drag right -> prev)
        if (dragDeltaX.value < -threshold) {
            next();
        } else if (dragDeltaX.value > threshold) {
            prev();
        }
    }
    dragDeltaY.value = 0;
    dragDeltaX.value = 0;
    startTimer();

    setTimeout(() => {
        hasDragged.value = false;
    }, 60);
}

// Touch swipe handlers (Vertical)
function onTouchStart(e) {
    if (!canRotate.value || !e.touches || e.touches.length === 0) return;
    isDragging.value = true;
    hasDragged.value = false;
    dragStartY.value = e.touches[0].clientY;
    dragStartX.value = e.touches[0].clientX;
    dragDeltaY.value = 0;
    dragDeltaX.value = 0;
    stopTimer();
}

function onTouchMove(e) {
    if (!isDragging.value || !e.touches || e.touches.length === 0) return;
    const deltaY = e.touches[0].clientY - dragStartY.value;
    const deltaX = e.touches[0].clientX - dragStartX.value;

    if (Math.abs(deltaY) > 5 || Math.abs(deltaX) > 5) {
        hasDragged.value = true;
    }

    // Prioritize vertical gesture while preventing unwanted page jump
    if (Math.abs(deltaY) >= Math.abs(deltaX)) {
        if (e.cancelable && Math.abs(deltaY) > 8) e.preventDefault();
        dragDeltaY.value = deltaY;
    } else {
        dragDeltaX.value = deltaX;
    }
}

function onTouchEnd() {
    if (!isDragging.value) return;
    isDragging.value = false;

    const threshold = 22;
    if (Math.abs(dragDeltaY.value) >= Math.abs(dragDeltaX.value)) {
        if (dragDeltaY.value < -threshold) {
            next();
        } else if (dragDeltaY.value > threshold) {
            prev();
        }
    } else {
        if (dragDeltaX.value < -threshold) {
            next();
        } else if (dragDeltaX.value > threshold) {
            prev();
        }
    }
    dragDeltaY.value = 0;
    dragDeltaX.value = 0;
    startTimer();

    setTimeout(() => {
        hasDragged.value = false;
    }, 60);
}

function handleClickCapture(e) {
    if (hasDragged.value) {
        e.preventDefault();
        e.stopPropagation();
    }
}

let mq = null;
function onMotionChange(event) {
    reducedMotion.value = event.matches;
}

onMounted(() => {
    mq = window.matchMedia?.("(prefers-reduced-motion: reduce)") || null;
    if (mq?.matches) reducedMotion.value = true;
    mq?.addEventListener?.("change", onMotionChange);
    startTimer();
});

onBeforeUnmount(() => {
    stopTimer();
    mq?.removeEventListener?.("change", onMotionChange);
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
});

function onFocusOut(event) {
    isFocused.value = event.currentTarget.contains(event.relatedTarget);
}
</script>

<template>
    <div
        :class="
            cn(
                'isf-hot-news-ticker flex items-center justify-between border-t border-neutral-300 bg-white px-3 sm:px-4 py-2 min-h-[42px] overflow-hidden text-sm select-none',
                props.class,
            )
        "
        role="region"
        aria-label="Hot News Ticker"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        @focusin="isFocused = true"
        @focusout="onFocusOut"
        @keydown.up.prevent="prev"
        @keydown.down.prevent="next"
        @keydown.left="prev"
        @keydown.right="next"
    >
        <!-- Left: Badge HOT NEWS & Divider -->
        <div class="flex items-center gap-2.5 shrink-0 select-none">
            <span
                class="inline-flex items-center gap-1.5 bg-isf-red text-white text-[10.5px] sm:text-[11px] font-space-grotesk font-bold uppercase tracking-wider px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm"
                aria-label="Label Hot News"
            >
                <span
                    class="inline-block h-1.5 w-1.5 rounded-full bg-white animate-pulse"
                    aria-hidden="true"
                ></span>
                <span>HOT NEWS</span>
            </span>
            <span
                class="h-3.5 w-px bg-neutral-300 shrink-0"
                aria-hidden="true"
            ></span>
        </div>

        <!-- Center & Full Track: Headline Track with Vertical Touch Swipe & Mouse Drag -->
        <div
            ref="trackRef"
            class="flex-1 min-w-0 mx-2 sm:mx-3 h-6 overflow-hidden relative flex items-center cursor-grab active:cursor-grabbing touch-none"
            :aria-live="isRunning ? 'off' : 'polite'"
            @mousedown="onMouseDown"
            @touchstart="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
            @click.capture="handleClickCapture"
        >
            <Transition :name="`ticker-${slideDirection}`">
                <div
                    v-if="currentItem"
                    :key="currentItem.id || activeIndex"
                    class="flex items-center gap-2 truncate w-full will-change-transform"
                    :style="
                        isDragging
                            ? {
                                  transform: `translateY(${dragDeltaY}px)`,
                                  transition: 'none',
                              }
                            : null
                    "
                >
                    <span
                        v-if="currentItem.category"
                        class="hidden md:inline-block font-ui text-[11px] font-bold text-isf-red uppercase tracking-wider shrink-0 pointer-events-none select-none"
                    >
                        [{{ currentItem.category }}]
                    </span>
                    <IsfLink
                        :to="currentItem.to || currentItem.url || '/beranda'"
                        class="font-ui text-[12.5px] sm:text-[13.5px] font-medium text-neutral-900 hover:text-isf-red hover:underline truncate transition-colors select-none"
                        :title="currentItem.title"
                        draggable="false"
                    >
                        {{ currentItem.title }}
                    </IsfLink>
                    <span
                        v-if="currentItem.published_at"
                        class="hidden lg:inline-block font-ui text-[11px] text-neutral-400 shrink-0 pointer-events-none select-none"
                    >
                        • {{ currentItem.published_at }}
                    </span>
                </div>
            </Transition>
        </div>

        <!-- Right: indikator posisi berita -->
        <div
            v-if="canRotate"
            class="flex shrink-0 items-center gap-1.5 select-none"
            role="group"
            aria-label="Pilih berita Hot News"
        >
            <span
                class="font-ui text-[11px] font-semibold tabular-nums text-neutral-500 sm:hidden"
                aria-hidden="true"
                >{{ activeIndex + 1 }}/{{ totalItems }}</span
            >
            <button
                v-for="(item, idx) in activeItems"
                :key="item.id || idx"
                type="button"
                :aria-label="`Berita ${idx + 1}: ${item.title}`"
                :aria-current="idx === activeIndex ? 'true' : undefined"
                class="hidden h-4 items-center justify-center sm:inline-flex focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                @click="goTo(idx)"
            >
                <span
                    :class="[
                        'block h-1.5 rounded-full transition-all duration-300',
                        idx === activeIndex
                            ? 'w-4 bg-isf-red'
                            : 'w-1.5 bg-neutral-300 hover:bg-neutral-400',
                    ]"
                ></span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.ticker-next-enter-active,
.ticker-next-leave-active,
.ticker-prev-enter-active,
.ticker-prev-leave-active {
    transition:
        transform 450ms cubic-bezier(0.65, 0, 0.35, 1),
        opacity 450ms cubic-bezier(0.65, 0, 0.35, 1);
}

/* Berita yang keluar ditumpuk di posisi yang sama agar keluar & masuk berjalan bersamaan. */
.ticker-next-leave-active,
.ticker-prev-leave-active {
    position: absolute;
    inset: 0;
}

/* Next: berita baru naik dari bawah, berita lama naik keluar ke atas. */
.ticker-next-enter-from {
    opacity: 0;
    transform: translateY(100%);
}
.ticker-next-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}

/* Prev: kebalikannya (turun). */
.ticker-prev-enter-from {
    opacity: 0;
    transform: translateY(-100%);
}
.ticker-prev-leave-to {
    opacity: 0;
    transform: translateY(100%);
}

@media (prefers-reduced-motion: reduce) {
    .ticker-next-enter-active,
    .ticker-next-leave-active,
    .ticker-prev-enter-active,
    .ticker-prev-leave-active {
        transition: none;
    }
}
</style>
