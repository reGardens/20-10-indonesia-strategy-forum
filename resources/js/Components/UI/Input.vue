<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    type: {
        type: String,
        default: "text",
    },
    class: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const classes = computed(() =>
    cn(
        "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
        props.class,
    ),
);

function handleInput(event) {
    emit("update:modelValue", event.target.value);
}
</script>

<template>
    <input
        :type="type"
        :class="classes"
        :value="modelValue"
        :disabled="disabled"
        :placeholder="placeholder"
        @input="handleInput"
    />
</template>
