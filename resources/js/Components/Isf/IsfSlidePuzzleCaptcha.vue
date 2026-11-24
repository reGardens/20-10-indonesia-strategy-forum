<script setup>
/**
 * IsfSlidePuzzleCaptcha — Interactive Slide Puzzle CAPTCHA (ISF Brand Identity).
 *
 * Powered by vue3-puzzle-vcode library for seamless interactive puzzle verification.
 * - Auto-responsive dynamic canvas scaling for 100% mobile & desktop fit with zero overflow.
 * - Secure server-side validation with session token verification.
 * - Styled to match ISF brand identity (ISF Red accent, clean typography, smooth feedback).
 */
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import Vcode from "vue3-puzzle-vcode";
import "vue3-puzzle-vcode/css";
import http from "@/api/http";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "verified", "reset", "error"]);

// Challenge state from server
const loading = ref(false);
const key = ref("");
const token = ref("");
const bgImage = ref("");
const status = ref("idle"); // 'idle' | 'verifying' | 'success' | 'error'
const containerRef = ref(null);
const vcodeRef = ref(null);

// Responsive canvas dimensions (fits mobile viewports cleanly without overflow)
const canvasWidth = ref(300);
const canvasHeight = computed(() => Math.round(canvasWidth.value * 0.52));

const isValid = computed(() => status.value === "success");

// Custom clean image array for Vcode
const vcodeImgs = computed(() => {
    if (bgImage.value) {
        return [bgImage.value];
    }
    return null;
});

function updateDimensions() {
    if (!containerRef.value) return;
    const clientWidth = containerRef.value.clientWidth;
    // Available width inside container padding (p-2.5 = 10px * 2 = 20px + 2px border)
    const available = clientWidth - 22;
    if (available > 100) {
        // Clamp between 240px and 310px to perfectly fit mobile & desktop screens
        const targetWidth = Math.min(310, Math.max(240, Math.floor(available)));
        if (Math.abs(canvasWidth.value - targetWidth) >= 4) {
            canvasWidth.value = targetWidth;
        }
    }
}

let resizeObserver = null;

// Fetch a fresh puzzle challenge from backend
async function fetchChallenge() {
    loading.value = true;
    resetState();

    try {
        const response = await http.get("/isf/captcha/puzzle");
        const data = response.data || {};

        key.value = data.key || "";
        token.value = data.token || "";
        bgImage.value = data.bg_image || "";

        status.value = "idle";
    } catch (err) {
        console.error("Gagal memuat puzzle captcha:", err);
        status.value = "error";
        emit("error", "Gagal memuat puzzle captcha.");
    } finally {
        loading.value = false;
    }
}

function resetState() {
    status.value = "idle";
    emit("update:modelValue", "");
    emit("reset");
    if (vcodeRef.value?.reset) {
        vcodeRef.value.reset();
    }
}

// -------------------------------------------------------------
// vue3-puzzle-vcode Event Handlers
// -------------------------------------------------------------
async function onVcodeSuccess(deviation, info) {
    status.value = "verifying";

    try {
        const payload = {
            key: key.value,
        };

        if (token.value) {
            payload.token = token.value;
        }
        if (info && typeof info.offsetX === "number") {
            payload.offset = Math.round(info.offsetX);
        }

        const response = await http.post("/isf/captcha/puzzle/verify", payload);

        if (response.data?.success) {
            const verifiedAnswer = String(response.data?.offset ?? payload.offset ?? "100");
            status.value = "success";

            emit("update:modelValue", verifiedAnswer);
            emit("verified", {
                key: key.value,
                offset: verifiedAnswer,
                answer: verifiedAnswer,
            });
        } else {
            handleVerificationFailure();
        }
    } catch (err) {
        if (err?.response?.data?.expired) {
            await fetchChallenge();
            return;
        }
        handleVerificationFailure();
    }
}

function onVcodeFail() {
    handleVerificationFailure();
}

function onVcodeReset() {
    fetchChallenge();
}

function handleVerificationFailure() {
    status.value = "error";
    emit("error", "Posisi belum tepat, silakan coba lagi.");

    setTimeout(() => {
        if (status.value === "error") {
            status.value = "idle";
        }
    }, 1500);
}

onMounted(() => {
    updateDimensions();
    if (typeof ResizeObserver !== "undefined" && containerRef.value) {
        resizeObserver = new ResizeObserver(() => {
            updateDimensions();
        });
        resizeObserver.observe(containerRef.value);
    }
    window.addEventListener("resize", updateDimensions);
    fetchChallenge();
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    window.removeEventListener("resize", updateDimensions);
});

