export const wishSchema = {
  required: ['guestName', 'message'],
  properties: {
    id: { type: 'string' },
    guestId: { type: 'string' },
    guestName: { type: 'string', minLength: 1 },
    message: { type: 'string', minLength: 1 },
    status: { type: 'string', enum: ['approved', 'hidden', 'pending'] },
    createdAt: { type: 'string' }
  }
}
