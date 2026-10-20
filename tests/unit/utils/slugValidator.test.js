import { describe, it, expect } from 'vitest'
import { validateSlug, normalizeSlug } from '../../../src/utils/slugValidator.js'

describe('validateSlug', () => {
  describe('valid slugs', () => {
    it('accepts a simple kebab-case slug', () => {
      const result = validateSlug('budi-ani')
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })

    it('accepts a 3-character slug (minimum length)', () => {
      const result = validateSlug('abc')
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })

    it('accepts a slug with digits', () => {
      const result = validateSlug('a1b2c3')
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })

    it('accepts a slug at maximum length (64 characters)', () => {
      const slug = 'a'.repeat(64)
      const result = validateSlug(slug)
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })

    it('accepts a slug with single hyphens between words', () => {
      const result = validateSlug('pernikahan-budi-dan-ani')
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })

    it('accepts all-digit slug', () => {
      const result = validateSlug('123')
      expect(result.valid).toBe(true)
      expect(result.error).toBeNull()
    })
  })

  describe('invalid slugs — not a string', () => {
    it('rejects null', () => {
      const result = validateSlug(null)
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug harus berupa teks')
    })

    it('rejects undefined', () => {
      const result = validateSlug(undefined)
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug harus berupa teks')
    })

    it('rejects a number', () => {
      const result = validateSlug(123)
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug harus berupa teks')
    })

    it('rejects an object', () => {
      const result = validateSlug({})
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug harus berupa teks')
    })
  })

  describe('invalid slugs — length', () => {
    it('rejects a slug that is too short (2 characters)', () => {
      const result = validateSlug('ab')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug minimal 3 karakter')
    })

    it('rejects an empty string', () => {
      const result = validateSlug('')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug minimal 3 karakter')
    })

    it('rejects a slug that is too long (65 characters)', () => {
      const slug = 'a'.repeat(65)
      const result = validateSlug(slug)
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug maksimal 64 karakter')
    })
  })

  describe('invalid slugs — invalid characters', () => {
    it('rejects uppercase letters', () => {
      const result = validateSlug('BudiAni')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung')
    })

    it('rejects spaces', () => {
      const result = validateSlug('budi ani')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung')
    })

    it('rejects underscores', () => {
      const result = validateSlug('budi_ani')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung')
    })

    it('rejects special characters', () => {
      const result = validateSlug('budi@ani')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung')
    })
  })

  describe('invalid slugs — hyphen placement', () => {
    it('rejects slug starting with a hyphen', () => {
      const result = validateSlug('-start')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug tidak boleh diawali atau diakhiri dengan tanda hubung')
    })

    it('rejects slug ending with a hyphen', () => {
      const result = validateSlug('end-')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug tidak boleh diawali atau diakhiri dengan tanda hubung')
    })

    it('rejects slug with consecutive hyphens', () => {
      const result = validateSlug('a--b')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('Slug tidak boleh mengandung tanda hubung berturut-turut')
    })
  })
})

describe('normalizeSlug', () => {
  it('converts uppercase to lowercase', () => {
    expect(normalizeSlug('BudiAni')).toBe('budiani')
  })

  it('trims leading and trailing whitespace', () => {
    expect(normalizeSlug('  budi-ani  ')).toBe('budi-ani')
  })

  it('converts uppercase and trims whitespace together', () => {
    expect(normalizeSlug('  Budi-Ani  ')).toBe('budi-ani')
  })

  it('returns empty string for non-string input', () => {
    expect(normalizeSlug(null)).toBe('')
    expect(normalizeSlug(undefined)).toBe('')
    expect(normalizeSlug(123)).toBe('')
  })

  it('handles already normalized input', () => {
    expect(normalizeSlug('budi-ani')).toBe('budi-ani')
  })

  it('handles empty string', () => {
    expect(normalizeSlug('')).toBe('')
  })
})
