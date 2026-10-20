export const rsvpSchema = {
  required: ['guestId', 'guestName', 'status'],
  properties: {
    id: { type: 'string' },
    guestId: { type: 'string', minLength: 1 },
    guestName: { type: 'string', minLength: 1 },
    status: { type: 'string', enum: ['Hadir', 'Tidak Hadir', 'Mungkin'] },
    numberOfGuests: { type: 'number', min: 1 },
    submittedAt: { type: 'string' }
  }
}
