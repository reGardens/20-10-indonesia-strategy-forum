import { describe, it, expect, beforeEach, vi } from 'vitest'
import { hasLegacyData, migrateLegacyData } from '../../../src/utils/legacyMigration.js'

describe('hasLegacyData', () => {
  it('returns false when no legacy keys exist', () => {
    expect(hasLegacyData()).toBe(false)
  })

  it('returns true when wedding_couple exists', () => {
    localStorage.setItem('wedding_couple', JSON.stringify({ groomName: 'Budi' }))
    expect(hasLegacyData()).toBe(true)
  })

  it('returns true when wedding_events exists', () => {
    localStorage.setItem('wedding_events', JSON.stringify([{ id: '1', title: 'Akad' }]))
    expect(hasLegacyData()).toBe(true)
  })

  it('returns true when wedding_guests exists', () => {
    localStorage.setItem('wedding_guests', JSON.stringify([]))
    expect(hasLegacyData()).toBe(true)
  })

  it('returns true when multiple legacy keys exist', () => {
    localStorage.setItem('wedding_couple', JSON.stringify({}))
    localStorage.setItem('wedding_events', JSON.stringify([]))
    localStorage.setItem('wedding_settings', JSON.stringify({}))
    expect(hasLegacyData()).toBe(true)
  })

  it('returns false when only namespaced keys exist', () => {
    localStorage.setItem('wedding_budi-ani_couple', JSON.stringify({}))
    localStorage.setItem('wedding_budi-ani_events', JSON.stringify([]))
    expect(hasLegacyData()).toBe(false)
  })

  it('returns false when only wedding_registry exists', () => {
    localStorage.setItem('wedding_registry', JSON.stringify([]))
    expect(hasLegacyData()).toBe(false)
  })
})

