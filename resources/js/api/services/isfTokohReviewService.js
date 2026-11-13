import http from '@/api/http'

/**
 * Indeks Tokoh — rating & ulasan publik (/api/isf/tokoh/{slug}/ulasan).
 * Sengaja tanpa cache (beda dengan isfPublicService) agar skor selalu terbaru setelah submit.
 */

/** @param {string} slug @param {{ tab?: 'suka'|'tidak-suka', page?: number }} params */
export function getTokohUlasan(slug, params = {}) {
  return http
    .get(`/isf/tokoh/${encodeURIComponent(slug)}/ulasan`, { params })
    .then((response) => response.data)
}

/**
 * Kirim/perbarui ulasan milik user yang login. 401 = belum login, 422 = validasi gagal.
 * @param {string} slug
 * @param {{ rating: number, body: string, is_anonymous: boolean }} payload
 * @param {{ tab?: 'suka'|'tidak-suka' }} params tab daftar yang dikembalikan
 */
export function submitTokohUlasan(slug, payload, params = {}) {
  return http
    .post(`/isf/tokoh/${encodeURIComponent(slug)}/ulasan`, payload, { params })
    .then((response) => response.data)
}

export default { getTokohUlasan, submitTokohUlasan }
