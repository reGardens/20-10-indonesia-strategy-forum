import axios from 'axios'

export const wishService = {
  async getAll(slug) {
    const response = await axios.get(`/api/wedding/${slug}/wishes`)
    return response.data
  },

  async getById(slug, id) {
    const response = await axios.get(`/api/wedding/${slug}/wishes/${id}`)
    return response.data
  },

  async create(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/wishes`, data)
    return response.data
  },

  async delete(slug, id) {
    const response = await axios.delete(`/api/wedding/${slug}/wishes/${id}`)
    return response.data.success
  },

  async getByStatus(slug, status) {
    const response = await axios.get(`/api/wedding/${slug}/wishes`, {
      params: { status }
    })
    return response.data
  },

  async updateStatus(slug, id, status) {
    const response = await axios.put(`/api/wedding/${slug}/wishes/${id}/status`, { status })
    return response.data
  }
}
