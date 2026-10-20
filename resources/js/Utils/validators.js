/**
 * Validates a coordinate value (latitude or longitude).
 * Latitude accepts [-90, 90], longitude accepts [-180, 180].
 *
 * @param {number} value - The coordinate value to validate
 * @param {'latitude'|'longitude'} type - The type of coordinate
 * @returns {boolean} True if the coordinate is valid
 */
export function validateCoordinate(value, type) {
  if (typeof value !== 'number' || Number.isNaN(value)) {
    return false
  }

  if (type === 'latitude') {
    return value >= -90 && value <= 90
  }

  if (type === 'longitude') {
    return value >= -180 && value <= 180
  }

  return false
}

/**
 * Validates that a file is an MP3 music file.
 * Accepts files with audio/mpeg MIME type or .mp3 extension.
 *
 * @param {File|{name?: string, type?: string}} file - The file to validate
 * @returns {boolean} True if the file is a valid MP3
 */
export function validateMusicFile(file) {
  if (!file) {
    return false
  }

  if (file.type === 'audio/mpeg') {
    return true
  }

  if (file.name && file.name.toLowerCase().endsWith('.mp3')) {
    return true
  }

  return false
}

/**
 * Checks whether a date is in the past (before today).
 *
 * @param {string|Date} date - The date to check
 * @returns {boolean} True if the date is in the past
 */
export function validatePastDate(date) {
  const inputDate = date instanceof Date ? date : new Date(date)

  if (Number.isNaN(inputDate.getTime())) {
    return false
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const compareDate = new Date(inputDate)
  compareDate.setHours(0, 0, 0, 0)

  return compareDate < today
}

/**
 * Validates an entity object against a schema definition.
 * Checks required fields, types, patterns, enums, min/max, and minLength constraints.
 *
 * @param {object} entity - The entity to validate
 * @param {object} schema - The schema definition with required[] and properties{}
 * @returns {{ valid: boolean, errors: string[] }}
 */
export function validateSchema(entity, schema) {
  const errors = []

  if (!entity || typeof entity !== 'object') {
    return { valid: false, errors: ['Entity must be a non-null object'] }
  }

  // Check required fields
  if (schema.required) {
    for (const field of schema.required) {
      if (entity[field] === undefined || entity[field] === null || entity[field] === '') {
        errors.push(`Field "${field}" is required`)
      }
    }
  }

  // Validate each property present in the entity
  if (schema.properties) {
    for (const [key, rules] of Object.entries(schema.properties)) {
      const value = entity[key]

      // Skip validation for undefined/null optional fields
      if (value === undefined || value === null) {
        continue
      }

      // Type check
      if (rules.type) {
        if (rules.type === 'object') {
          if (typeof value !== 'object' || Array.isArray(value)) {
            errors.push(`Field "${key}" must be of type object`)
            continue
          }
          // Recursively validate nested object properties
          if (rules.properties) {
            const nestedResult = validateSchema(value, { required: [], properties: rules.properties })
            errors.push(...nestedResult.errors.map(e => `${key}.${e.replace('Field "', '"').replace('"', '')}`).map(e => `Field ${e}`))
          }
          continue
        }

        if (rules.type === 'boolean') {
          if (typeof value !== 'boolean') {
            errors.push(`Field "${key}" must be of type boolean`)
            continue
          }
        } else if (rules.type === 'number') {
          if (typeof value !== 'number' || Number.isNaN(value)) {
            errors.push(`Field "${key}" must be of type number`)
            continue
          }
        } else if (rules.type === 'string') {
          if (typeof value !== 'string') {
            errors.push(`Field "${key}" must be of type string`)
            continue
          }
        }
      }

      // minLength check (for strings)
      if (rules.minLength !== undefined && typeof value === 'string') {
        if (value.length < rules.minLength) {
          errors.push(`Field "${key}" must have minimum length of ${rules.minLength}`)
        }
      }

      // Pattern check (for strings)
      if (rules.pattern && typeof value === 'string') {
        if (!rules.pattern.test(value)) {
          errors.push(`Field "${key}" does not match the required pattern`)
        }
      }

      // Enum check
      if (rules.enum && !rules.enum.includes(value)) {
        errors.push(`Field "${key}" must be one of: ${rules.enum.join(', ')}`)
      }

      // Min check (for numbers)
      if (rules.min !== undefined && typeof value === 'number') {
        if (value < rules.min) {
          errors.push(`Field "${key}" must be at least ${rules.min}`)
        }
      }

      // Max check (for numbers)
      if (rules.max !== undefined && typeof value === 'number') {
        if (value > rules.max) {
          errors.push(`Field "${key}" must be at most ${rules.max}`)
        }
      }
    }
  }

  return { valid: errors.length === 0, errors }
}
