import { ref } from 'vue'
import http from '@/api/http'

/**
 * Option lists for ISF CMS forms and filters.
 * Initialized with default categories (Politik, Ekonomi, Kebijakan, Hukum, RUU)
 * and can be dynamically refreshed from the database.
 */

export const CATEGORIES = [
  { value: 'politik', label: 'Politik' },
  { value: 'ekonomi', label: 'Ekonomi' },
  { value: 'kebijakan', label: 'Kebijakan' },
  { value: 'hukum', label: 'Hukum' },
  { value: 'ruu', label: 'RUU' }
]

export const activeCategories = ref([...CATEGORIES])

/**
 * Fetch latest categories from database (/api/cms/options/categories)
 */
export async function loadCategories() {
  try {
    const res = await http.get('/cms/options/categories')
    const list = res?.data?.data || []
    if (Array.isArray(list) && list.length > 0) {
      activeCategories.value = list
      return list
    }
  } catch (err) {
    // fallback to initial static categories if API fails
  }
  return activeCategories.value
}

export const PRIORITIES = [
  { value: 'merah', label: 'Prioritas Tinggi (Merah)' },
  { value: 'kuning', label: 'Perlu Dipantau (Kuning)' }
]

export const STATUSES = [
  { value: 'draf', label: 'Draf' },
  { value: 'terbit', label: 'Terbit' }
]

export function categoryLabel(value) {
  const found = activeCategories.value.find((c) => c.value === value) || CATEGORIES.find((c) => c.value === value)
  return found?.label || found?.name || value || '-'
}

export function priorityLabel(value) {
  return PRIORITIES.find((p) => p.value === value)?.label || value || '-'
}

export function statusLabel(value) {
  return STATUSES.find((s) => s.value === value)?.label || value || '-'
}
