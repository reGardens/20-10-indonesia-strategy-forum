<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">🖼️</span>
        <div>
          <h2 class="section-title">Galeri Foto & Video</h2>
          <p class="section-desc">Upload foto prewedding dan dokumentasi pernikahan</p>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label">Upload Foto</label>
        <div class="upload-area" @dragover.prevent @drop.prevent="handleDrop">
          <input ref="fileInput" type="file" multiple accept="image/*" class="hidden-input" @change="handleFileChange" />
          <button type="button" class="upload-btn" @click="$refs.fileInput.click()">
            📸 Pilih Foto
          </button>
          <p class="field-hint" style="margin-top:.5rem">Atau seret dan lepas file di sini · JPG, PNG, WEBP · Maks 5MB</p>
        </div>
      </div>

      <div v-if="form.photos.length" class="photo-grid">
        <div v-for="(photo, i) in form.photos" :key="i" class="photo-thumb">
          <img :src="photo.url" :alt="photo.name" class="photo-img" />
          <button type="button" class="photo-remove" @click="removePhoto(i)">✕</button>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label">Link Video (YouTube / Google Drive)</label>
        <input v-model="form.videoUrl" type="text" class="field-input" placeholder="https://youtube.com/embed/..." />
        <span class="field-hint">Gunakan link embed YouTube untuk video prewedding</span>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Izinkan Tamu Upload Foto</span>
            <p class="field-hint">Tamu bisa mengirimkan foto saat acara</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.allowGuestUpload }"
            @click="form.allowGuestUpload = !form.allowGuestUpload">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.allowGuestUpload }"></span>
          </button>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Galeri</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Galeri</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#0c4a6e">Galeri Foto</div>
          <div class="preview-divider" style="background:#0284c7"></div>
          <div class="preview-gallery-grid">
            <template v-if="form.photos.length">
              <div v-for="(photo, i) in form.photos.slice(0, 6)" :key="i" class="preview-gallery-thumb">
                <img :src="photo.url" class="preview-gallery-img" />
              </div>
            </template>
            <template v-else>
              <div v-for="n in 6" :key="n" class="preview-gallery-thumb">
                <span style="font-size:1.5rem">{{ ['📷','🌸','💒','💕','🌹','✨'][n-1] }}</span>
              </div>
            </template>
          </div>
          <div v-if="form.videoUrl" class="preview-stream-box" style="margin-top:.75rem;aspect-ratio:16/9;width:100%">
            🎬
          </div>
          <div v-if="form.allowGuestUpload" style="font-size:.75rem;color:#0284c7;font-weight:500;margin-top:.5rem">
            📤 Tamu dapat upload foto
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import './section.css'

const saving = ref(false)
const saved = ref(false)
const fileInput = ref(null)

const form = ref({
  photos: [],
  videoUrl: '',
  allowGuestUpload: false,
})

function handleFileChange(e) {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    if (file.size > 5 * 1024 * 1024) return
    form.value.photos.push({ url: URL.createObjectURL(file), name: file.name, file })
  })
}

function handleDrop(e) {
  const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'))
  files.forEach(file => {
    form.value.photos.push({ url: URL.createObjectURL(file), name: file.name, file })
  })
}

function removePhoto(i) {
  URL.revokeObjectURL(form.value.photos[i].url)
  form.value.photos.splice(i, 1)
}

async function handleSave() {
  saving.value = true
  await new Promise(r => setTimeout(r, 800))
  saving.value = false
  saved.value = true
  setTimeout(() => { saved.value = false }, 2500)
}
</script>

<style scoped>
.upload-area { border:2px dashed #d1d5db; border-radius:.75rem; padding:1.5rem; text-align:center; background:#f9fafb; }
.upload-btn { background:#10b981; color:white; border:none; border-radius:.5rem; padding:.5rem 1.25rem; font-size:.875rem; font-weight:600; cursor:pointer; }
.upload-btn:hover { background:#059669; }
.hidden-input { display:none; }
.photo-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem; }
.photo-thumb { position:relative; aspect-ratio:1; border-radius:.5rem; overflow:hidden; }
.photo-img { width:100%; height:100%; object-fit:cover; }
.photo-remove { position:absolute; top:.25rem; right:.25rem; width:1.25rem; height:1.25rem; border-radius:50%; background:rgba(0,0,0,.6); color:white; border:none; cursor:pointer; font-size:.65rem; display:flex; align-items:center; justify-content:center; }
.toggle-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
.toggle-btn { width:44px; height:24px; border-radius:12px; background:#d1d5db; border:none; cursor:pointer; position:relative; flex-shrink:0; transition:background .2s; }
.toggle-btn--on { background:#10b981; }
.toggle-thumb { position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; }
.toggle-thumb--on { transform:translateX(20px); }
</style>
