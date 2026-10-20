import { describe, it, expect, beforeEach } from 'vitest'
import { coupleService, ServiceError } from '../../../src/api/services/coupleService.js'
import coupleData from '../../../src/api/data/couple.json'

const TEST_SLUG = 'test-wedding'

describe('coupleService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('get', () => {
    it('returns default data when localStorage is empty', async () => {
      const result = await coupleService.get(TEST_SLUG)
      expect(result).toEqual(coupleData)
    })

    it('returns stored data from localStorage', async () => {
      const data = { id: '123', weddingSlug: 'test', groom: { fullName: 'Test' }, bride: { fullName: 'Test2' } }
      localStorage.setItem('wedding_test-wedding_couple', JSON.stringify(data))
      const result = await coupleService.get(TEST_SLUG)
      expect(result).toEqual(data)
    })

    it('falls back to default data on JSON parse error', async () => {
      localStorage.setItem('wedding_test-wedding_couple', 'invalid-json')
      const result = await coupleService.get(TEST_SLUG)
      expect(result).toEqual(coupleData)
    })
  })

  describe('save', () => {
    it('saves data to localStorage and returns it', async () => {
      const data = { id: '123', weddingSlug: 'budi-ani', groom: { fullName: 'Budi' }, bride: { fullName: 'Ani' } }
      const result = await coupleService.save(TEST_SLUG, data)
      expect(result).toEqual(data)
      expect(JSON.parse(localStorage.getItem('wedding_test-wedding_couple'))).toEqual(data)
    })

    it('performs JSON round-trip on save', async () => {
      const data = { id: '1', name: 'test' }
      const result = await coupleService.save(TEST_SLUG, data)
      expect(result).toEqual(data)
    })
  })

  describe('ServiceError', () => {
    it('creates error with correct properties', () => {
      const error = new ServiceError('test message', 'TEST_CODE', true)
      expect(error.message).toBe('test message')
      expect(error.code).toBe('TEST_CODE')
      expect(error.retryable).toBe(true)
      expect(error instanceof Error).toBe(true)
    })

    it('defaults retryable to false', () => {
      const error = new ServiceError('test', 'CODE')
      expect(error.retryable).toBe(false)
    })
  })
})
