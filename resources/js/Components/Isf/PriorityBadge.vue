<script setup>
/**
 * PriorityBadge — alert priority badge for the B2B member portal.
 * "merah" → red "Prioritas Tinggi", "kuning" → yellow "Perlu Dipantau".
 * Wraps the existing UI Badge with override colors.
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";
import Badge from "@/Components/UI/Badge.vue";

const props = defineProps({
    priority: {
        type: String,
        required: true,
        validator: (value) => ["merah", "kuning"].includes(value),
    },
    class: {
        type: String,
        default: "",
    },
});

const CONFIG = {
    merah: {
        text: "Prioritas Tinggi",
        // ISF red #E10600 + white text (4.98:1)
        classes: "bg-isf-red text-white hover:bg-isf-red",
    },
    kuning: {
        text: "Perlu Dipantau",
        // Semantic amber #F59E0B + navy text
        classes: "bg-isf-amber text-isf-navy hover:bg-isf-amber",
    },
};

const config = computed(() => CONFIG[props.priority] || CONFIG.kuning);
const classes = computed(() =>
    cn(
        "isf-editorial-compact rounded-none border-transparent font-ui text-xs font-semibold uppercase tracking-[0.08em] whitespace-nowrap",
        config.value.classes,
        props.class,
    ),
);
</script>

<template>
    <Badge :class="classes">{{ config.text }}</Badge>
</template>