describe('migrateLegacyData', () => {
  describe('slug validation', () => {
    it('rejects invalid slug format', async () => {
      localStorage.setItem('wedding_couple', JSON.stringify({ groomName: 'Budi' }))

      const result = await migrateLegacyData('AB')
      expect(result.success).toBe(false)
      expect(result.migratedEntities).toEqual([])
      expect(result.error).toBeDefined()
    })

    it('rejects slug with consecutive hyphens', async () => {
      localStorage.setItem('wedding_couple', JSON.stringify({}))

      const result = await migrateLegacyData('budi--ani')
      expect(result.success).toBe(false)
      expect(result.migratedEntities).toEqual([])
      expect(result.error).toBe('Slug tidak boleh mengandung tanda hubung berturut-turut')
    })

    it('rejects slug starting with hyphen', async () => {
      localStorage.setItem('wedding_couple', JSON.stringify({}))

      const result = await migrateLegacyData('-budi')
      expect(result.success).toBe(false)
      expect(result.error).toBe('Slug tidak boleh diawali atau diakhiri dengan tanda hubung')
    })
  })

  describe('successful migration', () => {
    it('migrates a single entity and removes old key', async () => {
      const coupleData = { groomName: 'Budi', brideName: 'Ani' }
      localStorage.setItem('wedding_couple', JSON.stringify(coupleData))

      const result = await migrateLegacyData('budi-ani')

      expect(result.success).toBe(true)
      expect(result.migratedEntities).toEqual(['couple'])

      // New key should have the data
      const newData = JSON.parse(localStorage.getItem('wedding_budi-ani_couple'))
      expect(newData).toEqual(coupleData)

      // Old key should be removed
      expect(localStorage.getItem('wedding_couple')).toBeNull()
    })

    it('migrates multiple entities', async () => {
      const coupleData = { groomName: 'Budi', brideName: 'Ani' }
      const eventsData = [{ id: '1', title: 'Akad Nikah' }]
      const guestsData = [{ id: '1', name: 'Tamu 1' }]

      localStorage.setItem('wedding_couple', JSON.stringify(coupleData))
      localStorage.setItem('wedding_events', JSON.stringify(eventsData))
      localStorage.setItem('wedding_guests', JSON.stringify(guestsData))

      const result = await migrateLegacyData('budi-ani')

      expect(result.success).toBe(true)
      expect(result.migratedEntities).toContain('couple')
      expect(result.migratedEntities).toContain('events')
      expect(result.migratedEntities).toContain('guests')
      expect(result.migratedEntities).toHaveLength(3)

      // Verify new keys
      expect(JSON.parse(localStorage.getItem('wedding_budi-ani_couple'))).toEqual(coupleData)
      expect(JSON.parse(localStorage.getItem('wedding_budi-ani_events'))).toEqual(eventsData)
      expect(JSON.parse(localStorage.getItem('wedding_budi-ani_guests'))).toEqual(guestsData)

      // Verify old keys removed
      expect(localStorage.getItem('wedding_couple')).toBeNull()
      expect(localStorage.getItem('wedding_events')).toBeNull()
      expect(localStorage.getItem('wedding_guests')).toBeNull()
    })

    it('migrates all 8 entity types', async () => {
      const entities = {
        couple: { groomName: 'Budi' },
        events: [{ id: '1' }],
        guests: [{ id: '1' }],
        rsvp: [{ id: '1' }],
        wishes: [{ id: '1' }],
        media: [{ id: '1' }],
        payments: { accounts: [] },
        settings: { template: 'default' }
      }

      for (const [entity, data] of Object.entries(entities)) {
        localStorage.setItem(`wedding_${entity}`, JSON.stringify(data))
      }

      const result = await migrateLegacyData('budi-ani')

      expect(result.success).toBe(true)
      expect(result.migratedEntities).toHaveLength(8)
      expect(result.migratedEntities).toEqual([
        'couple', 'events', 'guests', 'rsvp', 'wishes', 'media', 'payments', 'settings'
      ])

      // Verify all new keys exist with correct data
      for (const [entity, data] of Object.entries(entities)) {
        expect(JSON.parse(localStorage.getItem(`wedding_budi-ani_${entity}`))).toEqual(data)
      }

      // Verify all old keys removed
      for (const entity of Object.keys(entities)) {
        expect(localStorage.getItem(`wedding_${entity}`)).toBeNull()
      }
    })

    it('adds entry to wedding registry', async () => {
      localStorage.setItem('wedding_couple', JSON.stringify({ groomName: 'Budi' }))

      await migrateLegacyData('budi-ani')

      const registry = JSON.parse(localStorage.getItem('wedding_registry'))
      expect(registry).toHaveLength(1)
      expect(registry[0].slug).toBe('budi-ani')
      expect(registry[0].label).toBe('budi-ani')
      expect(registry[0].id).toBeDefined()
      expect(registry[0].createdAt).toBeDefined()
    })

    it('only migrates keys that exist', async () => {
      localStorage.setItem('wedding_couple', JSON.stringify({ groomName: 'Budi' }))
      localStorage.setItem('wedding_settings', JSON.stringify({ theme: 'dark' }))
      // Other legacy keys don't exist

      const result = await migrateLegacyData('budi-ani')

      expect(result.success).toBe(true)
      expect(result.migratedEntities).toEqual(['couple', 'settings'])

      // Only migrated keys should exist in new namespace
      expect(localStorage.getItem('wedding_budi-ani_couple')).not.toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_settings')).not.toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_events')).toBeNull()
    })

    it('preserves data integrity during migration', async () => {
      const complexData = {
        groomName: 'Budi Santoso',
        brideName: 'Ani Wijaya',
        photos: ['photo1.jpg', 'photo2.jpg'],
        nested: { deep: { value: 42 } }
      }
      localStorage.setItem('wedding_couple', JSON.stringify(complexData))

      await migrateLegacyData('budi-ani')

      const migratedData = JSON.parse(localStorage.getItem('wedding_budi-ani_couple'))
      expect(migratedData).toEqual(complexData)
    })
  })

  describe('rollback on failure', () => {
    it('rolls back new keys when registry create fails (duplicate slug)', async () => {
      // Pre-create a registry entry with the same slug
      localStorage.setItem('wedding_registry', JSON.stringify([
        { id: 'existing-id', slug: 'budi-ani', label: 'Existing', createdAt: '2024-01-01T00:00:00Z' }
      ]))

      const coupleData = { groomName: 'Budi' }
      const eventsData = [{ id: '1' }]
      localStorage.setItem('wedding_couple', JSON.stringify(coupleData))
      localStorage.setItem('wedding_events', JSON.stringify(eventsData))

      const result = await migrateLegacyData('budi-ani')

      expect(result.success).toBe(false)
      expect(result.migratedEntities).toEqual([])
      expect(result.error).toBe('Slug sudah digunakan oleh pernikahan lain')

      // New keys should be rolled back (removed)
      expect(localStorage.getItem('wedding_budi-ani_couple')).toBeNull()
      expect(localStorage.getItem('wedding_budi-ani_events')).toBeNull()

      // Old keys should still be intact
      expect(JSON.parse(localStorage.getItem('wedding_couple'))).toEqual(coupleData)
      expect(JSON.parse(localStorage.getItem('wedding_events'))).toEqual(eventsData)
    })

    it('keeps old keys intact when migration fails', async () => {
      // Pre-create a registry entry to cause duplicate slug error
      localStorage.setItem('wedding_registry', JSON.stringify([
        { id: 'existing-id', slug: 'test-slug', label: 'Existing', createdAt: '2024-01-01T00:00:00Z' }
      ]))

      const settingsData = { template: 'batik-elegance' }
      localStorage.setItem('wedding_settings', JSON.stringify(settingsData))

      const result = await migrateLegacyData('test-slug')

      expect(result.success).toBe(false)

      // Old key should still exist
      expect(JSON.parse(localStorage.getItem('wedding_settings'))).toEqual(settingsData)
    })
  })
})
