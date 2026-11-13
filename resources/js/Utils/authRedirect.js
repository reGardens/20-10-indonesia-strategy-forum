/**
 * Parameter "redirect" untuk kembali ke halaman asal setelah login/register
 * (mis. form ulasan Indeks Tokoh → /login?redirect=/tokoh/budi-santoso).
 *
 * Aturan sama dengan App\Support\SafeRedirect (server tetap memvalidasi ulang):
 * hanya path internal relatif; "//host", "/\host", URL absolut, dan /login|/logout|/register ditolak.
 */
const SAFE_PATH = /^\/(?![/\\])[A-Za-z0-9\-._~/%?=&+]*$/
const AUTH_PAGES = /^\/(login|logout|register)(?:[/?]|$)/

/** @returns {string} path aman, atau "" bila tidak valid */
export function safeRedirectPath(value) {
  if (typeof value !== 'string' || value === '' || value.length > 500) return ''
  if (!SAFE_PATH.test(value) || AUTH_PAGES.test(value)) return ''
  return value
}

/** Ambil "redirect" yang aman dari query string halaman saat ini. */
export function currentRedirect() {
  if (typeof window === 'undefined') return ''
  return safeRedirectPath(new URLSearchParams(window.location.search).get('redirect') || '')
}

/**
 * Tambahkan "redirect" ke URL (mis. "/register", "/login?registered=true") bila ada & aman.
 * @param {string} url
 * @param {string} redirect
 */
export function withRedirect(url, redirect) {
  const safe = safeRedirectPath(redirect)
  if (!safe) return url
  return `${url}${url.includes('?') ? '&' : '?'}redirect=${encodeURIComponent(safe)}`
}
