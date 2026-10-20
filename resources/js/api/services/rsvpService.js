import axios from 'axios'

export const rsvpService = {
  async getAll(slug) {
    const response = await axios.get(`/api/wedding/${slug}/rsvp`)
    return response.data
  },

  async create(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/rsvp`, data)
    return response.data
  },

  async updateStatus(slug, id, status) {
    const response = await axios.put(`/api/wedding/${slug}/rsvp/${id}/status`, { status })
    return response.data
  }
}
