<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">🎥</span>
        <div>
          <h2 class="section-title">Live Streaming</h2>
          <p class="section-desc">Siarkan acara pernikahanmu secara langsung</p>
        </div>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Aktifkan Live Streaming</span>
            <p class="field-hint">Tampilkan tombol tonton live di undangan</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.enabled }"
            @click="form.enabled = !form.enabled">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.enabled }"></span>
          </button>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="streamUrl">URL Live Streaming</label>
        <input id="streamUrl" v-model="form.url" type="text" class="field-input"
          placeholder="Contoh: https://youtube.com/live/..." />
        <span class="field-hint">YouTube Live, Zoom, atau platform streaming lainnya</span>
      </div>

      <div class="field-group">
        <label class="field-label" for="streamPlatform">Platform</label>
        <select id="streamPlatform" v-model="form.platform" class="field-input">
          <option value="youtube">▶️ YouTube Live</option>
          <option value="zoom">🔵 Zoom Webinar</option>
          <option value="instagram">📸 Instagram Live</option>
          <option value="tiktok">🎵 TikTok Live</option>
          <option value="other">🌐 Lainnya</option>
        </select>
      </div>

      <div class="field-group">
        <label class="field-label" for="streamStart">Mulai Siaran</label>
        <input id="streamStart" v-model="form.startAt" type="datetime-local" class="field-input" />
      </div>

      <div class="field-group">
        <label class="field-label" for="streamMsg">Pesan Siaran</label>
        <textarea id="streamMsg" v-model="form.message" rows="2" class="field-input field-textarea"
          placeholder="Bergabunglah dengan kami secara virtual dan saksikan momen bahagia kami!" />
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Streaming</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Live Streaming</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#0f172a,#1e293b)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:white">🎥 Live Streaming</div>
          <div class="preview-divider" style="background:#ef4444"></div>
          <div v-if="form.enabled">
            <div class="preview-stream-box">
              <span>{{ platformIcon }}</span>
            </div>
            <p v-if="form.message" style="font-size:.75rem;color:#94a3b8;font-style:italic;margin-top:.5rem;text-align:center">
              "{{ form.message }}"
            </p>
            <div v-if="form.startAt" style="font-size:.7rem;color:#64748b;margin-top:.25rem">
              📅 {{ formatDt(form.startAt) }}
            </div>
            <button style="background:#ef4444;color:white;border:none;border-radius:.5rem;padding:.5rem 1.25rem;font-size:.8125rem;font-weight:600;cursor:default;margin-top:.5rem;width:100%">
              🔴 Tonton Live
            </button>
          </div>
          <div v-else style="font-size:.875rem;color:#64748b">Streaming tidak aktif</div>
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

const form = ref({
  enabled: false,
  url: '',
  platform: 'youtube',
  startAt: '',
  message: 'Bergabunglah dengan kami secara virtual!',
})

const platformIcons = { youtube: '▶️', zoom: '🔵', instagram: '📸', tiktok: '🎵', other: '🌐' }
const platformIcon = computed(() => platformIcons[form.value.platform] || '🌐')

function formatDt(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleString('id-ID', { weekday:'long', day:'numeric', month:'long', hour:'2-digit', minute:'2-digit' })
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
.toggle-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
.toggle-btn { width:44px; height:24px; border-radius:12px; background:#d1d5db; border:none; cursor:pointer; position:relative; flex-shrink:0; transition:background .2s; }
.toggle-btn--on { background:#10b981; }
.toggle-thumb { position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; }
.toggle-thumb--on { transform:translateX(20px); }
</style>
