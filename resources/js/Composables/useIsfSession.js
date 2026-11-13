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
function readInitial() {
  const s = typeof window !== 'undefined' ? window.__ISF_SESSION__ : null
  if (!s || typeof s !== 'object') return null
  return { authenticated: Boolean(s.authenticated), name: s.name || '' }
}

const initial = readInitial()
const session = ref(initial ?? { authenticated: false, name: '' })
let pending = initial ? Promise.resolve() : null

export function useIsfSession() {
  function ensureLoaded() {
    if (!pending) {
      pending = getSession()
        .then((res) => {
          session.value = { authenticated: Boolean(res?.authenticated), name: res?.name || '' }
        })
        .catch(() => {
          // gagal memuat sesi → tetap tampil sebagai tamu; boleh dicoba lagi di mount berikutnya
          pending = null
        })
    }
    return pending
  }

  return {
    session,
    isLoggedIn: computed(() => session.value.authenticated),
    ensureLoaded,
  }
}
