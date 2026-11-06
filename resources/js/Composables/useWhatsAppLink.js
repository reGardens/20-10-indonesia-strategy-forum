import { encodeGuestName } from '../Utils/urlEncoder.js'

const DEFAULT_MESSAGE_TEMPLATE =
  'Assalamualaikum, kami mengundang {name} untuk hadir di acara pernikahan kami. Silakan buka undangan di: {url}'

/**
 * Composable for generating WhatsApp invitation links.
 * Produces personalized invitation URLs and WhatsApp deep links for each guest.
 *
 * @param {object} options
 * @param {string} options.domain - The base domain (e.g. "https://example.com")
 * @param {string} options.weddingSlug - The wedding slug (e.g. "budi-ani")
 * @param {string} [options.messageTemplate] - Message template with {name} and {url} placeholders
 * @returns {{ generateInvitationUrl: (guestName: string) => string, generateWhatsAppUrl: (phoneNumber: string, guestName: string) => string, generateBulkLinks: (guests: Array) => Array }}
 */
export function useWhatsAppLink(options = {}) {
  const { domain = '', weddingSlug = '', messageTemplate = DEFAULT_MESSAGE_TEMPLATE } = options

  /**
   * Generate a personalized invitation URL for a guest.
   *
   * @param {string} guestName - The guest's name
   * @returns {string} The invitation URL
   */
  function generateInvitationUrl(guestName) {
    const encodedName = encodeGuestName(guestName)
    const base = domain.replace(/\/+$/, '')
    return `${base}/wedding/${weddingSlug}?to=${encodedName}`
  }

  /**
   * Generate a WhatsApp deep link for sending an invitation to a guest.
   *
   * @param {string} phoneNumber - The guest's phone number (e.g. "628123456789")
   * @param {string} guestName - The guest's name
   * @returns {string} The WhatsApp URL (https://wa.me/...)
   */
  function generateWhatsAppUrl(phoneNumber, guestName) {
    const invitationUrl = generateInvitationUrl(guestName)
    const message = messageTemplate
      .replace(/\{name\}/g, guestName)
      .replace(/\{url\}/g, invitationUrl)
    const cleanPhone = phoneNumber.replace(/[^0-9]/g, '')
    return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`
  }

  /**
   * Generate invitation and WhatsApp links for a list of guests.
   *
   * @param {Array<{ id: string, name: string, phone: string }>} guests
   * @returns {Array<{ guestId: string, invitationUrl: string, whatsappUrl: string }>}
   */
  function generateBulkLinks(guests) {
    if (!Array.isArray(guests)) {
      return []
    }
    return guests.map((guest) => ({
      guestId: guest.id,
      invitationUrl: generateInvitationUrl(guest.name),
      whatsappUrl: generateWhatsAppUrl(guest.phone, guest.name)
    }))
  }

  return {
    generateInvitationUrl,
    generateWhatsAppUrl,
    generateBulkLinks
  }
}
