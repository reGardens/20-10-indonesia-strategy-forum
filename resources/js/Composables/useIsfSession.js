import { computed, ref } from 'vue'
import { getSession } from '@/api/services/isfSessionService'

/**
 * Status login untuk header publik ISF — satu state bersama untuk seluruh halaman.
 *
 * - Diisi SINKRON dari window.__ISF_SESSION__ (ditulis server di spa.blade.php), jadi header
 *   langsung benar saat Vue mengambil alih dari shell blade (tidak hilang lalu muncul).
 * - State ada di level modul: tetap terjaga saat header di-mount ulang ketika pindah halaman
 *   (tiap rute memakai instance layout sendiri).
 * - GET /api/isf/me hanya dipanggil bila data awal tidak ada, dan paling banyak sekali per muat halaman.
 *   Login/logout selalu memuat ulang halaman penuh, jadi data awal dari server selalu terbaru.
 */
// Status Nomor WhatsApp (Issue #42) ikut disimpan: dipakai alert "perbarui nomor" di menu profil.
function toSession(s) {
  return {
    authenticated: Boolean(s?.authenticated),
    name: s?.name || '',
    whatsapp: s?.whatsapp || '',
    whatsappDisplay: s?.whatsappDisplay || '',
    whatsappVerified: Boolean(s?.whatsappVerified),
    whatsappVerification: Boolean(s?.whatsappVerification),
  }
}

function readInitial() {
  const s = typeof window !== 'undefined' ? window.__ISF_SESSION__ : null
  if (!s || typeof s !== 'object') return null
  return toSession(s)
}

const initial = readInitial()
const session = ref(initial ?? toSession(null))
let pending = initial ? Promise.resolve() : null

export function useIsfSession() {
  function ensureLoaded() {
    if (!pending) {
      pending = getSession()
        .then((res) => {
          session.value = toSession(res)
        })
        .catch(() => {
          // gagal memuat sesi → tetap tampil sebagai tamu; boleh dicoba lagi di mount berikutnya
          pending = null
        })
    }
    return pending
  }

  /** Perbarui status WhatsApp setelah disimpan/diverifikasi (tanpa memuat ulang halaman). */
  function updateWhatsApp(status) {
    session.value = { ...session.value, ...toSession({ ...session.value, ...status }), authenticated: session.value.authenticated, name: session.value.name }
  }

  return {
    session,
    updateWhatsApp,
    isLoggedIn: computed(() => session.value.authenticated),
    ensureLoaded,
  }
}
