import http from '@/api/http'

/**
 * ISF CMS JSON API (/api/cms/*). Session auth + per-module ACL on the server.
 * Errors are axios errors: 422 → error.response.data.errors (field → messages).
 */

/**
 * CRUD client for one CMS module.
 * @param {string} path URL segment under /api/cms (e.g. "artikel")
 */
export function createCmsResource(path) {
  const base = `/cms/${path}`
  return {
    /** Paginated list: { data, current_page, last_page, total, ... } */
    list: (params = {}) => http.get(base, { params }).then((r) => r.data),
    get: (id) => http.get(`${base}/${id}`).then((r) => r.data.data),
    create: (data) => http.post(base, data).then((r) => r.data.data),
    update: (id, data) => http.put(`${base}/${id}`, data).then((r) => r.data.data),
    remove: (id) => http.delete(`${base}/${id}`)
  }
}

export const cmsResources = {
  banner: createCmsResource('banner'),
  artikel: {
    ...createCmsResource('artikel'),
    setHero: (id) => http.post(`/cms/artikel/${id}/hero`).then((r) => r.data)
  },
  infografik: createCmsResource('infografik'),
  tokoh: createCmsResource('tokoh'),
  isu: createCmsResource('isu'),
  dataGrafik: createCmsResource('data-grafik'),
  alert: createCmsResource('alert'),
  emailBriefing: createCmsResource('email-briefing'),
  runningText: createCmsResource('running-text'),
  mediaSorotan: createCmsResource('media-sorotan'),
  kategori: createCmsResource('kategori')
}

/** Pengaturan Halaman (singleton): { beranda, anggota } */
export const settings = {
  get: () => http.get('/cms/pengaturan-halaman').then((r) => r.data.data),
  put: (data) => http.put('/cms/pengaturan-halaman', data).then((r) => r.data.data),
  update: (data) => http.put('/cms/pengaturan-halaman', data).then((r) => r.data.data)
}

/** Running Text (Teks Sorotan) standalone service */
export const runningTextService = {
  ...cmsResources.runningText,
  saveAll: (items) => http.put('/cms/running-text', { items }).then((r) => r.data.data)
}

/** Media Sorotan standalone service */
export const mediaSorotanService = {
  ...cmsResources.mediaSorotan,
  saveAll: (items) => http.put('/cms/media-sorotan', { items }).then((r) => r.data.data)
}

/** Data Grafik options for the isu "Visual Kunci" select: [{ id, title, status }] */
export function chartOptions() {
  return http.get('/cms/options/charts').then((r) => r.data.data)
}

/** Category options for dropdown pickers across CMS modules: [{ id, value, label, color }] */
export function categoryOptions() {
  return http.get('/cms/options/categories').then((r) => r.data.data)
}

/** First readable message from an API error. */
export function errorMessage(error, fallback = 'Terjadi kesalahan. Silakan coba lagi.') {
  const status = error?.response?.status
  if (status === 403) return 'Anda tidak memiliki akses ke modul ini.'
  if (status === 404) return 'Data tidak ditemukan.'
  if (status === 419) return 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'
  return error?.response?.data?.message || fallback
}
