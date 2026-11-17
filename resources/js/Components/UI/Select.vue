<script setup>
import { computed } from "vue";
import { SelectRoot } from "radix-vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: undefined,
    },
    defaultValue: {
        type: [String, Number],
        default: undefined,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "change"]);

const internalValue = computed(() => {
    if (props.modelValue === undefined || props.modelValue === null) return undefined;
    return String(props.modelValue);
});

const internalDefault = computed(() => {
    if (props.defaultValue === undefined || props.defaultValue === null) return undefined;
    return String(props.defaultValue);
});

function handleUpdate(val) {
    emit("update:modelValue", val);
    emit("change", val);
}
</script>

<template>
    <SelectRoot
        :model-value="internalValue"
        :default-value="internalDefault"
        :disabled="disabled"
        @update:model-value="handleUpdate"
    >
        <slot />
    </SelectRoot>
</template>
