export const mediaSchema = {
  required: ['fileName', 'type'],
  properties: {
    id: { type: 'string' },
    fileName: { type: 'string', minLength: 1 },
    originalName: { type: 'string' },
    type: { type: 'string', enum: ['image', 'video'] },
    originalSize: { type: 'number', min: 0 },
    compressedSize: { type: 'number', min: 0 },
    url: { type: 'string' },
    thumbnailUrl: { type: 'string' },
    sortOrder: { type: 'number', min: 0 },
    createdAt: { type: 'string' }
  }
}
