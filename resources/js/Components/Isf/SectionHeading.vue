<script setup>
/**
 * SectionHeading — Politico-style column/section label row ("○ LATEST NEWS")
 * with a hairline beneath, optional eyebrow text/slot (rendered as a gray
 * byline beside the label, outside the heading element) and an optional
 * right-side "action" slot (e.g. "Lihat semua").
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    level: {
        type: Number,
        default: 2,
        validator: (value) => [2, 3].includes(value),
    },
    eyebrow: {
        type: String,
        default: "",
    },
    // Applied to the heading element (for aria-labelledby on the parent section)
    id: {
        type: String,
        default: undefined,
    },
    class: {
        type: String,
        default: "",
    },
});

const tag = computed(() => `h${props.level}`);
const classes = computed(() =>
    cn("isf-col-head justify-between text-left", props.class),
);
</script>

<template>
    <div :class="classes">
        <div class="flex min-w-0 flex-wrap items-baseline gap-x-3 gap-y-1">
            <component :is="tag" :id="id" class="isf-label">{{
                title
            }}</component>
            <span v-if="eyebrow || $slots.eyebrow" class="isf-byline"
                ><slot name="eyebrow">{{ eyebrow }}</slot></span
            >
        </div>
        <div v-if="$slots.action" class="flex shrink-0 items-center gap-2">
            <slot name="action" />
        </div>
    </div>
</template>
