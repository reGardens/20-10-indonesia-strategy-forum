import { createApp, h, ref, watch } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Swal from 'sweetalert2';
import AOS from 'aos';
import 'aos/dist/aos.css';

import '../css/app.css';

// Global loading state
const isLoading = ref(false);

router.on('start', () => {
    isLoading.value = true;
});

router.on('finish', () => {
    isLoading.value = false;
    
    // Refresh AOS on every page change
    setTimeout(() => {
        AOS.refresh();
    }, 100);

    // Handle flash messages
    const flash = router.page.props.flash;
    if (flash?.success || flash?.message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: flash.success || flash.message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
    if (flash?.error) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: flash.error,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});

createInertiaApp({
    title: (title) => title ? `${title}` : '',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-in-out',
        });

        const app = createApp({
            setup() {
                return () => h('div', [
                    h(App, props),
                    isLoading.value
                        ? h('div', {
                            class: 'fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm',
                        }, [
                            h('div', { class: 'flex flex-col items-center gap-3' }, [
                                h('div', { class: 'w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin' }),
                                h('p', { class: 'text-white text-sm font-medium' }, 'Memproses...'),
                            ]),
                        ])
                        : null,
                ]);
            },
        });

        app.use(plugin);
        app.mount(el);
        return app;
    },
    progress: false,
});
