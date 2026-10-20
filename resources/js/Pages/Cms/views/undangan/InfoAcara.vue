<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">📅</span>
        <div>
          <h2 class="section-title">Informasi Acara</h2>
          <p class="section-desc">Tambahkan detail rangkaian acara pernikahan</p>
        </div>
      </div>

      <div v-for="(event, idx) in form.events" :key="idx" class="event-block">
        <div class="event-block-header">
          <span class="field-label">Acara {{ idx + 1 }}</span>
          <button v-if="form.events.length > 1" type="button" class="btn-remove" @click="removeEvent(idx)">✕ Hapus</button>
        </div>
        <div class="field-group">
          <input v-model="event.name" type="text" class="field-input" placeholder="Nama acara, misal: Akad Nikah" />
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Tanggal</label>
            <input v-model="event.date" type="date" class="field-input" />
          </div>
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Dress Code</label>
            <input v-model="event.dressCode" type="text" class="field-input" placeholder="Contoh: Soft Pink" />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Mulai</label>
            <input v-model="event.startTime" type="time" class="field-input" />
          </div>
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Selesai</label>
            <input v-model="event.endTime" type="time" class="field-input" />
          </div>
        </div>
        <div class="field-group">
          <input v-model="event.venue" type="text" class="field-input" placeholder="Nama tempat, misal: Gedung Serbaguna Mawar" />
        </div>
        <div class="field-group">
          <textarea v-model="event.address" rows="2" class="field-input field-textarea" placeholder="Alamat lengkap..." />
        </div>
        <div class="field-group">
          <input v-model="event.mapsUrl" type="text" class="field-input" placeholder="Link Google Maps (opsional)" />
        </div>
      </div>

      <button type="button" class="btn-add" @click="addEvent">+ Tambah Acara</button>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Acara</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Informasi Acara</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#fffbeb,#fef3c7)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#92400e">Rangkaian Acara</div>
          <div class="preview-divider" style="background:#d97706"></div>
          <div v-for="(ev, i) in form.events" :key="i" class="preview-event-card" style="border-color:#d9770640;background:rgba(255,255,255,.7)">
            <div class="preview-event-name" style="color:#92400e">{{ ev.name || 'Nama Acara' }}</div>
            <div class="preview-event-time">
              <span v-if="ev.date">📅 {{ formatDate(ev.date) }}</span>
            </div>
            <div class="preview-event-time" v-if="ev.startTime">
              ⏰ {{ ev.startTime }} <span v-if="ev.endTime"> – {{ ev.endTime }}</span>
            </div>
            <div class="preview-event-time" v-if="ev.venue">📍 {{ ev.venue }}</div>
            <div class="preview-event-time" v-if="ev.dressCode">👗 Dress Code: {{ ev.dressCode }}</div>
            <a v-if="ev.mapsUrl" href="#" class="preview-maps-link">🗺️ Lihat di Maps</a>
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
  events: [
    { name: '', date: '', startTime: '', endTime: '', venue: '', address: '', mapsUrl: '', dressCode: '' }
  ],
})

function addEvent() {
  form.value.events.push({ name: '', date: '', startTime: '', endTime: '', venue: '', address: '', mapsUrl: '', dressCode: '' })
}

function removeEvent(idx) {
  form.value.events.splice(idx, 1)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' })
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
.event-block {
  border: 1px solid #e5e7eb;
  border-radius: .75rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  background: #f9fafb;
}
.event-block-header { display:flex; align-items:center; justify-content:space-between; }
.btn-remove { font-size:.75rem; color:#ef4444; background:none; border:none; cursor:pointer; font-weight:500; }
.btn-remove:hover { text-decoration:underline; }
.btn-add { border:2px dashed #d1d5db; border-radius:.75rem; padding:.625rem 1rem; font-size:.875rem; color:#6b7280; background:white; cursor:pointer; text-align:center; width:100%; transition:border-color .15s,color .15s; }
.btn-add:hover { border-color:#10b981; color:#10b981; }
.preview-maps-link { font-size:.75rem; color:#10b981; text-decoration:none; font-weight:500; }
</style>
