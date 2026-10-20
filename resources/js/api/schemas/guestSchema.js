export const guestSchema = {
  required: ['name'],
  properties: {
    id: { type: 'string' },
    name: { type: 'string', minLength: 1 },
    phone: { type: 'string', pattern: /^[0-9+\-\s]+$/ },
    slug: { type: 'string' },
    maxPax: { type: 'number', min: 1, max: 10 },
    qrCode: { type: 'string' },
    invitationUrl: { type: 'string' },
    createdAt: { type: 'string' }
  }
}
