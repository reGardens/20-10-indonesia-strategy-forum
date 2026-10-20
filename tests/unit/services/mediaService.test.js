import { describe, it, expect, beforeEach } from 'vitest'
import { mediaService } from '../../../src/api/services/mediaService.js'

const TEST_SLUG = 'test-wedding'

describe('mediaService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('CRUD operations', () => {
    it('creates media with auto-generated sortOrder', async () => {
      const media = await mediaService.create(TEST_SLUG, { fileName: 'photo1.webp', type: 'image' })
      expect(media.id).toBeDefined()
      expect(media.sortOrder).toBe(1)
      expect(media.createdAt).toBeDefined()
    })

    it('auto-increments sortOrder', async () => {
      await mediaService.create(TEST_SLUG, { fileName: 'photo1.webp', type: 'image' })
      const second = await mediaService.create(TEST_SLUG, { fileName: 'photo2.webp', type: 'image' })
      expect(second.sortOrder).toBe(2)
    })

    it('getAll returns sorted by sortOrder', async () => {
      await mediaService.create(TEST_SLUG, { fileName: 'photo2.webp', sortOrder: 2 })
      await mediaService.create(TEST_SLUG, { fileName: 'photo1.webp', sortOrder: 1 })
      const all = await mediaService.getAll(TEST_SLUG)
      expect(all[0].fileName).toBe('photo1.webp')
      expect(all[1].fileName).toBe('photo2.webp')
    })

    it('deletes media', async () => {
      const media = await mediaService.create(TEST_SLUG, { fileName: 'photo.webp' })
      expect(await mediaService.delete(TEST_SLUG, media.id)).toBe(true)
      expect(await mediaService.getById(TEST_SLUG, media.id)).toBeNull()
    })
  })

  describe('bulkDelete', () => {
    it('deletes multiple media items', async () => {
      const m1 = await mediaService.create(TEST_SLUG, { fileName: 'a.webp' })
      const m2 = await mediaService.create(TEST_SLUG, { fileName: 'b.webp' })
      await mediaService.create(TEST_SLUG, { fileName: 'c.webp' })
      const result = await mediaService.bulkDelete(TEST_SLUG, [m1.id, m2.id])
      expect(result).toBe(true)
      const all = await mediaService.getAll(TEST_SLUG)
      expect(all).toHaveLength(1)
      expect(all[0].fileName).toBe('c.webp')
    })

    it('returns false when no items match', async () => {
      const result = await mediaService.bulkDelete(TEST_SLUG, ['nonexistent'])
      expect(result).toBe(false)
    })
  })

  describe('reorder', () => {
    it('updates sortOrder for specified items', async () => {
      const m1 = await mediaService.create(TEST_SLUG, { fileName: 'a.webp' })
      const m2 = await mediaService.create(TEST_SLUG, { fileName: 'b.webp' })
      const reordered = await mediaService.reorder(TEST_SLUG, [
        { id: m1.id, sortOrder: 2 },
        { id: m2.id, sortOrder: 1 }
      ])
      expect(reordered[0].fileName).toBe('b.webp')
      expect(reordered[1].fileName).toBe('a.webp')
    })
  })
})
