<script setup>
/**
 * IsfBannerCarousel — Horizontal banner slider with touch swipe & mouse drag (plain Vue, zero extra npm packages).
 * - Full horizontal slider with smooth CSS transition
 * - Native touch gesture support (swipe left/right) on mobile/tablet
 * - Mouse drag support (drag left/right) on desktop
 * - Keyboard navigation (Left/Right arrow keys)
 * - Auto-advances every `interval` ms; pauses on hover/focus/drag; obeys prefers-reduced-motion
 * - Bottom slot features integrated IsfHotNewsTicker component (replacing old dot/arrow indicators)
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
        default: 6000,
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

// Gesture state
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const dragDeltaX = ref(0);
const hasDragged = ref(false);
const containerWidth = ref(0);
const containerRef = ref(null);

const canRotate = computed(() => props.slides.length > 1);
const running = computed(
    () =>
        canRotate.value &&
        !userPaused.value &&
        !hovered.value &&
        !focused.value &&
        !isDragging.value &&
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
watch(running, sync);

function onFocusOut(event) {
    focused.value = event.currentTarget.contains(event.relatedTarget);
}

let mq = null;
function onMotionChange(event) {
    reducedMotion.value = event.matches;
    if (event.matches) userPaused.value = true;
}

// Mouse drag handlers
function onMouseDown(e) {
    if (e.button !== 0 || !canRotate.value) return;
    isDragging.value = true;
    hasDragged.value = false;
    dragStartX.value = e.clientX;
    dragDeltaX.value = 0;
    if (containerRef.value) {
        containerWidth.value = containerRef.value.offsetWidth || 1;
    }
    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
}

function onMouseMove(e) {
    if (!isDragging.value) return;
    const delta = e.clientX - dragStartX.value;
    if (Math.abs(delta) > 5) {
        hasDragged.value = true;
    }
    // Dampen drag at ends
    let damped = delta;
    if (
        (active.value === 0 && delta > 0) ||
        (active.value === props.slides.length - 1 && delta < 0)
    ) {
        damped = delta * 0.3;
    }
    dragDeltaX.value = damped;
}

function onMouseUp() {
    if (!isDragging.value) return;
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    isDragging.value = false;

    const threshold = Math.min(
        Math.max((containerWidth.value || 300) * 0.15, 40),
        120,
    );
    if (dragDeltaX.value < -threshold) {
        next();
    } else if (dragDeltaX.value > threshold) {
        prev();
    }
    dragDeltaX.value = 0;
    sync();

    // Prevent immediate navigation click if user was dragging
    setTimeout(() => {
        hasDragged.value = false;
    }, 60);
}

// Touch swipe handlers
function onTouchStart(e) {
    if (!canRotate.value || !e.touches || e.touches.length === 0) return;
    isDragging.value = true;
    hasDragged.value = false;
    dragStartX.value = e.touches[0].clientX;
    dragStartY.value = e.touches[0].clientY;
    dragDeltaX.value = 0;
    if (containerRef.value) {
        containerWidth.value = containerRef.value.offsetWidth || 1;
    }
}

function onTouchMove(e) {
    if (!isDragging.value || !e.touches || e.touches.length === 0) return;
    const deltaX = e.touches[0].clientX - dragStartX.value;
    const deltaY = e.touches[0].clientY - dragStartY.value;

    if (Math.abs(deltaX) > Math.abs(deltaY)) {
        if (e.cancelable) e.preventDefault();
        if (Math.abs(deltaX) > 8) {
            hasDragged.value = true;
        }
        let damped = deltaX;
        if (
            (active.value === 0 && deltaX > 0) ||
            (active.value === props.slides.length - 1 && deltaX < 0)
        ) {
            damped = deltaX * 0.3;
        }
        dragDeltaX.value = damped;
    }
}

function onTouchEnd() {
    if (!isDragging.value) return;
    isDragging.value = false;

    const threshold = Math.min(
        Math.max((containerWidth.value || 300) * 0.15, 40),
        120,
    );
    if (dragDeltaX.value < -threshold) {
        next();
    } else if (dragDeltaX.value > threshold) {
        prev();
    }
    dragDeltaX.value = 0;
    sync();

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
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
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
        <!-- Horizontal Slider Track with Touch & Mouse Drag -->
        <div
            ref="containerRef"
            class="overflow-hidden relative w-full select-none cursor-grab active:cursor-grabbing touch-pan-y"
            :aria-live="running ? 'off' : 'polite'"
            @mousedown="onMouseDown"
            @touchstart="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
            @click.capture="handleClickCapture"
        >
            <div
                class="flex w-full will-change-transform"
                :style="{
                    transform: isDragging
                        ? `translateX(calc(-${active * 100}% + ${dragDeltaX}px))`
                        : `translateX(-${active * 100}%)`,
                    transition: isDragging
                        ? 'none'
                        : 'transform 450ms cubic-bezier(0.25, 1, 0.5, 1)',
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
                        draggable="false"
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
        </div>

        <!-- Slot bekas indikator banner lama: Komponen Ticker Hot News -->
        <IsfHotNewsTicker :items="hotNews" />
    </section>
</template>
