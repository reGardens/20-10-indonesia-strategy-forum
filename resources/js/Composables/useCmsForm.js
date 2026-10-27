import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { alertError, alertSuccess } from '@/Composables/useAlert'
import { errorMessage } from '@/api/services/isfCmsService'

function clone(value) {
  return JSON.parse(JSON.stringify(value))
}

/**
 * Create/edit form state for an ISF CMS module backed by the CMS JSON API.
 *
 * @param {object} options
 * @param {ReturnType<import('@/api/services/isfCmsService').createCmsResource>} options.resource
 * @param {number|null} options.id        record id (null = create)
 * @param {object} options.defaults       field defaults (also the list of submitted fields)
 * @param {string} options.indexHref      list page URL (e.g. "/cms/artikel")
 */
export function useCmsForm({ resource, id, defaults, indexHref }) {
  const form = reactive(clone(defaults))
  const errors = ref({})
  const loading = ref(Boolean(id))
  const loadError = ref('')
  const saving = ref(false)

  function fill(record) {
    for (const key of Object.keys(defaults)) {
      const value = record?.[key]
      form[key] = value === null || value === undefined ? clone(defaults[key]) : clone(value)
    }
  }

  async function load() {
    if (!id) return
    loading.value = true
    loadError.value = ''
    try {
      fill(await resource.get(id))
    } catch (error) {
      loadError.value = errorMessage(error, 'Data gagal dimuat.')
    } finally {
      loading.value = false
    }
  }

  function payload() {
    const data = {}
    for (const key of Object.keys(defaults)) data[key] = clone(form[key])
    return data
  }

  async function save() {
    saving.value = true
    errors.value = {}
    try {
      if (id) {
        await resource.update(id, payload())
      } else {
        await resource.create(payload())
      }
      alertSuccess('Berhasil disimpan.')
      router.visit(indexHref)
    } catch (error) {
      if (error?.response?.status === 422) {
        errors.value = error.response.data.errors || {}
        const errorEntries = Object.entries(errors.value)
        const firstEntry = errorEntries[0]
        const firstMsg = firstEntry ? `${firstEntry[0]}: ${firstEntry[1]?.[0] || ''}` : ''
        alertError(firstMsg ? `Periksa isian (${firstMsg})` : 'Periksa kembali isian yang ditandai.')
        setTimeout(() => {
          const firstErrEl = document.querySelector('.text-red-500, .text-red-400, .border-red-500')
          firstErrEl?.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }, 100)
      } else {
        alertError(errorMessage(error))
      }
    } finally {
      saving.value = false
    }
  }

  /** First validation message for a field key (e.g. "title" or "fakta_kunci"). */
  function err(key) {
    if (errors.value[key]?.[0]) return errors.value[key][0]
    // Check dot notation (e.g. fakta_kunci.0.claim matches err('fakta_kunci'))
    const match = Object.keys(errors.value).find(k => k === key || k.startsWith(`${key}.`))
    return match ? errors.value[match]?.[0] || '' : ''
  }

  load()

  return { form, errors, err, loading, loadError, saving, load, save }
}
