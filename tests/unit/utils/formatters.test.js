import { describe, it, expect } from 'vitest'
import { formatDate, formatTime, formatCurrency, formatEventDateTime } from '../../../src/utils/formatters.js'

describe('formatDate', () => {
  it('formats a date string in Bahasa Indonesia', () => {
    const result = formatDate('2024-12-25')
    expect(result).toContain('25')
    expect(result).toContain('Desember')
    expect(result).toContain('2024')
  })

  it('formats a Date object', () => {
    const result = formatDate(new Date(2024, 0, 1))
    expect(result).toContain('Januari')
    expect(result).toContain('2024')
  })

  it('returns empty string for invalid date', () => {
    expect(formatDate('not-a-date')).toBe('')
  })

  it('returns empty string for null', () => {
    expect(formatDate(null)).toBe('')
  })
})

describe('formatTime', () => {
  it('formats time with default WIB timezone', () => {
    expect(formatTime('08:00')).toBe('08:00 WIB')
  })

  it('formats time with custom timezone', () => {
    expect(formatTime('14:30', 'WITA')).toBe('14:30 WITA')
  })

  it('pads single-digit hours and minutes', () => {
    expect(formatTime('8:5')).toBe('08:05 WIB')
  })

  it('returns empty string for null', () => {
    expect(formatTime(null)).toBe('')
  })

  it('returns empty string for invalid format', () => {
    expect(formatTime('invalid')).toBe('')
  })
})

describe('formatCurrency', () => {
  it('formats a number as Indonesian Rupiah', () => {
    const result = formatCurrency(500000)
    // Intl may use different space characters, so check key parts
    expect(result).toContain('Rp')
    expect(result).toContain('500')
  })

  it('formats zero', () => {
    const result = formatCurrency(0)
    expect(result).toContain('Rp')
    expect(result).toContain('0')
  })

  it('returns empty string for NaN', () => {
    expect(formatCurrency(NaN)).toBe('')
  })

  it('returns empty string for non-number', () => {
    expect(formatCurrency('500000')).toBe('')
  })
})

describe('formatEventDateTime', () => {
  it('formats a full event date-time range', () => {
    const result = formatEventDateTime('2024-12-25', '08:00', '10:00')
    expect(result).toContain('25')
    expect(result).toContain('Desember')
    expect(result).toContain('08:00')
    expect(result).toContain('10:00')
    expect(result).toContain('WIB')
  })

  it('formats without end time', () => {
    const result = formatEventDateTime('2024-12-25', '08:00')
    expect(result).toContain('08:00')
    expect(result).toContain('WIB')
    expect(result).not.toContain('-')
  })

  it('returns empty string for invalid date', () => {
    expect(formatEventDateTime('invalid', '08:00')).toBe('')
  })

  it('returns just the date when start time is invalid', () => {
    const result = formatEventDateTime('2024-12-25', null)
    expect(result).toContain('Desember')
    expect(result).not.toContain('WIB')
  })
})
