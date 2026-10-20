import { ref } from 'vue'
import { decodeGuestName } from '@/Utils/urlEncoder.js'

/**
 * Composable that extracts and decodes the guest name from the `to` URL parameter.
 * Returns an empty string if the parameter is not present.
 *
 * @returns {{ guestName: import('vue').Ref<string> }}
 */
export function useGuestName() {
  const params = new URLSearchParams(window.location.search)
  const rawTo = params.get('to') || ''
  const guestName = ref(decodeGuestName(rawTo))

  return {
    guestName
  }
}
