import { describe, it, expect, beforeEach } from 'vitest'
import { eventService } from '../../../src/api/services/eventService.js'
import { ServiceError } from '../../../src/api/services/coupleService.js'

const TEST_SLUG = 'test-wedding'

describe('eventService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('getAll', () => {
    it('returns empty array when no events exist', async () => {
      const result = await eventService.getAll(TEST_SLUG)
      expect(result).toEqual([])
    })

    it('returns stored events', async () => {
      const events = [{ id: '1', name: 'Akad Nikah' }]
      localStorage.setItem('wedding_test-wedding_events', JSON.stringify(events))
      const result = await eventService.getAll(TEST_SLUG)
      expect(result).toEqual(events)
    })
  })

  describe('getById', () => {
    it('returns null for non-existent id', async () => {
      const result = await eventService.getById(TEST_SLUG, 'nonexistent')
      expect(result).toBeNull()
    })

    it('returns event by id', async () => {
      const event = await eventService.create(TEST_SLUG, { name: 'Akad Nikah', date: '2024-12-25' })
      const result = await eventService.getById(TEST_SLUG, event.id)
      expect(result.name).toBe('Akad Nikah')
    })
  })

  describe('create', () => {
    it('creates event with auto-generated id and timestamps', async () => {
      const result = await eventService.create(TEST_SLUG, { name: 'Resepsi', date: '2024-12-25' })
      expect(result.id).toBeDefined()
      expect(result.id.length).toBeGreaterThan(0)
      expect(result.name).toBe('Resepsi')
      expect(result.createdAt).toBeDefined()
      expect(result.updatedAt).toBeDefined()
    })

    it('persists created event', async () => {
      await eventService.create(TEST_SLUG, { name: 'Akad' })
      const all = await eventService.getAll(TEST_SLUG)
      expect(all).toHaveLength(1)
      expect(all[0].name).toBe('Akad')
    })
  })

  describe('update', () => {
    it('updates event fields', async () => {
      const event = await eventService.create(TEST_SLUG, { name: 'Akad', date: '2024-12-25' })
      const updated = await eventService.update(TEST_SLUG, event.id, { name: 'Akad Nikah' })
      expect(updated.name).toBe('Akad Nikah')
      expect(updated.date).toBe('2024-12-25')
    })

    it('throws ServiceError for non-existent event', async () => {
      await expect(eventService.update(TEST_SLUG, 'nonexistent', { name: 'test' }))
        .rejects.toThrow(ServiceError)
    })

    it('updates updatedAt timestamp', async () => {
      const event = await eventService.create(TEST_SLUG, { name: 'Akad' })
      const originalUpdatedAt = event.updatedAt
      // Small delay to ensure different timestamp
      await new Promise(r => setTimeout(r, 10))
      const updated = await eventService.update(TEST_SLUG, event.id, { name: 'Resepsi' })
      expect(updated.updatedAt).not.toBe(originalUpdatedAt)
    })
  })

  describe('delete', () => {
    it('deletes existing event and returns true', async () => {
      const event = await eventService.create(TEST_SLUG, { name: 'Akad' })
      const result = await eventService.delete(TEST_SLUG, event.id)
      expect(result).toBe(true)
      const all = await eventService.getAll(TEST_SLUG)
      expect(all).toHaveLength(0)
    })

    it('returns false for non-existent event', async () => {
      const result = await eventService.delete(TEST_SLUG, 'nonexistent')
      expect(result).toBe(false)
    })
  })
})
