import { describe, it, expect, beforeEach } from 'vitest'
import { guestService } from '../../../src/api/services/guestService.js'
import { ServiceError } from '../../../src/api/services/coupleService.js'

const TEST_SLUG = 'test-wedding'

describe('guestService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('CRUD operations', () => {
    it('creates guest with auto-generated fields', async () => {
      const guest = await guestService.create(TEST_SLUG, { name: 'Ahmad Fauzi', phone: '628123456789', maxPax: 2 })
      expect(guest.id).toBeDefined()
      expect(guest.name).toBe('Ahmad Fauzi')
      expect(guest.createdAt).toBeDefined()
    })

    it('reads back created guest', async () => {
      const created = await guestService.create(TEST_SLUG, { name: 'Ahmad' })
      const found = await guestService.getById(TEST_SLUG, created.id)
      expect(found).toEqual(created)
    })

    it('updates guest', async () => {
      const created = await guestService.create(TEST_SLUG, { name: 'Ahmad', phone: '123' })
      const updated = await guestService.update(TEST_SLUG, created.id, { phone: '456' })
      expect(updated.phone).toBe('456')
      expect(updated.name).toBe('Ahmad')
    })

    it('throws ServiceError when updating non-existent guest', async () => {
      await expect(guestService.update(TEST_SLUG, 'nonexistent', { name: 'test' }))
        .rejects.toThrow(ServiceError)
    })

    it('deletes guest', async () => {
      const created = await guestService.create(TEST_SLUG, { name: 'Ahmad' })
      expect(await guestService.delete(TEST_SLUG, created.id)).toBe(true)
      expect(await guestService.getById(TEST_SLUG, created.id)).toBeNull()
    })

    it('returns false when deleting non-existent guest', async () => {
      expect(await guestService.delete(TEST_SLUG, 'nonexistent')).toBe(false)
    })
  })

  describe('search', () => {
    it('returns all guests when query is empty', async () => {
      await guestService.create(TEST_SLUG, { name: 'Ahmad' })
      await guestService.create(TEST_SLUG, { name: 'Budi' })
      const result = await guestService.search(TEST_SLUG, '')
      expect(result).toHaveLength(2)
    })

    it('searches by name (case-insensitive)', async () => {
      await guestService.create(TEST_SLUG, { name: 'Ahmad Fauzi' })
      await guestService.create(TEST_SLUG, { name: 'Budi Santoso' })
      const result = await guestService.search(TEST_SLUG, 'ahmad')
      expect(result).toHaveLength(1)
      expect(result[0].name).toBe('Ahmad Fauzi')
    })

    it('searches by phone number', async () => {
      await guestService.create(TEST_SLUG, { name: 'Ahmad', phone: '628123456789' })
      await guestService.create(TEST_SLUG, { name: 'Budi', phone: '628987654321' })
      const result = await guestService.search(TEST_SLUG, '628123')
      expect(result).toHaveLength(1)
      expect(result[0].name).toBe('Ahmad')
    })
  })
})
