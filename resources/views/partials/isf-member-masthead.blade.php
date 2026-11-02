{{--
  Static Pre-rendered Masthead Header for ISF Member Portal (/anggota*).
  NOTE: Keep in sync with resources/js/Layouts/IsfMemberLayout.vue
--}}
<header class="border-b border-neutral-300 bg-white">
    <div class="mx-auto flex min-h-[4rem] sm:min-h-[4.5rem] lg:min-h-[5.5rem] w-full min-w-0 max-w-[1400px] items-center justify-between gap-3 px-4 py-3 sm:py-4 lg:px-6">
        <a href="/beranda" class="flex items-center justify-start gap-x-2 sm:gap-x-3 min-w-0">
            <img
                src="{{ asset('images/isf/logo-isf.jpeg') }}"
                alt="ISF Logo"
                class="isf-masthead-icon h-8 w-8 sm:h-10 sm:w-10 md:h-11 md:w-11 lg:h-12 lg:w-12 xl:h-[50px] xl:w-[50px] rounded-[6px] sm:rounded-[7px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                width="50"
                height="50"
            />
            <div class="flex flex-col items-start justify-center min-w-0">
                <span class="sr-only">ISF </span>
                <span class="isf-script text-neutral-950 text-[20px] sm:text-[32px] md:text-[36px] lg:text-[44px] xl:text-[50px] leading-tight sm:leading-none select-none whitespace-nowrap">Indonesia Strategy Forum</span>
                <span class="isf-byline mt-0.5 sm:mt-1 block text-left">Portal Anggota</span>
            </div>
        </a>
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <div class="inline-flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center text-neutral-700">
                <svg class="h-4 w-4 sm:h-5 sm:w-5" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                    <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"></path>
                    <path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
                </svg>
            </div>
        </div>
    </div>
</header>
