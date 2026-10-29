{{--
  Static Pre-rendered Masthead Header for ISF Member Portal (/anggota*).
  NOTE: Keep in sync with resources/js/Layouts/IsfMemberLayout.vue
--}}
<header class="border-b border-neutral-300 bg-white">
    <div class="mx-auto grid min-h-[4.5rem] w-full min-w-0 max-w-[1400px] grid-cols-[minmax(4.5rem,1fr)_auto_minmax(4.5rem,1fr)] items-center gap-3 px-4 py-3 sm:py-4 lg:px-6">
        <span aria-hidden="true"></span>
        <a href="/beranda" class="block min-w-0 text-center">
            <span class="isf-wordmark mx-auto flex items-center justify-center min-w-0 text-center leading-none">
                <span class="inline-flex items-stretch justify-center gap-x-2 sm:gap-x-2.5">
                    <img
                        src="{{ asset('images/isf/logo-isf.jpeg') }}"
                        alt="ISF Logo"
                        class="isf-masthead-icon w-auto self-stretch rounded-[5px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                        width="38"
                        height="38"
                    />
                    <span class="sr-only">ISF </span>
                    <span class="isf-script text-neutral-950 text-[26px] sm:text-[32px] lg:text-[38px] leading-none">Indonesia Strategy Forum</span>
                </span>
            </span>
            <span class="isf-byline mt-1 block">Portal Anggota</span>
        </a>
        <div class="flex items-center gap-2 justify-self-end">
            <div class="inline-flex h-8 w-8 items-center justify-center text-neutral-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"></path>
                    <path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
                </svg>
            </div>
        </div>
    </div>
</header>
