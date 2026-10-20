<template>
    <div class="photo-uploader">
        <!-- Preview -->
        <div v-if="previewUrl" class="mb-3">
            <img
                :src="previewUrl"
                :alt="alt"
                class="w-32 h-32 object-cover rounded-lg border border-gray-200"
            />
            <div class="mt-1 text-xs text-gray-500 space-y-0.5">
                <p v-if="originalSize">Asli: {{ formatSize(originalSize) }}</p>
                <p v-if="compressedSize">
                    Terkompresi: {{ formatSize(compressedSize) }}
                </p>
            </div>
        </div>

        <!-- Upload area -->
        <div
            class="relative rounded-lg border-2 border-dashed transition-colors"
            :class="
                isDragOver
                    ? 'border-emerald-500 bg-emerald-50'
                    : 'border-gray-300 bg-gray-50 hover:border-gray-400'
            "
            @dragenter.prevent="onDragEnter"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
        >
            <label
                :for="inputId"
                class="flex flex-col items-center justify-center gap-1 cursor-pointer px-4 py-5"
            >
                <span class="text-2xl" aria-hidden="true">📷</span>
                <span class="text-sm font-medium text-gray-700">
                    {{ previewUrl ? "Ganti foto" : "Pilih foto" }}
                </span>
                <span class="text-xs text-gray-400"
                    >Format: JPG, PNG, WebP</span
                >
            </label>

            <input
                :id="inputId"
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                @change="onFileSelect"
            />
        </div>

        <!-- Processing indicator -->
        <p v-if="processing" class="mt-2 text-xs text-emerald-600">
            Mengompresi foto...
        </p>

        <!-- Error -->
        <p v-if="errorText" class="mt-2 text-xs text-red-600" role="alert">
            {{ errorText }}
        </p>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useImageOptimizer } from "@/Composables/useImageOptimizer";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    alt: {
        type: String,
        default: "Foto",
    },
});

const emit = defineEmits(["update:modelValue", "optimized"]);

const { optimize } = useImageOptimizer();

const previewUrl = ref(props.modelValue || "");
const originalSize = ref(0);
const compressedSize = ref(0);
const processing = ref(false);
const errorText = ref("");
const isDragOver = ref(false);
const fileInput = ref(null);
const inputId = `photo-uploader-${Math.random().toString(36).slice(2, 9)}`;

let dragCounter = 0;

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function onDragEnter() {
    dragCounter++;
    isDragOver.value = true;
}

function onDragOver() {
    isDragOver.value = true;
}

function onDragLeave() {
    dragCounter--;
    if (dragCounter <= 0) {
        dragCounter = 0;
        isDragOver.value = false;
    }
}

function onDrop(event) {
    dragCounter = 0;
    isDragOver.value = false;
    const files = Array.from(event.dataTransfer?.files || []);
    if (files.length > 0) {
        processFile(files[0]);
    }
}

function onFileSelect(event) {
    const files = Array.from(event.target.files || []);
    if (files.length > 0) {
        processFile(files[0]);
    }
    if (fileInput.value) {
        fileInput.value.value = "";
    }
}

async function processFile(file) {
    errorText.value = "";

    if (!file.type.startsWith("image/")) {
        errorText.value = "Hanya file gambar yang didukung";
        return;
    }

    processing.value = true;

    try {
        const result = await optimize(file);
        previewUrl.value = result.url;
        originalSize.value = result.originalSize;
        compressedSize.value = result.compressedSize;
        emit("update:modelValue", result.url);
        emit("optimized", result);
    } catch (err) {
        errorText.value = "Gagal mengompresi foto. Silakan coba lagi.";
    } finally {
        processing.value = false;
    }
}
</script>
