import { ref, watch, onMounted } from 'vue'

/**
 * Persists pagination page number during create/edit/detail workflows within the same CMS menu,
 * but resets to page 1 when switching menus/submenus or upon fresh login.
 *
 * @param {string} [menuKey] Optional explicit menu key (defaults to current base path)
 * @param {number} [defaultPage=1]
 * @returns {import('vue').Ref<number>} Reactive page ref
 */
export function useCmsPagination(menuKey = null, defaultPage = 1) {
  const getBasePath = () => {
    if (menuKey) return menuKey
    if (typeof window === 'undefined') return ''
    return window.location.pathname.replace(/\/create|\/edit|\/\d+/g, '').replace(/\/$/, '')
  }

  const basePath = getBasePath()
  const storageKey = 'cms_page_' + basePath
  const lastMenuKey = 'cms_last_active_menu'

  let startingPage = defaultPage

  if (typeof window !== 'undefined' && typeof sessionStorage !== 'undefined') {
    const lastMenu = sessionStorage.getItem(lastMenuKey)
    if (lastMenu === basePath) {
      const saved = sessionStorage.getItem(storageKey)
      const parsed = parseInt(saved, 10)
      if (!isNaN(parsed) && parsed > 0) {
        startingPage = parsed
      }
    } else {
      // Pindah menu/sub-menu atau baru masuk menu ini -> reset ke page 1
      sessionStorage.setItem(storageKey, '1')
      sessionStorage.setItem(lastMenuKey, basePath)
    }
  }

  const page = ref(startingPage)

  watch(page, (val) => {
    if (typeof sessionStorage !== 'undefined' && val > 0) {
      sessionStorage.setItem(storageKey, String(val))
    }
  })

  onMounted(() => {
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.setItem(lastMenuKey, basePath)
    }
  })

  return page
}

