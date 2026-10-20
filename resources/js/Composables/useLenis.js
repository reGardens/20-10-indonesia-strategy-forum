import { onMounted, onUnmounted } from 'vue';
import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export function useLenis() {
    let lenis = null;

    onMounted(() => {
        // Initialize Lenis
        lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            mouseMultiplier: 1,
            smoothTouch: false,
            touchMultiplier: 2,
            infinite: false,
        });

        // Integrate Lenis with GSAP ScrollTrigger
        lenis.on('scroll', () => {
            ScrollTrigger.update();
            // Optional: If AOS is used, it might need manual refresh
            if (window.AOS) window.AOS.refresh();
        });

        gsap.ticker.add((time) => {
            lenis.raf(time * 1000);
        });

        gsap.ticker.lagSmoothing(0);

        // Optional: Global instance if needed
        window.lenis = lenis;
    });

    onUnmounted(() => {
        if (lenis) {
            lenis.destroy();
            window.lenis = null;
        }
    });

    return {
        lenis,
    };
}
