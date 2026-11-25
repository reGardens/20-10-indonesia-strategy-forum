import { ref, onMounted } from 'vue'

const isStandalone = ref(false)
const canInstall = ref(false)
const isInstalled = ref(false)
const swRegistration = ref(null)
let deferredPrompt = null

export function useIsfPwa() {
  const initPwa = () => {
    if (typeof window === 'undefined') return

    // Check standalone mode safely (guards against environments without matchMedia like JSDOM)
    isStandalone.value =
      (typeof window.matchMedia === 'function' && window.matchMedia('(display-mode: standalone)').matches) ||
      window.navigator.standalone === true

    // Check if previously dismissed recently
    const dismissedAt = localStorage.getItem('isf_pwa_install_dismissed_at')
    const isDismissedRecently = dismissedAt && Date.now() - parseInt(dismissedAt, 10) < 7 * 24 * 60 * 60 * 1000

    // Register Service Worker
    if ('serviceWorker' in navigator) {
      const registerSW = () => {
        navigator.serviceWorker
          .register('/sw.js', { scope: '/' })
          .then((reg) => {
            swRegistration.value = reg
          })
          .catch((err) => {
            console.warn('[PWA] Service Worker registration failed:', err)
          })
      }

      if (document.readyState === 'complete') {
        registerSW()
      } else {
        window.addEventListener('load', registerSW)
      }
    }

    // Capture beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault()
      deferredPrompt = e
      if (!isDismissedRecently && !isStandalone.value) {
        canInstall.value = true
      }
    })

    // Capture appinstalled
    window.addEventListener('appinstalled', () => {
      isInstalled.value = true
      canInstall.value = false
      deferredPrompt = null
      localStorage.removeItem('isf_pwa_install_dismissed_at')
    })
  }

  const installPwa = async () => {
    if (!deferredPrompt) return false
    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice
    deferredPrompt = null
    canInstall.value = false
    if (outcome === 'accepted') {
      isInstalled.value = true
      return true
    }
    return false
  }

  const dismissInstallPrompt = () => {
    canInstall.value = false
    if (typeof window !== 'undefined') {
      localStorage.setItem('isf_pwa_install_dismissed_at', Date.now().toString())
    }
  }

  return {
    isStandalone,
    canInstall,
    isInstalled,
    swRegistration,
    initPwa,
    installPwa,
    dismissInstallPrompt,
  }
}
