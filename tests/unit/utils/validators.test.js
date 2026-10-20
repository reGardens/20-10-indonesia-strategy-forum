import { describe, it, expect } from 'vitest'
import { validateCoordinate, validateMusicFile, validatePastDate, validateSchema } from '../../../src/utils/validators.js'
import { guestSchema } from '../../../src/api/schemas/guestSchema.js'
import { eventSchema } from '../../../src/api/schemas/eventSchema.js'
import { rsvpSchema } from '../../../src/api/schemas/rsvpSchema.js'
import { wishSchema } from '../../../src/api/schemas/wishSchema.js'
import { mediaSchema } from '../../../src/api/schemas/mediaSchema.js'
import { coupleSchema } from '../../../src/api/schemas/coupleSchema.js'
import { bankAccountSchema, giftSchema } from '../../../src/api/schemas/paymentSchema.js'
import { settingsSchema } from '../../../src/api/schemas/settingsSchema.js'

// --- validateCoordinate ---

describe('validateCoordinate', () => {
  it('accepts valid latitude values', () => {
    expect(validateCoordinate(0, 'latitude')).toBe(true)
    expect(validateCoordinate(-90, 'latitude')).toBe(true)
    expect(validateCoordinate(90, 'latitude')).toBe(true)
    expect(validateCoordinate(-6.17, 'latitude')).toBe(true)
  })

  it('rejects latitude outside [-90, 90]', () => {
    expect(validateCoordinate(91, 'latitude')).toBe(false)
    expect(validateCoordinate(-91, 'latitude')).toBe(false)
    expect(validateCoordinate(180, 'latitude')).toBe(false)
  })

  it('accepts valid longitude values', () => {
    expect(validateCoordinate(0, 'longitude')).toBe(true)
    expect(validateCoordinate(-180, 'longitude')).toBe(true)
    expect(validateCoordinate(180, 'longitude')).toBe(true)
    expect(validateCoordinate(106.831, 'longitude')).toBe(true)
  })

  it('rejects longitude outside [-180, 180]', () => {
    expect(validateCoordinate(181, 'longitude')).toBe(false)
    expect(validateCoordinate(-181, 'longitude')).toBe(false)
  })

  it('rejects non-number values', () => {
    expect(validateCoordinate('abc', 'latitude')).toBe(false)
    expect(validateCoordinate(NaN, 'longitude')).toBe(false)
    expect(validateCoordinate(undefined, 'latitude')).toBe(false)
    expect(validateCoordinate(null, 'longitude')).toBe(false)
  })

  it('rejects unknown coordinate type', () => {
    expect(validateCoordinate(45, 'altitude')).toBe(false)
  })
})

// --- validateMusicFile ---

describe('validateMusicFile', () => {
  it('accepts file with audio/mpeg MIME type', () => {
    const file = new File([''], 'song.mp3', { type: 'audio/mpeg' })
    expect(validateMusicFile(file)).toBe(true)
  })

  it('accepts file with .mp3 extension regardless of MIME type', () => {
    const file = new File([''], 'song.mp3', { type: '' })
    expect(validateMusicFile(file)).toBe(true)
  })

  it('accepts .MP3 extension (case insensitive)', () => {
    const file = new File([''], 'SONG.MP3', { type: '' })
    expect(validateMusicFile(file)).toBe(true)
  })

  it('rejects WAV file', () => {
    const file = new File([''], 'song.wav', { type: 'audio/wav' })
    expect(validateMusicFile(file)).toBe(false)
  })

  it('rejects image file', () => {
    const file = new File([''], 'photo.jpg', { type: 'image/jpeg' })
    expect(validateMusicFile(file)).toBe(false)
  })

  it('rejects null/undefined', () => {
    expect(validateMusicFile(null)).toBe(false)
    expect(validateMusicFile(undefined)).toBe(false)
  })
})

// --- validatePastDate ---

describe('validatePastDate', () => {
  it('flags a date in the past as true', () => {
    expect(validatePastDate('2020-01-01')).toBe(true)
    expect(validatePastDate(new Date(2020, 0, 1))).toBe(true)
  })

  it('returns false for a future date', () => {
    const future = new Date()
    future.setFullYear(future.getFullYear() + 1)
    expect(validatePastDate(future)).toBe(false)
  })

  it('returns false for today', () => {
    const today = new Date()
    expect(validatePastDate(today)).toBe(false)
  })

  it('returns false for invalid date string', () => {
    expect(validatePastDate('not-a-date')).toBe(false)
  })
})

// --- validateSchema ---

