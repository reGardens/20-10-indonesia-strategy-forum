import axios from 'axios'

export const paymentService = {
  async get(slug) {
    const response = await axios.get(`/api/wedding/${slug}/payments`)
    return response.data
  },

  async save(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/payments`, data)
    return response.data
  }
}
