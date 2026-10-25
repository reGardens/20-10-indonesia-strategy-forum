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
      const record = id ? await resource.update(id, payload()) : await resource.create(payload())
      alertSuccess('Berhasil disimpan.')
      if (id) {
        fill(record)
      } else {
        router.visit(`${indexHref}/${record.id}/edit`)
      }
    } catch (error) {
      if (error?.response?.status === 422) {
        errors.value = error.response.data.errors || {}
        alertError('Periksa kembali isian yang ditandai.')
      } else {
        alertError(errorMessage(error))
      }
    } finally {
      saving.value = false
    }
  }

  /** First validation message for a field key (e.g. "title"). */
  function err(key) {
    return errors.value[key]?.[0] || ''
  }

  load()

  return { form, errors, err, loading, loadError, saving, load, save }
}
