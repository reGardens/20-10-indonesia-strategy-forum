import { ref, onUnmounted } from 'vue'

/**
 * Reactive countdown composable that counts down to a target date.
 * Updates every second and cleans up the interval on unmount.
 *
 * @param {string|Date} targetDate - The target date to count down to
 * @returns {{ days: import('vue').Ref<number>, hours: import('vue').Ref<number>, minutes: import('vue').Ref<number>, seconds: import('vue').Ref<number>, isExpired: import('vue').Ref<boolean> }}
 */
export function useCountdown(targetDate) {
  const days = ref(0)
  const hours = ref(0)
  const minutes = ref(0)
  const seconds = ref(0)
  const isExpired = ref(false)

  function update() {
    const target = targetDate instanceof Date ? targetDate : new Date(targetDate)
    const now = new Date()
    const diff = target.getTime() - now.getTime()

    if (diff <= 0) {
      days.value = 0
      hours.value = 0
      minutes.value = 0
      seconds.value = 0
      isExpired.value = true
      return
    }

    isExpired.value = false

    const totalSeconds = Math.floor(diff / 1000)
    days.value = Math.floor(totalSeconds / 86400)
    hours.value = Math.floor((totalSeconds % 86400) / 3600)
    minutes.value = Math.floor((totalSeconds % 3600) / 60)
    seconds.value = totalSeconds % 60
  }

  // Initial calculation
  update()

  // Update every second
  const intervalId = setInterval(update, 1000)

  // Clean up on unmount
  onUnmounted(() => {
    clearInterval(intervalId)
  })

  return {
    days,
    hours,
    minutes,
    seconds,
    isExpired
  }
}
