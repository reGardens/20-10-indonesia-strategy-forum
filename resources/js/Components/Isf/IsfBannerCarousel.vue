<script setup>
/**
 * IsfBannerCarousel — dynamic banner carousel (plain Vue, no library).
 * - prev/next buttons, slide dots, Left/Right arrow keys while focus is inside
 * - auto-advance every `interval` ms; pauses on hover/focus and via the
 *   pause/play button; never auto-advances under prefers-reduced-motion
 * - inactive slides are hidden (v-show) and aria-hidden
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { cn } from "@/lib/utils";
import ImagePlaceholder from "./ImagePlaceholder.vue";
import IsfLink from "./IsfLink.vue";

const props = defineProps({
    // Each slide: { id, kicker, title, deck, to, imageLabel, imageUrl? }
    slides: {
        type: Array,
        required: true,
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

const canRotate = computed(() => props.slides.length > 1);
const running = computed(
    () =>
        canRotate.value &&
        !userPaused.value &&
        !hovered.value &&
        !focused.value,
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
function togglePlay() {
    userPaused.value = !userPaused.value;
}

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

const buttonClass =
    "inline-flex h-9 w-9 items-center justify-center text-neutral-900 hover:border-isf-red hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";
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
        <div class="relative" :aria-live="running ? 'off' : 'polite'">
            <!-- Full banner image with overlay editorial card on desktop -->
            <div
                v-for="(slide, i) in slides"
                v-show="i === active"
                :key="slide.id"
                role="group"
                aria-roledescription="slide"
                :aria-label="`${i + 1} dari ${slides.length}`"
                :aria-hidden="i === active ? undefined : 'true'"
                class="relative md:min-h-0"
            >
                <ImagePlaceholder
                    :label="slide.imageLabel || slide.title"
                    :src="slide.imageUrl || ''"
                    aspect="banner"
                    bare
                    class="w-full"
                />

                <div
                    class="border-t border-neutral-300 bg-white p-4 sm:p-5 md:absolute md:bottom-5 md:left-5 md:max-w-[36rem] md:border md:bg-white md:p-6 lg:bottom-6 lg:left-6 lg:max-w-[42rem] shadow-sm"
                >
                    <p class="isf-kicker text-isf-red font-bold uppercase tracking-wider text-[11px] sm:text-xs">
                        {{ slide.kicker }}
                    </p>
                    <h2
                        class="isf-headline mt-1.5 text-[22px] leading-[1.15] sm:text-[26px] lg:text-[30px]"
                    >
                        <IsfLink
                            :to="slide.to || '#'"
                            class="hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                            >{{ slide.title }}</IsfLink
                        >
                    </h2>
                    <p
                        v-if="slide.deck"
                        class="mt-2 max-w-[40rem] font-serif text-[15px] leading-[1.5] text-neutral-600 sm:text-[16px] lg:text-[17px]"
                    >
                        {{ slide.deck }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Controls: full width row at bottom -->
        <div
            v-if="canRotate"
            class="flex items-center justify-between gap-3 border-t border-neutral-300 bg-white px-3 py-2 sm:px-4"
        >
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :class="buttonClass"
                    :aria-label="
                        userPaused ? 'Putar otomatis' : 'Jeda putar otomatis'
                    "
                    @click="togglePlay"
                >
                    <svg
                        v-if="userPaused"
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M7 5v14l12-7z" />
                    </svg>
                    <svg
                        v-else
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        aria-hidden="true"
                    >
                        <path d="M8 5v14M16 5v14" />
                    </svg>
                </button>

                <div class="flex items-center gap-1.5">
                    <button
                        v-for="(slide, i) in slides"
                        :key="slide.id"
                        type="button"
                        class="group p-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                        :aria-label="`Tampilkan slide ${i + 1}`"
                        :aria-current="i === active ? 'true' : undefined"
                        @click="
                            go(i);
                            sync();
                        "
                    >
                        <span
                            aria-hidden="true"
                            :class="[
                                'block h-1.5 w-6 transition-colors',
                                i === active
                                    ? 'bg-isf-red'
                                    : 'bg-neutral-300 group-hover:bg-neutral-500',
                            ]"
                        ></span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    aria-label="Slide sebelumnya"
                    :class="[buttonClass, 'border border-neutral-300']"
                    @click="
                        prev();
                        sync();
                    "
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        aria-hidden="true"
                    >
                        <path d="m15 6-6 6 6 6" />
                    </svg>
                </button>
                <button
                    type="button"
                    aria-label="Slide berikutnya"
                    :class="[buttonClass, 'border border-neutral-300']"
                    @click="
                        next();
                        sync();
                    "
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        aria-hidden="true"
                    >
                        <path d="m9 6 6 6-6 6" />
                    </svg>
                </button>
            </div>
        </div>
    </section>
</template>
