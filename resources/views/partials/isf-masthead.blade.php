{{--
  Pre-rendered Masthead Header for ISF Public Pages.
  Matches IsfHeader.vue 100% pixel-perfect to eliminate layout shifts, font discrepancies, and flickering on refresh.
--}}
<style>
/* ── Initial SSR state synced with localStorage data-isf-layout ──────────── */
#isf-ssr-row1, #isf-ssr-row3 { display: none !important; }
#isf-ssr-row2 { display: flex !important; }
#isf-ssr-spacer { height: 255px; }
.isf-ssr-masthead { padding-top: 1.5rem; padding-bottom: 0.75rem; }

html[data-isf-layout="single-row"] #isf-ssr-row1 { display: inline-flex !important; }
html[data-isf-layout="single-row"] #isf-ssr-row2 { display: none !important; }
html[data-isf-layout="single-row"] #isf-ssr-row3 { display: none !important; }
html[data-isf-layout="single-row"] #isf-ssr-spacer { height: 145px; }
html[data-isf-layout="single-row"] .isf-ssr-masthead { padding-top: 0.875rem; padding-bottom: 0.875rem; }

html[data-isf-layout="row-3"] #isf-ssr-row1 { display: none !important; }
html[data-isf-layout="row-3"] #isf-ssr-row2 { display: none !important; }
html[data-isf-layout="row-3"] #isf-ssr-row3 { display: inline-flex !important; }
html[data-isf-layout="row-3"] #isf-ssr-spacer { height: 130px; }
html[data-isf-layout="row-3"] .isf-ssr-masthead { padding-top: 0.875rem; padding-bottom: 0.875rem; }

/* ── isf-ssr-title-size: ukuran judul sejak paint pertama (salinan class Tailwind) ── */
/* Row 1: ISF text-[2.4rem] sm:text-[3rem] */
#isf-ssr-row1 > span:first-child { font-size: 2.4rem; }
/* Row 3: ISF & judul text-[1.3rem]/[11px] sm:[1.65rem] md:[2.1rem] xl:[2.6rem] */
#isf-ssr-row3 > span:first-child { font-size: 1.3rem; }
#isf-ssr-row3 > span:last-child { font-size: 11px; text-transform: uppercase; }
/* Row 2: ISF text-[4.5rem] sm:text-[6rem] md:text-[8rem] */
#isf-ssr-row2 > span:first-child { font-size: 4.5rem; }
@media (min-width: 640px) {
    #isf-ssr-row1 > span:first-child { font-size: 3rem; }
    #isf-ssr-row3 > span:first-child,
    #isf-ssr-row3 > span:last-child { font-size: 1.65rem; text-transform: none; }
    #isf-ssr-row2 > span:first-child { font-size: 6rem; }
}
@media (min-width: 768px) {
    #isf-ssr-row3 > span:first-child,
    #isf-ssr-row3 > span:last-child { font-size: 2.1rem; }
    #isf-ssr-row2 > span:first-child { font-size: 8rem; }
}
@media (min-width: 1280px) {
    #isf-ssr-row3 > span:first-child,
    #isf-ssr-row3 > span:last-child { font-size: 2.6rem; }
}
/* ── Prevent initial shell from hiding metadata & action buttons on desktop ─ */
.isf-ssr-side { display: none; }
@media (min-width: 1024px) {
    .isf-ssr-side { display: flex !important; }
}

.isf-ssr-login { display: none; }
@media (min-width: 640px) {
    .isf-ssr-login,
    .isf-initial-shell .isf-ssr-login { display: inline-block !important; }
}
</style>

<!-- Spacer div: ensures page content is never covered by fixed header -->
<div
    id="isf-ssr-spacer"
    aria-hidden="true"
    class="isf-header-spacer w-full shrink-0"
    style="overflow-anchor: none;"
></div>

