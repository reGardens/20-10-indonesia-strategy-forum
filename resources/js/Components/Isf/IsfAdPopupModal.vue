<script setup>
/**
 * IsfAdPopupModal — Modal Popup Iklan Saat Akses Pertama.
 * Mendukung:
 * - Tipe A (Banner Statis): Gambar iklan dengan tombol close (X) di bagian bawah popup.
 * - Tipe B (Cuplikan Video): Video (pendek maupun panjang/webinar) dengan tombol Skip gaya YouTube.
 *   - Menghapus auto-close timer agar webinar/video panjang tidak tertutup mendadak.
 *   - Tombol skip interaktif di pojok kanan bawah area video (countdown -> tombol "Lewati Iklan →").
 *   - Opsi skippable vs non-skippable.
 * Mode mandiri (default): sessionStorage agar popup tidak muncul berulang di setiap refresh halaman.
 * Mode `controlled`: dibuka saat di-mount dan menutup dengan emit('close'); jadwal muncul ulang
 * diatur pemanggil (useIsfPopupQueue), tanpa flag "sudah dilihat".
 */
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";

const props = defineProps({
    ad: {
        type: Object,
        default: () => ({}),
    },
    forcePreview: {
        type: Boolean,
        default: false,
    },
    storageKey: {
        type: String,
        default: "",
    },
    // Dikendalikan antrean popup (Beranda): tanpa sessionStorage/cookie "sudah dilihat".
    controlled: {
        type: Boolean,
        default: false,
    },
});

// ad-click: tautan iklan diklik (untuk statistik klik).
const emit = defineEmits(["close", "ad-click"]);

const isVisible = ref(false);
const skipTimeLeft = ref(30);
let skipTimerInterval = null;
let nonSkippableTimer = null;

const adType = computed(() => props.ad?.type || "banner"); // 'banner' (Tipe A) | 'video' (Tipe B)
const title = computed(() => props.ad?.title || "Pengumuman Mitra");
const imageUrl = computed(
    () => props.ad?.imageUrl || props.ad?.image_url || "",
);
const videoUrl = computed(
    () => props.ad?.videoUrl || props.ad?.video_url || "",
);
const targetUrl = computed(
    () => props.ad?.targetUrl || props.ad?.target_url || "",
);
const isExternal = computed(() => /^https?:\/\//i.test(targetUrl.value));

// Skip settings (Issue #72)
const isSkippable = computed(() => {
    const val = props.ad?.isSkippable ?? props.ad?.is_skippable;
    return val !== undefined && val !== null ? Boolean(val) : true;
});

const skipDelaySeconds = computed(() => {
    const raw = props.ad?.skipDelaySeconds ?? props.ad?.skip_delay_seconds;
    if (raw === undefined || raw === null) {
        return adType.value === "video" ? 10 : 0;
    }
    const parsed = Number(raw);
    return Number.isFinite(parsed) && parsed >= 0
        ? parsed
        : adType.value === "video"
          ? 10
          : 0;
});

const canSkip = computed(() => {
    return isSkippable.value && skipTimeLeft.value <= 0;
});

const effectiveStorageKey = computed(() => {
    if (props.storageKey) return props.storageKey;
    return adType.value === "video"
        ? "isf_popup_video_seen"
        : "isf_popup_banner_seen";
});

const videoDuration = computed(() => {
    const d = Number(props.ad?.videoDuration ?? props.ad?.video_duration);
    return Number.isFinite(d) && d > 0 ? d : 30;
});

// Ekstraksi ID video YouTube
const youtubeId = computed(() => {
    if (!videoUrl.value) return "";
    let cleaned = String(videoUrl.value).trim();
    const iframeMatch = cleaned.match(/src=["']([^"']+)["']/i);
    if (iframeMatch && iframeMatch[1]) cleaned = iframeMatch[1].trim();

    if (/^[a-zA-Z0-9_-]{11}$/.test(cleaned)) return cleaned;

    const vMatch = cleaned.match(/[?&]v=([a-zA-Z0-9_-]{11})(?:[&?]|$)/);
    if (vMatch && vMatch[1]) return vMatch[1];

    const pathMatch = cleaned.match(
        /(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|live\/|v\/|watch\/))([a-zA-Z0-9_-]{11})/,
    );
    if (pathMatch && pathMatch[1]) return pathMatch[1];

    return "";
});

