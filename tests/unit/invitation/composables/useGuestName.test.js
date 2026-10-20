import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock Vue lifecycle hooks
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    onUnmounted: vi.fn()
  }
})

import { useGuestName } from '../../../../src/invitation/composables/useGuestName.js'

describe('useGuestName', () => {
  const originalLocation = window.location

  beforeEach(() => {
    // Allow overriding window.location.search
    delete window.location
    window.location = { ...originalLocation, search: '' }
  })

  afterEach(() => {
    window.location = originalLocation
  })

  it('extracts and decodes guest name from to parameter', () => {
    window.location.search = '?to=Bpk+Ahmad+Fauzi'
    const { guestName } = useGuestName()
    expect(guestName.value).toBe('Bpk Ahmad Fauzi')
  })

  it('decodes percent-encoded characters', () => {
    window.location.search = '?to=Bpk.+Ahmad+%26+Ibu'
    const { guestName } = useGuestName()
    expect(guestName.value).toBe('Bpk. Ahmad & Ibu')
  })

  it('returns empty string when to parameter is missing', () => {
    window.location.search = ''
    const { guestName } = useGuestName()
    expect(guestName.value).toBe('')
  })

  it('returns empty string when query string has other params but no to', () => {
    window.location.search = '?slug=budi-ani&ref=whatsapp'
    const { guestName } = useGuestName()
    expect(guestName.value).toBe('')
  })

  it('handles Unicode names', () => {
    window.location.search = '?to=M%C3%BCller-Schr%C3%B6der'
    const { guestName } = useGuestName()
    expect(guestName.value).toBe('Müller-Schröder')
  })
})
