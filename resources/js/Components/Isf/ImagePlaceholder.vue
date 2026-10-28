<script setup>
/**
 * ImagePlaceholder — shows the image when `src` is set (alt = label), otherwise a
 * neutral placeholder box exposed to assistive tech as an image with a descriptive label.
 */
import { computed, ref, watch } from "vue";
import { IconPhoto } from "@tabler/icons-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    // Optional image URL (CMS field); placeholder is shown when empty
    src: {
        type: String,
        default: "",
    },
    aspect: {
        type: String,
        default: "video",
        validator: (value) =>
            [
                "video",
                "news",
                "square",
                "portrait",
                "photo",
                "card",
                "banner",
                "reel",
            ].includes(value),
    },
    // Bare: icon only (no visible caption), for thumbnails and story images.
    bare: {
        type: Boolean,
        default: false,
    },
    priority: {
        type: Boolean,
        default: false,
    },
    class: {
        type: String,
        default: "",
    },
});

const imageFailed = ref(false);
watch(
    () => props.src,
    () => {
        imageFailed.value = false;
    },
);

const ASPECTS = {
    video: "aspect-video",
    news: "aspect-[16/10]",
    square: "aspect-square",
    portrait: "aspect-[3/4]",
    photo: "aspect-[3/2]",
    card: "aspect-[4/3]",
    banner: "aspect-video lg:aspect-[21/9]",
    reel: "aspect-[9/16]",
};

// Wrapper selalu block + w-full + max-w-full + overflow-hidden agar gambar tidak
// pernah melebihi lebar kontainernya (penyebab overflow di layar sempit/PWA).
const wrapperClasses = computed(() =>
    cn(
        "isf-image-placeholder block w-full max-w-full overflow-hidden",
        props.class,
    ),
);

const boxClasses = computed(() =>
    cn(
        "flex w-full flex-col items-center justify-center gap-2 bg-neutral-200 text-center text-neutral-500",
        props.bare ? "p-2" : "p-4",
        ASPECTS[props.aspect] || ASPECTS.video,
    ),
);

const imageClasses = computed(() =>
    cn(
        "block h-full w-full max-w-full bg-neutral-200 object-cover",
        ASPECTS[props.aspect] || ASPECTS.video,
    ),
);
</script>

<template>
    <div :class="wrapperClasses">
        <img
            v-if="src && !imageFailed"
            :src="src"
            :alt="label"
            :class="imageClasses"
            :loading="priority ? 'eager' : 'lazy'"
            :fetchpriority="priority ? 'high' : 'auto'"
            decoding="async"
            @error="imageFailed = true"
        />
        <div v-else role="img" :aria-label="label" :class="boxClasses">
            <IconPhoto
                :class="bare ? 'h-5 w-5' : 'h-8 w-8'"
                aria-hidden="true"
                stroke-width="1.5"
            />
            <span
                v-if="!bare"
                class="text-xs leading-snug"
                aria-hidden="true"
                >{{ label }}</span
            >
        </div>
    </div>
</template>
