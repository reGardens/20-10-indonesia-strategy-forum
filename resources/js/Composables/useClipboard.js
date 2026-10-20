import { ref } from 'vue'

const COPIED_RESET_DELAY = 2000

/**
 * Composable for copy-to-clipboard functionality.
 * Used for copying bank account numbers and invitation URLs.
 *
 * Uses the Clipboard API (navigator.clipboard.writeText) with a fallback
 * for older browsers using document.execCommand('copy').
 *
 * @returns {{ copy: (text: string) => Promise<boolean>, copied: import('vue').Ref<boolean>, error: import('vue').Ref<string> }}
 */
export function useClipboard() {
  const copied = ref(false)
  const error = ref('')

  let resetTimeout = null

  /**
   * Copy text to the clipboard.
   *
   * @param {string} text - The text to copy (e.g. bank account number or invitation URL)
   * @returns {Promise<boolean>} Whether the copy succeeded
   */
  async function copy(text) {
    // Clear any pending reset timeout
    if (resetTimeout) {
      clearTimeout(resetTimeout)
      resetTimeout = null
    }

    // Reset state
    copied.value = false
    error.value = ''

    try {
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        await navigator.clipboard.writeText(text)
      } else {
        // Fallback for older browsers
        fallbackCopy(text)
      }

      copied.value = true

      // Auto-reset copied state after ~2 seconds
      resetTimeout = setTimeout(() => {
        copied.value = false
        resetTimeout = null
      }, COPIED_RESET_DELAY)

      return true
    } catch (err) {
      error.value = err.message || 'Failed to copy to clipboard'
      copied.value = false
      return false
    }
  }

  /**
   * Fallback copy method using a temporary textarea and document.execCommand.
   *
   * @param {string} text - The text to copy
   * @throws {Error} If the fallback copy fails
   */
  function fallbackCopy(text) {
    const textarea = document.createElement('textarea')
    textarea.value = text
    // Prevent scrolling to bottom
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    textarea.style.top = '-9999px'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.focus()
    textarea.select()

    try {
      const success = document.execCommand('copy')
      if (!success) {
        throw new Error('Failed to copy to clipboard')
      }
    } finally {
      document.body.removeChild(textarea)
    }
  }

  return {
    copy,
    copied,
    error
  }
}
