<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">💬</span>
        <div>
          <h2 class="section-title">Ucapan & Doa</h2>
          <p class="section-desc">Buku tamu digital untuk ucapan dan doa dari tamu</p>
        </div>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Aktifkan Kolom Ucapan</span>
            <p class="field-hint">Tampilkan form ucapan di halaman undangan</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.enabled }"
            @click="form.enabled = !form.enabled">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.enabled }"></span>
          </button>
        </div>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Moderasi Ucapan</span>
            <p class="field-hint">Ucapan harus disetujui admin sebelum tampil</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.moderated }"
            @click="form.moderated = !form.moderated">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.moderated }"></span>
          </button>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="wishPlaceholder">Placeholder Pesan</label>
        <input id="wishPlaceholder" v-model="form.placeholder" type="text" class="field-input"
          placeholder="Tuliskan ucapan dan doa terbaik Anda untuk kami..." />
      </div>

      <div class="field-group">
        <label class="field-label" for="maxWish">Batas Karakter Ucapan</label>
        <input id="maxWish" v-model="form.maxChars" type="number" min="50" max="1000" class="field-input" />
        <span class="field-hint">Maksimum {{ form.maxChars }} karakter per ucapan</span>
      </div>

      <div class="field-group">
        <label class="field-label">Warna Kartu Ucapan</label>
        <div class="color-row">
          <div class="color-item">
            <label class="color-label">Latar Kartu</label>
            <input type="color" v-model="form.cardBg" class="color-input" />
          </div>
          <div class="color-item">
            <label class="color-label">Teks Nama</label>
            <input type="color" v-model="form.nameColor" class="color-input" />
          </div>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Ucapan</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Ucapan & Doa</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#fdf4ff,#fae8ff)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#581c87">💬 Ucapan & Doa</div>
          <div class="preview-divider" style="background:#a855f7"></div>
          <div v-if="form.enabled">
            <div class="preview-rsvp-section" style="background:rgba(255,255,255,.7)">
              <div class="preview-field-mock">👤 Nama</div>
              <div class="preview-field-mock" style="min-height:60px">{{ form.placeholder || 'Tulis ucapan Anda...' }}</div>
              <button class="preview-rsvp-btn" style="background:#a855f7">Kirim Ucapan</button>
            </div>
            <!-- Sample cards -->
            <div v-for="w in sampleWishes" :key="w.name" class="preview-wish-card"
              :style="{ background: form.cardBg, borderColor: form.cardBg }">
              <div class="preview-wish-name" :style="{ color: form.nameColor }">{{ w.name }}</div>
              <div class="preview-wish-msg">{{ w.msg }}</div>
            </div>
          </div>
          <div v-else style="font-size:.875rem;color:#9ca3af">Ucapan tidak aktif</div>
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
  moderated: false,
  placeholder: 'Tuliskan ucapan dan doa terbaik Anda...',
  maxChars: 300,
  cardBg: '#fef9ff',
  nameColor: '#7c3aed',
})

const sampleWishes = [
  { name: 'Siti Rahayu', msg: 'Semoga menjadi keluarga yang sakinah, mawaddah, warahmah 💕' },
  { name: 'Bapak Ahmad', msg: 'Selamat menempuh hidup baru, semoga bahagia selalu!' },
]

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
