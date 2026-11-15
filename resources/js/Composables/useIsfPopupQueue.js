import { computed, onBeforeUnmount, ref, watch } from 'vue'

/**
 * Antrean iklan popup Beranda dengan frequency capping (localStorage).
 *
 * - `popups`: daftar popup yang sedang tayang dari API (urut kolom "Urutan" di CMS). Tiap item punya
 *   `intervalMinutes` ("Tampil lagi setelah") dan `firstDelaySeconds` ("Tampil pertama setelah").
 * - Halaman dibuka: popup berikutnya dalam antrean muncul setelah `firstDelaySeconds` popup urutan
 *   pertama, tapi tidak sebelum jadwal tersimpan (lihat di bawah).
 * - Popup ditutup (tombol X / backdrop / video selesai): waktu tampil dicatat, lalu popup berikutnya
 *   dijadwalkan `intervalMinutes` miliknya sejak penutupan. Setelah popup terakhir → kembali ke popup
 *   pertama (minimal 1 menit).
 * - Jadwal & waktu terakhir tiap popup tampil disimpan di localStorage (`isf_ad_popup_queue`), jadi
 *   refresh / tab baru tidak memunculkan popup lagi sebelum jedanya habis. Satu popup juga tidak
 *   tampil lagi sebelum `intervalMinutes`-nya sendiri lewat sejak terakhir tampil.
 * - Field CMS kosong → default: tampil pertama 5 detik, tampil lagi 30 menit.
 */
export const POPUP_DEFAULT_FIRST_DELAY_SEC = 5
export const POPUP_DEFAULT_INTERVAL_MIN = 30
export const POPUP_QUEUE_STORAGE_KEY = 'isf_ad_popup_queue'
const MIN_LOOP_INTERVAL_MIN = 1

// Cadangan bila localStorage diblokir browser (berlaku selama halaman terbuka).
let memoryState = null

function numberOr(value, fallback) {
  // null/kosong (belum diisi di CMS) → default; Number(null) = 0 jadi harus dicek terpisah.
  if (value === null || value === undefined || value === '') return fallback
  const n = Number(value)
  return Number.isFinite(n) && n >= 0 ? n : fallback
}

function intervalMs(item, { loop = false } = {}) {
  let minutes = numberOr(item?.intervalMinutes, POPUP_DEFAULT_INTERVAL_MIN)
  if (loop) minutes = Math.max(minutes, MIN_LOOP_INTERVAL_MIN)
  return minutes * 60 * 1000
}

function firstDelayMs(item) {
  return numberOr(item?.firstDelaySeconds, POPUP_DEFAULT_FIRST_DELAY_SEC) * 1000
}

function emptyState() {
  return { lastSeen: {}, nextId: null, nextAt: 0 }
}

function readState() {
  try {
    const raw = window.localStorage.getItem(POPUP_QUEUE_STORAGE_KEY)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed && typeof parsed === 'object') {
        return {
          lastSeen: parsed.lastSeen && typeof parsed.lastSeen === 'object' ? parsed.lastSeen : {},
          nextId: parsed.nextId ?? null,
          nextAt: Number(parsed.nextAt) || 0,
        }
      }
    }
    return emptyState()
  } catch {
    return memoryState ? { ...memoryState, lastSeen: { ...memoryState.lastSeen } } : emptyState()
  }
}

function writeState(state) {
  memoryState = state
  try {
    window.localStorage.setItem(POPUP_QUEUE_STORAGE_KEY, JSON.stringify(state))
  } catch {
    // localStorage diblokir → memakai memoryState.
  }
}

/** Waktu paling cepat popup boleh tampil lagi berdasarkan kapan terakhir ia tampil. */
function capAt(state, item) {
  const seen = Number(state.lastSeen[String(item.id)]) || 0
  return seen ? seen + intervalMs(item, { loop: true }) : 0
}

/**
 * @param {import('vue').Ref<Array>|(() => Array)} source daftar popup tayang
 */
export function useIsfPopupQueue(source) {
  const popups = computed(() => {
    const list = typeof source === 'function' ? source() : source.value
    return Array.isArray(list) ? list.filter((p) => p && p.id !== undefined && p.isActive !== false) : []
  })
  const signature = computed(() =>
    popups.value.map((p) => `${p.id}:${p.intervalMinutes ?? ''}:${p.firstDelaySeconds ?? ''}`).join('|'),
  )

  const currentIndex = ref(-1)
  const current = computed(() => (currentIndex.value >= 0 ? popups.value[currentIndex.value] || null : null))
  let timer = null

  function clearTimer() {
    if (timer) {
      window.clearTimeout(timer)
      timer = null
    }
  }

  function schedule(index, showAt) {
    clearTimer()
    const item = popups.value[index]
    if (!item) return
    timer = window.setTimeout(() => {
      timer = null
      currentIndex.value = index
      // Catat saat tampil: refresh ketika popup terbuka tidak memunculkannya lagi sebelum jedanya habis.
      const state = readState()
      state.lastSeen[String(item.id)] = Date.now()
      writeState(state)
    }, Math.max(0, showAt - Date.now()))
  }

  /** Popup ditutup → jadwalkan popup berikutnya dan simpan jadwalnya. */
  function onClose() {
    const list = popups.value
    const n = list.length
    const closed = currentIndex.value
    currentIndex.value = -1
    if (!n || closed < 0) return

    const now = Date.now()
    const state = readState()
    state.lastSeen[String(list[closed].id)] = now

    const nextIndex = closed + 1 < n ? closed + 1 : 0
    const next = list[nextIndex]
    const showAt = Math.max(now + intervalMs(next, { loop: nextIndex === 0 }), capAt(state, next))

    state.nextId = next.id
    state.nextAt = showAt
    writeState(state)
    schedule(nextIndex, showAt)
  }

  function start() {
    clearTimer()
    currentIndex.value = -1
    const list = popups.value
    if (!list.length || typeof window === 'undefined') return

    const now = Date.now()
    const state = readState()
    const earliest = now + firstDelayMs(list[0])

    // Lanjutkan antrean tersimpan bila popup berikutnya masih tayang.
    const savedIndex = list.findIndex((p) => String(p.id) === String(state.nextId))
    if (savedIndex >= 0) {
      schedule(savedIndex, Math.max(earliest, state.nextAt, capAt(state, list[savedIndex])))
      return
    }

    // Belum ada jadwal (kunjungan pertama / daftar berubah): popup pertama yang jedanya sudah lewat,
    // atau popup yang paling cepat boleh tampil lagi.
    let bestIndex = 0
    let bestAt = Infinity
    list.forEach((item, idx) => {
      const at = Math.max(earliest, capAt(state, item))
      if (at < bestAt) {
        bestAt = at
        bestIndex = idx
      }
    })
    schedule(bestIndex, bestAt)
  }

  watch(signature, start, { immediate: true })
  onBeforeUnmount(clearTimer)

  return { current, currentIndex, onClose }
}
