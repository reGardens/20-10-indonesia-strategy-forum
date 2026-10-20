<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">📖</span>
        <div>
          <h2 class="section-title">Love Story & Timeline</h2>
          <p class="section-desc">Ceritakan perjalanan cinta kalian</p>
        </div>
      </div>

      <div v-for="(item, idx) in form.timeline" :key="idx" class="story-block">
        <div class="story-block-header">
          <span class="field-label">Momen {{ idx + 1 }}</span>
          <button v-if="form.timeline.length > 1" type="button" class="btn-remove" @click="remove(idx)">✕ Hapus</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Tanggal / Tahun</label>
            <input v-model="item.date" type="text" class="field-input" placeholder="Contoh: 14 Februari 2022" />
          </div>
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Emoji Momen</label>
            <input v-model="item.emoji" type="text" class="field-input" maxlength="4" placeholder="💕" style="font-size:1.25rem" />
          </div>
        </div>
        <div class="field-group">
          <input v-model="item.title" type="text" class="field-input" placeholder="Judul momen, misal: Pertama Bertemu" />
        </div>
        <div class="field-group">
          <textarea v-model="item.story" rows="2" class="field-input field-textarea"
            placeholder="Ceritakan momen ini secara singkat..." />
        </div>
      </div>

      <button type="button" class="btn-add" @click="add">+ Tambah Momen</button>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Love Story</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Love Story</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#9d174d">Love Story</div>
          <div class="preview-divider" style="background:#ec4899"></div>
          <div class="preview-timeline">
            <div v-for="(item, i) in form.timeline" :key="i" class="preview-story-item">
              <div class="preview-story-dot" style="background:#ec4899"></div>
              <div class="preview-story-text">
                <div class="preview-story-date">{{ item.emoji || '💕' }} {{ item.date || 'Tanggal' }}</div>
                <div class="preview-story-title" style="color:#9d174d">{{ item.title || 'Judul Momen' }}</div>
                <div v-if="item.story" style="font-size:.7rem;color:#6b7280;margin-top:.125rem">{{ item.story }}</div>
              </div>
            </div>
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

const form = ref({
  timeline: [
    { date: '', emoji: '💕', title: '', story: '' },
    { date: '', emoji: '💍', title: '', story: '' },
  ],
})

function add() {
  form.value.timeline.push({ date: '', emoji: '💕', title: '', story: '' })
}

function remove(idx) {
  form.value.timeline.splice(idx, 1)
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
.story-block { border:1px solid #fce7f3; border-radius:.75rem; padding:1rem; display:flex; flex-direction:column; gap:.75rem; background:#fdf9fc; }
.story-block-header { display:flex; align-items:center; justify-content:space-between; }
.btn-remove { font-size:.75rem; color:#ef4444; background:none; border:none; cursor:pointer; font-weight:500; }
.btn-add { border:2px dashed #d1d5db; border-radius:.75rem; padding:.625rem 1rem; font-size:.875rem; color:#6b7280; background:white; cursor:pointer; text-align:center; width:100%; transition:border-color .15s,color .15s; }
.btn-add:hover { border-color:#ec4899; color:#ec4899; }
</style>
