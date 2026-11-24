<script setup>
/**
 * Reusable editorial footer for ISF layouts.
 * Matches original design across public and member portal pages:
 * - Row 1: Nav links + Copyright
 * - Row 2: Legal links
 * Uses IsfLink component to ensure rock-solid link resolution.
 */
import IsfLink from '@/Components/Isf/IsfLink.vue';

const currentYear = new Date().getFullYear();

const navLinks = [
    { label: 'Tentang ISF', url: '/tentang-isf' },
    { label: 'Dewan Pakar', url: '/dewan-pakar' },
    { label: 'Event', url: '/event' },
    { label: 'Iklan & Kemitraan', url: '/media-kit' },
    { label: 'Kontak', url: '/kontak' },
];

const legalLinks = [
    { label: 'Syarat & Ketentuan', url: '/syarat-ketentuan' },
    { label: 'Privacy Policy', url: '/privacy-policy' },
    { label: 'Pedoman Media Siber', url: '/pedoman-media-siber' },
    { label: 'Cookie Policy', url: '/cookie-policy' },
];
</script>

<template>
    <footer
        class="mt-8 border-t border-neutral-800 bg-neutral-950 text-neutral-400"
    >
        <!-- Row 1: Main nav links -->
        <div
            class="isf-mobile-gutter mx-auto max-w-[1400px] px-4 py-4 lg:px-6"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-y-2"
            >
                <nav
                    aria-label="Footer utama"
                    class="flex flex-wrap items-center gap-x-0 text-[11px]"
                >
                    <template v-for="(link, idx) in navLinks" :key="idx">
                        <span v-if="idx > 0" class="text-neutral-700 select-none">|</span>
                        <IsfLink
                            :to="link.url"
                            class="px-2 py-1 text-neutral-400 hover:text-white transition-colors first:pl-0 inline-block"
                        >
                            {{ link.label }}
                        </IsfLink>
                    </template>
                </nav>
                <p class="text-[11px] text-neutral-500">
                    © {{ currentYear }} Indonesia Strategy Forum
                </p>
            </div>
        </div>

        <!-- Row 2: Legal links -->
        <div class="border-t border-neutral-800">
            <div
                class="isf-mobile-gutter mx-auto max-w-[1400px] px-4 py-3 lg:px-6"
            >
                <nav
                    aria-label="Footer legal"
                    class="flex flex-wrap items-center gap-x-0 text-[11px]"
                >
                    <template v-for="(link, idx) in legalLinks" :key="idx">
                        <span v-if="idx > 0" class="text-neutral-700 select-none">|</span>
                        <IsfLink
                            :to="link.url"
                            class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors first:pl-0 inline-block"
                        >
                            {{ link.label }}
                        </IsfLink>
                    </template>
                </nav>
            </div>
        </div>
    </footer>
</template>
