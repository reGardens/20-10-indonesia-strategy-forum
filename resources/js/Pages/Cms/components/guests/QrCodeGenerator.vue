<template>
    <div
        class="qr-code-generator rounded-lg border border-gray-200 bg-white p-5"
    >
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">QR Code Tamu</h2>
        </div>

        <div
            v-if="!guest || !guest.id"
            class="text-sm text-gray-500 py-4 text-center"
        >
            Data tamu tidak tersedia.
        </div>

        <div v-else class="flex flex-col items-center gap-4">
            <p class="text-sm font-medium text-gray-900">{{ guest.name }}</p>

            <div
                ref="qrContainer"
                class="rounded-lg border border-gray-100 bg-white p-4"
                :aria-label="`QR Code untuk ${guest.name}`"
            >
                <QrcodeVue
                    v-if="qrValue"
                    :value="qrValue"
                    :size="200"
                    level="M"
                    render-as="svg"
                />
                <div
                    v-else
                    class="flex items-center justify-center w-[200px] h-[200px] text-sm text-gray-400"
                >
                    Gagal generate QR code
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!qrValue || downloading"
                    :aria-label="`Download QR code untuk ${guest.name}`"
                    @click="handleDownload"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                        />
                    </svg>
                    {{ downloading ? "Mengunduh..." : "Download QR" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import QrcodeVue from "qrcode.vue";
import { useQrCode } from "@/Composables/useQrCode.js";

const props = defineProps({
    guest: {
        type: Object,
        default: () => ({ id: "", name: "" }),
    },
    weddingSlug: {
        type: String,
        default: "",
    },
});

const { encode } = useQrCode();

const qrContainer = ref(null);
const downloading = ref(false);

const qrValue = computed(() => {
    if (!props.guest?.id || !props.weddingSlug) {
        return "";
    }
    return encode({
        guestId: props.guest.id,
        weddingSlug: props.weddingSlug,
    });
});

async function handleDownload() {
    if (!qrContainer.value || downloading.value) return;

    downloading.value = true;

    try {
        const svgElement = qrContainer.value.querySelector("svg");
        if (!svgElement) {
            return;
        }

        const canvas = document.createElement("canvas");
        const size = 400;
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext("2d");

        // Fill white background
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, size, size);

        // Serialize SVG and draw to canvas
        const svgData = new XMLSerializer().serializeToString(svgElement);
        const svgBlob = new Blob([svgData], {
            type: "image/svg+xml;charset=utf-8",
        });
        const url = URL.createObjectURL(svgBlob);

        const img = new Image();
        img.onload = () => {
            ctx.drawImage(img, 0, 0, size, size);
            URL.revokeObjectURL(url);

            // Trigger download
            const dataUrl = canvas.toDataURL("image/png");
            const link = document.createElement("a");
            const safeName = (props.guest.name || "guest")
                .replace(/[^a-zA-Z0-9\s-]/g, "")
                .replace(/\s+/g, "-")
                .toLowerCase();
            link.download = `qr-${safeName}.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            downloading.value = false;
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            downloading.value = false;
        };
        img.src = url;
    } catch {
        downloading.value = false;
    }
}
</script>
