import http from '@/api/http'

/**
 * Service API untuk Halaman Survei Member ISF
 */

export async function getSurveiKatalog() {
  const res = await http.get('/isf/member/survei')
  return res.data?.data || []
}

export async function getSurveiWidget() {
  const res = await http.get('/isf/member/survei/widget')
  return res.data || {}
}

export async function getSurveiDetail(id) {
  const res = await http.get(`/isf/member/survei/${id}`)
  return res.data || {}
}

export async function saveSurveiDraft(id, payload) {
  const res = await http.post(`/isf/member/survei/${id}/draft`, payload)
  return res.data
}

export async function submitSurvei(id, payload) {
  const res = await http.post(`/isf/member/survei/${id}/submit`, payload)
  return res.data
}

export async function getSurveiResults(id) {
  const res = await http.get(`/isf/member/survei/${id}/results`)
  return res.data || {}
}
