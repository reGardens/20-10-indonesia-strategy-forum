import { describe, it, expect, beforeEach } from 'vitest'
import { wishService } from '../../../src/api/services/wishService.js'
import { ServiceError } from '../../../src/api/services/coupleService.js'

const TEST_SLUG = 'test-wedding'

describe('wishService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('CRUD operations', () => {
    it('creates wish with auto-generated fields', async () => {
      const wish = await wishService.create(TEST_SLUG, { guestName: 'Ahmad', message: 'Selamat!', status: 'pending' })
      expect(wish.id).toBeDefined()
      expect(wish.guestName).toBe('Ahmad')
      expect(wish.message).toBe('Selamat!')
      expect(wish.createdAt).toBeDefined()
    })

    it('reads back created wish', async () => {
      const created = await wishService.create(TEST_SLUG, { guestName: 'Ahmad', message: 'Selamat!' })
      const found = await wishService.getById(TEST_SLUG, created.id)
      expect(found).toEqual(created)
    })

    it('updates wish', async () => {
      const created = await wishService.create(TEST_SLUG, { guestName: 'Ahmad', message: 'Selamat!' })
      const updated = await wishService.update(TEST_SLUG, created.id, { message: 'Selamat menempuh hidup baru!' })
      expect(updated.message).toBe('Selamat menempuh hidup baru!')
    })

    it('deletes wish', async () => {
      const created = await wishService.create(TEST_SLUG, { guestName: 'Ahmad', message: 'Selamat!' })
      expect(await wishService.delete(TEST_SLUG, created.id)).toBe(true)
      expect(await wishService.getById(TEST_SLUG, created.id)).toBeNull()
    })

    it('returns false when deleting non-existent wish', async () => {
      expect(await wishService.delete(TEST_SLUG, 'nonexistent')).toBe(false)
    })
  })

  describe('getByStatus', () => {
    it('returns all wishes when status is "all"', async () => {
      await wishService.create(TEST_SLUG, { guestName: 'A', status: 'approved' })
      await wishService.create(TEST_SLUG, { guestName: 'B', status: 'pending' })
      const result = await wishService.getByStatus(TEST_SLUG, 'all')
      expect(result).toHaveLength(2)
    })

    it('filters by specific status', async () => {
      await wishService.create(TEST_SLUG, { guestName: 'A', status: 'approved' })
      await wishService.create(TEST_SLUG, { guestName: 'B', status: 'pending' })
      await wishService.create(TEST_SLUG, { guestName: 'C', status: 'approved' })
      const result = await wishService.getByStatus(TEST_SLUG, 'approved')
      expect(result).toHaveLength(2)
      expect(result.every(w => w.status === 'approved')).toBe(true)
    })

    it('returns all when no status provided', async () => {
      await wishService.create(TEST_SLUG, { guestName: 'A', status: 'approved' })
      const result = await wishService.getByStatus(TEST_SLUG, null)
      expect(result).toHaveLength(1)
    })
  })

  describe('updateStatus', () => {
    it('updates wish status', async () => {
      const wish = await wishService.create(TEST_SLUG, { guestName: 'Ahmad', status: 'pending' })
      const updated = await wishService.updateStatus(TEST_SLUG, wish.id, 'approved')
      expect(updated.status).toBe('approved')
    })

    it('throws for non-existent wish', async () => {
      await expect(wishService.updateStatus(TEST_SLUG, 'nonexistent', 'approved'))
        .rejects.toThrow(ServiceError)
    })
  })
})
