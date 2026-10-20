const personSchema = {
  type: 'object',
  properties: {
    fullName: { type: 'string', minLength: 1 },
    nickname: { type: 'string' },
    photo: { type: 'string' },
    fatherName: { type: 'string' },
    motherName: { type: 'string' },
    instagramUrl: { type: 'string' },
    childOrder: { type: 'string' }
  }
}

export const coupleSchema = {
  required: ['groom', 'bride'],
  properties: {
    id: { type: 'string' },
    weddingSlug: { type: 'string' },
    groom: personSchema,
    bride: personSchema,
    musicUrl: { type: 'string' },
    createdAt: { type: 'string' },
    updatedAt: { type: 'string' }
  }
}
