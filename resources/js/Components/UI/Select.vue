<script setup>
import { SelectRoot } from "radix-vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: undefined,
    },
    defaultValue: {
        type: String,
        default: undefined,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <SelectRoot
        :model-value="modelValue"
        :default-value="defaultValue"
        :disabled="disabled"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <slot />
    </SelectRoot>
</template>
