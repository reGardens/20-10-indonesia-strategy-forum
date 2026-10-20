import { describe, it, expect } from 'vitest'
import { useQrCode } from '../../../src/composables/useQrCode.js'

describe('useQrCode', () => {
  const { encode, decode } = useQrCode()

  describe('encode', () => {
    it('returns a non-empty base64 string for valid input', () => {
      const result = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      expect(result).toBeTruthy()
      expect(typeof result).toBe('string')
      // base64 should only contain valid characters
      expect(result).toMatch(/^[A-Za-z0-9+/=]+$/)
    })

    it('returns empty string when guestId is missing', () => {
      expect(encode({ guestId: '', weddingSlug: 'budi-ani' })).toBe('')
      expect(encode({ weddingSlug: 'budi-ani' })).toBe('')
    })

    it('returns empty string when weddingSlug is missing', () => {
      expect(encode({ guestId: 'guest-1', weddingSlug: '' })).toBe('')
      expect(encode({ guestId: 'guest-1' })).toBe('')
    })

    it('returns empty string for null or undefined input', () => {
      expect(encode(null)).toBe('')
      expect(encode(undefined)).toBe('')
    })

    it('produces different QR codes for different guests', () => {
      const qr1 = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      const qr2 = encode({ guestId: 'guest-2', weddingSlug: 'budi-ani' })
      expect(qr1).not.toBe(qr2)
    })

    it('produces different QR codes for different weddings', () => {
      const qr1 = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      const qr2 = encode({ guestId: 'guest-1', weddingSlug: 'andi-sari' })
      expect(qr1).not.toBe(qr2)
    })

    it('uses provided checksum when given', () => {
      const result = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani', checksum: 'custom123' })
      const decoded = JSON.parse(atob(result))
      expect(decoded.checksum).toBe('custom123')
    })

    it('auto-generates checksum when not provided', () => {
      const result = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      const decoded = JSON.parse(atob(result))
      expect(decoded.checksum).toBeTruthy()
      expect(typeof decoded.checksum).toBe('string')
    })
  })

  describe('decode', () => {
    it('decodes a valid QR code string back to original data', () => {
      const original = { guestId: 'guest-1', weddingSlug: 'budi-ani' }
      const encoded = encode(original)
      const decoded = decode(encoded)
      expect(decoded).not.toBeNull()
      expect(decoded.guestId).toBe('guest-1')
      expect(decoded.weddingSlug).toBe('budi-ani')
      expect(decoded.checksum).toBeTruthy()
    })

    it('returns null for empty string', () => {
      expect(decode('')).toBeNull()
    })

    it('returns null for null or undefined', () => {
      expect(decode(null)).toBeNull()
      expect(decode(undefined)).toBeNull()
    })

    it('returns null for non-string input', () => {
      expect(decode(123)).toBeNull()
      expect(decode({})).toBeNull()
    })

    it('returns null for invalid base64', () => {
      expect(decode('not-valid-base64!!!')).toBeNull()
    })

    it('returns null for valid base64 but invalid JSON', () => {
      const invalidJson = btoa('this is not json')
      expect(decode(invalidJson)).toBeNull()
    })

    it('returns null for JSON missing required fields', () => {
      const missingGuestId = btoa(JSON.stringify({ weddingSlug: 'budi-ani', checksum: 'abc' }))
      expect(decode(missingGuestId)).toBeNull()

      const missingSlug = btoa(JSON.stringify({ guestId: 'guest-1', checksum: 'abc' }))
      expect(decode(missingSlug)).toBeNull()

      const missingChecksum = btoa(JSON.stringify({ guestId: 'guest-1', weddingSlug: 'budi-ani' }))
      expect(decode(missingChecksum)).toBeNull()
    })

    it('returns null for tampered checksum', () => {
      const encoded = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      const decoded = JSON.parse(atob(encoded))
      decoded.checksum = 'tampered'
      const tampered = btoa(JSON.stringify(decoded))
      expect(decode(tampered)).toBeNull()
    })

    it('returns null for tampered guestId (checksum mismatch)', () => {
      const encoded = encode({ guestId: 'guest-1', weddingSlug: 'budi-ani' })
      const decoded = JSON.parse(atob(encoded))
      decoded.guestId = 'guest-hacked'
      const tampered = btoa(JSON.stringify(decoded))
      expect(decode(tampered)).toBeNull()
    })
  })

  describe('encode/decode round-trip', () => {
    it('round-trips guest data correctly', () => {
      const data = { guestId: 'abc-123', weddingSlug: 'budi-ani' }
      const decoded = decode(encode(data))
      expect(decoded.guestId).toBe(data.guestId)
      expect(decoded.weddingSlug).toBe(data.weddingSlug)
    })

    it('round-trips with special characters in guestId', () => {
      const data = { guestId: 'guest-with-special_chars.123', weddingSlug: 'wedding-slug' }
      const decoded = decode(encode(data))
      expect(decoded.guestId).toBe(data.guestId)
      expect(decoded.weddingSlug).toBe(data.weddingSlug)
    })

    it('round-trips with Unicode in weddingSlug', () => {
      const data = { guestId: 'guest-1', weddingSlug: 'pernikahan-müller' }
      const decoded = decode(encode(data))
      expect(decoded.guestId).toBe(data.guestId)
      expect(decoded.weddingSlug).toBe(data.weddingSlug)
    })

    it('produces deterministic output for same input', () => {
      const data = { guestId: 'guest-1', weddingSlug: 'budi-ani' }
      const encoded1 = encode(data)
      const encoded2 = encode(data)
      expect(encoded1).toBe(encoded2)
    })
  })

  describe('uniqueness', () => {
    it('generates unique QR codes for a batch of distinct guests', () => {
      const slugs = ['budi-ani']
      const guestIds = Array.from({ length: 20 }, (_, i) => `guest-${i}`)
      const codes = new Set()

      for (const slug of slugs) {
        for (const guestId of guestIds) {
          codes.add(encode({ guestId, weddingSlug: slug }))
        }
      }

      expect(codes.size).toBe(guestIds.length * slugs.length)
    })
  })
})
