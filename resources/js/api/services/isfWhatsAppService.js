import http from '@/api/http'

/**
 * Nomor WhatsApp Aktif milik user yang login + verifikasi OTP (Issue #42).
 * Setiap respons berisi status terbaru: { whatsapp, whatsappDisplay, whatsappVerified, resendIn, message }.
 * Error: 422 (validasi / kode salah), 429 (tunggu sebelum kirim ulang), 502 (WhatsApp gagal dikirim).
 */

/** Simpan/ganti nomor lalu kirim kode verifikasi. */
export function saveWhatsApp(whatsapp) {
  return http.put('/isf/me/whatsapp', { whatsapp }).then((r) => r.data)
}

/** Kirim ulang kode verifikasi ke nomor tersimpan. */
export function resendWhatsAppCode() {
  return http.post('/isf/me/whatsapp/kirim-kode').then((r) => r.data)
}

/** Cocokkan kode 6 digit. */
export function verifyWhatsAppCode(code) {
  return http.post('/isf/me/whatsapp/verifikasi', { code }).then((r) => r.data)
}

export default { saveWhatsApp, resendWhatsAppCode, verifyWhatsAppCode }
