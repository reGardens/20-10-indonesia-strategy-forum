<template>
  <div class="section-layout">
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">⏰</span>
        <div>
          <h2 class="section-title">Countdown Wedding</h2>
          <p class="section-desc">Hitung mundur menuju hari bahagia</p>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="weddingDate">Tanggal & Waktu Akad</label>
        <input id="weddingDate" v-model="form.weddingDatetime" type="datetime-local" class="field-input" />
      </div>

      <div class="field-group">
        <label class="field-label" for="countdownTitle">Judul Countdown</label>
        <input id="countdownTitle" v-model="form.title" type="text" class="field-input"
          placeholder="Contoh: Menuju Hari Bahagia Kami" />
      </div>

      <div class="field-group">
        <label class="field-label">Tampilkan Unit Waktu</label>
        <div class="checkbox-group">
          <label v-for="unit in units" :key="unit.key" class="checkbox-item">
            <input type="checkbox" v-model="form.showUnits" :value="unit.key" />
            <span>{{ unit.label }}</span>
          </label>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label">Warna Angka Countdown</label>
        <div class="color-row">
          <div class="color-item">
            <label class="color-label">Angka</label>
            <input type="color" v-model="form.numColor" class="color-input" />
          </div>
          <div class="color-item">
            <label class="color-label">Latar</label>
            <input type="color" v-model="form.bgColor" class="color-input" />
          </div>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Countdown</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">Countdown</span>
      </div>
      <div class="preview-body" :style="{ background: `linear-gradient(135deg, ${form.bgColor}22, ${form.bgColor}44)` }">
        <div class="preview-content" style="font-family:'Playfair Display',serif">
          <div class="preview-couple" :style="{ color: form.numColor }">⏳ {{ form.title || 'Menuju Hari Bahagia' }}</div>
          <div class="preview-divider" :style="{ background: form.numColor }"></div>
          <div class="preview-countdown">
            <div v-for="u in activeUnits" :key="u.key" class="preview-countdown-unit"
              :style="{ background: form.bgColor + '22', border: `1px solid ${form.numColor}30` }">
              <div class="preview-countdown-num" :style="{ color: form.numColor }">{{ countdown[u.key] }}</div>
              <div class="preview-countdown-lbl">{{ u.label }}</div>
            </div>
          </div>
          <div v-if="form.weddingDatetime" style="font-size:.75rem;margin-top:.5rem" :style="{ color: form.numColor + 'aa' }">
            📅 {{ formatDt(form.weddingDatetime) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import './section.css'

const saving = ref(false)
const saved = ref(false)
let timer = null

const units = [
  { key: 'days', label: 'Hari' },
  { key: 'hours', label: 'Jam' },
  { key: 'minutes', label: 'Menit' },
  { key: 'seconds', label: 'Detik' },
]

const form = ref({
  weddingDatetime: '',
  title: 'Menuju Hari Bahagia Kami',
  showUnits: ['days', 'hours', 'minutes', 'seconds'],
  numColor: '#10b981',
  bgColor: '#ffffff',
})

const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' })
const activeUnits = computed(() => units.filter(u => form.value.showUnits.includes(u.key)))

function tick() {
  if (!form.value.weddingDatetime) {
    countdown.value = { days: '12', hours: '08', minutes: '45', seconds: '30' }
    return
  }
  const target = new Date(form.value.weddingDatetime).getTime()
  const diff = target - Date.now()
  if (diff <= 0) {
    countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' }
    return
  }
  const s = Math.floor(diff / 1000)
  countdown.value = {
    days: String(Math.floor(s / 86400)).padStart(2, '0'),
    hours: String(Math.floor((s % 86400) / 3600)).padStart(2, '0'),
    minutes: String(Math.floor((s % 3600) / 60)).padStart(2, '0'),
    seconds: String(s % 60).padStart(2, '0'),
  }
}

function formatDt(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

onMounted(() => { tick(); timer = setInterval(tick, 1000) })
onUnmounted(() => clearInterval(timer))

async function handleSave() {
  saving.value = true
  await new Promise(r => setTimeout(r, 800))
  saving.value = false
  saved.value = true
  setTimeout(() => { saved.value = false }, 2500)
}
</script>

<style scoped>
.checkbox-group { display:flex; flex-wrap:wrap; gap:.75rem; }
.checkbox-item { display:flex; align-items:center; gap:.375rem; font-size:.875rem; color:#374151; cursor:pointer; }
.checkbox-item input { accent-color:#10b981; cursor:pointer; }
</style>
