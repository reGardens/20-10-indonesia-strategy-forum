<template>
  <div
    class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
  >
    <button
      type="button"
      class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
      :aria-label="isPlaying ? 'Jeda musik' : 'Putar musik'"
      @click="togglePlay"
    >
      <svg
        v-if="!isPlaying"
        class="h-5 w-5 ml-0.5"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.84z"
        />
      </svg>
      <svg
        v-else
        class="h-5 w-5"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z"
        />
      </svg>
    </button>

    <div class="flex-1 min-w-0">
      <div class="text-xs text-gray-500 mb-1 truncate" v-if="src">
        {{ fileName }}
      </div>
      <audio
        ref="audioEl"
        :src="src"
        preload="metadata"
        @play="isPlaying = true"
        @pause="isPlaying = false"
        @ended="isPlaying = false"
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMetadata"
      />
      <div class="flex items-center gap-2">
        <input
          type="range"
          min="0"
          :max="duration"
          :value="currentTime"
          step="0.1"
          class="flex-1 h-1.5 cursor-pointer accent-emerald-600"
          aria-label="Posisi audio"
          @input="onSeek"
        />
        <span
          class="text-xs text-gray-400 font-mono tabular-nums whitespace-nowrap"
        >
          {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  src: {
    type: String,
    default: "",
  },
});

const audioEl = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);

const fileName = computed(() => {
  if (!props.src) return "";
  try {
    const parts = props.src.split("/");
    return decodeURIComponent(parts[parts.length - 1]);
  } catch {
    return props.src;
  }
});

function togglePlay() {
  if (!audioEl.value) return;
  if (isPlaying.value) {
    audioEl.value.pause();
  } else {
    audioEl.value.play();
  }
}

function onTimeUpdate() {
  if (audioEl.value) {
    currentTime.value = audioEl.value.currentTime;
  }
}

function onLoadedMetadata() {
  if (audioEl.value) {
    duration.value = audioEl.value.duration;
  }
}

function onSeek(event) {
  if (audioEl.value) {
    audioEl.value.currentTime = Number(event.target.value);
  }
}

function formatTime(seconds) {
  if (!seconds || isNaN(seconds)) return "0:00";
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, "0")}`;
}
</script>
