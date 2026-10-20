import { ref, onUnmounted } from 'vue'

/**
 * Music player composable with autoplay after first user interaction.
 * Provides toggle play/pause and a persistent floating button pattern.
 *
 * @param {string} src - URL or path to the audio file
 * @returns {{ isPlaying: import('vue').Ref<boolean>, toggle: () => void, audioElement: HTMLAudioElement }}
 */
export function useMusicPlayer(src) {
  const isPlaying = ref(false)
  const audio = new Audio(src)
  audio.loop = true

  let hasInteracted = false

  function play() {
    audio.play()
      .then(() => {
        isPlaying.value = true
      })
      .catch(() => {
        // Browser blocked autoplay — will retry on next interaction
        isPlaying.value = false
      })
  }

  function pause() {
    audio.pause()
    isPlaying.value = false
  }

  /**
   * Toggle between play and pause.
   */
  function toggle() {
    if (isPlaying.value) {
      pause()
    } else {
      play()
    }
  }

  /**
   * Handler for the first user interaction (click/touch).
   * Starts playback automatically and removes the listener.
   */
  function onFirstInteraction() {
    if (hasInteracted) return
    hasInteracted = true
    play()
    document.removeEventListener('click', onFirstInteraction)
    document.removeEventListener('touchstart', onFirstInteraction)
  }

  // Listen for first user interaction to trigger autoplay
  document.addEventListener('click', onFirstInteraction)
  document.addEventListener('touchstart', onFirstInteraction)

  // Clean up on unmount
  onUnmounted(() => {
    pause()
    audio.src = ''
    document.removeEventListener('click', onFirstInteraction)
    document.removeEventListener('touchstart', onFirstInteraction)
  })

  return {
    isPlaying,
    toggle,
    audioElement: audio
  }
}
