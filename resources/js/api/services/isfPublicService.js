import http from '@/api/http'

/**
 * Public, read-only ISF content (published content only).
 * Each function resolves to the response JSON; a missing/unpublished
 * tokoh or isu rejects with an axios error whose response.status is 404.
 */

// Cached promises with TTL so prefetch and concurrent calls share responses seamlessly
const cache = new Map()

function get(url) {
  const cached = cache.get(url)
  if (cached && Date.now() - cached.time < 30000) {
    return cached.promise
  }

  const promise = http
    .get(url)
    .then((response) => response.data)
    .catch((error) => {
      cache.delete(url)
      throw error
    })

  cache.set(url, { promise, time: Date.now() })
  return promise
}

export function invalidateCache(url) {
  if (url) {
    cache.delete(url)
  } else {
    cache.clear()
  }
}

export function getBeranda() {
  return get('/isf/beranda')
}

export function getTokoh(slug) {
  return get(`/isf/tokoh/${encodeURIComponent(slug)}`)
}

export function getAnggota() {
  return get('/isf/anggota')
}

export function getInfografik() {
  return get('/isf/infografik')
}

export function getMediaDetail(id) {
  return get('/isf/media/' + encodeURIComponent(id))
}

export function getArtikel(slug) {
  return get('/isf/artikel/' + encodeURIComponent(slug))
}

export function getInfografikDetail(slug) {
  return get('/isf/infografik/' + encodeURIComponent(slug))
}

// offset 0 = 1 Sorotan Utama + 6 Artikel Terbaru (limit 7); "Lihat lainnya" = 6 berikutnya.
export function getKategori(kategori, offset = 0, limit = 7) {
  return get('/isf/kategori/' + encodeURIComponent(kategori) + '?offset=' + Number(offset) + '&limit=' + Number(limit))
}

export function getPolitik(subkategori = null, page = 1, limit = 9, params = {}) {
  const qs = new URLSearchParams()
  if (subkategori) qs.set('subkategori', subkategori)
  if (page) qs.set('page', String(page))
  if (limit) qs.set('limit', String(limit))
  if (params.q) qs.set('q', params.q)
  if (params.date) qs.set('date', params.date)
  const queryString = qs.toString()
  return get('/isf/politik' + (queryString ? `?${queryString}` : ''))
}

export function getPetaKursi(lembaga = 'DPR', periode = '2024-2029') {
  const qs = new URLSearchParams()
  if (lembaga) qs.set('lembaga', lembaga)
  if (periode) qs.set('periode', periode)
  return get('/isf/peta-kursi?' + qs.toString())
}

export function getHukum(subkategori = null, page = 1, limit = 9) {
  const qs = new URLSearchParams()
  if (subkategori) qs.set('subkategori', subkategori)
  if (page) qs.set('page', String(page))
  if (limit) qs.set('limit', String(limit))
  const queryString = qs.toString()
  return get('/isf/hukum' + (queryString ? `?${queryString}` : ''))
}

export function getGlosariumHukum(params = {}) {
  const qs = new URLSearchParams()
  if (params.q) qs.set('q', params.q)
  if (params.letter) qs.set('letter', params.letter)
  const queryString = qs.toString()
  return get('/isf/glosarium-hukum' + (queryString ? `?${queryString}` : ''))
}

export function getTimelineKasus(slug = null) {
  const qs = new URLSearchParams()
  if (slug) qs.set('slug', slug)
  const queryString = qs.toString()
  return get('/isf/timeline-kasus' + (queryString ? `?${queryString}` : ''))
}

export function getKebijakanPublik(subkategori = null, page = 1, limit = 9) {
  const qs = new URLSearchParams()
  if (subkategori) qs.set('subkategori', subkategori)
  if (page) qs.set('page', String(page))
  if (limit) qs.set('limit', String(limit))
  const queryString = qs.toString()
  return get('/isf/kebijakan-publik' + (queryString ? `?${queryString}` : ''))
}

export function getPolicyBriefs(params = {}) {
  const qs = new URLSearchParams()
  if (params.sector) qs.set('sector', params.sector)
  if (params.q) qs.set('q', params.q)
  const queryString = qs.toString()
  return get('/isf/policy-brief' + (queryString ? `?${queryString}` : ''))
}

export function getMonitoringKebijakan(params = {}) {
  const qs = new URLSearchParams()
  if (params.sector) qs.set('sector', params.sector)
  if (params.status_label) qs.set('status_label', params.status_label)
  if (params.q) qs.set('q', params.q)
  const queryString = qs.toString()
  return get('/isf/monitoring-kebijakan' + (queryString ? `?${queryString}` : ''))
}

export function getOpiniPublik(subkategori = null, page = 1, limit = 9) {
  const params = new URLSearchParams()
  if (subkategori) params.set('subkategori', subkategori)
  if (page > 1) params.set('page', String(page))
  if (limit !== 9) params.set('limit', String(limit))
  const qs = params.toString()
  return get('/isf/opini-publik' + (qs ? `?${qs}` : ''))
}

