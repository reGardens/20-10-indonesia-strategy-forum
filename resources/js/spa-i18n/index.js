import { createI18n } from 'vue-i18n'
import id from './locales/id.json'
import en from './locales/en.json'

const savedLocale = typeof window !== 'undefined' ? localStorage.getItem('cms-locale') || 'id' : 'id'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'id',
  messages: {
    id,
    en
  }
})

export default i18n
