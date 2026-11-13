<script setup>
/**
 * CategoryLabel — category label (Politik, Hukum, Kebijakan Publik = menu navbar).
 * Nilai lama "ekonomi" & "ruu" (sudah digabung) tetap tampil sebagai Kebijakan Publik & Hukum.
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
    ekonomi: "Kebijakan Publik",
    kebijakan: "Kebijakan Publik",
    hukum: "Hukum",
    ruu: "Hukum",
};

const key = computed(() => String(props.category).toLowerCase());
const text = computed(() => LABELS[key.value] || props.category);
const classes = computed(() => cn("isf-kicker", props.class));
</script>

<template>
    <span :class="classes">{{ text }}</span>
</template>
