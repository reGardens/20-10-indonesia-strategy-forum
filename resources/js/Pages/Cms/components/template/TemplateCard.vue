<template>
  <button
    type="button"
    class="group relative flex flex-col overflow-hidden rounded-lg border-2 bg-white text-left transition-all hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
    :class="selected ? 'border-emerald-600 shadow-md' : 'border-gray-200'"
    @click="$emit('select', id)"
  >
    <!-- Thumbnail placeholder -->
    <div
      class="relative flex h-40 items-center justify-center bg-gradient-to-br text-4xl"
      :class="thumbnailGradient"
    >
      <span aria-hidden="true">{{ thumbnailEmoji }}</span>
      <!-- Selected indicator -->
      <div
        v-if="selected"
        class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white"
        aria-hidden="true"
      >
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>

    <!-- Card body -->
    <div class="flex flex-1 flex-col p-4">
      <h3 class="text-sm font-semibold text-gray-900">{{ name }}</h3>
      <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ description }}</p>
    </div>

    <!-- Selected label -->
    <div
      v-if="selected"
      class="bg-emerald-600 px-4 py-1.5 text-center text-xs font-medium text-white"
    >
      Dipilih
    </div>
  </button>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: "",
  },
  thumbnail: {
    type: String,
    default: "",
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["select"]);

const gradients = {
  "jawa-klasik": "from-amber-100 to-amber-300",
  "sunda-pasundan": "from-green-100 to-green-300",
  "bali-dewata": "from-red-100 to-red-300",
  "betawi-jakarta": "from-orange-100 to-orange-300",
  "minang-rantau": "from-rose-100 to-rose-300",
  "bugis-makassar": "from-blue-100 to-blue-300",
  "dayak-borneo": "from-red-100 to-amber-200",
  "toraja-sulawesi": "from-stone-100 to-stone-300",
  "melayu-riau": "from-yellow-100 to-yellow-300",
  "papua-cendrawasih": "from-orange-100 to-stone-300",
};

const emojis = {
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

const thumbnailGradient = computed(
  () => gradients[props.id] || "from-gray-100 to-gray-300",
);
const thumbnailEmoji = computed(() => emojis[props.id] || "💒");
</script>