// Expose methods for parent components (e.g. RegisterView.vue)
defineExpose({
    refresh: fetchChallenge,
    reset: resetState,
    isValid,
    getKey: () => key.value,
    getAnswer: () => String(props.modelValue || ""),
});
</script>

<template>
    <div
        ref="containerRef"
        class="w-full max-w-full sm:max-w-[340px] mx-auto select-none transition-all duration-200"
        :class="props.disabled ? 'opacity-60 pointer-events-none' : ''"
    >
        <!-- Header: Label & Refresh Button -->
        <div class="flex items-center justify-between pb-2 px-0.5">
            <span class="text-xs font-bold uppercase tracking-wider text-neutral-700 flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-isf-red" aria-hidden="true"></span>
                <span>Verifikasi Puzzle</span>
            </span>
            <button
                type="button"
                :disabled="loading || status === 'verifying'"
                title="Muat ulang puzzle baru"
                aria-label="Muat ulang gambar puzzle baru"
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-500 hover:text-isf-red transition-colors cursor-pointer px-1.5 py-0.5 rounded-md hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                @click="fetchChallenge"
            >
                <svg
                    :class="['h-3.5 w-3.5', loading ? 'animate-spin' : '']"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                <span>Muat Ulang</span>
            </button>
        </div>

        <!-- Puzzle Visual Card Container -->
        <div class="relative w-full rounded-xl border border-neutral-200 bg-white p-2.5 shadow-2xs overflow-hidden box-border">
            <!-- Loading Skeleton State -->
            <div
                v-if="loading && !key"
                class="flex h-[180px] sm:h-[200px] w-full flex-col items-center justify-center gap-2 rounded-lg bg-neutral-100 text-neutral-500"
            >
                <svg class="h-6 w-6 animate-spin text-isf-red" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-medium">Menyiapkan puzzle...</span>
            </div>

            <!-- Vcode component with dynamic responsive width -->
            <Vcode
                v-else
                :key="`vcode-${canvasWidth}`"
                ref="vcodeRef"
                type="inside"
                :show="true"
                :canvas-width="canvasWidth"
                :canvas-height="canvasHeight"
                :slider-size="40"
                :range="10"
                :imgs="vcodeImgs"
                success-text="✓ Verifikasi Berhasil!"
                fail-text="✕ Posisi belum tepat, silakan coba lagi."
                slider-text="Geser slider untuk melengkapi puzzle"
                @success="onVcodeSuccess"
                @fail="onVcodeFail"
                @reset="onVcodeReset"
            />
        </div>
    </div>
</template>

<style scoped>
:deep(.vue-puzzle-vcode.inside_) {
    position: relative !important;
    background-color: transparent !important;
    width: 100% !important;
    display: flex;
    justify-content: center;
}
:deep(.vue-puzzle-vcode.inside_ .vue-auth-box_) {
    position: relative !important;
    top: 0 !important;
    left: 0 !important;
    transform: none !important;
    border-radius: 8px;
    background: transparent !important;
    padding: 0 !important;
    box-shadow: none !important;
    width: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
}
:deep(.vue-puzzle-vcode.inside_ .auth-body_) {
    border-radius: 8px;
    overflow: hidden;
    max-width: 100% !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
:deep(.vue-puzzle-vcode.inside_ .auth-control_) {
    width: 100% !important;
    padding: 10px 0 2px !important;
    display: flex;
    justify-content: center;
}
:deep(.vue-puzzle-vcode.inside_ .range-box) {
    margin-top: 0 !important;
    border-radius: 9999px !important;
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden;
    height: 40px !important;
    max-width: 100% !important;
}
:deep(.vue-puzzle-vcode.inside_ .range-box .range-text) {
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    padding: 0 44px !important;
}
:deep(.vue-puzzle-vcode.inside_ .range-box .range-slider) {
    background-color: rgba(185, 28, 28, 0.16) !important;
    border-radius: 9999px !important;
}
:deep(.vue-puzzle-vcode.inside_ .range-box .range-slider .range-btn) {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    border-radius: 9999px !important;
    cursor: grab;
}
:deep(.vue-puzzle-vcode.inside_ .range-box .range-slider .range-btn:active) {
    cursor: grabbing;
}
:deep(.vue-puzzle-vcode.inside_ .range-box .range-slider .range-btn > div) {
    border-color: #991b1b !important;
}
:deep(.vue-puzzle-vcode.inside_ .info-box_.show) {
    background-color: #059669 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}
:deep(.vue-puzzle-vcode.inside_ .info-box_.fail) {
    background-color: #dc2626 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}
/* Hide duplicate internal reload button since we have the branded ISF header button */
:deep(.vue-puzzle-vcode .reset_) {
    display: none !important;
}
</style>
