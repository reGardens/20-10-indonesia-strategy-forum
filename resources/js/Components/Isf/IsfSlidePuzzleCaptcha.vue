<script setup>
/**
 * IsfSlidePuzzleCaptcha — Interactive Slide Puzzle CAPTCHA (ISF Brand Identity).
 *
 * Replaces old text/code captcha with a friendly, interactive visual slider puzzle.
 * - Mobile & desktop responsive with touch and mouse drag support.
 * - Accessible keyboard navigation (Left/Right arrow keys).
 * - Real-time visual feedback: Track turns Navy during drag, Emerald Green on success, Red on failure.
 * - Server-side validation via /api/isf/captcha/puzzle/verify with +-6px tolerance.
 */
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
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
const bgImage = ref("");
const pieceImage = ref("");
const puzzleY = ref(60);
const canvasWidth = ref(320);
const canvasHeight = ref(160);
const pieceSize = ref(44);
const maxOffset = ref(276);

// Interaction & verification state
const sliderOffset = ref(0);
const isDragging = ref(false);
const startClientX = ref(0);
const startOffset = ref(0);
const status = ref("idle"); // 'idle' | 'dragging' | 'verifying' | 'success' | 'error'
const statusMessage = ref("");
const containerRef = ref(null);
const sliderTrackRef = ref(null);

const isValid = computed(() => status.value === "success");

// Thumb dimensions & track math
const thumbWidth = 44;
const trackMaxTravel = computed(() => Math.max(1, canvasWidth.value - thumbWidth));

// Calculate piece left position in pixels
const pieceLeft = computed(() => {
    // Map slider drag (0..trackMaxTravel) to puzzle max offset (0..maxOffset)
    const ratio = sliderOffset.value / trackMaxTravel.value;
    const puzzlePos = ratio * maxOffset.value;
    return puzzlePos - 4; // 4px offset matches SVG tab padding
});

const pieceTop = computed(() => {
    return puzzleY.value - 10; // 10px offset matches SVG tab padding
});

// Fetch a fresh puzzle challenge from backend
async function fetchChallenge() {
    loading.value = true;
    resetState();

    try {
        const response = await http.get("/isf/captcha/puzzle");
        const data = response.data || {};

        key.value = data.key || "";
        bgImage.value = data.bg_image || "";
        pieceImage.value = data.piece_image || "";
        puzzleY.value = Number(data.puzzle_y) || 60;
        canvasWidth.value = Number(data.width) || 320;
        canvasHeight.value = Number(data.height) || 160;
        pieceSize.value = Number(data.piece_size) || 44;
        maxOffset.value = Number(data.max_offset) || (canvasWidth.value - pieceSize.value);

        status.value = "idle";
        statusMessage.value = "Geser slider untuk melengkapi puzzle";
    } catch (err) {
        console.error("Gagal memuat puzzle captcha:", err);
        status.value = "error";
        statusMessage.value = "Gagal memuat puzzle. Klik muat ulang.";
        emit("error", "Gagal memuat puzzle captcha.");
    } finally {
        loading.value = false;
    }
}

function resetState() {
    sliderOffset.value = 0;
    isDragging.value = false;
    status.value = "idle";
    statusMessage.value = "";
    emit("update:modelValue", "");
    emit("reset");
}

// -------------------------------------------------------------
// Mouse & Touch Drag Handlers
// -------------------------------------------------------------
function onDragStart(clientX) {
    if (props.disabled || loading.value || status.value === "success" || status.value === "verifying") {
        return;
    }

    isDragging.value = true;
    startClientX.value = clientX;
    startOffset.value = sliderOffset.value;
    status.value = "dragging";
    statusMessage.value = "Lepaskan kepingan pada lubang puzzle";

    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
    window.addEventListener("touchmove", onTouchMove, { passive: false });
    window.addEventListener("touchend", onTouchEnd);
}

function onMouseDown(e) {
    if (e.button !== 0) return;
    onDragStart(e.clientX);
}

function onTouchStart(e) {
    if (!e.touches || e.touches.length === 0) return;
    onDragStart(e.touches[0].clientX);
}

function onMove(clientX) {
    if (!isDragging.value) return;

    const delta = clientX - startClientX.value;
    let nextOffset = startOffset.value + delta;

    if (nextOffset < 0) nextOffset = 0;
    if (nextOffset > trackMaxTravel.value) nextOffset = trackMaxTravel.value;

    sliderOffset.value = nextOffset;
}

function onMouseMove(e) {
    onMove(e.clientX);
}

function onTouchMove(e) {
    if (!isDragging.value) return;
    if (e.cancelable) e.preventDefault();
    if (e.touches && e.touches.length > 0) {
        onMove(e.touches[0].clientX);
    }
}

