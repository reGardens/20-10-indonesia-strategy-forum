import { computed, onBeforeUnmount, ref, watch } from 'vue'

/**
 * Antrean iklan popup Beranda (berulang / looping).
 *
 * - `popups`: daftar iklan popup aktif dari API (urut kolom "Urutan" di CMS), tiap item punya
 *   `intervalMinutes` ("Jeda Waktu Muncul Kembali") dan `firstDelaySeconds` ("Jeda Tampil Pertama").
 * - Setiap halaman dimuat (refresh / sesi baru / tab baru): popup urutan pertama muncul setelah
 *   `firstDelaySeconds` milik popup pertama.
 * - Setelah popup ditutup (atau video selesai), popup berikutnya muncul setelah `intervalMinutes`
 *   milik popup berikutnya tersebut.
 * - Setelah popup terakhir ditutup, antrean kembali ke popup pertama dengan jeda `intervalMinutes`
 *   popup pertama (minimal 1 menit agar tidak muncul terus-menerus).
 * - Jadwal disimpan di memori modul: tetap berlanjut saat pindah halaman di dalam SPA, dan
 *   dimulai ulang dari popup pertama saat halaman di-refresh.
 * - Field CMS kosong → default: tampil pertama 5 detik, tampil lagi (iklan berikutnya & kembali
 *   ke iklan pertama) 30 menit.
 */
export const POPUP_DEFAULT_FIRST_DELAY_SEC = 5
export const POPUP_DEFAULT_INTERVAL_MIN = 30
const MIN_LOOP_INTERVAL_MIN = 1

// Jadwal antrean selama halaman terbuka (hilang saat refresh → mulai lagi dari popup pertama).
let memory = null

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

/**
 * @param {import('vue').Ref<Array>|(() => Array)} source daftar popup aktif
 */
export function useIsfPopupQueue(source) {
  const popups = computed(() => {
    const list = typeof source === 'function' ? source() : source.value
    return Array.isArray(list) ? list.filter((p) => p && p.isActive !== false) : []
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

  function show(index) {
    clearTimer()
    currentIndex.value = index
  }

  function schedule(index, showAt) {
    clearTimer()
    memory = { signature: signature.value, nextIndex: index, showAt }
    timer = window.setTimeout(() => show(index), Math.max(0, showAt - Date.now()))
  }

  /** Popup ditutup (tombol X / backdrop / video selesai) → jadwalkan popup berikutnya. */
  function onClose() {
    const n = popups.value.length
    if (!n) return
    const closed = currentIndex.value
    currentIndex.value = -1
    const nextIndex = closed + 1 < n ? closed + 1 : 0
    schedule(nextIndex, Date.now() + intervalMs(popups.value[nextIndex], { loop: nextIndex === 0 }))
  }

  function start() {
    clearTimer()
    currentIndex.value = -1
    const n = popups.value.length
    if (!n || typeof window === 'undefined') return
    if (memory && memory.signature === signature.value && memory.nextIndex >= 0 && memory.nextIndex < n) {
      // Kembali ke Beranda (tanpa refresh): lanjutkan jadwal sebelumnya.
      schedule(memory.nextIndex, memory.showAt)
      return
    }
    // Halaman baru dimuat / daftar iklan berubah → mulai dari popup pertama.
    schedule(0, Date.now() + firstDelayMs(popups.value[0]))
  }

  watch(signature, start, { immediate: true })
  onBeforeUnmount(clearTimer)

  return { current, currentIndex, onClose }
}
