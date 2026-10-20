<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">👥</span>
        <div>
          <h2 class="section-title">Manajemen Tamu</h2>
          <p class="section-desc">Kelola daftar tamu dan tracking kehadiran</p>
        </div>
      </div>

      <!-- Quick add guest -->
      <div class="field-group">
        <label class="field-label">Tambah Tamu Cepat</label>
        <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem">
          <input v-model="newGuest.name" type="text" class="field-input" placeholder="Nama tamu"
            @keydown.enter="addGuest" />
          <button type="button" class="btn-save" style="padding:.5rem 1rem;white-space:nowrap" @click="addGuest">
            + Tambah
          </button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem">
          <input v-model="newGuest.phone" type="text" class="field-input" placeholder="No HP (opsional)" />
          <select v-model="newGuest.category" class="field-input">
            <option value="keluarga">👨‍👩‍👧 Keluarga</option>
            <option value="teman">👫 Teman</option>
            <option value="kantor">💼 Rekan Kerja</option>
            <option value="lainnya">👤 Lainnya</option>
          </select>
        </div>
      </div>

      <!-- Import Excel -->
      <div class="field-group">
        <label class="field-label">Import dari Excel</label>
        <div class="upload-area">
          <input ref="xlsInput" type="file" accept=".xlsx,.csv" class="hidden-input" />
          <button type="button" class="upload-btn" @click="$refs.xlsInput.click()">
            📊 Upload Excel / CSV
          </button>
          <p class="field-hint" style="margin-top:.5rem">
            Format: Kolom Nama, No HP, Kategori. <a href="#" style="color:#10b981">Download template</a>
          </p>
        </div>
      </div>

      <!-- Guest list -->
      <div v-if="form.guests.length" class="field-group">
        <label class="field-label">Daftar Tamu ({{ form.guests.length }})</label>
        <div class="guest-list">
          <div v-for="(g, i) in form.guests" :key="i" class="guest-item">
            <span class="guest-cat-dot" :style="{ background: catColor(g.category) }"></span>
            <div class="guest-info">
              <span class="guest-name">{{ g.name }}</span>
              <span class="guest-cat">{{ catLabel(g.category) }}</span>
            </div>
            <div class="guest-status">
              <span v-if="g.opened" class="status-badge status-badge--open">Dibuka ✓</span>
              <span v-else class="status-badge">Belum</span>
            </div>
            <button type="button" class="btn-remove" @click="removeGuest(i)">✕</button>
          </div>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Daftar Tamu</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Manajemen Tamu</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#0c4a6e">👥 Tamu Undangan</div>
          <div class="preview-divider" style="background:#0284c7"></div>
          <!-- Stats -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;width:100%">
            <div style="background:rgba(255,255,255,.7);border-radius:.5rem;padding:.5rem;text-align:center">
              <div style="font-size:1.25rem;font-weight:700;color:#0c4a6e">{{ form.guests.length }}</div>
              <div style="font-size:.65rem;color:#64748b">Total</div>
            </div>
            <div style="background:rgba(34,197,94,.15);border-radius:.5rem;padding:.5rem;text-align:center">
              <div style="font-size:1.25rem;font-weight:700;color:#166534">{{ openedCount }}</div>
              <div style="font-size:.65rem;color:#166534">Dibuka</div>
            </div>
            <div style="background:rgba(234,179,8,.15);border-radius:.5rem;padding:.5rem;text-align:center">
              <div style="font-size:1.25rem;font-weight:700;color:#713f12">{{ form.guests.length - openedCount }}</div>
              <div style="font-size:.65rem;color:#713f12">Belum Buka</div>
            </div>
          </div>
          <!-- Sample guests -->
          <div v-for="(g, i) in form.guests.slice(0, 4)" :key="i" class="preview-wishlist-item">
            <span style="font-size:1rem">{{ catEmoji(g.category) }}</span>
            <span class="preview-wishlist-text" style="color:#0c4a6e">{{ g.name || 'Nama Tamu' }}</span>
            <span class="preview-wishlist-price">{{ catLabel(g.category) }}</span>
          </div>
          <div v-if="form.guests.length === 0" style="font-size:.8125rem;color:#9ca3af">
            Belum ada tamu ditambahkan
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import './section.css'

const saving = ref(false)
const saved = ref(false)
const xlsInput = ref(null)

const newGuest = ref({ name: '', phone: '', category: 'keluarga' })

const form = ref({
  guests: [
    { name: 'Bapak Santoso', phone: '', category: 'keluarga', opened: true },
    { name: 'Ibu Rahayu', phone: '', category: 'keluarga', opened: false },
    { name: 'Dian Pratiwi', phone: '', category: 'teman', opened: true },
  ],
})

const openedCount = computed(() => form.value.guests.filter(g => g.opened).length)

const catColors = { keluarga: '#10b981', teman: '#3b82f6', kantor: '#f59e0b', lainnya: '#8b5cf6' }
const catLabels = { keluarga: 'Keluarga', teman: 'Teman', kantor: 'Kantor', lainnya: 'Lainnya' }
const catEmojis = { keluarga: '👨‍👩‍👧', teman: '👫', kantor: '💼', lainnya: '👤' }

function catColor(c) { return catColors[c] || '#9ca3af' }
function catLabel(c) { return catLabels[c] || c }
function catEmoji(c) { return catEmojis[c] || '👤' }

function addGuest() {
  if (!newGuest.value.name.trim()) return
  form.value.guests.push({ ...newGuest.value, opened: false })
  newGuest.value = { name: '', phone: '', category: 'keluarga' }
}

function removeGuest(i) {
  form.value.guests.splice(i, 1)
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
.upload-area { border:2px dashed #d1d5db; border-radius:.75rem; padding:1.25rem; text-align:center; background:#f9fafb; }
.upload-btn { background:#0284c7; color:white; border:none; border-radius:.5rem; padding:.5rem 1.25rem; font-size:.875rem; font-weight:600; cursor:pointer; }
.upload-btn:hover { background:#0369a1; }
.hidden-input { display:none; }
.guest-list { display:flex; flex-direction:column; gap:.375rem; max-height:280px; overflow-y:auto; }
.guest-item { display:flex; align-items:center; gap:.5rem; padding:.5rem .625rem; background:#f9fafb; border-radius:.5rem; border:1px solid #e5e7eb; }
.guest-cat-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.guest-info { flex:1; min-width:0; }
.guest-name { font-size:.8125rem; font-weight:500; color:#111827; display:block; truncate:ellipsis; }
.guest-cat { font-size:.7rem; color:#9ca3af; }
.guest-status { flex-shrink:0; }
.status-badge { font-size:.65rem; padding:.125rem .375rem; border-radius:9999px; background:#f3f4f6; color:#6b7280; }
.status-badge--open { background:#d1fae5; color:#059669; }
.btn-remove { font-size:.75rem; color:#ef4444; background:none; border:none; cursor:pointer; flex-shrink:0; }
</style>