export function getReaderPoll() {
  return get('/isf/reader-poll')
}

export function voteReaderPoll(pollId, optionId) {
  return http.post(`/isf/reader-poll/${pollId}/vote`, { option_id: optionId }).then((r) => r.data)
}

export function getKutipanPublik(authorType = null, q = '') {
  const params = new URLSearchParams()
  if (authorType) params.set('author_type', authorType)
  if (q) params.set('q', q)
  const qs = params.toString()
  return get('/isf/kutipan-publik' + (qs ? `?${qs}` : ''))
}

export function getSuratPembaca(page = 1, limit = 10, q = '') {
  const params = new URLSearchParams()
  if (page > 1) params.set('page', String(page))
  if (limit !== 10) params.set('limit', String(limit))
  if (q) params.set('q', q)
  const qs = params.toString()
  return get('/isf/surat-pembaca' + (qs ? `?${qs}` : ''))
}

export function submitSuratPembaca(payload) {
  return http.post('/isf/surat-pembaca', payload).then((r) => r.data)
}

export function getSurvei(params = {}) {
  const query = typeof params === 'string' ? { isu: params } : params
  const qs = new URLSearchParams()
  if (query.isu && query.isu !== 'semua') qs.set('isu', query.isu)
  if (query.topic && query.topic !== 'semua') qs.set('topic', query.topic)
  if (query.wilayah && query.wilayah !== 'semua') qs.set('wilayah', query.wilayah)
  if (query.region && query.region !== 'semua') qs.set('region', query.region)
  if (query.page && query.page > 1) qs.set('page', String(query.page))
  if (query.limit && query.limit !== 9) qs.set('limit', String(query.limit))
  if (query.q) qs.set('q', query.q)
  const queryString = qs.toString()
  return get('/isf/survei' + (queryString ? `?${queryString}` : ''))
}

export function getSurveiDetail(slug) {
  return get(`/isf/survei/${slug}`)
}

export function getSosok(params = {}) {
  const qs = new URLSearchParams()
  if (params.kategori && params.kategori !== 'semua') qs.set('kategori', params.kategori)
  if (params.category && params.category !== 'semua') qs.set('kategori', params.category)
  if (params.isu && params.isu !== 'semua') qs.set('isu', params.isu)
  if (params.abjad && params.abjad !== 'semua') qs.set('abjad', params.abjad)
  if (params.q) qs.set('q', params.q)
  if (params.page && params.page > 1) qs.set('page', String(params.page))
  if (params.limit && params.limit !== 12) qs.set('limit', String(params.limit))
  const queryString = qs.toString()
  return get('/isf/sosok' + (queryString ? `?${queryString}` : ''))
}

export function getSosokDetail(slug) {
  return get(`/isf/sosok/${slug}`)
}

export function searchContent(params = {}) {
  const query = typeof params === 'string' ? { q: params } : params
  const qs = new URLSearchParams()
  if (query.q) qs.set('q', query.q)
  if (query.type && query.type !== 'semua') qs.set('type', query.type)
  const queryString = qs.toString()
  return get('/isf/search' + (queryString ? `?${queryString}` : ''))
}

/**
 * Iklan Beranda yang sedang tayang (tanpa cache agar rotasi spanduk & jam tayang akurat).
 * slot "spanduk" → satu iklan (atau null), slot "popup" → daftar popup urut CMS.
 */
export function getAds(slot) {
  return http.get('/isf/ads', { params: { slot } }).then((response) => response.data?.data ?? null)
}

function readCookie(name) {
  if (typeof document === 'undefined') return ''
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : ''
}

// fetch keepalive: tetap terkirim walau halaman berpindah (klik tautan iklan). Gagal = diabaikan.
function sendAdEvent(id, event) {
  if (!id || typeof fetch !== 'function') return
  try {
    fetch(`/api/isf/ads/${encodeURIComponent(id)}/${event}`, {
      method: 'POST',
      keepalive: true,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': readCookie('XSRF-TOKEN'),
      },
    }).catch(() => {})
  } catch {
    // abaikan
  }
}

export function trackAdImpression(id) {
  sendAdEvent(id, 'impression')
}

export function trackAdClick(id) {
  sendAdEvent(id, 'click')
}

/** True when the error is an API "not found" (missing or unpublished content). */
export function isNotFound(error) {
  return error?.response?.status === 404
}

export default { getBeranda, getTokoh, getAnggota, getInfografik, getMediaDetail, getArtikel, getInfografikDetail, getKategori, getPolitik, getPetaKursi, getHukum, getGlosariumHukum, getTimelineKasus, getKebijakanPublik, getPolicyBriefs, getMonitoringKebijakan, getOpiniPublik, getReaderPoll, voteReaderPoll, getKutipanPublik, getSuratPembaca, submitSuratPembaca, getSurvei, getSurveiDetail, getSosok, getSosokDetail, searchContent, getAds, trackAdImpression, trackAdClick, isNotFound }
