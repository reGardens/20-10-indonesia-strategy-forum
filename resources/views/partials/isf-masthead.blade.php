{{--
  Static Pre-rendered Masthead Header for ISF Public Pages.
  NOTE: Keep in sync with resources/js/Layouts/IsfPublicLayout.vue
--}}
<header class="bg-white text-neutral-900">
    <div class="border-b border-neutral-300">
        <div class="mx-auto grid w-full max-w-[1400px] grid-cols-[minmax(2.5rem,1fr)_auto_minmax(2.5rem,1fr)] items-center gap-3 px-4 py-3 sm:py-4 lg:px-6 lg:py-6">
            <div class="justify-self-start">
                <div class="inline-flex h-10 w-10 items-center justify-center text-neutral-900">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </div>
            </div>

            <a href="/beranda" class="isf-wordmark mx-auto flex items-center justify-center min-w-0 text-center leading-none">
                <span class="inline-flex items-stretch justify-center gap-x-2.5 sm:gap-x-3.5">
                    <img
                        src="{{ asset('images/isf/logo-isf.jpeg') }}"
                        alt="ISF Logo"
                        class="isf-masthead-icon w-auto self-stretch rounded-[7px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                        width="50"
                        height="50"
                    />
                    <span class="sr-only">ISF </span>
                    <span class="isf-script text-neutral-950 text-[30px] sm:text-[38px] lg:text-[46px] xl:text-[50px] leading-none">
                        Indonesia Strategy Forum
                    </span>
                </span>
            </a>

            <div class="flex items-center gap-4 justify-self-end">
                <span class="hidden whitespace-nowrap font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-900 lg:inline">
                    Portal Anggota
                </span>
                <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center text-neutral-900">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" />
                        <path d="m16 16 4.5 4.5" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav row -->
    <div class="border-b border-neutral-300">
        <nav aria-label="Navigasi utama" class="isf-no-scrollbar mx-auto max-w-[1400px] overflow-x-auto overflow-y-hidden px-4 lg:overflow-visible lg:px-6">
            <ul class="mx-auto flex w-max items-center gap-x-4 py-2.5 font-ui text-[13px] font-normal xl:gap-x-5 xl:text-[15px]">
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Beranda</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Politik</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Hukum</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Kebijakan Publik</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Sosok</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Opini Publik</span></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Survei</span></li>
                <li aria-hidden="true" class="mx-1 h-4 w-px bg-neutral-400"></li>
                <li><span class="inline-block whitespace-nowrap py-0.5 text-neutral-900">Anatomi Isu</span></li>
            </ul>
        </nav>
    </div>
</header>
