import axios from 'axios'

export const eventService = {
  async getAll(slug) {
    const response = await axios.get(`/api/wedding/${slug}/events`)
    return response.data
  },

  async getById(slug, id) {
    const response = await axios.get(`/api/wedding/${slug}/events/${id}`)
    return response.data
  },

  async create(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/events`, data)
    return response.data
  },

  async update(slug, id, data) {
    const response = await axios.put(`/api/wedding/${slug}/events/${id}`, data)
    return response.data
  },

  async delete(slug, id) {
    const response = await axios.delete(`/api/wedding/${slug}/events/${id}`)
    return response.data.success
  }
}
