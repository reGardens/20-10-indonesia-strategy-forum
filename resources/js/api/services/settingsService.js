import axios from 'axios'

export const settingsService = {
  async get(slug) {
    const response = await axios.get(`/api/wedding/${slug}/settings`)
    return response.data
  },

  async save(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/settings`, data)
    return response.data
  }
}
