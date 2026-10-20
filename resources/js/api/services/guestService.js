import axios from 'axios'

export const guestService = {
  async getAll(slug) {
    const response = await axios.get(`/api/wedding/${slug}/guests`)
    return response.data
  },

  async getById(slug, id) {
    const response = await axios.get(`/api/wedding/${slug}/guests/${id}`)
    return response.data
  },

  async create(slug, data) {
    const response = await axios.post(`/api/wedding/${slug}/guests`, data)
    return response.data
  },

  async update(slug, id, data) {
    const response = await axios.put(`/api/wedding/${slug}/guests/${id}`, data)
    return response.data
  },

  async delete(slug, id) {
    const response = await axios.delete(`/api/wedding/${slug}/guests/${id}`)
    return response.data.success
  },

  async search(slug, query) {
    const response = await axios.get(`/api/wedding/${slug}/guests`, {
      params: { q: query }
    })
    return response.data
  },

  async checkIn(slug, id) {
    const response = await axios.post(`/api/wedding/${slug}/guests/${id}/checkin`)
    return response.data
  },

  async importFromExcel(slug, file) {
    try {
      const XLSX = await import('xlsx')
      const data = await file.arrayBuffer()
      const workbook = XLSX.read(data, { type: 'array' })
      const sheetName = workbook.SheetNames[0]
      const worksheet = workbook.Sheets[sheetName]
      const rows = XLSX.utils.sheet_to_json(worksheet)

      if (rows.length === 0) {
        return []
      }

      const imported = []
      for (const row of rows) {
        const name = row['Nama'] || row['name'] || row['Name'] || ''
        if (!name) continue

        const phone = String(row['No. Telepon'] || row['phone'] || row['Phone'] || row['Telepon'] || '')
        const maxPax = Number(row['Jumlah Tamu'] || row['maxPax'] || row['Max Pax'] || 1)
        const guestSlug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')

        imported.push({
          name,
          phone,
          slug: guestSlug,
          maxPax: isNaN(maxPax) ? 1 : maxPax,
          rsvpStatus: 'pending'
        })
      }

      const response = await axios.post(`/api/wedding/${slug}/guests/batch`, { guests: imported })
      return response.data
    } catch (error) {
      throw new Error('Gagal mengimpor data dari Excel. Pastikan format kolom sesuai: Nama, No. Telepon, Jumlah Tamu')
    }
  },

  async exportToExcel(slug) {
    try {
      const XLSX = await import('xlsx')
      const guests = await this.getAll(slug)

      const exportData = guests.map(g => ({
        'Nama': g.name || '',
        'No. Telepon': g.phone || '',
        'Jumlah Tamu': g.maxPax || 1
      }))

      const worksheet = XLSX.utils.json_to_sheet(exportData)
      const workbook = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Daftar Tamu')

      return XLSX.write(workbook, { type: 'array', bookType: 'xlsx' })
    } catch (error) {
      throw new Error('Gagal mengekspor data tamu ke Excel')
    }
  }
}
