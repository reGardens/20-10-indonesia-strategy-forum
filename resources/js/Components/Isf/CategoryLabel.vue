<script setup>
/**
 * CategoryLabel — colored category label (Politik, Ekonomi, Kebijakan, Hukum, RUU).
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    category: {
        type: String,
        required: true,
    },
    class: {
        type: String,
        default: "",
    },
});

const LABELS = {
    politik: "Politik",
    ekonomi: "Ekonomi",
    kebijakan: "Kebijakan",
    hukum: "Hukum",
    ruu: "RUU",
};

const key = computed(() => String(props.category).toLowerCase());
const text = computed(() => LABELS[key.value] || props.category);
const classes = computed(() => cn("isf-kicker", props.class));
</script>

<template>
    <span :class="classes">{{ text }}</span>
</template>
