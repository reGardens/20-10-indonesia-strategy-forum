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

export function searchContent(params = {}) {
  const query = typeof params === 'string' ? { q: params } : params
  const qs = new URLSearchParams()
  if (query.q) qs.set('q', query.q)
  if (query.type && query.type !== 'semua') qs.set('type', query.type)
  const queryString = qs.toString()
  return get('/isf/search' + (queryString ? `?${queryString}` : ''))
}

/** True when the error is an API "not found" (missing or unpublished content). */
export function isNotFound(error) {
  return error?.response?.status === 404
}

export default { getBeranda, getTokoh, getAnggota, getInfografik, getMediaDetail, searchContent, isNotFound }
