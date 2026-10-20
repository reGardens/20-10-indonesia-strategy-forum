<script setup>
import { computed } from "vue";
import { Label } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    for: {
        type: String,
        default: undefined,
    },
    class: {
        type: String,
        default: "",
    },
});

const classes = computed(() =>
    cn(
        "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
        props.class,
    ),
);
</script>

<template>
    <Label :for="props.for" :class="classes">
        <slot />
    </Label>
</template>
