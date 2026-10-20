/**
 * useDarkMode Composable
 * Mengelola dark mode toggle. Menyimpan preferensi di localStorage.
 * Menambahkan class 'dark' ke <html> element.
 */
import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'cms-dark-mode';

// Global reactive state
const isDark = ref(false);

/**
 * Check initial dark mode preference.
 * Priority: localStorage > system preference > false (light)
 */
function initDarkMode() {
    if (typeof window === 'undefined') return;

    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved !== null) {
        isDark.value = saved === 'true';
    } else {
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    applyDarkMode();
}

/**
 * Apply dark mode class to <html>.
 */
function applyDarkMode() {
    if (typeof window === 'undefined') return;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

/**
 * Toggle dark mode on/off.
 */
function toggleDarkMode() {
    isDark.value = !isDark.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, String(isDark.value));
    }
    applyDarkMode();
}

export function useDarkMode() {
    onMounted(() => {
        initDarkMode();
    });

    watch(isDark, () => {
        applyDarkMode();
    });

    return {
        isDark,
        toggleDarkMode,
    };
}

// Initialize on module load (SSR-safe check done inside)
if (typeof window !== 'undefined') {
    initDarkMode();
}
