export const settingsSchema = {
  required: [],
  properties: {
    id: { type: 'string' },
    templateId: { type: 'string' },
    themeColors: {
      type: 'object',
      properties: {
        primary: { type: 'string' },
        secondary: { type: 'string' },
        accent: { type: 'string' }
      }
    },
    galleryLayout: { type: 'string', enum: ['masonry', 'slider', 'grid'] },
    moderationEnabled: { type: 'boolean' },
    passwordProtected: { type: 'boolean' },
    password: { type: 'string' },
    showWatermark: { type: 'boolean' },
    customDomain: { type: 'string' },
    healthProtocol: { type: 'string' },
    liveStreamUrl: { type: 'string' },
    shippingAddress: { type: 'string' },
    seoMeta: {
      type: 'object',
      properties: {
        title: { type: 'string' },
        description: { type: 'string' },
        image: { type: 'string' }
      }
    },
    qrisImageUrl: { type: 'string' },
    createdAt: { type: 'string' },
    updatedAt: { type: 'string' }
  }
}
