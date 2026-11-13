<script setup>
/**
 * IsfProfileMenu — tombol avatar (inisial) yang membuka menu akun: nama + Keluar.
 * Pola sama dengan dropdown profil di CmsLayout, gaya mengikuti header ISF (panel navy, aksen merah).
 *
 * <IsfProfileMenu name="Rina Kartika" initials="RK" :logging-out="false" @logout="..." />
 *
 * Menu di-Teleport ke <body> dengan posisi fixed di bawah tombol, karena baris navigasi header
 * memakai overflow:hidden + max-height (animasi collapse): bila menu berada di dalamnya, menu terpotong
 * dan memfokuskan item menu menggulir isi baris nav sehingga link navigasi "hilang".
 *
 * Aksesibilitas: aria-haspopup/aria-expanded, Esc & klik di luar menutup menu,
 * fokus pindah ke item pertama saat dibuka (tanpa scroll) dan kembali ke tombol saat ditutup.
 */
import { nextTick, onBeforeUnmount, onMounted, ref, useId } from "vue";

defineProps({
    name: { type: String, default: "" },
    initials: { type: String, default: "" },
    loggingOut: { type: Boolean, default: false },
    /** Issue #42: tampilkan alert "perbarui Nomor WhatsApp" bila nomor kosong / belum terverifikasi. */
    whatsappStatus: {
        type: String,
        default: "verified",
        validator: (v) => ["verified", "unverified", "missing"].includes(v),
    },
});
const emit = defineEmits(["logout", "verify-whatsapp"]);

function onVerifyWhatsApp() {
    setOpen(false);
    emit("verify-whatsapp");
}

const open = ref(false);
const triggerEl = ref(null);
const menuEl = ref(null);
const firstItemEl = ref(null);
const position = ref({ top: 0, right: 0 });
const menuId = `isf-profile-menu-${useId()}`;

function updatePosition() {
    const rect = triggerEl.value?.getBoundingClientRect();
    if (!rect) return;
    // clientWidth (tanpa scrollbar) agar tepi kanan menu sejajar dengan tepi kanan tombol.
    const viewportWidth =
        document.documentElement.clientWidth || window.innerWidth;
    position.value = {
        top: Math.round(rect.bottom + 8),
        right: Math.max(8, Math.round(viewportWidth - rect.right)),
    };
}

function setOpen(value, { focusTrigger = false } = {}) {
    if (value) updatePosition();
    open.value = value;
    if (value)
        nextTick(() => firstItemEl.value?.focus({ preventScroll: true }));
    else if (focusTrigger)
        nextTick(() => triggerEl.value?.focus({ preventScroll: true }));
}

function onDocumentClick(event) {
    if (!open.value) return;
    const target = event.target;
    if (triggerEl.value?.contains(target) || menuEl.value?.contains(target))
        return;
    setOpen(false);
}

function onKeydown(event) {
    if (event.key === "Escape" && open.value) {
        event.stopPropagation();
        setOpen(false, { focusTrigger: true });
    }
}

// Header berubah ukuran saat discroll (nav collapse) → tutup menu daripada melayang di posisi lama.
function onViewportChange() {
    if (open.value) setOpen(false);
}

onMounted(() => {
    document.addEventListener("click", onDocumentClick);
    window.addEventListener("scroll", onViewportChange, { passive: true });
    window.addEventListener("resize", onViewportChange);
});
onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick);
    window.removeEventListener("scroll", onViewportChange);
    window.removeEventListener("resize", onViewportChange);
});

const focusRing =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2 focus-visible:ring-offset-isf-navy";
</script>

<template>
    <button
        ref="triggerEl"
        type="button"
        aria-haspopup="menu"
        :aria-expanded="open ? 'true' : 'false'"
        :aria-controls="menuId"
        :aria-label="`Menu akun ${name}`.trim()"
        :title="name || 'Akun'"
        data-testid="profile-menu-trigger"
        :class="[
            'inline-flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-xs bg-isf-red text-[11px] sm:text-xs font-bold text-white tracking-wider shadow-xs transition-colors hover:bg-isf-red-dark cursor-pointer',
            open
                ? 'ring-2 ring-white/40 ring-offset-2 ring-offset-isf-navy'
                : '',
            focusRing,
        ]"
        @click="setOpen(!open)"
        @keydown="onKeydown"
    >
        {{ initials }}
    </button>

    <Teleport to="body">
        <div
            v-show="open"
            :id="menuId"
            ref="menuEl"
            role="menu"
            :aria-label="`Akun ${name}`.trim()"
            data-testid="profile-menu"
            class="isf-profile-menu fixed z-[70] w-64 border border-white/10 border-t-2 border-t-isf-red bg-isf-navy text-left text-white shadow-2xl"
            :style="{ top: `${position.top}px`, right: `${position.right}px` }"
            @keydown="onKeydown"
        >
            <div
                class="flex items-center gap-3 border-b border-white/10 px-4 py-3.5"
                role="none"
            >
                <span
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xs bg-isf-red font-space-grotesk text-xs font-bold tracking-wider text-white"
                    aria-hidden="true"
                    >{{ initials }}</span
                >
                <div class="min-w-0">
                    <p
                        class="font-space-grotesk text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-400"
                    >
                        Masuk sebagai
                    </p>
                    <p
                        class="mt-0.5 truncate font-space-grotesk text-sm font-semibold text-white"
                        :title="name"
                        data-testid="profile-menu-name"
                    >
                        {{ name }}
                    </p>
                </div>
            </div>
            <!-- Issue #42: alert Nomor WhatsApp (kosong / belum terverifikasi) -->
            <div
                v-if="whatsappStatus !== 'verified'"
                class="border-b border-white/10 px-4 py-3"
                role="none"
                data-testid="profile-menu-wa-alert"
            >
                <p class="flex items-start gap-2 text-[12px] leading-snug text-amber-300">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                        <path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />
                    </svg>
                    <span>{{ whatsappStatus === "missing" ? "Nomor WhatsApp belum diisi." : "Nomor WhatsApp belum diverifikasi." }}</span>
                </p>
                <button
                    ref="firstItemEl"
                    type="button"
                    role="menuitem"
                    data-testid="profile-menu-wa-action"
                    class="mt-2 w-full cursor-pointer bg-isf-red px-2.5 py-1.5 font-space-grotesk text-[11px] font-bold uppercase tracking-[0.12em] text-white transition-colors hover:bg-isf-red-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                    @click="onVerifyWhatsApp"
                >
                    {{ whatsappStatus === "missing" ? "Tambahkan nomor" : "Verifikasi sekarang" }}
                </button>
            </div>
            <div class="p-1.5" role="none">
                <button
                    :ref="(el) => { if (whatsappStatus === 'verified') firstItemEl = el; }"
                    type="button"
                    role="menuitem"
                    :disabled="loggingOut"
                    data-testid="header-logout"
                    class="flex w-full cursor-pointer items-center gap-2.5 px-2.5 py-2 font-space-grotesk text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-200 transition-colors hover:bg-white/10 hover:text-white focus-visible:bg-white/10 focus-visible:text-white focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    @click="emit('logout')"
                >
                    <svg
                        class="h-4 w-4 shrink-0 text-isf-red"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        aria-hidden="true"
                        focusable="false"
                    >
                        <path
                            d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"
                        />
                        <path d="M9 12h12l-3 -3" />
                        <path d="M18 15l3 -3" />
                    </svg>
                    {{ loggingOut ? "Keluar…" : "Keluar" }}
                </button>
            </div>
        </div>
    </Teleport>
</template>
