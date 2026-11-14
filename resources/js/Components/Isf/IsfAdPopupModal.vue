<script setup>
/**
 * IsfAdPopupModal — Modal Popup Iklan Saat Akses Pertama.
 * Mendukung:
 * - Tipe A (Banner Statis): Gambar iklan dengan tombol close (X) di bagian bawah popup.
 * - Tipe B (Cuplikan Video 30 Detik): Video pendek yang otomatis tertutup (auto-close) saat durasi habis, disertai kontrol manual.
 * Mode mandiri (default): sessionStorage agar popup tidak muncul berulang di setiap refresh halaman.
 * Mode `controlled`: dibuka saat di-mount dan menutup dengan emit('close'); jadwal muncul ulang
 * diatur pemanggil (useIsfPopupQueue), tanpa flag "sudah dilihat".
 */
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';

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
        default: '',
    },
    // Dikendalikan antrean popup (Beranda): tanpa sessionStorage/cookie "sudah dilihat".
    controlled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const isVisible = ref(false);
const timeLeft = ref(30);
let timerInterval = null;

const adType = computed(() => props.ad?.type || 'banner'); // 'banner' (Tipe A) | 'video' (Tipe B)
const title = computed(() => props.ad?.title || 'Pengumuman Mitra');
const imageUrl = computed(() => props.ad?.imageUrl || props.ad?.image_url || '');
const videoUrl = computed(() => props.ad?.videoUrl || props.ad?.video_url || '');
const targetUrl = computed(() => props.ad?.targetUrl || props.ad?.target_url || '');
const isExternal = computed(() => /^https?:\/\//i.test(targetUrl.value));

const effectiveStorageKey = computed(() => {
    if (props.storageKey) return props.storageKey;
    return adType.value === 'video' ? 'isf_popup_video_seen' : 'isf_popup_banner_seen';
});

const videoDuration = computed(() => {
    const d = Number(props.ad?.videoDuration ?? props.ad?.video_duration);
    return Number.isFinite(d) && d > 0 ? d : 30;
});

const progressPercent = computed(() => {
    const total = videoDuration.value;
    const remaining = timeLeft.value;
    const elapsed = Math.max(0, total - remaining);
    return Math.min(100, Math.round((elapsed / total) * 100));
});

// Ekstraksi ID video YouTube
const youtubeId = computed(() => {
    if (!videoUrl.value) return '';
    let cleaned = String(videoUrl.value).trim();
    const iframeMatch = cleaned.match(/src=["']([^"']+)["']/i);
    if (iframeMatch && iframeMatch[1]) cleaned = iframeMatch[1].trim();

    if (/^[a-zA-Z0-9_-]{11}$/.test(cleaned)) return cleaned;

    const vMatch = cleaned.match(/[?&]v=([a-zA-Z0-9_-]{11})(?:[&?]|$)/);
    if (vMatch && vMatch[1]) return vMatch[1];

    const pathMatch = cleaned.match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|live\/|v\/|watch\/))([a-zA-Z0-9_-]{11})/);
    if (pathMatch && pathMatch[1]) return pathMatch[1];

    return '';
});

const youtubeEmbedUrl = computed(() => {
    if (!youtubeId.value) return '';
    return `https://www.youtube.com/embed/${youtubeId.value}?autoplay=1&mute=0&controls=1&rel=0&playsinline=1`;
});

const isDirectVideo = computed(() => {
    if (!videoUrl.value) return false;
    return /\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(videoUrl.value) || videoUrl.value.startsWith('/storage/uploads/videos/');
});

function openModal() {
    isVisible.value = true;
    if (adType.value === 'video') {
        startVideoCountdown();
    }
}

function startVideoCountdown() {
    clearInterval(timerInterval);
    timeLeft.value = videoDuration.value;
    timerInterval = setInterval(() => {
        if (timeLeft.value > 1) {
            timeLeft.value -= 1;
        } else {
            timeLeft.value = 0;
            clearInterval(timerInterval);
            closeModal();
        }
    }, 1000);
}