<header class="isf-header fixed top-0 left-0 right-0 z-50 w-full bg-isf-navy text-white" style="position: fixed; top: 0; left: 0; right: 0; z-index: 50; background-color: #0B1220; color: #ffffff; overflow-anchor: none;">
    <!-- Masthead Row: Centered brand wordmark with left & right editorial widgets -->
    <div class="border-b border-transparent">
        <div class="isf-ssr-masthead w-full flex items-center justify-between px-4 sm:px-6 md:px-8 xl:px-12" style="display: flex; align-items: center; justify-content: space-between;">
            <!-- Sisi Kiri: Editorial Date & Location Metadata -->
            <div class="isf-ssr-side flex-col items-start justify-center min-w-[220px] xl:min-w-[260px] text-left font-space-grotesk select-none shrink-0" style="flex-direction: column; align-items: flex-start; text-align: left; min-width: 220px; font-family: 'Space Grotesk', sans-serif;">
                <span class="text-[11px] font-bold tracking-[0.12em] text-neutral-300 uppercase whitespace-nowrap" style="font-size: 11px; font-weight: 700; letter-spacing: 0.12em; color: #d4d4d4; text-transform: uppercase; white-space: nowrap;">
                    {{ strtoupper(\Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y')) }}
                </span>
                <span class="text-[10px] tracking-[0.08em] text-neutral-400 mt-0.5 whitespace-nowrap" style="font-size: 10px; letter-spacing: 0.08em; color: #a3a3a3; margin-top: 2px; white-space: nowrap;">
                    Jakarta, Indonesia • Edisi Digital
                </span>
            </div>

            <!-- Tengah: Centered brand wordmark -->
            <a href="/beranda" class="isf-wordmark group mx-auto flex items-center justify-center min-w-0 text-center leading-none no-underline" style="display: flex; align-items: center; justify-content: center; text-decoration: none; line-height: 1;">
                <!-- Opsi A: Row 1 (ISF di samping Indonesia Strategy Forum dengan garis divider halus) -->
                <div id="isf-ssr-row1" class="inline-flex items-center justify-center gap-x-3.5 sm:gap-x-4 py-1" style="align-items: center; justify-content: center;">
                    <span class="text-[2.4rem] sm:text-[3rem] font-bold text-isf-red font-space-grotesk leading-none whitespace-nowrap shrink-0" style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: #E10600; line-height: 1; user-select: none;">ISF</span>
                    <span aria-hidden="true" class="h-8 sm:h-9 w-px bg-white/20 shrink-0" style="height: 34px; width: 1px; background-color: rgba(255, 255, 255, 0.2); flex-shrink: 0;"></span>
                    <span class="text-left text-[17px] leading-[19px] tracking-[2px] font-bold text-white font-space-grotesk select-none shrink-0" style="font-family: 'Space Grotesk', sans-serif; font-size: 17px; line-height: 19px; letter-spacing: 2px; font-weight: 700; color: #ffffff; width: 156px; text-align: left; user-select: none;">Indonesia Strategy Forum</span>
                </div>

                <!-- Opsi C: Row 3 (ISF di samping Indonesia Strategy Forum inline seukuran ISF) -->
                <div id="isf-ssr-row3" class="inline-flex items-center justify-center gap-x-2.5 sm:gap-x-3 md:gap-x-3.5 xl:gap-x-4 py-0.5 sm:py-1 max-w-full select-none whitespace-nowrap" style="align-items: center; justify-content: center;">
                    <span class="text-[1.3rem] sm:text-[1.65rem] md:text-[2.1rem] xl:text-[2.6rem] font-bold text-isf-red font-space-grotesk leading-none shrink-0" style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: #E10600; line-height: 1; user-select: none;">ISF</span>
                    <span aria-hidden="true" class="h-3.5 sm:h-7 md:h-8 xl:h-9 w-px bg-white/25 sm:bg-white/20 shrink-0" style="width: 1px; background-color: rgba(255, 255, 255, 0.25); flex-shrink: 0;"></span>
                    <span class="text-[11px] sm:text-[1.65rem] md:text-[2.1rem] xl:text-[2.6rem] font-bold text-white font-space-grotesk leading-none uppercase sm:normal-case tracking-[1.5px] sm:tracking-[1px] md:tracking-[1.5px] xl:tracking-[2px] shrink-0" style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: #ffffff; line-height: 1; letter-spacing: 1.5px; white-space: nowrap; user-select: none;">Indonesia Strategy Forum</span>
                </div>

                <!-- Opsi B: Row 2 (ISF Anton 8rem di atas, Indonesia Strategy Forum di bawah) -->
                <div id="isf-ssr-row2" class="flex flex-col items-center justify-center text-center py-1 sm:py-2" style="flex-direction: column; align-items: center; justify-content: center;">
                    <span class="text-[4.5rem] sm:text-[6rem] md:text-[8rem] font-anton text-isf-red uppercase leading-none whitespace-nowrap" style="font-family: 'Anton', sans-serif; line-height: 1; color: #E10600; text-transform: uppercase; user-select: none;">ISF</span>
                    <span class="text-[14px] capitalize text-white font-space-grotesk font-medium text-center whitespace-nowrap -mt-1.5 tracking-[2px] leading-[27px]" style="font-family: 'Space Grotesk', sans-serif; font-size: 14px; text-transform: capitalize; color: #ffffff; font-weight: 500; margin-top: -6px; letter-spacing: 2px; line-height: 27px; white-space: nowrap; user-select: none;">Indonesia Strategy Forum</span>
                </div>
            </a>

            <!-- Sisi Kanan: Financial & Policy Index Indicator -->
            <div class="isf-ssr-side flex-col items-end justify-center min-w-[220px] xl:min-w-[260px] text-right font-space-grotesk select-none shrink-0" style="flex-direction: column; align-items: flex-end; text-align: right; min-width: 220px; font-family: 'Space Grotesk', sans-serif;">
                <div class="flex items-center gap-x-1.5 text-[11px] font-semibold text-neutral-300 tracking-[0.04em] whitespace-nowrap" style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #d4d4d4; white-space: nowrap;">
                    <span>IHSG 7.450 <span class="text-emerald-400 font-bold" style="color: #34d399; font-weight: 700;">(+0.3%)</span></span>
                    <span style="color: #737373;">•</span>
                    <span>USD/IDR 15.620</span>
                </div>
                <div class="flex items-center gap-x-1.5 text-[10px] tracking-[0.06em] text-neutral-400 mt-0.5 whitespace-nowrap" style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #a3a3a3; margin-top: 2px; white-space: nowrap;">
                    <span>Indeks Stabilitas Kebijakan:</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; color: #fbbf24;">
                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 9999px; background-color: #fbbf24;"></span>
                        Moderat
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Red Line (2px) -->
    <div class="h-[2px] w-full bg-isf-red shrink-0" style="height: 2px; width: 100%; background-color: #E10600;"></div>

    <!-- Navigation Menu Bar: 3 distinct groups (space-between) -->
    <div class="border-b border-neutral-800 bg-isf-navy" style="border-bottom: 1px solid #262626; background-color: #0B1220;">
        <div class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 md:px-8 xl:px-12 py-1.5" style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-top: 0.375rem; padding-bottom: 0.375rem; width: 100%;">
            <!-- Kelompok 1: Hamburger Menu (kiri) -->
            <div class="flex items-center justify-start shrink-0 lg:min-w-[220px]" style="display: flex; align-items: center; justify-content: flex-start; flex-shrink: 0;">
                <button type="button" aria-label="Buka menu" class="inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white" style="display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); background: transparent; color: #ffffff; cursor: pointer;">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
            </div>

            <!-- Kelompok 2: Nav Menu Links (tengah terpusat, dengan jarak natural) -->
            <nav aria-label="Navigasi utama" class="isf-no-scrollbar min-w-0 flex-1 overflow-x-auto overflow-y-hidden px-2 lg:overflow-visible flex items-center justify-center" style="display: flex; align-items: center; justify-content: center; flex: 1 1 0%; min-width: 0; overflow-x: auto; padding: 0 0.5rem;">
                <ul class="flex items-center gap-x-4 sm:gap-x-5 xl:gap-x-6 py-1 font-space-grotesk text-[12px] sm:text-[13px] font-semibold xl:text-[14px] whitespace-nowrap" style="display: flex; align-items: center; padding: 0.25rem 0; margin: 0; list-style: none; font-family: 'Space Grotesk', sans-serif; font-weight: 600; white-space: nowrap; color: #d4d4d4;">
                    <li class="shrink-0"><a href="/beranda" class="inline-block whitespace-nowrap py-1 border-b-2 !border-isf-red !text-white font-bold" style="color: #ffffff; text-decoration: none; border-bottom: 2px solid #E10600; padding-top: 4px; padding-bottom: 4px; font-weight: 700;">Beranda</a></li>
                    <li class="shrink-0"><a href="#" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Politik</a></li>
                    <li class="shrink-0"><a href="#" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Hukum</a></li>
                    <li class="shrink-0"><a href="#" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Kebijakan Publik</a></li>
                    <li class="shrink-0"><a href="/tokoh/budi-santoso" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Sosok</a></li>
                    <li class="shrink-0"><a href="#" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Opini Publik</a></li>
                    <li class="shrink-0"><a href="#" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Survei</a></li>
                    <li aria-hidden="true" class="mx-1 h-3.5 w-px bg-neutral-700 shrink-0" style="width: 1px; height: 14px; background-color: #404040; margin: 0 4px;"></li>
                    <li class="shrink-0"><a href="/anggota" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">Portal Anggota</a></li>
                </ul>
            </nav>

            <!-- Kelompok 3: Login, Search (kanan) -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0 justify-end lg:min-w-[220px]" style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;">
                <a href="/login" class="isf-ssr-login whitespace-nowrap font-space-grotesk text-[11px] md:text-[12px] font-bold uppercase tracking-[0.1em] rounded-md border border-white/30 px-2.5 py-1 text-white sm:inline shadow-2xs" style="font-family: 'Space Grotesk', sans-serif; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 10px; color: #ffffff; text-decoration: none;">Login</a>
                <button type="button" aria-label="Buka pencarian" class="inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white" style="display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); background: transparent; color: #ffffff; cursor: pointer;">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="6.5" />
                        <path d="m16 16 4.5 4.5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>
