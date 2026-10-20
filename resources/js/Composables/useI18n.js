/**
 * useI18n Composable
 * Simple i18n solution untuk CMS. Mendukung Bahasa Indonesia (id) dan English (en).
 * Locale disimpan di localStorage agar persist antar halaman.
 */
import { ref, computed } from 'vue';
import { translations } from '@/i18n/translations';

const STORAGE_KEY = 'cms-locale';
const DEFAULT_LOCALE = 'id';

// Global reactive state
const currentLocale = ref(
    typeof window !== 'undefined' && localStorage.getItem(STORAGE_KEY)
        ? localStorage.getItem(STORAGE_KEY)
        : DEFAULT_LOCALE
);

/**
 * Get a translation by dot-notation key.
 * @param {string} key - Dot-notation key (e.g., 'common.save')
 * @returns {string} Translated string
 */
function translate(key) {
    const keys = key.split('.');
    let result = translations[currentLocale.value];

    for (const k of keys) {
        if (result && typeof result === 'object' && k in result) {
            result = result[k];
        } else {
            return key; // fallback to key if not found
        }
    }

    return typeof result === 'string' ? result : key;
}

/**
 * Set the current locale and persist to localStorage.
 * @param {string} locale - 'id' or 'en'
 */
function setLocale(locale) {
    if (translations[locale]) {
        currentLocale.value = locale;
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, locale);
            document.documentElement.lang = locale;
        }
    }
}

/**
 * Toggle between 'id' and 'en'.
 */
function toggleLocale() {
    setLocale(currentLocale.value === 'id' ? 'en' : 'id');
}

export function useI18n() {
    return {
        locale: computed(() => currentLocale.value),
        t: translate,
        setLocale,
        toggleLocale,
        availableLocales: ['id', 'en'],
    };
}
