<script setup>
import { computed } from "vue";
import { cva } from "class-variance-authority";
import { cn } from "@/lib/utils";

const buttonVariants = cva(
    "inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
    {
        variants: {
            variant: {
                default:
                    "bg-primary text-primary-foreground hover:bg-primary/90",
                destructive:
                    "bg-destructive text-destructive-foreground hover:bg-destructive/90",
                outline:
                    "border border-input bg-background hover:bg-accent hover:text-accent-foreground",
                secondary:
                    "bg-secondary text-secondary-foreground hover:bg-secondary/80",
                ghost: "hover:bg-accent hover:text-accent-foreground",
                link: "text-primary underline-offset-4 hover:underline",
                sweep: "relative overflow-hidden z-0 bg-white text-primary border-2 border-primary after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-full after:h-0 after:bg-gradient-to-t after:from-[#3AB0E5] after:to-[#8ED9FB] after:-z-10 after:transition-all after:duration-500 hover:after:h-full hover:text-white hover:border-white transition-all duration-300 font-normal tracking-wide shadow-sm hover:shadow-lg",
                "sweep-reverse":
                    "relative overflow-hidden z-0 bg-white text-white border-2 border-white after:content-[''] after:absolute after:top-0 after:left-0 after:w-full after:h-full after:bg-gradient-to-t after:from-[#3AB0E5] after:to-[#8ED9FB] after:-z-10 after:transition-all after:duration-500 hover:after:h-0 hover:text-[#3AB0E5] hover:border-[#3AB0E5] transition-all duration-300 font-normal tracking-wide shadow-lg hover:shadow-sm",
            },
            size: {
                default: "h-10 px-4 py-2",
                sm: "h-9 rounded-md px-3",
                lg: "h-11 rounded-md px-8",
                xl: "h-14 rounded-2xl px-12 text-lg font-bold",
                icon: "h-10 w-10",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    },
);

const props = defineProps({
    variant: {
        type: String,
        default: "default",
    },
    size: {
        type: String,
        default: "default",
    },
    as: {
        type: String,
        default: "button",
    },
    class: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    cn(
        buttonVariants({ variant: props.variant, size: props.size }),
        props.class,
    ),
);
</script>

<template>
    <component :is="as" :class="classes" :disabled="disabled">
        <slot />
    </component>
</template>
