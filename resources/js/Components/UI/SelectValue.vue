<script setup>
import { SelectValue } from "radix-vue";

const props = defineProps({
    placeholder: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <SelectValue :placeholder="placeholder">
        <slot />
    </SelectValue>
</template>
