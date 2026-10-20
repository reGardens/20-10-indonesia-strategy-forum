import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n from './spa-i18n'
import { VueQueryPlugin } from '@tanstack/vue-query'
import AOS from 'aos'
import 'aos/dist/aos.css'
import './assets/main.css'

const app = createApp(App)

app.use(router)
app.use(store)
app.use(i18n)
app.use(VueQueryPlugin)

app.mount('#app')

AOS.init({
  duration: 800,
  once: true
})
