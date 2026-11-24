<script setup>
/**
 * IsfLogo — ISF logo for CMS login (public/images/isf/ISF-Logo-1.png).
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";

const ISF_LOGO_URL = "/images/isf/ISF-Logo-1.png";

const props = defineProps({
    // Square size: sm = 32px, md = 40px, lg = 64px, xl = 96px
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg", "xl"].includes(value),
    },
    alt: {
        type: String,
        default: "Indonesia Strategy Forum",
    },
    class: {
        type: String,
        default: "",
    },
});

const SIZES = {
    sm: "h-8 w-8",
    md: "h-10 w-10",
    lg: "h-16 w-16",
    xl: "h-24 w-24",
};

const classes = computed(() =>
    cn(
        "isf-logo inline-flex shrink-0 overflow-hidden border border-neutral-400 bg-isf-navy",
        SIZES[props.size] || SIZES.md,
        props.class,
    ),
);
</script>

<template>
    <span :class="classes">
        <img
            :src="ISF_LOGO_URL"
            :alt="alt"
            class="h-full w-full object-contain p-1"
            width="96"
            height="96"
            decoding="async"
        />
    </span>
</template>
