<template>
  <div
    v-if="src && !audioError"
    class="music-player fixed bottom-6 right-6 z-50"
    data-aos="fade-left"
  >
    <button
      class="flex h-12 w-12 items-center justify-center rounded-full shadow-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-white/50"
      :class="isPlaying ? 'bg-amber-600 text-white' : 'bg-white text-amber-600'"
      @click="toggle"
      :aria-label="isPlaying ? 'Matikan musik' : 'Nyalakan musik'"
    >
      <!-- Playing icon (animated bars) -->
      <svg
        v-if="isPlaying"
        class="h-5 w-5"
        viewBox="0 0 24 24"
        fill="currentColor"
        aria-hidden="true"
      >
        <rect x="4" y="4" width="3.5" height="16" rx="1">
          <animate
            attributeName="height"
            values="16;8;16"
            dur="0.8s"
            repeatCount="indefinite"
          />
          <animate
            attributeName="y"
            values="4;8;4"
            dur="0.8s"
            repeatCount="indefinite"
          />
        </rect>
        <rect x="10.25" y="4" width="3.5" height="16" rx="1">
          <animate
            attributeName="height"
            values="8;16;8"
            dur="0.8s"
            repeatCount="indefinite"
          />
          <animate
            attributeName="y"
            values="8;4;8"
            dur="0.8s"
            repeatCount="indefinite"
          />
        </rect>
        <rect x="16.5" y="4" width="3.5" height="16" rx="1">
          <animate
            attributeName="height"
            values="16;8;16"
            dur="0.8s"
            repeatCount="indefinite"
            begin="0.2s"
          />
          <animate
            attributeName="y"
            values="4;8;4"
            dur="0.8s"
            repeatCount="indefinite"
            begin="0.2s"
          />
        </rect>
      </svg>
      <!-- Paused icon -->
      <svg
        v-else
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"
        />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useMusicPlayer } from "../composables/useMusicPlayer.js";

const props = defineProps({
  src: {
    type: String,
    default: "",
  },
});

const audioError = ref(false);

let playerState = { isPlaying: ref(false), toggle: () => {} };

if (props.src) {
  try {
    const result = useMusicPlayer(props.src);
    playerState = result;

    // Listen for audio error to hide player silently
    if (result.audioElement) {
      result.audioElement.addEventListener("error", () => {
        audioError.value = true;
      });
    }
  } catch {
    audioError.value = true;
  }
}

const { isPlaying, toggle } = playerState;
</script>
