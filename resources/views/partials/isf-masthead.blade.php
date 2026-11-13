{{--
  Pre-rendered Masthead Header for ISF Public Pages.
  Matches IsfHeader.vue 100% pixel-perfect to eliminate layout shifts, font discrepancies, and flickering on refresh.
--}}
@php
    // = isMemberRoute di IsfHeader.vue: /anggota* menampilkan label "Portal Anggota" + notifikasi & avatar (bukan Login).
    $isfMember = request()->is('anggota*');
    // = isLoggedIn di IsfHeader.vue (GET /api/isf/me): user login → avatar + nama + Keluar di semua halaman.
    $isfAuthUser = auth()->user();
    $isfShowMemberArea = $isfMember || $isfAuthUser;
    $isfMemberName = 'Anindya Putri'; // fallback yang sama dengan IsfHeader.vue
    if ($isfAuthUser && trim((string) $isfAuthUser->name) !== '') {
        $isfMemberName = (string) $isfAuthUser->name;
    } elseif ($isfMember) {
        try {
            $isfMemberName = (\App\Models\Isf\Setting::get('anggota', [])['name'] ?? '') ?: $isfMemberName;
        } catch (\Throwable $e) {
            // shell tetap tampil walau DB tidak tersedia
        }
    }
    // = memberInitials di IsfHeader.vue
    $isfMemberInitials = collect(preg_split('/\s+/', trim($isfMemberName)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'AP';
@endphp
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

/*
 * ── Satu sumber style header shell ─────────────────────────────────────────
 * CSS di bawah ini DIHASILKAN Tailwind dari class yang dipakai partial ini (theme proyek
 * yang sama), dibatasi ke .isf-initial-shell, agar header shell sudah benar sejak paint
 * pertama tanpa menunggu CSS dari Vite (npm). Nilainya identik dengan class Tailwind.
 * Jika class di partial ini diubah, hasilkan ulang:
 *   npx tailwindcss -c .agents/tasks/issue-40/shell.tailwind.config.mjs \
 *     -i .agents/tasks/issue-40/shell.in.css -o shell.out.css --minify
 */
.isf-initial-shell .fixed{position:fixed}.isf-initial-shell .left-0{left:0}.isf-initial-shell .right-0{right:0}.isf-initial-shell .top-0{top:0}.isf-initial-shell .z-50{z-index:50}.isf-initial-shell .mx-1{margin-left:.25rem;margin-right:.25rem}.isf-initial-shell .mx-auto{margin-left:auto;margin-right:auto}.isf-initial-shell .-mt-1{margin-top:-.25rem}.isf-initial-shell .-mt-1\.5{margin-top:-.375rem}.isf-initial-shell .mt-0{margin-top:0}.isf-initial-shell .mt-0\.5{margin-top:.125rem}.isf-initial-shell .block{display:block}.isf-initial-shell .inline-block{display:inline-block}.isf-initial-shell .inline{display:inline}.isf-initial-shell .flex{display:flex}.isf-initial-shell .inline-flex{display:inline-flex}.isf-initial-shell .hidden{display:none}.isf-initial-shell .h-3{height:.75rem}.isf-initial-shell .h-3\.5{height:.875rem}.isf-initial-shell .h-4{height:1rem}.isf-initial-shell .h-7{height:1.75rem}.isf-initial-shell .h-8{height:2rem}.isf-initial-shell .h-\[2px\]{height:2px}.isf-initial-shell .w-4{width:1rem}.isf-initial-shell .w-7{width:1.75rem}.isf-initial-shell .w-8{width:2rem}.isf-initial-shell .w-full{width:100%}.isf-initial-shell .w-px{width:1px}.isf-initial-shell .min-w-0{min-width:0}.isf-initial-shell .min-w-\[220px\]{min-width:220px}.isf-initial-shell .max-w-\[120px\]{max-width:120px}.isf-initial-shell .max-w-full{max-width:100%}.isf-initial-shell .flex-1{flex:1 1 0%}.isf-initial-shell .flex-shrink{flex-shrink:1}.isf-initial-shell .shrink-0{flex-shrink:0}.isf-initial-shell .select-none{-webkit-user-select:none;-moz-user-select:none;user-select:none}.isf-initial-shell .flex-col{flex-direction:column}.isf-initial-shell .items-start{align-items:flex-start}.isf-initial-shell .items-end{align-items:flex-end}.isf-initial-shell .items-center{align-items:center}.isf-initial-shell .justify-start{justify-content:flex-start}.isf-initial-shell .justify-end{justify-content:flex-end}.isf-initial-shell .justify-center{justify-content:center}.isf-initial-shell .justify-between{justify-content:space-between}.isf-initial-shell .gap-2{gap:.5rem}.isf-initial-shell .gap-3{gap:.75rem}.isf-initial-shell .gap-x-1{-moz-column-gap:.25rem;column-gap:.25rem}.isf-initial-shell .gap-x-1\.5{-moz-column-gap:.375rem;column-gap:.375rem}.isf-initial-shell .gap-x-2{-moz-column-gap:.5rem;column-gap:.5rem}.isf-initial-shell .gap-x-2\.5{-moz-column-gap:.625rem;column-gap:.625rem}.isf-initial-shell .gap-x-3{-moz-column-gap:.75rem;column-gap:.75rem}.isf-initial-shell .gap-x-3\.5{-moz-column-gap:.875rem;column-gap:.875rem}.isf-initial-shell .gap-x-4{-moz-column-gap:1rem;column-gap:1rem}.isf-initial-shell .overflow-x-auto{overflow-x:auto}.isf-initial-shell .overflow-y-hidden{overflow-y:hidden}.isf-initial-shell .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.isf-initial-shell .whitespace-nowrap{white-space:nowrap}.isf-initial-shell .rounded-md{border-radius:calc(var(--radius) - 2px)}.isf-initial-shell .border{border-width:1px}.isf-initial-shell .border-b{border-bottom-width:1px}.isf-initial-shell .border-b-2{border-bottom-width:2px}.isf-initial-shell .\!border-isf-red{--tw-border-opacity:1!important;border-color:rgb(225 6 0/var(--tw-border-opacity,1))!important}.isf-initial-shell .border-neutral-800{--tw-border-opacity:1;border-color:rgb(38 38 38/var(--tw-border-opacity,1))}.isf-initial-shell .border-transparent{border-color:transparent}.isf-initial-shell .border-white{--tw-border-opacity:1;border-color:rgb(255 255 255/var(--tw-border-opacity,1))}.isf-initial-shell .border-white\/10{border-color:hsla(0,0%,100%,.1)}.isf-initial-shell .border-white\/30{border-color:hsla(0,0%,100%,.3)}.isf-initial-shell .bg-isf-navy{--tw-bg-opacity:1;background-color:rgb(11 18 32/var(--tw-bg-opacity,1))}.isf-initial-shell .bg-isf-red{--tw-bg-opacity:1;background-color:rgb(225 6 0/var(--tw-bg-opacity,1))}.isf-initial-shell .bg-neutral-700{--tw-bg-opacity:1;background-color:rgb(64 64 64/var(--tw-bg-opacity,1))}.isf-initial-shell .bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255/var(--tw-bg-opacity,1))}.isf-initial-shell .bg-white\/20{background-color:hsla(0,0%,100%,.2)}.isf-initial-shell .bg-white\/25{background-color:hsla(0,0%,100%,.25)}.isf-initial-shell .px-2{padding-left:.5rem;padding-right:.5rem}.isf-initial-shell .px-2\.5{padding-left:.625rem;padding-right:.625rem}.isf-initial-shell .px-4{padding-left:1rem;padding-right:1rem}.isf-initial-shell .py-0{padding-top:0;padding-bottom:0}.isf-initial-shell .py-0\.5{padding-top:.125rem;padding-bottom:.125rem}.isf-initial-shell .py-1{padding-top:.25rem;padding-bottom:.25rem}.isf-initial-shell .py-1\.5{padding-top:.375rem;padding-bottom:.375rem}.isf-initial-shell .py-2{padding-top:.5rem;padding-bottom:.5rem}.isf-initial-shell .text-left{text-align:left}.isf-initial-shell .text-center{text-align:center}.isf-initial-shell .text-right{text-align:right}.isf-initial-shell .font-anton{font-family:Anton,sans-serif}.isf-initial-shell .font-space-grotesk{font-family:Space Grotesk,sans-serif}.isf-initial-shell .text-\[1\.3rem\]{font-size:1.3rem}.isf-initial-shell .text-\[10px\]{font-size:10px}.isf-initial-shell .text-\[11px\]{font-size:11px}.isf-initial-shell .text-\[12px\]{font-size:12px}.isf-initial-shell .text-\[14px\]{font-size:14px}.isf-initial-shell .text-\[17px\]{font-size:17px}.isf-initial-shell .text-\[2\.4rem\]{font-size:2.4rem}.isf-initial-shell .text-\[4\.5rem\]{font-size:4.5rem}.isf-initial-shell .text-\[9px\]{font-size:9px}.isf-initial-shell .font-bold{font-weight:700}.isf-initial-shell .font-medium{font-weight:500}.isf-initial-shell .font-semibold{font-weight:600}.isf-initial-shell .uppercase{text-transform:uppercase}.isf-initial-shell .capitalize{text-transform:capitalize}.isf-initial-shell .leading-\[19px\]{line-height:19px}.isf-initial-shell .leading-\[27px\]{line-height:27px}.isf-initial-shell .leading-none{line-height:1}.isf-initial-shell .tracking-\[0\.04em\]{letter-spacing:.04em}.isf-initial-shell .tracking-\[0\.06em\]{letter-spacing:.06em}.isf-initial-shell .tracking-\[0\.08em\]{letter-spacing:.08em}.isf-initial-shell .tracking-\[0\.12em\]{letter-spacing:.12em}.isf-initial-shell .tracking-\[0\.16em\]{letter-spacing:.16em}.isf-initial-shell .tracking-\[0\.1em\]{letter-spacing:.1em}.isf-initial-shell .tracking-\[0\.2em\]{letter-spacing:.2em}.isf-initial-shell .tracking-\[1\.5px\]{letter-spacing:1.5px}.isf-initial-shell .tracking-\[2px\]{letter-spacing:2px}.isf-initial-shell .tracking-wider{letter-spacing:.05em}.isf-initial-shell .\!text-white{--tw-text-opacity:1!important;color:rgb(255 255 255/var(--tw-text-opacity,1))!important}.isf-initial-shell .text-emerald-400{--tw-text-opacity:1;color:rgb(52 211 153/var(--tw-text-opacity,1))}.isf-initial-shell .text-isf-red{--tw-text-opacity:1;color:rgb(225 6 0/var(--tw-text-opacity,1))}.isf-initial-shell .text-neutral-200{--tw-text-opacity:1;color:rgb(229 229 229/var(--tw-text-opacity,1))}.isf-initial-shell .text-neutral-300{--tw-text-opacity:1;color:rgb(212 212 212/var(--tw-text-opacity,1))}.isf-initial-shell .text-neutral-400{--tw-text-opacity:1;color:rgb(163 163 163/var(--tw-text-opacity,1))}.isf-initial-shell .text-white{--tw-text-opacity:1;color:rgb(255 255 255/var(--tw-text-opacity,1))}.isf-initial-shell .no-underline{text-decoration-line:none}.isf-initial-shell .antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.isf-initial-shell .grayscale{--tw-grayscale:grayscale(100%)}.isf-initial-shell .filter,.isf-initial-shell .grayscale{filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}@keyframes enter{0%{opacity:var(--tw-enter-opacity,1);transform:translate3d(var(--tw-enter-translate-x,0),var(--tw-enter-translate-y,0),0) scale3d(var(--tw-enter-scale,1),var(--tw-enter-scale,1),var(--tw-enter-scale,1)) rotate(var(--tw-enter-rotate,0))}}@keyframes exit{to{opacity:var(--tw-exit-opacity,1);transform:translate3d(var(--tw-exit-translate-x,0),var(--tw-exit-translate-y,0),0) scale3d(var(--tw-exit-scale,1),var(--tw-exit-scale,1),var(--tw-exit-scale,1)) rotate(var(--tw-exit-rotate,0))}}@media (min-width:640px){.isf-initial-shell .sm\:inline{display:inline}.isf-initial-shell .sm\:h-5{height:1.25rem}.isf-initial-shell .sm\:h-7{height:1.75rem}.isf-initial-shell .sm\:h-8{height:2rem}.isf-initial-shell .sm\:h-9{height:2.25rem}.isf-initial-shell .sm\:w-5{width:1.25rem}.isf-initial-shell .sm\:w-8{width:2rem}.isf-initial-shell .sm\:w-9{width:2.25rem}.isf-initial-shell .sm\:gap-3{gap:.75rem}.isf-initial-shell .sm\:gap-x-3{-moz-column-gap:.75rem;column-gap:.75rem}.isf-initial-shell .sm\:gap-x-4{-moz-column-gap:1rem;column-gap:1rem}.isf-initial-shell .sm\:gap-x-5{-moz-column-gap:1.25rem;column-gap:1.25rem}.isf-initial-shell .sm\:bg-white\/20{background-color:hsla(0,0%,100%,.2)}.isf-initial-shell .sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.isf-initial-shell .sm\:py-1{padding-top:.25rem;padding-bottom:.25rem}.isf-initial-shell .sm\:py-2{padding-top:.5rem;padding-bottom:.5rem}.isf-initial-shell .sm\:text-\[1\.65rem\]{font-size:1.65rem}.isf-initial-shell .sm\:text-\[13px\]{font-size:13px}.isf-initial-shell .sm\:text-\[3rem\]{font-size:3rem}.isf-initial-shell .sm\:text-\[6rem\]{font-size:6rem}.isf-initial-shell .sm\:text-xs{font-size:.75rem;line-height:1rem}.isf-initial-shell .sm\:normal-case{text-transform:none}.isf-initial-shell .sm\:tracking-\[1px\]{letter-spacing:1px}}@media (min-width:768px){.isf-initial-shell .md\:h-8{height:2rem}.isf-initial-shell .md\:gap-x-3\.5{-moz-column-gap:.875rem;column-gap:.875rem}.isf-initial-shell .md\:px-8{padding-left:2rem;padding-right:2rem}.isf-initial-shell .md\:text-\[12px\]{font-size:12px}.isf-initial-shell .md\:text-\[2\.1rem\]{font-size:2.1rem}.isf-initial-shell .md\:text-\[8rem\]{font-size:8rem}.isf-initial-shell .md\:tracking-\[1\.5px\]{letter-spacing:1.5px}}@media (min-width:1024px){.isf-initial-shell .lg\:min-w-\[220px\]{min-width:220px}.isf-initial-shell .lg\:overflow-visible{overflow:visible}}@media (min-width:1280px){.isf-initial-shell .xl\:h-9{height:2.25rem}.isf-initial-shell .xl\:min-w-\[260px\]{min-width:260px}.isf-initial-shell .xl\:gap-x-4{-moz-column-gap:1rem;column-gap:1rem}.isf-initial-shell .xl\:gap-x-6{-moz-column-gap:1.5rem;column-gap:1.5rem}.isf-initial-shell .xl\:px-12{padding-left:3rem;padding-right:3rem}.isf-initial-shell .xl\:text-\[14px\]{font-size:14px}.isf-initial-shell .xl\:text-\[2\.6rem\]{font-size:2.6rem}.isf-initial-shell .xl\:tracking-\[2px\]{letter-spacing:2px}}
/* ── isf-shell-preflight: salinan preflight Tailwind (nilai dari CSS build proyek), hanya untuk header shell ── */
.isf-initial-shell header,
.isf-initial-shell header *,
.isf-initial-shell header ::before,
.isf-initial-shell header ::after { box-sizing: border-box; border-width: 0; border-style: solid; border-color: #e5e7eb; }
.isf-initial-shell header { line-height: 1.5; -webkit-text-size-adjust: 100%; tab-size: 4; -webkit-tap-highlight-color: transparent; }
/* = class "antialiased" Tailwind pada <body> (belum berlaku sebelum CSS Vite termuat) */
.isf-initial-shell header { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
/* = .isf-no-scrollbar di resources/js/assets/isf-theme.css */
.isf-initial-shell .isf-no-scrollbar { scrollbar-width: none; }
.isf-initial-shell .isf-no-scrollbar::-webkit-scrollbar { display: none; }
.isf-initial-shell header a { color: inherit; text-decoration: inherit; }
/* = aturan wrap global :where(.isf-theme) di resources/js/assets/isf-theme.css (header Vue berada di dalam .isf-theme) */
:where(.isf-initial-shell) :where(h1, h2, h3, h4, p, a, li, td, th) { overflow-wrap: anywhere; word-break: break-word; max-width: 100%; padding-right: 1px; }
.isf-initial-shell header button { font-family: inherit; font-feature-settings: inherit; font-variation-settings: inherit; font-size: 100%; font-weight: inherit; line-height: inherit; letter-spacing: inherit; color: inherit; margin: 0; padding: 0; text-transform: none; -webkit-appearance: button; background-color: transparent; background-image: none; cursor: pointer; }
.isf-initial-shell header ul { list-style: none; margin: 0; padding: 0; }
.isf-initial-shell header svg { display: block; vertical-align: middle; }
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
    <!-- Wrapper tanpa border: header Vue tidak punya garis 1px di sini (tinggi shell harus = spacer). -->
    <div>
        <div class="isf-ssr-masthead w-full flex items-center justify-between px-4 sm:px-6 md:px-8 xl:px-12" style="display: flex; align-items: center; justify-content: space-between;">
            <!-- Sisi Kiri: Editorial Date & Location Metadata -->
            <div class="isf-ssr-side flex-col items-start justify-center min-w-[220px] xl:min-w-[260px] text-left font-space-grotesk select-none shrink-0" style="flex-direction: column; align-items: flex-start; text-align: left; font-family: 'Space Grotesk', sans-serif;">
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
                    <div class="flex flex-col items-start justify-center">
                        <span class="text-left text-[17px] leading-[19px] tracking-[2px] font-bold text-white font-space-grotesk select-none shrink-0" style="font-family: 'Space Grotesk', sans-serif; font-size: 17px; line-height: 19px; letter-spacing: 2px; font-weight: 700; color: #ffffff; width: 156px; text-align: left; user-select: none;">Indonesia Strategy Forum</span>
                        @if ($isfMember)
                            <span class="text-[9px] font-bold uppercase tracking-[0.16em] text-neutral-400 font-space-grotesk mt-0.5 select-none">Portal Anggota</span>
                        @endif
                    </div>
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
                    @if ($isfMember)
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-400 font-space-grotesk mt-0.5 select-none">Portal Anggota</span>
                    @endif
                </div>
            </a>

            <!-- Sisi Kanan: Financial & Policy Index Indicator -->
            <div class="isf-ssr-side flex-col items-end justify-center min-w-[220px] xl:min-w-[260px] text-right font-space-grotesk select-none shrink-0" style="flex-direction: column; align-items: flex-end; text-align: right; font-family: 'Space Grotesk', sans-serif;">
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
            <nav aria-label="Navigasi utama" class="isf-no-scrollbar min-w-0 flex-1 overflow-x-auto overflow-y-hidden px-2 lg:overflow-visible flex items-center justify-center" style="display: flex; align-items: center; justify-content: center; flex: 1 1 0%; min-width: 0; padding: 0 0.5rem;">
                <ul class="flex items-center gap-x-4 sm:gap-x-5 xl:gap-x-6 py-1 font-space-grotesk text-[12px] sm:text-[13px] font-semibold xl:text-[14px] whitespace-nowrap" style="display: flex; align-items: center; padding: 0.25rem 0; margin: 0; list-style: none; font-family: 'Space Grotesk', sans-serif; font-weight: 600; white-space: nowrap; color: #d4d4d4;">
                    {{-- = NAV_LINKS di IsfHeader.vue; link aktif mengikuti path (rute Vue datar, jadi cocok persis = router-link-active). --}}
                    @php
                        $isfPath = '/' . trim(request()->path(), '/');
                        if ($isfPath === '/') { $isfPath = '/beranda'; }
                        $isfNavGroups = [
                            [['Beranda', '/beranda'], ['Politik', '#'], ['Hukum', '#'], ['Kebijakan Publik', '#'], ['Sosok', '/tokoh/budi-santoso'], ['Opini Publik', '#'], ['Survei', '#']],
                            [['Portal Anggota', '/anggota']],
                        ];
                    @endphp
                    @foreach ($isfNavGroups as $isfGroupIndex => $isfGroup)
                        @if ($isfGroupIndex > 0)
                    <li aria-hidden="true" class="mx-1 h-3.5 w-px bg-neutral-700 shrink-0" style="width: 1px; height: 14px; background-color: #404040; margin: 0 4px;"></li>
                        @endif
                        @foreach ($isfGroup as [$isfLabel, $isfTo])
                            @if ($isfTo !== '#' && $isfTo === $isfPath)
                    <li class="shrink-0"><a href="{{ $isfTo }}" class="inline-block whitespace-nowrap py-1 border-b-2 !border-isf-red !text-white font-bold" style="color: #ffffff; text-decoration: none; border-bottom: 2px solid #E10600; padding-top: 4px; padding-bottom: 4px; font-weight: 700;">{{ $isfLabel }}</a></li>
                            @else
                    <li class="shrink-0"><a href="{{ $isfTo }}" class="inline-block whitespace-nowrap py-1 border-b-2 border-transparent text-neutral-300" style="color: #d4d4d4; text-decoration: none; padding-top: 4px; padding-bottom: 4px;">{{ $isfLabel }}</a></li>
                            @endif
                        @endforeach
                    @endforeach
                </ul>
            </nav>

            <!-- Kelompok 3: Login, Search (kanan) -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0 justify-end lg:min-w-[220px]" style="display: flex; align-items: center; justify-content: flex-end; flex-shrink: 0;">
                @if ($isfShowMemberArea)
                <button type="button" aria-label="Notifikasi" title="Notifikasi" class="inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                        <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                        <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                    </svg>
                </button>
                @if ($isfAuthUser)
                {{-- = pemicu IsfProfileMenu (menu nama + Keluar dibuka oleh Vue) --}}
                <button type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Menu akun {{ $isfMemberName }}" title="{{ $isfMemberName }}" class="inline-flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-xs bg-isf-red text-[11px] sm:text-xs font-bold text-white tracking-wider shadow-xs">{{ $isfMemberInitials }}</button>
                @else
                <a href="/anggota" class="flex items-center gap-2 select-none group" title="Portal Anggota">
                    <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-xs bg-isf-red text-[11px] sm:text-xs font-bold text-white tracking-wider shadow-xs" aria-hidden="true">{{ $isfMemberInitials }}</span>
                    <span class="hidden max-w-[120px] truncate text-[12px] font-space-grotesk font-semibold text-neutral-200 sm:inline">{{ $isfMemberName }}</span>
                </a>
                @endif
                @else
                <a href="/login" class="isf-ssr-login whitespace-nowrap font-space-grotesk text-[11px] md:text-[12px] font-bold uppercase tracking-[0.1em] rounded-md border border-white/30 px-2.5 py-1 text-white sm:inline shadow-2xs" style="font-family: 'Space Grotesk', sans-serif; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 10px; color: #ffffff; text-decoration: none;">Login</a>
                @endif
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