const youtubeEmbedUrl = computed(() => {
    if (!youtubeId.value) return "";
    // Browser memblokir autoplay bersuara bila popup muncul tanpa klik pengguna (timer), jadi video
    // diputar tanpa suara (mute=1); pengunjung bisa menyalakan suara lewat kontrol player.
    // enablejsapi=1 disertakan agar event video berakhir (ended) dapat dideteksi via postMessage.
    const origin = typeof window !== "undefined" ? window.location.origin : "";
    const originParam = origin ? `&origin=${encodeURIComponent(origin)}` : "";
    return `https://www.youtube.com/embed/${youtubeId.value}?autoplay=1&mute=1&controls=1&rel=0&playsinline=1&enablejsapi=1${originParam}`;
});

const isDirectVideo = computed(() => {
    if (!videoUrl.value) return false;
    return (
        /\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(videoUrl.value) ||
        videoUrl.value.startsWith("/storage/uploads/videos/")
    );
});

let videoAutoCloseTimer = null;
const youtubeIframeRef = ref(null);

function startVideoAutoCloseTimer(seconds) {
    clearTimeout(videoAutoCloseTimer);
    clearTimeout(nonSkippableTimer);
    const duration =
        Number(seconds) > 0 ? Number(seconds) : videoDuration.value;
    if (duration > 0) {
        videoAutoCloseTimer = setTimeout(() => {
            closeModal();
        }, duration * 1000);
    }
}

function handleVideoMessage(event) {
    if (!event.data) return;
    try {
        const msg =
            typeof event.data === "string"
                ? JSON.parse(event.data)
                : event.data;
        // YouTube iframe player: state 0 = YT.PlayerState.ENDED
        if (
            (msg?.event === "onStateChange" && (msg?.info === 0 || msg?.info === "0")) ||
            (msg?.event === "infoDelivery" && msg?.info && (msg.info.playerState === 0 || msg.info.playerState === "0")) ||
            (msg?.info && typeof msg.info === "object" && (msg.info.playerState === 0 || msg.info.playerState === "0"))
        ) {
            closeModal();
        }
    } catch {
        // Not JSON
    }
}

function onYouTubeIframeLoad(e) {
    const iframe = e?.target || youtubeIframeRef.value;
    if (!iframe?.contentWindow) return;
    try {
        iframe.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 1 }), '*');
        iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'addEventListener', args: ['onStateChange'] }), '*');
    } catch {}
}

function onNativeVideoLoaded(e) {
    if (
        e?.target?.duration &&
        Number.isFinite(e.target.duration) &&
        e.target.duration > 0
    ) {
        startVideoAutoCloseTimer(e.target.duration);
    }
}

function onNativeVideoTimeUpdate(e) {
    const v = e?.target;
    if (v && v.duration > 0 && v.currentTime >= v.duration - 0.3) {
        closeModal();
    }
}

function openModal() {
    isVisible.value = true;
    if (adType.value === "video") {
        if (isSkippable.value) {
            startSkipCountdown();
        }
        // Selalu pasang auto-close saat durasi video habis (Issue #93)
        startVideoAutoCloseTimer(videoDuration.value);
    } else {
        if (skipDelaySeconds.value > 0) {
            startSkipCountdown();
        } else {
            skipTimeLeft.value = 0;
        }
    }
}

function startSkipCountdown() {
    clearInterval(skipTimerInterval);
    skipTimeLeft.value = skipDelaySeconds.value;
    if (skipTimeLeft.value <= 0) return;

    skipTimerInterval = setInterval(() => {
        if (skipTimeLeft.value > 1) {
            skipTimeLeft.value -= 1;
        } else {
            skipTimeLeft.value = 0;
            clearInterval(skipTimerInterval);
            // CATATAN ISSUE #72: Tidak auto-close! Video tetap dapat ditonton sampai selesai,
            // tombol berubah menjadi tombol aktif "Lewati Iklan →".
        }
    }, 1000);
}

