<script setup>
import { computed } from "vue";
import { cva } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badgeVariants = cva(
    "inline-flex items-center rounded-full border px-1.5 py-0 text-[9.5px] leading-tight font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-ring",
    {
        variants: {
            variant: {
                default:
                    "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
                secondary:
                    "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
                destructive:
                    "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
                outline: "text-foreground",
            },
        },
        defaultVariants: {
            variant: "default",
        },
    },
);

const props = defineProps({
    variant: {
        type: String,
        default: "default",
    },
    class: {
        type: String,
        default: "",
    },
});

const classes = computed(() =>
    cn(badgeVariants({ variant: props.variant }), props.class),
);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
