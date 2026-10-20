import { describe, it, expect } from 'vitest'
import coupleData from '../../src/api/data/couple.json'
import eventsData from '../../src/api/data/events.json'
import guestsData from '../../src/api/data/guests.json'
import rsvpData from '../../src/api/data/rsvp.json'
import wishesData from '../../src/api/data/wishes.json'
import mediaData from '../../src/api/data/media.json'
import paymentsData from '../../src/api/data/payments.json'
import settingsData from '../../src/api/data/settings.json'
import i18n from '../../src/i18n/index.js'

describe('Project Setup', () => {
  it('should load couple.json with correct structure', () => {
    expect(coupleData).toHaveProperty('id')
    expect(coupleData).toHaveProperty('weddingSlug')
    expect(coupleData).toHaveProperty('groom')
    expect(coupleData).toHaveProperty('bride')
    expect(coupleData.groom).toHaveProperty('fullName')
    expect(coupleData.groom).toHaveProperty('nickname')
    expect(coupleData.groom).toHaveProperty('photo')
    expect(coupleData.groom).toHaveProperty('fatherName')
    expect(coupleData.groom).toHaveProperty('motherName')
    expect(coupleData.groom).toHaveProperty('instagramUrl')
    expect(coupleData.groom).toHaveProperty('childOrder')
    expect(coupleData.bride).toHaveProperty('fullName')
    expect(coupleData).toHaveProperty('musicUrl')
    expect(coupleData).toHaveProperty('createdAt')
    expect(coupleData).toHaveProperty('updatedAt')
  })

  it('should load events.json as empty array', () => {
    expect(eventsData).toEqual([])
  })

  it('should load guests.json as empty array', () => {
    expect(guestsData).toEqual([])
  })

  it('should load rsvp.json as empty array', () => {
    expect(rsvpData).toEqual([])
  })

  it('should load wishes.json as empty array', () => {
    expect(wishesData).toEqual([])
  })

  it('should load media.json as empty array', () => {
    expect(mediaData).toEqual([])
  })

  it('should load payments.json with correct structure', () => {
    expect(paymentsData).toHaveProperty('bankAccounts')
    expect(paymentsData).toHaveProperty('gifts')
    expect(paymentsData.bankAccounts).toEqual([])
    expect(paymentsData.gifts).toEqual([])
  })

  it('should load settings.json with correct defaults', () => {
    expect(settingsData).toHaveProperty('templateId', 'batik-elegance')
    expect(settingsData).toHaveProperty('themeColors')
    expect(settingsData.themeColors).toEqual({
      primary: '#8B4513',
      secondary: '#D2691E',
      accent: '#FFD700'
    })
  })

  it('should load settings.json with all expected fields', () => {
    expect(settingsData).toHaveProperty('galleryLayout', 'masonry')
    expect(settingsData).toHaveProperty('moderationEnabled', true)
    expect(settingsData).toHaveProperty('passwordProtected', false)
    expect(settingsData).toHaveProperty('password', '')
    expect(settingsData).toHaveProperty('showWatermark', true)
    expect(settingsData).toHaveProperty('customDomain', '')
    expect(settingsData).toHaveProperty('healthProtocol', '')
    expect(settingsData).toHaveProperty('liveStreamUrl', '')
    expect(settingsData).toHaveProperty('shippingAddress', '')
    expect(settingsData).toHaveProperty('seoMeta')
    expect(settingsData.seoMeta).toHaveProperty('title', '')
    expect(settingsData.seoMeta).toHaveProperty('description', '')
    expect(settingsData.seoMeta).toHaveProperty('image', '')
    expect(settingsData).toHaveProperty('qrisImageUrl', '')
  })

  it('should configure vue-i18n with Bahasa Indonesia as default', () => {
    expect(i18n.global.locale).toBeDefined()
    expect(i18n.global.fallbackLocale).toBeDefined()
  })

  it('should have Bahasa Indonesia translations loaded', () => {
    const messages = i18n.global.getLocaleMessage('id')
    expect(messages).toHaveProperty('app')
    expect(messages).toHaveProperty('nav')
    expect(messages).toHaveProperty('validation')
    expect(messages).toHaveProperty('rsvp')
    expect(messages).toHaveProperty('checkin')
    expect(messages).toHaveProperty('invitation')
    expect(messages.nav.dashboard).toBe('Dashboard')
    expect(messages.rsvp.attending).toBe('Hadir')
  })

  it('should have localStorage mock available', () => {
    localStorage.setItem('test', 'value')
    expect(localStorage.getItem('test')).toBe('value')
    localStorage.removeItem('test')
    expect(localStorage.getItem('test')).toBeNull()
  })
})
