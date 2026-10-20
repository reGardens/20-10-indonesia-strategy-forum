<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5 space-y-5">
    <h2 class="text-lg font-semibold text-gray-900">Kustomisasi Tema</h2>

    <!-- Color pickers -->
    <div class="space-y-4">
      <h3 class="text-sm font-medium text-gray-700">Warna Tema</h3>
      <ColorPicker
        label="Warna Utama"
        :model-value="themeColors.primary"
        @update:model-value="updateColor('primary', $event)"
      />
      <ColorPicker
        label="Warna Sekunder"
        :model-value="themeColors.secondary"
        @update:model-value="updateColor('secondary', $event)"
      />
      <ColorPicker
        label="Warna Aksen"
        :model-value="themeColors.accent"
        @update:model-value="updateColor('accent', $event)"
      />
    </div>

    <!-- Font family selector -->
    <div>
      <label
        for="font-family"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Font Keluarga
      </label>
      <select
        id="font-family"
        :value="fontFamily"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        @change="$emit('update:fontFamily', $event.target.value)"
      >
        <option
          v-for="font in fontOptions"
          :key="font.value"
          :value="font.value"
          :style="{ fontFamily: font.value }"
        >
          {{ font.label }}
        </option>
      </select>
    </div>
  </div>
</template>

<script setup>
import ColorPicker from "@/Pages/Cms/components/shared/ColorPicker.vue";

const props = defineProps({
  themeColors: {
    type: Object,
    required: true,
    default: () => ({
      primary: "#8B4513",
      secondary: "#D2691E",
      accent: "#FFD700",
    }),
  },
  fontFamily: {
    type: String,
    default: "Playfair Display",
  },
});

const emit = defineEmits(["update:themeColors", "update:fontFamily"]);

const fontOptions = [
  { value: "Playfair Display", label: "Playfair Display" },
  { value: "Lora", label: "Lora" },
  { value: "Poppins", label: "Poppins" },
  { value: "Montserrat", label: "Montserrat" },
  { value: "Dancing Script", label: "Dancing Script" },
];

function updateColor(key, value) {
  emit("update:themeColors", {
    ...props.themeColors,
    [key]: value,
  });
}
</script>
