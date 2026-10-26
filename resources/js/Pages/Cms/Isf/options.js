/**
 * Fixed option lists for ISF CMS forms and filters.
 * Mirrors App\Support\IsfOptions on the backend.
 */

export const CATEGORIES = [
  { value: 'politik', label: 'Politik' },
  { value: 'ekonomi', label: 'Ekonomi' },
  { value: 'kebijakan', label: 'Kebijakan' },
  { value: 'hukum', label: 'Hukum' },
  { value: 'ruu', label: 'RUU' }
]

export const PRIORITIES = [
  { value: 'merah', label: 'Prioritas Tinggi (Merah)' },
  { value: 'kuning', label: 'Perlu Dipantau (Kuning)' }
]

export const STATUSES = [
  { value: 'draf', label: 'Draf' },
  { value: 'terbit', label: 'Terbit' }
]

export function categoryLabel(value) {
  return CATEGORIES.find((c) => c.value === value)?.label || value || '-'
}

export function priorityLabel(value) {
  return PRIORITIES.find((p) => p.value === value)?.label || value || '-'
}

export function statusLabel(value) {
  return STATUSES.find((s) => s.value === value)?.label || value || '-'
}
