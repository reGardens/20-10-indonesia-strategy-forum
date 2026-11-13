import http from '@/api/http'

/**
 * Sesi login untuk header publik ISF. Sengaja TANPA cache (beda dengan isfPublicService),
 * karena status login bisa berubah kapan saja (login/logout di tab lain).
 */

/** @returns {Promise<{ authenticated: boolean, name: string|null, initials: string|null }>} */
export function getSession() {
  return http.get('/isf/me').then((response) => response.data)
}

/**
 * Logout lewat rute web yang sudah ada (POST /logout, di luar prefix /api).
 * CSRF: axios mengirim cookie XSRF-TOKEN sebagai header X-XSRF-TOKEN secara otomatis.
 */
export function logout() {
  return http.post('/logout', null, { baseURL: '' })
}

export default { getSession, logout }
