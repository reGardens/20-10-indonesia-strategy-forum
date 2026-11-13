<script setup>
/**
 * IsfShareBar — tombol bagikan (WhatsApp, X, Salin Tautan) untuk halaman detail publik ISF.
 * Tautan yang dibagikan = URL halaman saat ini.
 */
import { onBeforeUnmount, ref } from "vue";
import {
    IconBrandWhatsapp,
    IconBrandX,
    IconCheck,
    IconCopy,
} from "@tabler/icons-vue";

const props = defineProps({
    // Judul konten untuk teks bagikan.
    title: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "Bagikan",
    },
});

const btnClass =
    "inline-flex min-h-9 items-center gap-1.5 border border-neutral-300 bg-white px-3 py-1.5 font-ui text-[12px] font-bold uppercase tracking-[0.08em] text-neutral-800 transition-colors hover:border-isf-red hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

const copied = ref(false);
let timer = null;

function pageUrl() {
    return typeof window !== "undefined" ? window.location.href : "";
}

function shareText() {
    return `${props.title || "Indonesia Strategy Forum"} — Indonesia Strategy Forum`;
}

function shareWhatsapp() {
    const text = encodeURIComponent(`${shareText()} ${pageUrl()}`);
    window.open(`https://wa.me/?text=${text}`, "_blank", "noopener,noreferrer");
}

function shareX() {
    const text = encodeURIComponent(shareText());
    const url = encodeURIComponent(pageUrl());
    window.open(
        `https://twitter.com/intent/tweet?text=${text}&url=${url}`,
        "_blank",
        "noopener,noreferrer",
    );
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(pageUrl());
        copied.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        copied.value = false;
    }
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-2 sm:flex-nowrap"
        role="group"
        :aria-label="label"
    >
        <button type="button" :class="btnClass" @click="shareWhatsapp">
            <IconBrandWhatsapp class="h-4 w-4" aria-hidden="true" />
            WhatsApp
        </button>
        <button type="button" :class="btnClass" @click="shareX">
            <IconBrandX class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">Bagikan ke</span>
            X
        </button>
        <button type="button" :class="btnClass" @click="copyLink">
            <IconCheck
                v-if="copied"
                class="h-4 w-4 text-isf-red"
                aria-hidden="true"
            />
            <IconCopy v-else class="h-4 w-4" aria-hidden="true" />
            {{ copied ? "Tersalin" : "Salin Tautan" }}
        </button>
        <span class="sr-only" role="status">{{
            copied ? "Tautan tersalin" : ""
        }}</span>
    </div>
</template>
