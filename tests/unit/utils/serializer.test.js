import { describe, it, expect } from 'vitest'
import { serialize, deserialize, roundTrip } from '../../../src/utils/serializer.js'

describe('serialize', () => {
  it('serializes a simple object to JSON string', () => {
    const obj = { name: 'Budi', age: 30 }
    expect(serialize(obj)).toBe('{"name":"Budi","age":30}')
  })

  it('serializes nested objects', () => {
    const obj = { groom: { fullName: 'Budi Santoso' }, bride: { fullName: 'Ani Rahayu' } }
    const result = serialize(obj)
    expect(typeof result).toBe('string')
    expect(JSON.parse(result)).toEqual(obj)
  })

  it('serializes arrays', () => {
    const arr = [{ id: '1' }, { id: '2' }]
    expect(JSON.parse(serialize(arr))).toEqual(arr)
  })

  it('serializes null values in objects', () => {
    const obj = { name: 'Budi', phone: null }
    expect(JSON.parse(serialize(obj))).toEqual(obj)
  })
})

describe('deserialize', () => {
  it('deserializes a JSON string to an object', () => {
    const json = '{"name":"Budi","age":30}'
    expect(deserialize(json)).toEqual({ name: 'Budi', age: 30 })
  })

  it('deserializes arrays', () => {
    const json = '[{"id":"1"},{"id":"2"}]'
    expect(deserialize(json)).toEqual([{ id: '1' }, { id: '2' }])
  })

  it('throws on invalid JSON', () => {
    expect(() => deserialize('not-json')).toThrow()
  })
})

describe('roundTrip', () => {
  it('produces a deeply equal object for a flat entity', () => {
    const entity = { id: 'uuid-1', name: 'Ahmad Fauzi', phone: '628123456789' }
    expect(roundTrip(entity)).toEqual(entity)
  })

  it('produces a deeply equal object for a nested entity', () => {
    const couple = {
      id: 'c1',
      groom: { fullName: 'Budi Santoso', nickname: 'Budi', fatherName: 'H. Santoso' },
      bride: { fullName: 'Ani Rahayu', nickname: 'Ani', fatherName: 'H. Rahayu' }
    }
    expect(roundTrip(couple)).toEqual(couple)
  })

  it('produces a new object reference (deep copy)', () => {
    const entity = { id: '1', data: { nested: true } }
    const result = roundTrip(entity)
    expect(result).toEqual(entity)
    expect(result).not.toBe(entity)
    expect(result.data).not.toBe(entity.data)
  })

  it('handles entities with arrays', () => {
    const settings = {
      templateId: 'batik-elegance',
      themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' }
    }
    expect(roundTrip(settings)).toEqual(settings)
  })

  it('handles entities with Unicode strings', () => {
    const guest = { name: 'Bpk. Müller-Schröder', message: 'Selamat menempuh hidup baru! 🎉' }
    expect(roundTrip(guest)).toEqual(guest)
  })
})
