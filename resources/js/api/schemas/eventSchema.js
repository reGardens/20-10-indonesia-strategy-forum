export const eventSchema = {
  required: ['name', 'date'],
  properties: {
    id: { type: 'string' },
    name: { type: 'string', minLength: 1 },
    date: { type: 'string' },
    startTime: { type: 'string' },
    endTime: { type: 'string' },
    venueName: { type: 'string' },
    address: { type: 'string' },
    latitude: { type: 'number', min: -90, max: 90 },
    longitude: { type: 'number', min: -180, max: 180 },
    createdAt: { type: 'string' }
  }
}
