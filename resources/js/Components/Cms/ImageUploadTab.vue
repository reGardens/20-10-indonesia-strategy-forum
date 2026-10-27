<script setup>
/**
 * ImageUploadTab — composit: Tabs (URL Gambar | Pratinjau Tampilan) + ImageUpload + preview.
 *
 * Digunakan di seluruh form CMS untuk input gambar:
 * - URL Gambar: input langsung link foto
 * - Pratinjau Tampilan:
 *   - Jika gambar sudah ada: tampil preview terukur (tidak kebesaran) dengan overlay hover (Ganti Gambar & Hapus).
 *   - Jika gambar belum ada: tampil area drag-and-drop / upload file lokal.
 */
import { ref } from 'vue'
import { Tabs, ImageUpload } from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  aspectClass: { type: String, default: 'aspect-[16/9]' },
  placeholder: { type: String, default: 'https://images.unsplash.com/...' },
  previewLabel: { type: String, default: 'Pratinjau Gambar' }
})

const emit = defineEmits(['update:modelValue'])

const activeTab = ref(props.modelValue ? 'preview' : 'url')
const uploadComponentRef = ref(null)
const imageLoadError = ref(false)

const IMAGE_TABS = [
  { key: 'url', label: 'URL Gambar' },
  { key: 'preview', label: 'Pratinjau Tampilan' }
]

function onUrlInput(e) {
  imageLoadError.value = false
  emit('update:modelValue', e.target.value)
}

function onUpload(url) {
  imageLoadError.value = false
  emit('update:modelValue', url)
  activeTab.value = 'preview'
}

function triggerReplace() {
  uploadComponentRef.value?.triggerFileInput()
}

function removeImage() {
  imageLoadError.value = false
  emit('update:modelValue', '')
}
</script>

<template>
  <div class="space-y-3">
    <!-- Tab bar -->
    <Tabs v-model="activeTab" :tabs="IMAGE_TABS" />

    <!-- Tab 1: URL Gambar -->
    <div v-if="activeTab === 'url'" class="space-y-2">
      <input
        type="text"
        :value="modelValue"
        @input="onUrlInput"
        :placeholder="placeholder"
        class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs font-mono text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
      />
      <p class="text-[11px] text-muted-foreground">
        Masukkan URL gambar eksternal atau beralih ke tab <strong>Pratinjau Tampilan</strong> untuk mengunggah file.
      </p>
    </div>

    <!-- Tab 2: Pratinjau Tampilan & Upload -->
    <div v-else class="space-y-3">
      <!-- 1. Kondisi gambar SUDAH ADA: tampil preview proporsional dengan hover edit/hapus -->
      <div v-if="modelValue && !imageLoadError" class="space-y-1.5">
        <div class="flex items-center justify-between">
          <p class="text-[11px] text-muted-foreground font-medium">{{ previewLabel }}:</p>
          <span class="text-[10px] text-muted-foreground italic">Arahkan kursor ke gambar untuk mengganti atau menghapus</span>
        </div>

        <div
          :class="[
            'relative w-full max-w-xl mx-auto max-h-60 rounded-lg overflow-hidden border border-border bg-neutral-950 flex items-center justify-center shadow-xs group cursor-pointer',
            aspectClass
          ]"
        >
          <img
            :src="modelValue"
            alt="Pratinjau Gambar"
            class="w-full h-full max-h-60 object-cover transition duration-300 group-hover:scale-102"
            @error="imageLoadError = true"
          />

          <!-- Overlay saat hover -->
          <div
            class="absolute inset-0 bg-neutral-950/70 backdrop-blur-xs flex items-center justify-center gap-2.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
          >
            <button
              type="button"
              @click.stop="triggerReplace"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-white text-neutral-900 text-xs font-semibold shadow hover:bg-neutral-100 transition"
              title="Ganti Gambar"
            >
              <IconComponent name="Pencil" :size="13" />
              <span>Ganti Gambar</span>
            </button>
            <button
              type="button"
              @click.stop="removeImage"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-semibold shadow hover:bg-red-700 transition"
              title="Hapus Gambar"
            >
              <IconComponent name="Trash" :size="13" />
              <span>Hapus</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 2. Kondisi gambar BELUM ADA atau gagal dimuat: tampil dropzone uploader -->
      <div v-if="!modelValue || imageLoadError" class="space-y-2">
        <div v-if="imageLoadError" class="p-2.5 rounded-md border border-red-500/20 bg-red-500/5 text-xs text-red-500 flex items-center justify-between">
          <span>Gambar tidak dapat dimuat dari tautan. Silakan unggah gambar baru.</span>
          <button type="button" @click="removeImage" class="font-semibold underline ml-2">Reset</button>
        </div>
        <ImageUpload ref="uploadComponentRef" @upload="onUpload" />
      </div>

      <!-- Keep hidden uploader available when image exists so triggerReplace() can open file picker -->
      <div v-else class="hidden">
        <ImageUpload ref="uploadComponentRef" @upload="onUpload" />
      </div>
    </div>
  </div>
</template>
