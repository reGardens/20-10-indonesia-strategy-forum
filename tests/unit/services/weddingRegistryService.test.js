import { describe, it, expect, beforeEach } from 'vitest'
import { weddingRegistryService } from '../../../src/api/services/weddingRegistryService.js'
import { ServiceError } from '../../../src/api/services/coupleService.js'

describe('weddingRegistryService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('getAll', () => {
    it('returns empty array when no registry exists', async () => {
      const result = await weddingRegistryService.getAll()
      expect(result).toEqual([])
    })

    it('returns stored registry entries', async () => {
      const entries = [
        { id: '1', slug: 'budi-ani', label: 'Pernikahan Budi & Ani', createdAt: '2024-01-01T00:00:00.000Z' }
      ]
      localStorage.setItem('wedding_registry', JSON.stringify(entries))
      const result = await weddingRegistryService.getAll()
      expect(result).toEqual(entries)
    })

    it('returns empty array on corrupted JSON', async () => {
      localStorage.setItem('wedding_registry', 'invalid-json')
      const result = await weddingRegistryService.getAll()
      expect(result).toEqual([])
    })
  })

  describe('getBySlug', () => {
    it('returns null when registry is empty', async () => {
      const result = await weddingRegistryService.getBySlug('budi-ani')
      expect(result).toBeNull()
    })

    it('returns the matching entry', async () => {
      const entries = [
        { id: '1', slug: 'budi-ani', label: 'Pernikahan Budi & Ani', createdAt: '2024-01-01T00:00:00.000Z' },
        { id: '2', slug: 'dian-reza', label: 'Pernikahan Dian & Reza', createdAt: '2024-02-01T00:00:00.000Z' }
      ]
      localStorage.setItem('wedding_registry', JSON.stringify(entries))
      const result = await weddingRegistryService.getBySlug('dian-reza')
      expect(result).toEqual(entries[1])
    })

    it('returns null when slug not found', async () => {
      const entries = [
        { id: '1', slug: 'budi-ani', label: 'Pernikahan Budi & Ani', createdAt: '2024-01-01T00:00:00.000Z' }
      ]
      localStorage.setItem('wedding_registry', JSON.stringify(entries))
      const result = await weddingRegistryService.getBySlug('not-exist')
      expect(result).toBeNull()
    })
  })

  describe('create', () => {
    it('creates a new wedding entry with correct fields', async () => {
      const result = await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })

      expect(result.id).toBeDefined()
      expect(result.id.length).toBeGreaterThan(0)
      expect(result.slug).toBe('budi-ani')
      expect(result.label).toBe('Pernikahan Budi & Ani')
      expect(result.createdAt).toBeDefined()
      // Verify it's a valid ISO date
      expect(new Date(result.createdAt).toISOString()).toBe(result.createdAt)
    })

    it('persists the entry to localStorage', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })

      const stored = JSON.parse(localStorage.getItem('wedding_registry'))
      expect(stored).toHaveLength(1)
      expect(stored[0].slug).toBe('budi-ani')
    })

    it('allows creating multiple weddings', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })
      await weddingRegistryService.create({ slug: 'dian-reza', label: 'Pernikahan Dian & Reza' })

      const result = await weddingRegistryService.getAll()
      expect(result).toHaveLength(2)
    })

    it('rejects duplicate slug with correct error message', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })

      await expect(
        weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Lain' })
      ).rejects.toThrow('Slug sudah digunakan oleh pernikahan lain')
    })

    it('rejects duplicate slug with ServiceError and DUPLICATE_SLUG code', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })

      try {
        await weddingRegistryService.create({ slug: 'budi-ani', label: 'Lain' })
      } catch (error) {
        expect(error).toBeInstanceOf(ServiceError)
        expect(error.code).toBe('DUPLICATE_SLUG')
        expect(error.retryable).toBe(false)
      }
    })

    it('rejects invalid slug format', async () => {
      await expect(
        weddingRegistryService.create({ slug: 'AB', label: 'Test' })
      ).rejects.toThrow()
    })

    it('rejects slug starting with hyphen', async () => {
      await expect(
        weddingRegistryService.create({ slug: '-invalid', label: 'Test' })
      ).rejects.toThrow()
    })

    it('rejects slug with consecutive hyphens', async () => {
      await expect(
        weddingRegistryService.create({ slug: 'budi--ani', label: 'Test' })
      ).rejects.toThrow()
    })

    it('forwards validation error from slugValidator', async () => {
      try {
        await weddingRegistryService.create({ slug: 'ab', label: 'Test' })
      } catch (error) {
        expect(error).toBeInstanceOf(ServiceError)
        expect(error.code).toBe('INVALID_SLUG')
        expect(error.message).toBe('Slug minimal 3 karakter')
      }
    })
  })

  describe('delete', () => {
    it('removes the entry from registry', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })
      await weddingRegistryService.delete('budi-ani')

      const result = await weddingRegistryService.getAll()
      expect(result).toHaveLength(0)
    })

    it('removes all namespaced localStorage keys', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })

      // Set up namespaced data
      localStorage.setItem('wedding_budi-ani_couple', JSON.stringify({ name: 'test' }))
      localStorage.setItem('wedding_budi-ani_events', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_guests', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_rsvp', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_wishes', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_media', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_payments', JSON.stringify([]))
      localStorage.setItem('wedding_budi-ani_settings', JSON.stringify({}))

      await weddingRegistryService.delete('budi-ani')

      expect(localStorage.getItem('wedding_budi-ani_couple')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_events')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_guests')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_rsvp')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_wishes')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_media')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_payments')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_settings')).toBeNull()
    })

    it('does not affect other wedding data', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Pernikahan Budi & Ani' })
      await weddingRegistryService.create({ slug: 'dian-reza', label: 'Pernikahan Dian & Reza' })

      localStorage.setItem('wedding_budi-ani_couple', JSON.stringify({ name: 'budi' }))
      localStorage.setItem('wedding_dian-reza_couple', JSON.stringify({ name: 'dian' }))

      await weddingRegistryService.delete('budi-ani')

      expect(localStorage.getItem('wedding_dian-reza_couple')).not.toBeNull()
      const remaining = await weddingRegistryService.getAll()
      expect(remaining).toHaveLength(1)
      expect(remaining[0].slug).toBe('dian-reza')
    })

    it('throws error when slug not found', async () => {
      await expect(
        weddingRegistryService.delete('not-exist')
      ).rejects.toThrow('Pernikahan tidak ditemukan')
    })

    it('throws ServiceError with NOT_FOUND code', async () => {
      try {
        await weddingRegistryService.delete('not-exist')
      } catch (error) {
        expect(error).toBeInstanceOf(ServiceError)
        expect(error.code).toBe('NOT_FOUND')
        expect(error.retryable).toBe(false)
      }
    })

    it('returns true on successful deletion', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Test' })
      const result = await weddingRegistryService.delete('budi-ani')
      expect(result).toBe(true)
    })
  })

  describe('exists', () => {
    it('returns false when registry is empty', () => {
      expect(weddingRegistryService.exists('budi-ani')).toBe(false)
    })

    it('returns true when slug exists in registry', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Test' })
      expect(weddingRegistryService.exists('budi-ani')).toBe(true)
    })

    it('returns false when slug does not exist', async () => {
      await weddingRegistryService.create({ slug: 'budi-ani', label: 'Test' })
      expect(weddingRegistryService.exists('dian-reza')).toBe(false)
    })

    it('is synchronous', () => {
      // exists() should return a boolean directly, not a Promise
      const result = weddingRegistryService.exists('test')
      expect(typeof result).toBe('boolean')
    })
  })
})
