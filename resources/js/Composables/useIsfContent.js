import { ref, shallowRef } from 'vue'
import { isNotFound } from '@/api/services/isfPublicService'

/**
 * Loading state for an ISF public API call.
 *
 * status: 'loading' | 'ready' | 'error' | 'not-found'
 * Only the latest load() call may update the state (slug changes cancel older results).
 *
 * @param {() => Promise<any>} loader
 */
export function useIsfContent(loader) {
  const data = shallowRef(null)
  const status = ref('loading')
  let requestId = 0

  async function load() {
    const id = ++requestId
    status.value = 'loading'
    try {
      const result = await loader()
      if (id !== requestId) return
      data.value = result
      status.value = 'ready'
    } catch (error) {
      if (id !== requestId) return
      data.value = null
      status.value = isNotFound(error) ? 'not-found' : 'error'
    }
  }

  return { data, status, load }
}
