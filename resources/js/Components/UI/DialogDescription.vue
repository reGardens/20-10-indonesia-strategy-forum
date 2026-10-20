<script setup>
import { computed } from "vue";
import { DialogDescription } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    class: {
        type: String,
        default: "",
    },
});

const classes = computed(() =>
    cn("text-sm text-muted-foreground", props.class),
);
</script>

<template>
    <DialogDescription :class="classes">
        <slot />
    </DialogDescription>
</template>
