<script setup>
/**
 * FactBlock — "Blok Fakta Kunci"
 * Reusable key-fact block: a claim + a clearly visible link to the primary
 * source document (title, date, and hostname shown as text).
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { formatDate } from "@/Utils/formatters";
import IsfLink from "./IsfLink.vue";

const props = defineProps({
    claim: {
        type: String,
        required: true,
    },
    sourceUrl: {
        type: String,
        required: true,
    },
    sourceTitle: {
        type: String,
        required: true,
    },
    // Format YYYY-MM-DD
    sourceDate: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: "Fakta Kunci",
    },
    class: {
        type: String,
        default: "",
    },
});

const hostname = computed(() => {
    try {
        return new URL(props.sourceUrl).hostname;
    } catch (e) {
        return props.sourceUrl;
    }
});

const hasSource = computed(() => Boolean(props.sourceUrl?.trim()));

const classes = computed(() =>
    cn("isf-editorial-compact block bg-white", props.class),
);
</script>

<template>
    <IsfLink
        v-if="hasSource"
        :to="sourceUrl"
        :class="[
            classes,
            'hover:[&_.isf-headline]:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2',
        ]"
        :aria-label="`${label}: ${claim}. Sumber: ${sourceTitle}`"
    >
        <p class="isf-kicker">{{ label }}</p>
        <p class="isf-headline mt-1.5 text-[17px] leading-[1.25]">
            {{ claim }}
        </p>
        <p class="mt-2 font-body text-xs leading-snug text-neutral-600">
            Sumber:
            <cite class="not-italic font-semibold text-neutral-800">
                {{ sourceTitle }}
            </cite>
            <span aria-hidden="true"> · </span>
            <time :datetime="sourceDate">{{ formatDate(sourceDate) }}</time>
            <span aria-hidden="true"> · </span>
            <span class="break-all">{{ hostname }}</span>
        </p>
    </IsfLink>
    <aside v-else :class="classes" :aria-label="label">
        <p class="isf-kicker">{{ label }}</p>
        <p class="isf-headline mt-1.5 text-[17px] leading-[1.25]">
            {{ claim }}
        </p>
    </aside>
</template>
