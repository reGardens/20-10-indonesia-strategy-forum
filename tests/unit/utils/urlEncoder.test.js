import { describe, it, expect } from 'vitest'
import { encodeGuestName, decodeGuestName } from '../../../src/utils/urlEncoder.js'

describe('encodeGuestName', () => {
  it('encodes spaces as +', () => {
    expect(encodeGuestName('Bpk Ahmad Fauzi')).toBe('Bpk+Ahmad+Fauzi')
  })

  it('encodes special characters', () => {
    const encoded = encodeGuestName('Bpk. Ahmad & Ibu')
    // '.' is not encoded by encodeURIComponent (it's URL-safe)
    // '&' must be encoded to avoid query param conflicts
    expect(encoded).not.toContain('&')
    expect(encoded).toContain('%26')
  })

  it('encodes Unicode characters (Indonesian names)', () => {
    const encoded = encodeGuestName('Müller-Schröder')
    expect(encoded).toContain('%')
  })

  it('returns empty string for null', () => {
    expect(encodeGuestName(null)).toBe('')
  })

  it('returns empty string for empty string', () => {
    expect(encodeGuestName('')).toBe('')
  })

  it('returns empty string for non-string', () => {
    expect(encodeGuestName(123)).toBe('')
  })
})

describe('decodeGuestName', () => {
  it('decodes + back to spaces', () => {
    expect(decodeGuestName('Bpk+Ahmad+Fauzi')).toBe('Bpk Ahmad Fauzi')
  })

  it('decodes percent-encoded characters', () => {
    expect(decodeGuestName('Bpk%2E+Ahmad+%26+Ibu')).toBe('Bpk. Ahmad & Ibu')
  })

  it('decodes Unicode characters', () => {
    const name = 'Müller-Schröder'
    const encoded = encodeGuestName(name)
    expect(decodeGuestName(encoded)).toBe(name)
  })

  it('returns empty string for null', () => {
    expect(decodeGuestName(null)).toBe('')
  })

  it('returns empty string for empty string', () => {
    expect(decodeGuestName('')).toBe('')
  })
})

describe('round-trip', () => {
  it('preserves simple names', () => {
    const name = 'Ahmad Fauzi'
    expect(decodeGuestName(encodeGuestName(name))).toBe(name)
  })

  it('preserves names with titles and dots', () => {
    const name = 'Bpk. H. Ahmad Fauzi, S.Pd.'
    expect(decodeGuestName(encodeGuestName(name))).toBe(name)
  })

  it('preserves names with Unicode characters', () => {
    const name = 'Drs. Müller & Frau Schröder'
    expect(decodeGuestName(encodeGuestName(name))).toBe(name)
  })

  it('preserves names with emoji', () => {
    const name = 'Ahmad 🎉'
    expect(decodeGuestName(encodeGuestName(name))).toBe(name)
  })

  it('preserves Indonesian names with common patterns', () => {
    const names = [
      'Bpk. Ahmad Fauzi',
      'Ibu Siti Aminah',
      'Keluarga Besar Santoso',
      'dr. Dewi Sartika, Sp.OG',
      'H. Muhammad Rizki'
    ]
    for (const name of names) {
      expect(decodeGuestName(encodeGuestName(name))).toBe(name)
    }
  })
})
