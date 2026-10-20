<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">🔍</span>
        <div>
          <h2 class="section-title">QR Code & Check-in</h2>
          <p class="section-desc">Kelola check-in tamu dengan QR Code personal</p>
        </div>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Aktifkan QR Code per Tamu</span>
            <p class="field-hint">Setiap tamu mendapatkan QR Code unik untuk check-in</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.enabled }"
            @click="form.enabled = !form.enabled">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.enabled }"></span>
          </button>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="qrStyle">Gaya QR Code</label>
        <select id="qrStyle" v-model="form.style" class="field-input">
          <option value="square">◼ Kotak Klasik</option>
          <option value="rounded">⬛ Sudut Membulat</option>
          <option value="dots">⚫ Titik-Titik</option>
        </select>
      </div>

      <div class="field-group">
        <label class="field-label">Warna QR</label>
        <div class="color-row">
          <div class="color-item">
            <label class="color-label">Foreground</label>
            <input type="color" v-model="form.fgColor" class="color-input" />
          </div>
          <div class="color-item">
            <label class="color-label">Background</label>
            <input type="color" v-model="form.bgColor" class="color-input" />
          </div>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="checkinMsg">Pesan Setelah Check-in</label>
        <textarea id="checkinMsg" v-model="form.message" rows="2" class="field-input field-textarea"
          placeholder="Selamat datang! Silakan menuju tempat duduk Anda." />
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Notifikasi Real-time ke Admin</span>
            <p class="field-hint">Terima notifikasi setiap kali tamu check-in</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.notifyAdmin }"
            @click="form.notifyAdmin = !form.notifyAdmin">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.notifyAdmin }"></span>
          </button>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan QR Setting</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">QR Check-in</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#166534">🔍 QR Code Tamu</div>
          <div class="preview-divider" style="background:#16a34a"></div>
          <div v-if="form.enabled">
            <div class="preview-qr-box" :style="{ background: form.bgColor, borderColor: form.fgColor, color: form.fgColor }">
              {{ form.style === 'dots' ? '⚫⬛' : '▩' }}
            </div>
            <p style="font-size:.75rem;color:#6b7280;margin-top:.5rem">Budi & Ani — Meja 3</p>
            <p v-if="form.message" style="font-size:.75rem;color:#166534;font-style:italic;margin-top:.375rem;padding:.5rem;background:rgba(255,255,255,.6);border-radius:.5rem">
              "{{ form.message }}"
            </p>
          </div>
          <div v-else style="font-size:.875rem;color:#9ca3af">QR Code tidak aktif</div>
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
  enabled: true,
  style: 'square',
  fgColor: '#166534',
  bgColor: '#ffffff',
  message: 'Selamat datang! Silakan menuju tempat duduk Anda.',
  notifyAdmin: true,
})

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