async function onDragEnd() {
    if (!isDragging.value) return;

    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    window.removeEventListener("touchmove", onTouchMove);
    window.removeEventListener("touchend", onTouchEnd);

    isDragging.value = false;

    // Minimum travel check to prevent accidental click-release
    if (sliderOffset.value < 10) {
        sliderOffset.value = 0;
        status.value = "idle";
        statusMessage.value = "Geser slider untuk melengkapi puzzle";
        return;
    }

    // Calculate actual puzzle target offset from slider ratio
    const ratio = sliderOffset.value / trackMaxTravel.value;
    const computedPuzzleX = Math.round(ratio * maxOffset.value);

    // Verify with backend
    status.value = "verifying";
    statusMessage.value = "Memverifikasi...";

    try {
        const response = await http.post("/isf/captcha/puzzle/verify", {
            key: key.value,
            offset: computedPuzzleX,
        });

        if (response.data?.success) {
            status.value = "success";
            statusMessage.value = "✓ Verifikasi Berhasil!";
            emit("update:modelValue", String(computedPuzzleX));
            emit("verified", {
                key: key.value,
                offset: computedPuzzleX,
                answer: String(computedPuzzleX),
            });
        } else {
            handleVerificationFailure();
        }
    } catch (err) {
        // Terlalu banyak percobaan → key hangus di server, muat puzzle baru.
        if (err?.response?.data?.expired) {
            statusMessage.value = err.response.data.message || "Puzzle baru dimuat, silakan coba lagi.";
            await fetchChallenge();
            statusMessage.value = "Terlalu banyak percobaan. Puzzle baru dimuat, silakan coba lagi.";
            return;
        }
        handleVerificationFailure();
    }
}

function onMouseUp() {
    onDragEnd();
}

function onTouchEnd() {
    onDragEnd();
}

function handleVerificationFailure() {
    status.value = "error";
    statusMessage.value = "✕ Posisi belum tepat, silakan coba lagi.";

    // Smooth bounce back
    setTimeout(() => {
        if (status.value === "error") {
            sliderOffset.value = 0;
            status.value = "idle";
            statusMessage.value = "Geser slider untuk melengkapi puzzle";
        }
    }, 800);
}

// -------------------------------------------------------------
// Keyboard Accessibility Handlers
// -------------------------------------------------------------
function onKeyDown(e) {
    if (props.disabled || loading.value || status.value === "success" || status.value === "verifying") {
        return;
    }

    const step = e.shiftKey ? 12 : 3;

    if (e.key === "ArrowRight") {
        e.preventDefault();
        sliderOffset.value = Math.min(trackMaxTravel.value, sliderOffset.value + step);
    } else if (e.key === "ArrowLeft") {
        e.preventDefault();
        sliderOffset.value = Math.max(0, sliderOffset.value - step);
    } else if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        isDragging.value = true;
        onDragEnd();
    }
}

onMounted(() => {
    fetchChallenge();
});

onBeforeUnmount(() => {
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    window.removeEventListener("touchmove", onTouchMove);
    window.removeEventListener("touchend", onTouchEnd);
});

// Expose methods for parent components
defineExpose({
    refresh: fetchChallenge,
    reset: resetState,
    isValid,
    getKey: () => key.value,
    getAnswer: () => String(Math.round((sliderOffset.value / trackMaxTravel.value) * maxOffset.value)),
});
</script>