describe('validateSchema', () => {
  it('returns valid for a correct entity', () => {
    const guest = { name: 'Ahmad', phone: '08123456789', maxPax: 2 }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(true)
    expect(result.errors).toHaveLength(0)
  })

  it('returns errors for missing required fields', () => {
    const guest = { phone: '08123456789' }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('"name"'))).toBe(true)
  })

  it('returns errors for wrong type', () => {
    const guest = { name: 'Ahmad', maxPax: 'two' }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('"maxPax"'))).toBe(true)
  })

  it('returns errors for value below min', () => {
    const guest = { name: 'Ahmad', maxPax: 0 }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('at least'))).toBe(true)
  })

  it('returns errors for value above max', () => {
    const guest = { name: 'Ahmad', maxPax: 11 }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('at most'))).toBe(true)
  })

  it('returns errors for invalid enum value', () => {
    const rsvp = { guestId: 'g1', guestName: 'Ahmad', status: 'Maybe' }
    const result = validateSchema(rsvp, rsvpSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('must be one of'))).toBe(true)
  })

  it('validates enum correctly for valid value', () => {
    const rsvp = { guestId: 'g1', guestName: 'Ahmad', status: 'Hadir', numberOfGuests: 1 }
    const result = validateSchema(rsvp, rsvpSchema)
    expect(result.valid).toBe(true)
  })

  it('returns errors for pattern mismatch', () => {
    const guest = { name: 'Ahmad', phone: 'abc-not-phone!' }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('pattern'))).toBe(true)
  })

  it('returns errors for minLength violation', () => {
    const guest = { name: '' }
    const result = validateSchema(guest, guestSchema)
    expect(result.valid).toBe(false)
  })

  it('rejects null entity', () => {
    const result = validateSchema(null, guestSchema)
    expect(result.valid).toBe(false)
  })

  it('validates event schema with coordinate constraints', () => {
    const event = { name: 'Akad Nikah', date: '2024-12-25', latitude: -6.17, longitude: 106.83 }
    const result = validateSchema(event, eventSchema)
    expect(result.valid).toBe(true)
  })

  it('rejects event with out-of-range latitude', () => {
    const event = { name: 'Akad Nikah', date: '2024-12-25', latitude: 100 }
    const result = validateSchema(event, eventSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('at most 90'))).toBe(true)
  })

  it('validates wish schema with valid status', () => {
    const wish = { guestName: 'Ahmad', message: 'Selamat!', status: 'approved' }
    const result = validateSchema(wish, wishSchema)
    expect(result.valid).toBe(true)
  })

  it('validates media schema', () => {
    const media = { fileName: 'photo.webp', type: 'image', sortOrder: 1 }
    const result = validateSchema(media, mediaSchema)
    expect(result.valid).toBe(true)
  })

  it('rejects media with invalid type enum', () => {
    const media = { fileName: 'doc.pdf', type: 'document' }
    const result = validateSchema(media, mediaSchema)
    expect(result.valid).toBe(false)
  })

  it('validates couple schema with nested objects', () => {
    const couple = {
      groom: { fullName: 'Budi Santoso', nickname: 'Budi' },
      bride: { fullName: 'Ani Rahayu', nickname: 'Ani' }
    }
    const result = validateSchema(couple, coupleSchema)
    expect(result.valid).toBe(true)
  })

  it('rejects couple with missing required groom/bride', () => {
    const couple = { groom: { fullName: 'Budi' } }
    const result = validateSchema(couple, coupleSchema)
    expect(result.valid).toBe(false)
    expect(result.errors.some(e => e.includes('"bride"'))).toBe(true)
  })

  it('validates bank account schema', () => {
    const account = { bankName: 'BCA', accountNumber: '1234567890', accountHolder: 'Budi' }
    const result = validateSchema(account, bankAccountSchema)
    expect(result.valid).toBe(true)
  })

  it('rejects bank account with missing required fields', () => {
    const account = { bankName: 'BCA' }
    const result = validateSchema(account, bankAccountSchema)
    expect(result.valid).toBe(false)
  })

  it('validates gift schema', () => {
    const gift = { senderName: 'Ahmad', amount: 500000 }
    const result = validateSchema(gift, giftSchema)
    expect(result.valid).toBe(true)
  })

  it('validates settings schema with nested themeColors', () => {
    const settings = {
      templateId: 'batik-elegance',
      themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' },
      galleryLayout: 'masonry',
      moderationEnabled: true
    }
    const result = validateSchema(settings, settingsSchema)
    expect(result.valid).toBe(true)
  })

  it('rejects settings with invalid galleryLayout enum', () => {
    const settings = { galleryLayout: 'carousel' }
    const result = validateSchema(settings, settingsSchema)
    expect(result.valid).toBe(false)
  })
})
