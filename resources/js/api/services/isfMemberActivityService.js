import http from '@/api/http'

/**
 * Fetch member's historical activities (articles, reviews, surveys, reports).
 */
export async function getMemberActivities() {
  const res = await http.get('/isf/member/activities')
  return res.data
}
