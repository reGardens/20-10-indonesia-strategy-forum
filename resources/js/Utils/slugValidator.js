/**
 * Validates a wedding slug against the required format rules.
 *
 * Rules:
 * - Must be a string
 * - Only lowercase letters (a-z), digits (0-9), and hyphens (-)
 * - Minimum 3 characters, maximum 64 characters
 * - Must not start or end with a hyphen
 * - Must not contain consecutive hyphens (--)
 *
 * @param {string} slug - The slug to validate
 * @returns {{ valid: boolean, error: string | null }}
 */
export function validateSlug(slug) {
  if (typeof slug !== 'string') {
    return { valid: false, error: 'Slug harus berupa teks' }
  }

  if (slug.length < 3) {
    return { valid: false, error: 'Slug minimal 3 karakter' }
  }

  if (slug.length > 64) {
    return { valid: false, error: 'Slug maksimal 64 karakter' }
  }

  if (!/^[a-z0-9-]+$/.test(slug)) {
    return { valid: false, error: 'Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung' }
  }

  if (slug.startsWith('-') || slug.endsWith('-')) {
    return { valid: false, error: 'Slug tidak boleh diawali atau diakhiri dengan tanda hubung' }
  }

  if (slug.includes('--')) {
    return { valid: false, error: 'Slug tidak boleh mengandung tanda hubung berturut-turut' }
  }

  return { valid: true, error: null }
}

/**
 * Normalizes input to a valid slug format.
 * Converts to lowercase and trims whitespace.
 *
 * @param {string} input - Raw input string
 * @returns {string} Normalized (lowercased, trimmed) string
 */
export function normalizeSlug(input) {
  if (typeof input !== 'string') {
    return ''
  }
  return input.trim().toLowerCase()
}
