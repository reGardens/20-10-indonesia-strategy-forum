import axios from 'axios'

export const coupleService = {
  async get(slug) {
    const response = await axios.get(`/api/wedding/${slug}/couple`)
    return response.data
  },

  async save(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/couple`, data)
    return response.data
  }
}
