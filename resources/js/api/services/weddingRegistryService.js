import axios from 'axios'

export const weddingRegistryService = {
  async getAll() {
    const response = await axios.get('/api/wedding/registry')
    return response.data
  },

  async getBySlug(slug) {
    const response = await axios.get(`/api/wedding/registry/${slug}`)
    return response.data
  },

  async create({ slug, label }) {
    const response = await axios.post('/api/wedding/registry', { slug, label })
    return response.data
  },

  async delete(slug) {
    const response = await axios.delete(`/api/wedding/registry/${slug}`)
    return response.data.success
  },

  async exists(slug) {
    const response = await axios.get(`/api/wedding/registry/exists/${slug}`)
    return response.data.exists
  }
}