function startNonSkippableTimer() {
    clearTimeout(nonSkippableTimer);
    if (videoDuration.value <= 0) {
        closeModal();
        return;
    }
    nonSkippableTimer = setTimeout(() => {
        closeModal();
    }, videoDuration.value * 1000);
}

function closeModal() {
    isVisible.value = false;
    clearInterval(skipTimerInterval);
    clearTimeout(nonSkippableTimer);
    clearTimeout(videoAutoCloseTimer);
    if (!props.forcePreview && !props.controlled) {
        try {
            sessionStorage.setItem(effectiveStorageKey.value, "true");
            sessionStorage.setItem("isf_popup_ad_seen", "true");
            // Juga set cookie session sebagai fallback
            document.cookie = `${effectiveStorageKey.value}=1; path=/; SameSite=Lax`;
        } catch (e) {
            // Ignore storage restriction
        }
    }

    emit("close");
}

function handleBackdropClick(e) {
    if (e.target === e.currentTarget) {
        if (props.forcePreview) {
            closeModal();
            return;
        }
        // Jika video non-skippable, tidak bisa ditutup via backdrop
        if (adType.value === "video" && !isSkippable.value) {
            return;
        }
        // Jika timer skip/tutup belum selesai, tidak bisa ditutup via backdrop
        if (!canSkip.value) {
            return;
        }
        closeModal();
    }
}

function handleKeydown(e) {
    if (e.key === "Escape" && isVisible.value) {
        if (props.forcePreview) {
            closeModal();
            return;
        }
        if (adType.value === "video" && !isSkippable.value) {
            return;
        }
        if (!canSkip.value) {
            return;
        }
        closeModal();
    }
}

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    window.addEventListener("message", handleVideoMessage);

    if (props.forcePreview || props.controlled) {
        openModal();
        return;
    }
    try {
        const seenInSession =
            sessionStorage.getItem(effectiveStorageKey.value) ||
            sessionStorage.getItem("isf_popup_ad_seen");
        const seenInCookie = document.cookie
            .split("; ")
            .some(
                (row) =>
                    row.startsWith(`${effectiveStorageKey.value}=`) ||
                    row.startsWith("isf_popup_ad_seen="),
            );

        if (!seenInSession && !seenInCookie) {
            const isActive = props.ad?.isActive ?? props.ad?.is_active ?? true;
            const isDraft = props.ad?.status === "draf";

            if (isActive && !isDraft) {
                // Beri jeda 500ms agar halaman beranda ter-render terlebih dahulu
                setTimeout(() => {
                    openModal();
                }, 500);
            }
        }
    } catch (e) {
        // Fallback jika browser block storage
    }
});

onBeforeUnmount(() => {
    clearInterval(skipTimerInterval);
    clearTimeout(nonSkippableTimer);
    clearTimeout(videoAutoCloseTimer);
    window.removeEventListener("keydown", handleKeydown);
    window.removeEventListener("message", handleVideoMessage);
});

