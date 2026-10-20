<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">🎁</span>
        <div>
          <h2 class="section-title">Wishlist Hadiah</h2>
          <p class="section-desc">Buat daftar hadiah yang bisa dibeli tamu</p>
        </div>
      </div>

      <div class="field-group">
        <div class="toggle-row">
          <div>
            <span class="field-label">Aktifkan Wishlist</span>
            <p class="field-hint">Tampilkan halaman wishlist di undangan</p>
          </div>
          <button type="button" class="toggle-btn" :class="{ 'toggle-btn--on': form.enabled }"
            @click="form.enabled = !form.enabled">
            <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.enabled }"></span>
          </button>
        </div>
      </div>

      <div v-for="(item, i) in form.items" :key="i" class="wish-block">
        <div class="wish-block-header">
          <span class="field-label">Item {{ i + 1 }}</span>
          <button v-if="form.items.length > 1" type="button" class="btn-remove" @click="removeItem(i)">✕</button>
        </div>
        <div style="display:grid;grid-template-columns:auto 1fr;gap:.75rem;align-items:center">
          <input v-model="item.emoji" type="text" class="field-input" maxlength="4"
            placeholder="🎁" style="width:56px;font-size:1.5rem;text-align:center" />
          <input v-model="item.name" type="text" class="field-input" placeholder="Nama hadiah" />
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Harga (opsional)</label>
            <input v-model="item.price" type="text" class="field-input" placeholder="Rp 500.000" />
          </div>
          <div class="field-group">
            <label class="field-label" style="font-size:.75rem">Link Toko</label>
            <input v-model="item.url" type="text" class="field-input" placeholder="https://..." />
          </div>
        </div>
        <input v-model="item.note" type="text" class="field-input" placeholder="Catatan tambahan (opsional)" />
      </div>

      <button type="button" class="btn-add" @click="addItem">+ Tambah Item Wishlist</button>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Wishlist</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Wishlist</span>
      </div>
      <div class="preview-body" style="background:linear-gradient(135deg,#fff7ed,#ffedd5)">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" style="color:#9a3412">🎁 Wishlist Kami</div>
          <div class="preview-divider" style="background:#ea580c"></div>
          <div v-if="form.enabled">
            <div v-for="(item, i) in form.items" :key="i" class="preview-wishlist-item">
              <span class="preview-wishlist-emoji">{{ item.emoji || '🎁' }}</span>
              <span class="preview-wishlist-text" style="color:#9a3412">{{ item.name || 'Nama Hadiah' }}</span>
              <span v-if="item.price" class="preview-wishlist-price">{{ item.price }}</span>
            </div>
          </div>
          <div v-else style="font-size:.875rem;color:#9ca3af">Wishlist tidak aktif</div>
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
  items: [
    { emoji: '🍳', name: '', price: '', url: '', note: '' },
    { emoji: '🛁', name: '', price: '', url: '', note: '' },
  ],
})

function addItem() {
  form.value.items.push({ emoji: '🎁', name: '', price: '', url: '', note: '' })
}

function removeItem(i) {
  form.value.items.splice(i, 1)
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
.wish-block { border:1px solid #fed7aa; border-radius:.75rem; padding:1rem; display:flex; flex-direction:column; gap:.625rem; background:#fff7ed; }
.wish-block-header { display:flex; align-items:center; justify-content:space-between; }
.btn-remove { font-size:.875rem; color:#ef4444; background:none; border:none; cursor:pointer; font-weight:700; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; }
.btn-remove:hover { background:#fee2e2; }
.btn-add { border:2px dashed #d1d5db; border-radius:.75rem; padding:.625rem 1rem; font-size:.875rem; color:#6b7280; background:white; cursor:pointer; text-align:center; width:100%; }
.btn-add:hover { border-color:#ea580c; color:#ea580c; }
.toggle-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
.toggle-btn { width:44px; height:24px; border-radius:12px; background:#d1d5db; border:none; cursor:pointer; position:relative; flex-shrink:0; transition:background .2s; }
.toggle-btn--on { background:#10b981; }
.toggle-thumb { position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; }
.toggle-thumb--on { transform:translateX(20px); }
</style>
