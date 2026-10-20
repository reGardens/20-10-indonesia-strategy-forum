<template>
  <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
    <div
      class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2"
    >
      <h2 class="text-sm font-semibold text-gray-700">Live Preview</h2>
      <span class="text-xs text-gray-400">{{ templateName }}</span>
    </div>

    <!-- Preview area -->
    <div
      class="relative flex flex-col items-center justify-center p-6 min-h-[400px]"
      :style="previewStyles"
    >
      <!-- Decorative top border -->
      <div
        class="absolute top-0 left-0 right-0 h-2"
        :style="{ backgroundColor: themeColors.accent }"
      />

      <!-- Template name badge -->
      <div
        class="mb-6 rounded-full px-4 py-1 text-xs font-medium text-white"
        :style="{ backgroundColor: themeColors.primary }"
      >
        {{ templateName }}
      </div>

      <!-- Simulated invitation content -->
      <div class="w-full max-w-xs space-y-4 text-center">
        <!-- Couple names placeholder -->
        <div>
          <p
            class="text-2xl font-bold"
            :style="{ color: themeColors.primary, fontFamily: fontFamily }"
          >
            Budi & Ani
          </p>
          <div
            class="mx-auto mt-2 h-0.5 w-16"
            :style="{ backgroundColor: themeColors.accent }"
          />
        </div>

        <!-- Date placeholder -->
        <p class="text-sm" :style="{ color: themeColors.secondary }">
          25 Desember 2024
        </p>

        <!-- Decorative element -->
        <div class="flex items-center justify-center gap-2">
          <div
            class="h-px flex-1"
            :style="{ backgroundColor: themeColors.accent }"
          />
          <span
            class="text-lg"
            :style="{ color: themeColors.accent }"
            aria-hidden="true"
          >
            {{ decorativeIcon }}
          </span>
          <div
            class="h-px flex-1"
            :style="{ backgroundColor: themeColors.accent }"
          />
        </div>

        <!-- Event placeholder -->
        <div
          class="rounded-lg border p-3"
          :style="{ borderColor: themeColors.secondary + '40' }"
        >
          <p
            class="text-xs font-medium"
            :style="{ color: themeColors.primary }"
          >
            Akad Nikah
          </p>
          <p class="mt-1 text-xs text-gray-500">08:00 - 10:00 WIB</p>
        </div>

        <!-- RSVP button placeholder -->
        <button
          type="button"
          class="w-full rounded-md px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
          :style="{ backgroundColor: themeColors.primary }"
          disabled
        >
          Konfirmasi Kehadiran
        </button>
      </div>

      <!-- Decorative bottom border -->
      <div
        class="absolute bottom-0 left-0 right-0 h-2"
        :style="{ backgroundColor: themeColors.accent }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  templateId: {
    type: String,
    default: "batik-elegance",
  },
  templateName: {
    type: String,
    default: "Template",
  },
  themeColors: {
    type: Object,
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

const backgrounds = {
  "jawa-klasik": "linear-gradient(135deg, #FFF8F0 0%, #FFF0E0 100%)",
  "sunda-pasundan": "linear-gradient(135deg, #F0F7E8 0%, #E8F2DC 100%)",
  "bali-dewata": "linear-gradient(135deg, #FFF5F5 0%, #FCE8E8 100%)",
  "betawi-jakarta": "linear-gradient(135deg, #FFFBF0 0%, #FFF3E0 100%)",
  "minang-rantau": "linear-gradient(135deg, #FDF6F0 0%, #F8ECE2 100%)",
  "bugis-makassar": "linear-gradient(135deg, #F0F5FA 0%, #E0ECF5 100%)",
  "dayak-borneo": "linear-gradient(135deg, #FAF5F0 0%, #F0E8E0 100%)",
  "toraja-sulawesi": "linear-gradient(135deg, #F8F0EA 0%, #F0E4DA 100%)",
  "melayu-riau": "linear-gradient(135deg, #FFFDE8 0%, #FDF8D0 100%)",
  "papua-cendrawasih": "linear-gradient(135deg, #F8F0E8 0%, #F0E4D8 100%)",
};

const icons = {
  "jawa-klasik": "🪷",
  "sunda-pasundan": "🌿",
  "bali-dewata": "🏵️",
  "betawi-jakarta": "🎪",
  "minang-rantau": "👑",
  "bugis-makassar": "⚓",
  "dayak-borneo": "🛡️",
  "toraja-sulawesi": "🏛️",
  "melayu-riau": "✨",
  "papua-cendrawasih": "🦅",
};

const previewStyles = computed(() => ({
  background:
    backgrounds[props.templateId] ||
    "linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%)",
  fontFamily: props.fontFamily,
}));

const decorativeIcon = computed(() => icons[props.templateId] || "💒");
</script>
