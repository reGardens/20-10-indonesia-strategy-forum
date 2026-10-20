<template>
  <div class="flex items-center gap-3">
    <label :for="inputId" class="text-sm font-medium text-gray-700 min-w-0">
      {{ label }}
    </label>
    <div class="flex items-center gap-2">
      <input
        :id="inputId"
        type="color"
        :value="modelValue"
        class="h-8 w-8 cursor-pointer rounded border border-gray-300 p-0.5"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <span class="text-xs font-mono text-gray-500 uppercase">{{
        modelValue
      }}</span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    default: "#000000",
  },
  label: {
    type: String,
    required: true,
  },
});

defineEmits(["update:modelValue"]);

const inputId = `color-picker-${Math.random().toString(36).slice(2, 9)}`;
</script>