function closeModal() {
    isVisible.value = false;
    clearInterval(timerInterval);
    if (!props.forcePreview && !props.controlled) {
        try {
            sessionStorage.setItem(effectiveStorageKey.value, 'true');
            sessionStorage.setItem('isf_popup_ad_seen', 'true');
            // Juga set cookie session sebagai fallback
            document.cookie = `${effectiveStorageKey.value}=1; path=/; SameSite=Lax`;
        } catch (e) {
            // Ignore storage restriction
        }
    }

    emit('close');
}

function handleBackdropClick(e) {
    if (e.target === e.currentTarget) {
        // Iklan video baru hilang setelah durasi selesai (timeLeft <= 0)
        if (adType.value === 'video' && timeLeft.value > 0 && !props.forcePreview) {
            return;
        }
        closeModal();
    }
}

function handleKeydown(e) {
    if (e.key === 'Escape' && isVisible.value) {
        if (adType.value === 'video' && timeLeft.value > 0 && !props.forcePreview) {
            return;
        }
        closeModal();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);

    if (props.forcePreview || props.controlled) {
        openModal();
        return;
    }
    try {
        const seenInSession = sessionStorage.getItem(effectiveStorageKey.value) || sessionStorage.getItem('isf_popup_ad_seen');
        const seenInCookie = document.cookie.split('; ').some((row) => row.startsWith(`${effectiveStorageKey.value}=`) || row.startsWith('isf_popup_ad_seen='));

        if (!seenInSession && !seenInCookie) {
            const isActive = props.ad?.isActive ?? props.ad?.is_active ?? true;
            const isDraft = props.ad?.status === 'draf';

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
    clearInterval(timerInterval);
    window.removeEventListener('keydown', handleKeydown);
});

watch(() => props.forcePreview, (newVal) => {
    if (newVal) {
        openModal();
    }
});
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
                    <div class="w-full overflow-hidden rounded-xl bg-neutral-900 border border-neutral-700/70 shadow-2xl">
                        <a
                            v-if="imageUrl"
                            :href="targetUrl || '#'"
                            :target="isExternal ? '_blank' : undefined"
                            :rel="isExternal ? 'noopener noreferrer' : undefined"
                            class="group block relative overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                        >
                            <img
                                :src="imageUrl"
                                :alt="title || 'Iklan Sponsor'"
                                class="w-full max-h-[70vh] object-contain group-hover:scale-[1.01] transition-transform duration-300"
                            />
                            <div v-if="title" class="p-3.5 bg-neutral-900/95 border-t border-neutral-800 text-left">
                                <h3 class="text-sm font-semibold text-white font-space-grotesk group-hover:text-isf-red transition-colors line-clamp-2">
                                    {{ title }}
                                </h3>
                                <p v-if="targetUrl" class="mt-1 text-[11px] text-neutral-400 font-sans flex items-center gap-1">
                                    <span>Klik untuk info lebih lanjut</span>
                                    <span class="text-isf-red font-bold">&rarr;</span>
                                </p>
                            </div>
                        </a>
                        <div v-else class="p-12 text-center text-neutral-400 bg-neutral-900">
                            <p class="text-sm font-space-grotesk">Materi iklan sponsor belum diunggah.</p>
                        </div>
                    </div>

                    <!-- Tombol Close (X) di BAGIAN BAWAH popup tanpa wording, icon X lebih besar -->
                    <div class="mt-4 flex justify-center">
                        <button
                            type="button"
                            class="cursor-pointer group flex items-center justify-center w-11 h-11 rounded-full bg-neutral-900/90 hover:bg-neutral-800 text-white border border-neutral-600/80 shadow-2xl backdrop-blur-md transition-all hover:scale-110 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                            data-testid="ad-popup-close-button-bottom"
                            aria-label="Tutup"
                            title="Tutup"
                            @click="closeModal"
                        >
                            <span class="text-xl leading-none font-bold text-neutral-300 group-hover:text-white transition-colors">✕</span>
                        </button>
                    </div>
                </div>

                <!-- TIPE B: CUPLIKAN VIDEO 30 DETIK -->
                <div
                    v-else
                    class="relative w-full max-w-2xl bg-neutral-900 rounded-xl border border-neutral-700/80 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200"
                    data-testid="ad-popup-video-content"
                >
                    <!-- Header dengan Countdown Timer & Durasi Video (Baru hilang saat durasi selesai) -->
                    <div class="px-4 py-2.5 bg-neutral-950 border-b border-neutral-800 flex items-center justify-between text-xs text-neutral-300 font-space-grotesk">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-isf-red animate-ping" />
                            <span class="font-bold text-white uppercase tracking-wider text-[11px]">Iklan Video</span>
                            <span class="text-neutral-500">•</span>
                            <span class="text-neutral-400 text-[11px]" data-testid="video-countdown-text">
                                Menutup otomatis dalam <strong class="text-white font-mono">{{ timeLeft }}s</strong>
                            </span>
                        </div>

                        <!-- Status durasi: Baru hilang setelah durasi video selesai -->
                        <div class="flex items-center gap-2">
                            <span v-if="timeLeft > 0" class="text-[10px] text-neutral-400 bg-neutral-900 border border-neutral-800 px-2 py-0.5 rounded font-mono">
                                Durasi {{ videoDuration }}s
                            </span>
                            <button
                                v-else
                                type="button"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1 rounded bg-neutral-800 hover:bg-neutral-700 text-white text-[11px] font-bold transition-all border border-neutral-700"
                                data-testid="ad-popup-manual-close"
                                aria-label="Tutup iklan video"
                                @click="closeModal"
                            >
                                <span>Tutup</span>
                                <span class="text-neutral-400 font-normal">✕</span>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar Countdown Timer -->
                    <div class="w-full bg-neutral-800 h-1 overflow-hidden">
                        <div
                            class="bg-isf-red h-full transition-all duration-1000 ease-linear"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>

                    <!-- Video Container (16:9) -->
                    <div class="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden">
                        <!-- Direct Video (MP4 / WebM / File Upload) -->
                        <video
                            v-if="isDirectVideo"
                            :src="videoUrl"
                            autoplay
                            controls
                            playsinline
                            class="w-full h-full object-contain"
                            data-testid="ad-popup-native-video"
                        />

                        <!-- YouTube Embed -->
                        <iframe
                            v-else-if="youtubeEmbedUrl"
                            :src="youtubeEmbedUrl"
                            class="w-full h-full border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"
                            data-testid="ad-popup-youtube-video"
                        />

                        <!-- Fallback jika belum ada video -->
                        <div v-else class="text-center p-8 text-neutral-400">
                            <span class="text-3xl block mb-2">🎬</span>
                            <p class="text-sm font-space-grotesk">Cuplikan video sponsor belum disetel.</p>
                        </div>
                    </div>

                    <!-- Footer Info & Tautan Sponsor -->
                    <div class="p-3.5 bg-neutral-950 border-t border-neutral-800 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-white font-space-grotesk truncate">
                                {{ title }}
                            </h4>
                            <p class="text-[11px] text-neutral-400 truncate font-sans">
                                Tayangan iklan sponsor resmi Indonesia Strategy Forum.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a
                                v-if="targetUrl"
                                :href="targetUrl"
                                :target="isExternal ? '_blank' : undefined"
                                :rel="isExternal ? 'noopener noreferrer' : undefined"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-isf-red hover:bg-isf-red-dark text-white text-xs font-bold font-space-grotesk transition-all shadow-xs"
                            >
                                <span>Kunjungi Sponsor</span>
                                <span>&rarr;</span>
                            </a>
                            <button
                                v-if="timeLeft <= 0 || forcePreview"
                                type="button"
                                class="cursor-pointer px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-xs font-semibold font-space-grotesk border border-neutral-700 transition-colors"
                                @click="closeModal"
                            >
                                Tutup (✕)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

