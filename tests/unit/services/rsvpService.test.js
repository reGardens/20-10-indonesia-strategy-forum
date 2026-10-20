import { describe, it, expect, beforeEach } from 'vitest'
import { rsvpService } from '../../../src/api/services/rsvpService.js'
import { ServiceError } from '../../../src/api/services/coupleService.js'

const TEST_SLUG = 'test-wedding'

describe('rsvpService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('CRUD operations', () => {
    it('creates RSVP with auto-generated fields', async () => {
      const rsvp = await rsvpService.create(TEST_SLUG, { guestId: 'g1', guestName: 'Ahmad', status: 'Hadir', numberOfGuests: 2 })
      expect(rsvp.id).toBeDefined()
      expect(rsvp.guestName).toBe('Ahmad')
      expect(rsvp.status).toBe('Hadir')
      expect(rsvp.submittedAt).toBeDefined()
    })

    it('reads back created RSVP', async () => {
      const created = await rsvpService.create(TEST_SLUG, { guestId: 'g1', status: 'Hadir' })
      const found = await rsvpService.getById(TEST_SLUG, created.id)
      expect(found).toEqual(created)
    })

    it('updates RSVP', async () => {
      const created = await rsvpService.create(TEST_SLUG, { guestId: 'g1', status: 'Mungkin' })
      const updated = await rsvpService.update(TEST_SLUG, created.id, { status: 'Hadir' })
      expect(updated.status).toBe('Hadir')
    })

    it('deletes RSVP', async () => {
      const created = await rsvpService.create(TEST_SLUG, { guestId: 'g1', status: 'Hadir' })
      const result = await rsvpService.delete(TEST_SLUG, created.id)
      expect(result).toBe(true)
      const found = await rsvpService.getById(TEST_SLUG, created.id)
      expect(found).toBeNull()
    })
  })

  describe('getByGuestId', () => {
    it('returns RSVP for a specific guest', async () => {
      await rsvpService.create(TEST_SLUG, { guestId: 'g1', status: 'Hadir' })
      await rsvpService.create(TEST_SLUG, { guestId: 'g2', status: 'Tidak Hadir' })
      const result = await rsvpService.getByGuestId(TEST_SLUG, 'g1')
      expect(result.guestId).toBe('g1')
      expect(result.status).toBe('Hadir')
    })

    it('returns null for non-existent guest', async () => {
      const result = await rsvpService.getByGuestId(TEST_SLUG, 'nonexistent')
      expect(result).toBeNull()
    })
  })

  describe('getSummary', () => {
    it('returns zero counts when empty', async () => {
      const summary = await rsvpService.getSummary(TEST_SLUG)
      expect(summary).toEqual({ hadir: 0, tidakHadir: 0, mungkin: 0, total: 0 })
    })

    it('counts statuses correctly', async () => {
      await rsvpService.create(TEST_SLUG, { guestId: 'g1', status: 'Hadir' })
      await rsvpService.create(TEST_SLUG, { guestId: 'g2', status: 'Hadir' })
      await rsvpService.create(TEST_SLUG, { guestId: 'g3', status: 'Tidak Hadir' })
      await rsvpService.create(TEST_SLUG, { guestId: 'g4', status: 'Mungkin' })

      const summary = await rsvpService.getSummary(TEST_SLUG)
      expect(summary.hadir).toBe(2)
      expect(summary.tidakHadir).toBe(1)
      expect(summary.mungkin).toBe(1)
      expect(summary.total).toBe(4)
    })
  })
})
