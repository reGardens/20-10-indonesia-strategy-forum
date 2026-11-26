import { ref } from 'vue'
import http from '@/api/http'

const isSupported = ref(false)
const permissionState = ref('default')
const isSubscribed = ref(false)
const isLoading = ref(false)
export const isNotificationPromptActive = ref(false)

function urlB64ToUint8Array(base64String) {
  const clean = String(base64String || '').trim().replace(/^["']|["']$/g, '')
  const padding = '='.repeat((4 - (clean.length % 4)) % 4)
  const base64 = (clean + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  const outputArray = new Uint8Array(rawData.length)
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i)
  }
  return outputArray
}

export function useIsfPushNotification() {
  const getRegistration = async () => {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return null
    try {
      let reg = await navigator.serviceWorker.getRegistration()
      if (!reg) {
        reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' })
      }
      if (navigator.serviceWorker.ready) {
        const readyReg = await Promise.race([
          navigator.serviceWorker.ready,
          new Promise((resolve) => setTimeout(resolve, 3000)),
        ])
        if (readyReg) return readyReg
      }
      return reg || (await navigator.serviceWorker.getRegistration())
    } catch (e) {
      console.warn('[Push] Error getting SW registration:', e)
      return null
    }
  }

  const initPush = async () => {
    if (typeof window === 'undefined') return

    isSupported.value =
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    if (!isSupported.value) {
      permissionState.value = 'unsupported'
      return
    }

    permissionState.value = Notification.permission

    try {
      const reg = await getRegistration()
      if (reg?.pushManager) {
        let sub = await reg.pushManager.getSubscription()

        // Jika browser/HP sudah mengizinkan notifikasi (granted) tapi belum punya subscription aktif di PushManager, daftarkan otomatis sekarang!
        if (Notification.permission === 'granted' && !sub) {
          const autoSubRes = await subscribe()
          if (autoSubRes?.success) {
            isSubscribed.value = true
            return
          }
        }

        isSubscribed.value = !!sub

        if (sub) {
          // Sinkronkan ke server agar endpoint otomatis terhubung ke akun user yang sedang aktif login
          try {
            const raw = sub.toJSON()
            await http.post('/isf/push/subscribe', {
              endpoint: sub.endpoint,
              keys: raw.keys,
              content_encoding: (window.PushManager?.supportedContentEncodings || ['aes128gcm'])[0],
            })
          } catch (_) {}
        }
      }
    } catch (e) {
      console.warn('[Push] Error in initPush:', e)
    }
  }

  const subscribe = async () => {
    if (!isSupported.value) return { success: false, reason: 'unsupported' }

    isLoading.value = true
    try {
      const permission = await Notification.requestPermission()
      permissionState.value = permission

      if (permission !== 'granted') {
        isLoading.value = false
        return { success: false, reason: 'denied' }
      }

      // Fetch VAPID public key
      const keyRes = await http.get('/isf/push/vapid-key')
      const vapidKey = keyRes.data?.public_key

      if (!vapidKey) {
        isLoading.value = false
        return { success: false, reason: 'no_key' }
      }

      const reg = await getRegistration()
      if (!reg || !reg.pushManager) {
        console.warn('[Push] ServiceWorker or pushManager not available')
        isLoading.value = false
        return { success: false, reason: 'no_sw' }
      }

      let sub = await reg.pushManager.getSubscription()

      if (!sub) {
        try {
          sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlB64ToUint8Array(vapidKey),
          })
        } catch (subErr) {
          console.warn('[Push] Subscribe with current key failed, resetting stale subscription:', subErr)
          const existing = await reg.pushManager.getSubscription()
          if (existing) {
            await existing.unsubscribe().catch(() => {})
          }
          sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlB64ToUint8Array(vapidKey),
          })
        }
      }

      const raw = sub.toJSON()
      const postRes = await http.post('/isf/push/subscribe', {
        endpoint: sub.endpoint,
        keys: raw.keys,
        content_encoding: (window.PushManager?.supportedContentEncodings || ['aes128gcm'])[0],
      })

      isSubscribed.value = true
      localStorage.removeItem('isf_push_prompt_dismissed_at')
      isLoading.value = false
      return { success: true, data: postRes.data }
    } catch (err) {
      console.error('[Push] Subscription failed:', err)
      isLoading.value = false
      return { success: false, error: err }
    }
  }

  const unsubscribe = async () => {
    if (!isSupported.value) return false

    isLoading.value = true
    try {
      const reg = await getRegistration()
      if (reg?.pushManager) {
        const sub = await reg.pushManager.getSubscription()
        if (sub) {
          const endpoint = sub.endpoint
          await sub.unsubscribe()
          await http.post('/isf/push/unsubscribe', { endpoint })
        }
      }

      isSubscribed.value = false
      isLoading.value = false
      return true
    } catch (err) {
      console.error('[Push] Unsubscription failed:', err)
      isLoading.value = false
      return false
    } finally {
      isLoading.value = false
    }
  }

  const dismissPrompt = () => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('isf_push_prompt_dismissed_at', Date.now().toString())
    }
  }

  const isDismissedRecently = () => {
    if (typeof window === 'undefined') return false
    const dismissedAt = localStorage.getItem('isf_push_prompt_dismissed_at')
    if (!dismissedAt) return false
    // 7 days cooldown
    return Date.now() - parseInt(dismissedAt, 10) < 7 * 24 * 60 * 60 * 1000
  }

  return {
    isSupported,
    permissionState,
    isSubscribed,
    isLoading,
    initPush,
    subscribe,
    unsubscribe,
    dismissPrompt,
    isDismissedRecently,
  }
}
