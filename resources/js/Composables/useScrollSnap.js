import { onMounted, onUnmounted } from 'vue';

/**
 * Native CSS Scroll Snapping activation (Ultra-Responsive).
 * This method has zero JS overhead and provides the fastest response time.
 */
export function useScrollSnap(selector = '[data-scroll-section]') {
    onMounted(() => {
        if (window.location.hash) {
            // Beri waktu agar browser bisa scroll otomatis ke hash anchor tanpa terhalang snapping
            setTimeout(() => {
                document.documentElement.classList.add('snap-active');
            }, 800);
        } else {
            document.documentElement.classList.add('snap-active');
        }
    });

    onUnmounted(() => {
        document.documentElement.classList.remove('snap-active');
    });
}
