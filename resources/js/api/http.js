import axios from 'axios'

/**
 * Shared axios instance for the ISF JSON APIs (/api/isf/*, /api/cms/*).
 * Same-origin session auth: axios sends the XSRF-TOKEN cookie back as the
 * X-XSRF-TOKEN header automatically, so CMS writes pass CSRF verification.
 */
const http = axios.create({
  baseURL: '/api',
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  withCredentials: true
})

export default http
