import axios from 'axios'

export const mediaService = {
  async getAll(slug) {
    const response = await axios.get(`/api/wedding/${slug}/media`)
    return response.data
  },

  async upload(slug, file) {
    const formData = new FormData()
    formData.append('file', file)

    const response = await axios.post(`/api/wedding/${slug}/media`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  async delete(slug, id) {
    const response = await axios.delete(`/api/wedding/${slug}/media/${id}`)
    return response.data.success
  }
}
