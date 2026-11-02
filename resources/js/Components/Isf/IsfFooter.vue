<script setup>
/**
 * Reusable editorial footer for ISF layouts.
 * Matches design across public and member portal pages.
 */
const currentYear = new Date().getFullYear();
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
                    <router-link
                        to="/tentang-isf"
                        class="px-2 py-1 text-neutral-400 hover:text-white transition-colors first:pl-0"
                    >
                        Tentang ISF
                    </router-link>
                    <span class="text-neutral-700 select-none">|</span>
                    <router-link
                        to="/dewan-pakar"
                        class="px-2 py-1 text-neutral-400 hover:text-white transition-colors"
                    >
                        Dewan Pakar
                    </router-link>
                    <span class="text-neutral-700 select-none">|</span>
                    <router-link
                        to="/event"
                        class="px-2 py-1 text-neutral-400 hover:text-white transition-colors"
                    >
                        Event
                    </router-link>
                    <span class="text-neutral-700 select-none">|</span>
                    <router-link
                        to="/kontak"
                        class="px-2 py-1 text-neutral-400 hover:text-white transition-colors"
                    >
                        Kontak
                    </router-link>
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
                    <router-link
                        to="/syarat-ketentuan"
                        class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors first:pl-0"
                    >
                        Syarat &amp; Ketentuan
                    </router-link>
                    <span class="text-neutral-700 select-none">|</span>
                    <router-link
                        to="/privacy-policy"
                        class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors"
                    >
                        Privacy Policy
                    </router-link>
                    <span class="text-neutral-700 select-none">|</span>
                    <router-link
                        to="/cookie-policy"
                        class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors"
                    >
                        Cookie Policy
                    </router-link>
                </nav>
            </div>
        </div>
    </footer>
</template>