<template>
    <div
        ref="containerRef"
        class="w-full max-w-[340px] rounded-xl border border-neutral-200 bg-[#FBF9F4] p-3.5 shadow-2xs select-none transition-all duration-200"
        :class="props.disabled ? 'opacity-60 pointer-events-none' : ''"
    >
        <!-- Header: Label & Refresh Button -->
        <div class="flex items-center justify-between pb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-neutral-700 flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-isf-red" aria-hidden="true"></span>
                <span>Verifikasi Puzzle</span>
            </span>
            <button
                type="button"
                :disabled="loading || status === 'verifying'"
                title="Muat ulang puzzle baru"
                aria-label="Muat ulang gambar puzzle baru"
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-500 hover:text-isf-red transition-colors cursor-pointer px-1.5 py-0.5 rounded-md hover:bg-neutral-200/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
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

        <!-- Puzzle Visual Canvas Area -->
        <div
            class="relative w-full h-[160px] rounded-lg overflow-hidden bg-[#0B1220] border border-neutral-300 shadow-inner flex items-center justify-center select-none"
            style="touch-action: none;"
        >
            <!-- Background Image with Cutout Hole -->
            <img
                v-if="bgImage && !loading"
                :src="bgImage"
                alt="Gambar Latar Puzzle ISF"
                class="w-full h-full object-cover select-none pointer-events-none"
                width="320"
                height="160"
                draggable="false"
            />

            <!-- Loading Skeleton State -->
            <div
                v-else-if="loading"
                class="flex flex-col items-center justify-center gap-2 text-white/70"
            >
                <svg class="h-6 w-6 animate-spin text-isf-red" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-medium">Menyiapkan puzzle...</span>
            </div>

            <!-- Movable Puzzle Piece Overlay -->
            <div
                v-if="pieceImage && !loading"
                class="absolute pointer-events-none select-none z-10 will-change-transform"
                :style="{
                    top: `${pieceTop}px`,
                    left: `${pieceLeft}px`,
                    transition: isDragging ? 'none' : 'left 300ms cubic-bezier(0.25, 1, 0.5, 1)',
                }"
            >
                <img
                    :src="pieceImage"
                    alt="Kepingan Puzzle"
                    class="w-[58px] h-[58px] select-none pointer-events-none"
                    draggable="false"
                />
            </div>
        </div>

        <!-- Slider Bar Section -->
        <div class="mt-3">
            <div
                ref="sliderTrackRef"
                tabindex="0"
                role="slider"
                :aria-valuemin="0"
                :aria-valuemax="trackMaxTravel"
                :aria-valuenow="Math.round(sliderOffset)"
                :aria-label="'Geser kepingan puzzle untuk verifikasi'"
                :aria-disabled="props.disabled || status === 'success'"
                class="relative h-11 w-full rounded-full bg-neutral-200 border border-neutral-300 overflow-hidden cursor-pointer select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                @keydown="onKeyDown"
            >
                <!-- Active Color Track Fill -->
                <div
                    class="absolute inset-y-0 left-0 transition-colors duration-150"
                    :class="{
                        'bg-isf-navy/80': status === 'dragging',
                        'bg-emerald-600': status === 'success',
                        'bg-red-600': status === 'error',
                        'bg-transparent': status === 'idle' || status === 'verifying',
                    }"
                    :style="{
                        width: `${sliderOffset + thumbWidth}px`,
                        transition: isDragging ? 'none' : 'width 300ms cubic-bezier(0.25, 1, 0.5, 1)',
                    }"
                ></div>

                <!-- Centered Instruction / Status Text -->
                <div
                    class="absolute inset-0 flex items-center justify-center pointer-events-none px-12"
                    aria-live="polite"
                >
                    <span
                        class="text-xs font-semibold tracking-wide truncate transition-colors duration-200"
                        :class="{
                            'text-white': status === 'dragging' || status === 'success' || status === 'error',
                            'text-neutral-500': status === 'idle' || status === 'verifying',
                        }"
                    >
                        {{ statusMessage }}
                    </span>
                </div>

                <!-- Draggable Thumb / Handle -->
                <div
                    class="absolute top-0.5 bottom-0.5 w-[42px] rounded-full shadow-md flex items-center justify-center transition-all duration-150 select-none cursor-grab active:cursor-grabbing border z-20"
                    :class="{
                        'bg-white text-isf-navy border-neutral-300 hover:bg-neutral-50': status === 'idle' || status === 'dragging',
                        'bg-emerald-600 text-white border-emerald-700': status === 'success',
                        'bg-red-600 text-white border-red-700': status === 'error',
                        'bg-neutral-100 text-neutral-400 border-neutral-300': status === 'verifying',
                    }"
                    :style="{
                        left: `${sliderOffset + 2}px`,
                        transition: isDragging ? 'none' : 'left 300ms cubic-bezier(0.25, 1, 0.5, 1)',
                    }"
                    @mousedown="onMouseDown"
                    @touchstart="onTouchStart"
                >
                    <!-- Icon during Verifying -->
                    <svg
                        v-if="status === 'verifying'"
                        class="h-4 w-4 animate-spin text-neutral-600"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <!-- Icon on Success -->
                    <svg
                        v-else-if="status === 'success'"
                        class="h-5 w-5 text-white animate-scale-in"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>

                    <!-- Icon on Error -->
                    <svg
                        v-else-if="status === 'error'"
                        class="h-5 w-5 text-white animate-shake"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                    </svg>

                    <!-- Default Double Chevron Arrows -->
                    <svg
                        v-else
                        class="h-4 w-4 text-neutral-700"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scaleIn {
    0% {
        transform: scale(0.6);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-scale-in {
    animation: scaleIn 200ms ease-out forwards;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-3px); }
    40%, 80% { transform: translateX(3px); }
}
.animate-shake {
    animation: shake 300ms ease-in-out;
}
</style>

