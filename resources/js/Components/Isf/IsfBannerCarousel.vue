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
            <!-- Clean banner slides: full clickable banner image optimized for SEO -->
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
                <IsfLink
                    :to="slide.to || '#'"
                    :title="slide.imageLabel || slide.title || 'Buka tautan banner'"
                    :aria-label="`Buka banner: ${slide.imageLabel || slide.title || 'Banner Sorotan'}`"
                    class="group block relative overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                >
                    <ImagePlaceholder
                        :label="slide.imageLabel || slide.title || 'Banner Sorotan'"
                        :src="slide.imageUrl || ''"
                        :priority="i === 0"
                        aspect="banner"
                        bare
                        class="w-full transition-transform duration-300 ease-out group-hover:scale-[1.008]"
                    />
                </IsfLink>
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
