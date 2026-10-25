import http from '@/api/http'

/**
 * Public, read-only ISF content (published content only).
 * Each function resolves to the response JSON; a missing/unpublished
 * tokoh or isu rejects with an axios error whose response.status is 404.
 */

// Concurrent calls for the same URL share one request (e.g. /anggota layout + view).
const inFlight = new Map()

function get(url) {
  if (!inFlight.has(url)) {
    const request = http
      .get(url)
      .then((response) => response.data)
      .finally(() => inFlight.delete(url))
    inFlight.set(url, request)
  }
  return inFlight.get(url)
}

export function getBeranda() {
  return get('/isf/beranda')
}

export function getTokoh(slug) {
  return get(`/isf/tokoh/${encodeURIComponent(slug)}`)
}

export function getIsu(slug) {
  return get(`/isf/isu/${encodeURIComponent(slug)}`)
}

export function getAnggota() {
  return get('/isf/anggota')
}

/** True when the error is an API "not found" (missing or unpublished content). */
export function isNotFound(error) {
  return error?.response?.status === 404
}

export default { getBeranda, getTokoh, getIsu, getAnggota, isNotFound }
