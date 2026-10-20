import { validateSlug } from './slugValidator.js'
import { weddingRegistryService } from '../api/services/weddingRegistryService.js'

/**
 * Legacy key names (without slug prefix).
 * These are the keys used by the old single-tenant system.
 */
const LEGACY_KEYS = [
  'wedding_couple',
  'wedding_events',
  'wedding_guests',
  'wedding_rsvp',
  'wedding_wishes',
  'wedding_media',
  'wedding_payments',
  'wedding_settings'
]

/**
 * Extracts the entity name from a legacy key.
 * e.g., 'wedding_couple' → 'couple'
 * @param {string} key - Legacy localStorage key
 * @returns {string} Entity name
 */
function getEntityFromKey(key) {
  return key.replace('wedding_', '')
}

/**
 * Detects if any legacy (non-namespaced) wedding data exists in localStorage.
 * Checks for the presence of any legacy keys that don't have a slug prefix.
 * @returns {boolean}
 */
export function hasLegacyData() {
  for (const key of LEGACY_KEYS) {
    if (localStorage.getItem(key) !== null) {
      return true
    }
  }
  return false
}

/**
 * Migrates legacy data to a new slug namespace.
 * 
 * Flow:
 * 1. Validate slug format
 * 2. For each legacy key that exists:
 *    a. Read data from old key
 *    b. Write to new key wedding_{slug}_{entity}
 *    c. Track copied keys for rollback
 * 3. Add entry to wedding registry
 * 4. Remove old legacy keys
 * 5. If any step fails, rollback: remove all new keys, keep old keys
 *
 * @param {string} slug - Target slug for the migrated data
 * @returns {Promise<{ success: boolean, migratedEntities: string[], error?: string }>}
 */
export async function migrateLegacyData(slug) {
  // 1. Validate slug
  const validation = validateSlug(slug)
  if (!validation.valid) {
    return { success: false, migratedEntities: [], error: validation.error }
  }

  const copiedKeys = [] // Track new keys for rollback
  const migratedEntities = [] // Track successfully migrated entity names

  try {
    // 2. For each legacy key that exists, copy to new namespace
    for (const legacyKey of LEGACY_KEYS) {
      const data = localStorage.getItem(legacyKey)
      if (data !== null) {
        const entity = getEntityFromKey(legacyKey)
        const newKey = `wedding_${slug}_${entity}`

        localStorage.setItem(newKey, data)
        copiedKeys.push(newKey)
        migratedEntities.push(entity)
      }
    }

    // 3. Add entry to wedding registry
    await weddingRegistryService.create({ slug, label: slug })

    // 4. Remove old legacy keys
    for (const legacyKey of LEGACY_KEYS) {
      if (localStorage.getItem(legacyKey) !== null) {
        localStorage.removeItem(legacyKey)
      }
    }

    return { success: true, migratedEntities }
  } catch (error) {
    // 5. Rollback: remove all new keys, keep old keys intact
    for (const newKey of copiedKeys) {
      localStorage.removeItem(newKey)
    }

    return {
      success: false,
      migratedEntities: [],
      error: error.message || 'Migrasi gagal'
    }
  }
}
