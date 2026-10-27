<script setup>
import { ref } from 'vue'
import axios from 'axios'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

/**
 * ImageUpload — standalone drag-and-drop / click-to-browse image uploader.
 * Uploads image to /api/cms/media/upload and emits the resulting URL string.
 */
const emit = defineEmits(['upload'])

const isDragging = ref(false)
const fileInput = ref(null)
const uploadError = ref('')
const uploading = ref(false)

function triggerFileInput() {
  fileInput.value?.click()
}

defineExpose({
  triggerFileInput
})

function onDragOver(e) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave() {
  isDragging.value = false
}

function onDrop(e) {
  e.preventDefault()
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) processFile(file)
}

function onFileChange(e) {
  const file = e.target?.files?.[0]
  if (file) processFile(file)
  e.target.value = ''
}

async function processFile(file) {
  uploadError.value = ''
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'File harus berupa gambar (JPG, PNG, WebP, GIF, SVG).'
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    uploadError.value = 'Ukuran file tidak boleh melebihi 5 MB.'
    return
  }

  uploading.value = true
  const formData = new FormData()
  formData.append('image', file)

  try {
    const res = await axios.post('/api/cms/media/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (res.data?.url) {
      emit('upload', res.data.url)
    } else {
      uploadError.value = 'Gagal mendapatkan tautan gambar dari server.'
    }
  } catch (err) {
    uploadError.value = err.response?.data?.message || 'Gagal mengunggah gambar ke server.'
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <div class="space-y-1">
    <!-- Hidden file input for programmatical trigger -->
    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
      class="sr-only"
      @change="onFileChange"
    />

    <!-- Drop zone -->
    <div
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="triggerFileInput"
      :class="[
        'border-2 border-dashed rounded-lg p-5 flex flex-col items-center justify-center cursor-pointer transition-colors',
        isDragging
          ? 'border-red-500 bg-red-500/5'
          : 'border-border hover:border-red-500/50 hover:bg-muted/30'
      ]"
    >
      <div v-if="uploading" class="text-center space-y-2">
        <IconComponent name="Refresh" :size="24" class="mx-auto text-red-500 animate-spin" />
        <p class="text-xs text-muted-foreground font-medium">Mengunggah gambar ke server...</p>
      </div>

      <div v-else class="text-center space-y-1.5">
        <IconComponent name="Upload" :size="26" class="mx-auto text-muted-foreground/60" />
        <div class="text-xs font-medium text-foreground">
          Klik atau seret gambar ke sini
        </div>
        <p class="text-[11px] text-muted-foreground">
          JPG, PNG, WebP, GIF, SVG — maks. 5 MB
        </p>
      </div>
    </div>

    <p v-if="uploadError" class="text-xs text-red-500 font-medium">
      {{ uploadError }}
    </p>
  </div>
</template>