watch(
    () => props.forcePreview,
    (newVal) => {
        if (newVal) {
            openModal();
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm select-none"
                data-testid="ad-popup-modal"
                role="dialog"
                aria-modal="true"
                aria-label="Modal Iklan Sponsor"
                @click="handleBackdropClick"
            >
                <!-- TIPE A: BANNER STATIS -->
                <div
                    v-if="adType === 'banner'"
                    class="relative w-full max-w-lg md:max-w-xl flex flex-col items-center animate-in fade-in zoom-in-95 duration-200"
                    data-testid="ad-popup-banner-content"
                >
                    <!-- Banner Card -->
                    <div
                        class="w-full overflow-hidden rounded-xl bg-neutral-900 border border-neutral-700/70 shadow-2xl"
                    >
                        <a
                            v-if="imageUrl"
                            :href="targetUrl || '#'"
                            :target="isExternal ? '_blank' : undefined"
                            :rel="
                                isExternal ? 'noopener noreferrer' : undefined
                            "
                            class="group block relative overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                            @click="emit('ad-click')"
                        >
                            <img
                                :src="imageUrl"
                                :alt="title || 'Iklan Sponsor'"
                                class="w-full max-h-[70vh] object-contain group-hover:scale-[1.01] transition-transform duration-300"
                            />
                            <div
                                v-if="title"
                                class="p-3.5 bg-neutral-900/95 border-t border-neutral-800 text-left"
                            >
                                <h3
                                    class="text-sm font-semibold text-white font-space-grotesk group-hover:text-isf-red transition-colors line-clamp-2"
                                >
                                    {{ title }}
                                </h3>
                                <p
                                    v-if="targetUrl"
                                    class="mt-1 text-[11px] text-neutral-400 font-sans flex items-center gap-1"
                                >
                                    <span>Klik untuk info lebih lanjut</span>
                                    <span class="text-isf-red font-bold"
                                        >&rarr;</span
                                    >
                                </p>
                            </div>
                        </a>
                        <div
                            v-else
                            class="p-12 text-center text-neutral-400 bg-neutral-900"
                        >
                            <p class="text-sm font-space-grotesk">
                                Materi iklan sponsor belum diunggah.
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Close (X) di BAGIAN BAWAH popup dengan durasi jeda tutup -->
                    <div class="mt-4 flex justify-center">
                        <!-- State A: Countdown berjalan (Belum bisa ditutup) -->
                        <div
                            v-if="!canSkip"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-neutral-900/90 border border-neutral-700/80 text-white/90 text-xs font-medium font-space-grotesk shadow-2xl backdrop-blur-md select-none animate-in fade-in duration-150"
                            data-testid="ad-popup-banner-countdown"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"
                            />
                            <span>Dapat ditutup dalam</span>
                            <strong class="font-mono text-white font-bold"
                                >{{ skipTimeLeft }}s</strong
                            >
                        </div>

                        <!-- State B: Tombol Tutup (✕) aktif setelah countdown habis -->
                        <button
                            v-else
                            type="button"
                            class="cursor-pointer group flex items-center justify-center w-11 h-11 rounded-full bg-neutral-900/90 hover:bg-neutral-800 text-white border border-neutral-600/80 shadow-2xl backdrop-blur-md transition-all hover:scale-110 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red animate-in fade-in zoom-in-95 duration-150"
                            data-testid="ad-popup-close-button-bottom"
                            aria-label="Tutup"
                            title="Tutup"
                            @click="closeModal"
                        >
                            <span
                                class="text-xl leading-none font-bold text-neutral-300 group-hover:text-white transition-colors"
                                >✕</span
                            >
                        </button>
                    </div>
                </div>

                <!-- TIPE B: CUPLIKAN VIDEO DENGAN TOMBOL SKIP YOUTUBE-STYLE -->
                <div
                    v-else
                    class="relative w-full max-w-2xl bg-neutral-900 rounded-xl border border-neutral-700/80 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200"
                    data-testid="ad-popup-video-content"
                >
                    <!-- Header Modal Video -->
                    <div
                        class="px-4 py-2.5 bg-neutral-950 border-b border-neutral-800 flex items-center justify-between text-xs text-neutral-300 font-space-grotesk"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="w-2 h-2 rounded-full bg-isf-red animate-pulse"
                            />
                            <span
                                class="font-bold text-white uppercase tracking-wider text-[11px]"
                                >Iklan Video</span
                            >
                            <template v-if="!isSkippable">
                                <span class="text-neutral-500">•</span>
                                <span
                                    class="text-amber-400 text-[11px] font-semibold"
                                    data-testid="video-non-skippable-info"
                                >
                                    Wajib ditonton
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Video Container (16:9) dengan YouTube-Style Skip Button -->
                    <div
                        class="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden"
                    >
                        <!-- Direct Video (MP4 / WebM / File Upload) -->
                        <video
                            v-if="isDirectVideo"
                            :src="videoUrl"
                            autoplay
                            muted
                            controls
                            playsinline
                            class="w-full h-full object-contain"
                            data-testid="ad-popup-native-video"
                            @ended="closeModal"
                            @timeupdate="onNativeVideoTimeUpdate"
                            @loadedmetadata="onNativeVideoLoaded"
                        />

                        <!-- YouTube Embed -->
                        <iframe
                            v-else-if="youtubeEmbedUrl"
                            ref="youtubeIframeRef"
                            :src="youtubeEmbedUrl"
                            class="w-full h-full border-0"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                            "
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"
                            data-testid="ad-popup-youtube-video"
                            @load="onYouTubeIframeLoad"
                        />

                        <!-- Fallback jika belum ada video -->
                        <div v-else class="text-center p-8 text-neutral-400">
                            <span class="text-3xl block mb-2">🎬</span>
                            <p class="text-sm font-space-grotesk">
                                Cuplikan video sponsor belum disetel.
                            </p>
                        </div>

                        <!-- ═══ YOUTUBE-STYLE SKIP BUTTON OVERLAY (Pojok Kanan Bawah) ═══ -->
                        <div
                            v-if="isSkippable"
                            class="absolute bottom-5 right-4 z-20 pointer-events-auto"
                        >
                            <!-- State A: Countdown (Belum bisa di-skip) -->
                            <div
                                v-if="!canSkip"
                                class="select-none inline-flex items-center gap-2 px-3.5 py-1.5 rounded-sm bg-black/85 border border-white/20 text-white/95 text-xs font-medium font-space-grotesk shadow-xl backdrop-blur-md"
                                data-testid="ad-popup-skip-countdown"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"
                                />
                                <span>Lewati iklan dalam</span>
                                <strong class="font-mono text-white font-bold"
                                    >{{ skipTimeLeft }}s</strong
                                >
                            </div>

                            <!-- State B: Tombol Aktif (Dapat di-skip) -->
                            <button
                                v-else
                                type="button"
                                class="cursor-pointer group inline-flex items-center gap-2 px-4 py-2 rounded-sm bg-neutral-900/95 hover:bg-black text-white text-xs font-bold font-space-grotesk border border-white/40 hover:border-white/80 shadow-2xl backdrop-blur-md transition-all active:scale-95"
                                data-testid="ad-popup-skip-button"
                                aria-label="Lewati Iklan"
                                @click="closeModal"
                            >
                                <span>Lewati Iklan</span>
                                <span
                                    class="text-sm font-bold text-isf-red group-hover:translate-x-0.5 transition-transform"
                                    >→</span
                                >
                            </button>
                        </div>

                        <!-- Non-skippable Overlay Badge (Pojok Kanan Bawah) -->
                        <div
                            v-else
                            class="absolute bottom-5 right-4 z-20 pointer-events-none select-none"
                            data-testid="ad-popup-non-skippable-badge"
                        >
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-sm bg-black/85 border border-white/15 text-neutral-300 text-xs font-space-grotesk shadow-lg backdrop-blur-md"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-isf-red"
                                />
                                <span class="text-[11px] font-medium"
                                    >Iklan tidak dapat dilewati</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info & Tautan Sponsor -->
                    <div
                        class="p-3.5 bg-neutral-950 border-t border-neutral-800 flex items-center justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <h4
                                class="text-xs font-bold text-white font-space-grotesk truncate"
                            >
                                {{ title }}
                            </h4>
                            <p
                                class="text-[11px] text-neutral-400 truncate font-sans"
                            >
                                Tayangan iklan sponsor resmi Indonesia Strategy
                                Forum.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a
                                v-if="targetUrl"
                                :href="targetUrl"
                                :target="isExternal ? '_blank' : undefined"
                                :rel="
                                    isExternal
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-isf-red hover:bg-isf-red-dark text-white text-xs font-bold font-space-grotesk transition-all shadow-xs"
                                @click="emit('ad-click')"
                            >
                                <span>Kunjungi Sponsor</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
