import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n from './spa-i18n'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { getBeranda, getAnggota, getInfografik, getIsu, getTokoh } from './api/services/isfPublicService'
import './assets/main.css'
import './assets/isf-theme.css'

// Prefetch initial page data immediately in parallel with chunk downloading
if (typeof window !== 'undefined') {
  const path = window.location.pathname
  if (path === '/' || path === '/beranda') {
    getBeranda().catch(() => {})
  } else if (path.startsWith('/anggota')) {
    getAnggota().catch(() => {})
  } else if (path === '/infografik') {
    getInfografik().catch(() => {})
  } else if (path.startsWith('/isu/')) {
    const slug = path.split('/')[2]
    if (slug) getIsu(slug).catch(() => {})
  } else if (path.startsWith('/tokoh/')) {
    const slug = path.split('/')[2]
    if (slug) getTokoh(slug).catch(() => {})
  }
}

const app = createApp(App)

app.use(router)
app.use(store)
app.use(i18n)
app.use(VueQueryPlugin)

router.isReady().finally(() => {
  app.mount('#app')
})
