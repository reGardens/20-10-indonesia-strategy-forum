<template>
    <div class="template-view space-y-6">
        <h1 class="text-2xl font-bold text-gray-900">Template & Preview</h1>

        <!-- Loading state -->
        <LoadingIndicator v-if="loading" text="Memuat pengaturan template..." />

        <!-- Error state -->
        <ErrorMessage
            v-else-if="error"
            :message="error"
            :retryable="true"
            @retry="loadData"
        />

        <template v-else>
            <!-- Template Gallery -->
            <TemplateGallery
                :templates="templates"
                :selected-id="selectedTemplateId"
                @select="handleTemplateSelect"
            />

            <!-- Theme Customizer + Live Preview side by side -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Theme Customizer + Music -->
                <div class="space-y-6">
                    <ThemeCustomizer
                        :theme-colors="themeColors"
                        :font-family="fontFamily"
                        @update:theme-colors="themeColors = $event"
                        @update:font-family="fontFamily = $event"
                    />

                    <!-- Music Upload Section -->
                    <div
                        class="rounded-lg border border-gray-200 bg-white p-5 space-y-4"
                    >
                        <h2 class="text-lg font-semibold text-gray-900">
                            Musik Latar
                        </h2>

                        <FileUploader
                            accept=".mp3,audio/mpeg"
                            :max-size-m-b="10"
                            @files-selected="handleMusicUpload"
                        />

                        <p
                            v-if="musicError"
                            class="text-xs text-red-600"
                            role="alert"
                        >
                            {{ musicError }}
                        </p>

                        <!-- Audio preview -->
                        <div v-if="musicPreviewUrl">
                            <AudioPlayer :src="musicPreviewUrl" />
                            <button
                                type="button"
                                class="mt-2 text-xs text-red-600 hover:text-red-700 underline"
                                @click="removeMusic"
                            >
                                Hapus musik
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Live Preview -->
                <LivePreview
                    :template-id="selectedTemplateId"
                    :template-name="selectedTemplateName"
                    :theme-colors="themeColors"
                    :font-family="fontFamily"
                />
            </div>

            <!-- Save button -->
            <div class="flex justify-end gap-3">
                <a
                    v-if="activeSlug"
                    :href="`/wedding/${activeSlug}`"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-md bg-emerald-100 border border-emerald-300 px-5 py-2.5 text-sm font-medium text-emerald-800 shadow-sm hover:bg-emerald-200 transition-colors"
                >
                    🔍 Lihat Undangan (Preview)
                </a>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="saving"
                    @click="handleSave"
                >
                    <span v-if="saving">Menyimpan...</span>
                    <span v-else>Simpan Pengaturan</span>
                </button>
            </div>

            <!-- Success message -->
            <p
                v-if="successMessage"
                class="text-sm text-green-600"
                role="status"
            >
                {{ successMessage }}
            </p>

            <!-- Save error -->
            <p v-if="saveError" class="text-sm text-red-600" role="alert">
                {{ saveError }}
            </p>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import { validateMusicFile } from "@/Utils/validators";
import TemplateGallery from "@/Pages/Cms/components/template/TemplateGallery.vue";
import ThemeCustomizer from "@/Pages/Cms/components/template/ThemeCustomizer.vue";
import LivePreview from "@/Pages/Cms/components/template/LivePreview.vue";
import FileUploader from "@/Pages/Cms/components/shared/FileUploader.vue";
import AudioPlayer from "@/Pages/Cms/components/shared/AudioPlayer.vue";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";

const store = useStore();

// Local state
const selectedTemplateId = ref("batik-elegance");
const themeColors = ref({
    primary: "#8B4513",
    secondary: "#D2691E",
    accent: "#FFD700",
});
const fontFamily = ref("Playfair Display");
const musicPreviewUrl = ref("");
const musicFile = ref(null);
const musicError = ref("");
const saving = ref(false);
const successMessage = ref("");
const saveError = ref("");

// Store state
const loading = computed(
    () =>
        store.getters["settings/isLoading"] ||
        store.getters["template/isLoading"],
);
const error = computed(() => store.getters["settings/error"]);
const templates = computed(() => store.getters["template/templates"]);
const activeSlug = computed(() => store.getters["wedding/activeSlug"]);

const selectedTemplateName = computed(() => {
    const tmpl = templates.value.find((t) => t.id === selectedTemplateId.value);
    return tmpl ? tmpl.name : "Template";
});

function populateFromStore() {
    const settings = store.getters["settings/settings"];
    if (settings) {
        selectedTemplateId.value = settings.templateId || "batik-elegance";
        if (settings.themeColors) {
            themeColors.value = {
                ...themeColors.value,
                ...settings.themeColors,
            };
        }
        if (settings.fontFamily) {
            fontFamily.value = settings.fontFamily;
        }
        if (settings.musicUrl) {
            musicPreviewUrl.value = settings.musicUrl;
        }
    }
}

async function loadData() {
    await Promise.all([
        store.dispatch("settings/fetchSettings"),
        store.dispatch("template/fetchTemplates"),
    ]);
    populateFromStore();
}

function handleTemplateSelect(templateId) {
    selectedTemplateId.value = templateId;
    store.dispatch("template/selectTemplate", templateId);
}

function handleMusicUpload(files) {
    musicError.value = "";
    const file = files[0];

    if (!validateMusicFile(file)) {
        musicError.value = "Hanya file format MP3 yang didukung.";
        return;
    }

    musicFile.value = file;
    // Create object URL for preview
    if (musicPreviewUrl.value && musicPreviewUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(musicPreviewUrl.value);
    }
    musicPreviewUrl.value = URL.createObjectURL(file);
}

function removeMusic() {
    if (musicPreviewUrl.value && musicPreviewUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(musicPreviewUrl.value);
    }
    musicPreviewUrl.value = "";
    musicFile.value = null;
}

async function handleSave() {
    successMessage.value = "";
    saveError.value = "";
    saving.value = true;

    try {
        const payload = {
            templateId: selectedTemplateId.value,
            themeColors: { ...themeColors.value },
            fontFamily: fontFamily.value,
        };

        // If a new music file was uploaded, store its name as the URL
        // In a real app this would upload to a server; here we store the filename
        if (musicFile.value) {
            payload.musicUrl = `media/${musicFile.value.name}`;
        } else if (!musicPreviewUrl.value) {
            payload.musicUrl = "";
        }

        await store.dispatch("settings/saveSettings", payload);
        successMessage.value = "Pengaturan template berhasil disimpan.";

        setTimeout(() => {
            successMessage.value = "";
        }, 3000);
    } catch (err) {
        saveError.value = err.message || "Gagal menyimpan pengaturan template.";
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    loadData();
});
</script>
