import { describe, it, expect } from 'vitest'
import { useWhatsAppLink } from '../../../src/composables/useWhatsAppLink.js'

const DEFAULT_OPTIONS = {
  domain: 'https://example.com',
  weddingSlug: 'budi-ani'
}

describe('useWhatsAppLink', () => {
  describe('generateInvitationUrl', () => {
    it('generates a URL with the correct format', () => {
      const { generateInvitationUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateInvitationUrl('Ahmad Fauzi')
      expect(url).toBe('https://example.com/wedding/budi-ani?to=Ahmad+Fauzi')
    })

    it('encodes special characters in guest names', () => {
      const { generateInvitationUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateInvitationUrl('Bpk. Ahmad & Ibu Siti')
      expect(url).toBe('https://example.com/wedding/budi-ani?to=Bpk.+Ahmad+%26+Ibu+Siti')
    })

    it('handles empty guest name', () => {
      const { generateInvitationUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateInvitationUrl('')
      expect(url).toBe('https://example.com/wedding/budi-ani?to=')
    })

    it('strips trailing slashes from domain', () => {
      const { generateInvitationUrl } = useWhatsAppLink({
        domain: 'https://example.com/',
        weddingSlug: 'budi-ani'
      })
      const url = generateInvitationUrl('Ahmad')
      expect(url).toBe('https://example.com/wedding/budi-ani?to=Ahmad')
    })

    it('handles Unicode characters in names', () => {
      const { generateInvitationUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateInvitationUrl('Müller Straße')
      expect(url).toContain('?to=')
      expect(url).toContain('M%C3%BCller')
    })
  })

  describe('generateWhatsAppUrl', () => {
    it('generates a WhatsApp URL with the correct format', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateWhatsAppUrl('628123456789', 'Ahmad Fauzi')
      expect(url).toMatch(/^https:\/\/wa\.me\/628123456789\?text=/)
    })

    it('includes the guest name in the message', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateWhatsAppUrl('628123456789', 'Ahmad Fauzi')
      const decoded = decodeURIComponent(url.split('?text=')[1])
      expect(decoded).toContain('Ahmad Fauzi')
    })

    it('includes the invitation URL in the message', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateWhatsAppUrl('628123456789', 'Ahmad Fauzi')
      const decoded = decodeURIComponent(url.split('?text=')[1])
      expect(decoded).toContain('https://example.com/wedding/budi-ani?to=Ahmad+Fauzi')
    })

    it('strips non-numeric characters from phone number', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateWhatsAppUrl('+62-812-345-6789', 'Ahmad')
      expect(url).toMatch(/^https:\/\/wa\.me\/628123456789\?text=/)
    })

    it('uses custom message template', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink({
        ...DEFAULT_OPTIONS,
        messageTemplate: 'Hai {name}, buka: {url}'
      })
      const url = generateWhatsAppUrl('628123456789', 'Budi')
      const decoded = decodeURIComponent(url.split('?text=')[1])
      expect(decoded).toBe('Hai Budi, buka: https://example.com/wedding/budi-ani?to=Budi')
    })
  })

  describe('generateBulkLinks', () => {
    it('generates links for all guests', () => {
      const { generateBulkLinks } = useWhatsAppLink(DEFAULT_OPTIONS)
      const guests = [
        { id: '1', name: 'Ahmad Fauzi', phone: '628123456789' },
        { id: '2', name: 'Siti Rahayu', phone: '628987654321' }
      ]
      const links = generateBulkLinks(guests)
      expect(links).toHaveLength(2)
      expect(links[0].guestId).toBe('1')
      expect(links[0].invitationUrl).toContain('Ahmad+Fauzi')
      expect(links[0].whatsappUrl).toContain('wa.me/628123456789')
      expect(links[1].guestId).toBe('2')
      expect(links[1].invitationUrl).toContain('Siti+Rahayu')
      expect(links[1].whatsappUrl).toContain('wa.me/628987654321')
    })

    it('returns empty array for non-array input', () => {
      const { generateBulkLinks } = useWhatsAppLink(DEFAULT_OPTIONS)
      expect(generateBulkLinks(null)).toEqual([])
      expect(generateBulkLinks(undefined)).toEqual([])
      expect(generateBulkLinks('not-array')).toEqual([])
    })

    it('returns empty array for empty guest list', () => {
      const { generateBulkLinks } = useWhatsAppLink(DEFAULT_OPTIONS)
      expect(generateBulkLinks([])).toEqual([])
    })

    it('each link has guestId, invitationUrl, and whatsappUrl', () => {
      const { generateBulkLinks } = useWhatsAppLink(DEFAULT_OPTIONS)
      const guests = [{ id: 'abc', name: 'Test', phone: '628111222333' }]
      const [link] = generateBulkLinks(guests)
      expect(link).toHaveProperty('guestId', 'abc')
      expect(link).toHaveProperty('invitationUrl')
      expect(link).toHaveProperty('whatsappUrl')
    })
  })

  describe('default options', () => {
    it('works with default empty options', () => {
      const { generateInvitationUrl } = useWhatsAppLink()
      const url = generateInvitationUrl('Test')
      expect(url).toBe('/wedding/?to=Test')
    })

    it('uses default message template', () => {
      const { generateWhatsAppUrl } = useWhatsAppLink(DEFAULT_OPTIONS)
      const url = generateWhatsAppUrl('628123456789', 'Ahmad')
      const decoded = decodeURIComponent(url.split('?text=')[1])
      expect(decoded).toContain('Assalamualaikum')
      expect(decoded).toContain('mengundang Ahmad')
    })
  })
})
