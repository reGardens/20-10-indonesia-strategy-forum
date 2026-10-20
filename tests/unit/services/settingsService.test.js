import { describe, it, expect, beforeEach } from 'vitest'
import { settingsService } from '../../../src/api/services/settingsService.js'
import settingsData from '../../../src/api/data/settings.json'

const TEST_SLUG = 'test-wedding'

describe('settingsService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('get', () => {
    it('returns default settings when localStorage is empty', async () => {
      const result = await settingsService.get(TEST_SLUG)
      expect(result.templateId).toBe('batik-elegance')
      expect(result.moderationEnabled).toBe(true)
    })

    it('returns stored settings', async () => {
      const custom = { ...settingsData, templateId: 'wayang-romance' }
      localStorage.setItem('wedding_test-wedding_settings', JSON.stringify(custom))
      const result = await settingsService.get(TEST_SLUG)
      expect(result.templateId).toBe('wayang-romance')
    })

    it('falls back to default on JSON parse error', async () => {
      localStorage.setItem('wedding_test-wedding_settings', 'invalid')
      const result = await settingsService.get(TEST_SLUG)
      expect(result.templateId).toBe('batik-elegance')
    })
  })

  describe('save', () => {
    it('saves and returns merged settings', async () => {
      const result = await settingsService.save(TEST_SLUG, { templateId: 'songket-royal' })
      expect(result.templateId).toBe('songket-royal')
      expect(result.moderationEnabled).toBe(true) // preserved from default
      expect(result.updatedAt).toBeDefined()
    })

    it('persists settings to localStorage', async () => {
      await settingsService.save(TEST_SLUG, { galleryLayout: 'grid' })
      const stored = JSON.parse(localStorage.getItem('wedding_test-wedding_settings'))
      expect(stored.galleryLayout).toBe('grid')
    })
  })
})
