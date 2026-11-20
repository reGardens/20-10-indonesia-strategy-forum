import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import i18n from './spa-i18n'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { getBeranda, getAnggota, getInfografik, getTokoh } from './api/services/isfPublicService'
// Satu-satunya file style landing page (font, skeleton, Tailwind, tema ISF, header loading).
import '../css/isf.css'

// Prefetch initial page data immediately in parallel with chunk downloading
if (typeof window !== 'undefined') {
  const path = window.location.pathname
  if (path === '/' || path === '/beranda') {
    getBeranda().catch(() => {})
  } else if (path.startsWith('/anggota')) {
    getAnggota().catch(() => {})
  } else if (path === '/infografik') {
    getInfografik().catch(() => {})
  } else if (path.startsWith('/tokoh/')) {
    const slug = path.split('/')[2]
    if (slug) getTokoh(slug).catch(() => {})
  }
}

const app = createApp(App)

app.use(router)
app.use(i18n)
app.use(VueQueryPlugin)

router.isReady().finally(() => {
  app.mount('#app')
})
