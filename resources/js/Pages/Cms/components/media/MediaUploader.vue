<template>
    <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Unggah Media</h2>

        <FileUploader
            accept="image/*,video/*"
            :multiple="true"
            :max-size-m-b="20"
            @files-selected="handleFilesSelected"
        />

        <!-- Upload progress list -->
        <ul v-if="uploadQueue.length" class="mt-4 space-y-2">
            <li
                v-for="(item, index) in uploadQueue"
                :key="index"
                class="flex items-center gap-3 rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-sm"
            >
                <span class="truncate flex-1 text-gray-700">{{
                    item.name
                }}</span>

                <span
                    v-if="item.status === 'compressing'"
                    class="text-xs text-emerald-600 whitespace-nowrap"
                >
                    Mengompresi...
                </span>
                <span
                    v-else-if="item.status === 'saving'"
                    class="text-xs text-emerald-600 whitespace-nowrap"
                >
                    Menyimpan...
                </span>
                <span
                    v-else-if="item.status === 'done'"
                    class="text-xs text-green-600 whitespace-nowrap"
                >
                    ✓ Selesai
                </span>
                <span
                    v-else-if="item.status === 'error'"
                    class="text-xs text-red-600 whitespace-nowrap"
                    :title="item.errorMessage"
                >
                    ✗ Gagal
                </span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useStore } from "vuex";
import FileUploader from "@/Pages/Cms/components/shared/FileUploader.vue";
import { useImageOptimizer } from "@/Composables/useImageOptimizer";

const store = useStore();
const { optimize } = useImageOptimizer();

const uploadQueue = ref([]);

async function handleFilesSelected(files) {
    for (const file of files) {
        const entry = {
            name: file.name,
            status: "compressing",
            errorMessage: "",
        };
        uploadQueue.value.push(entry);

        try {
            const isImage = file.type.startsWith("image/");

            let mediaData;
            if (isImage) {
                entry.status = "compressing";
                const optimized = await optimize(file);

                entry.status = "saving";
                mediaData = {
                    fileName: file.name.replace(/\.[^.]+$/, ".webp"),
                    originalName: file.name,
                    type: "image",
                    originalSize: optimized.originalSize,
                    compressedSize: optimized.compressedSize,
                    url: optimized.url,
                    thumbnailUrl: optimized.url,
                };
            } else {
                entry.status = "saving";
                const url = URL.createObjectURL(file);
                mediaData = {
                    fileName: file.name,
                    originalName: file.name,
                    type: "video",
                    originalSize: file.size,
                    compressedSize: file.size,
                    url,
                    thumbnailUrl: "",
                };
            }

            await store.dispatch("media/addMedia", mediaData);
            entry.status = "done";
        } catch (err) {
            entry.status = "error";
            entry.errorMessage = err.message || "Gagal mengunggah file";
        }
    }

    // Clear completed items after a short delay
    setTimeout(() => {
        uploadQueue.value = uploadQueue.value.filter(
            (item) => item.status !== "done",
        );
    }, 2000);
}
</script>
