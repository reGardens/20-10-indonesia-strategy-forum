<template>
  <div class="section-layout">
    <!-- Left: Form -->
    <div class="section-form">
      <div class="section-header">
        <span class="section-icon">🎨</span>
        <div>
          <h2 class="section-title">Tema Undangan</h2>
          <p class="section-desc">Pilih tema yang sesuai dengan nuansa pernikahanmu</p>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label">Pilih Tema</label>
        <div class="template-grid">
          <button
            v-for="tmpl in templates"
            :key="tmpl.id"
            type="button"
            class="template-card"
            :class="{ 'template-card--active': form.templateId === tmpl.id }"
            @click="form.templateId = tmpl.id"
          >
            <div class="template-swatch" :style="{ background: tmplGradient(tmpl.id) }">
              <span class="template-icon">{{ tmplIcon(tmpl.id) }}</span>
            </div>
            <span class="template-name">{{ tmpl.name }}</span>
          </button>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label">Warna Utama</label>
        <div class="color-row">
          <div class="color-item">
            <label class="color-label">Primer</label>
            <input type="color" v-model="form.primaryColor" class="color-input" />
            <span class="color-hex">{{ form.primaryColor }}</span>
          </div>
          <div class="color-item">
            <label class="color-label">Sekunder</label>
            <input type="color" v-model="form.secondaryColor" class="color-input" />
            <span class="color-hex">{{ form.secondaryColor }}</span>
          </div>
          <div class="color-item">
            <label class="color-label">Aksen</label>
            <input type="color" v-model="form.accentColor" class="color-input" />
            <span class="color-hex">{{ form.accentColor }}</span>
          </div>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="fontFamily">Font Huruf</label>
        <select id="fontFamily" v-model="form.fontFamily" class="field-input">
          <option v-for="f in fonts" :key="f" :value="f">{{ f }}</option>
        </select>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-save" :disabled="saving" @click="handleSave">
          <span v-if="saving">Menyimpan…</span>
          <span v-else>💾 Simpan Tema</span>
        </button>
        <span v-if="saved" class="save-ok">✅ Tersimpan!</span>
      </div>
    </div>

    <!-- Right: Live Preview -->
    <div class="section-preview">
      <div class="preview-header">
        <span class="preview-badge">Live Preview</span>
        <span class="preview-tmpl-name">{{ selectedTemplateName }}</span>
      </div>
      <div class="preview-body" :style="previewBg">
        <div class="preview-accent-bar" :style="{ background: form.accentColor }"></div>
        <div class="preview-content" :style="{ fontFamily: form.fontFamily }">
          <div class="preview-badge-pill" :style="{ background: form.primaryColor }">
            {{ selectedTemplateName }}
          </div>
          <div class="preview-couple" :style="{ color: form.primaryColor }">Budi &amp; Ani</div>
          <div class="preview-divider" :style="{ background: form.accentColor }"></div>
          <div class="preview-date" :style="{ color: form.secondaryColor }">25 Desember 2024</div>
          <div class="preview-icon-row">
            <div class="preview-line" :style="{ background: form.accentColor }"></div>
            <span>{{ tmplIcon(form.templateId) }}</span>
            <div class="preview-line" :style="{ background: form.accentColor }"></div>
          </div>
          <div class="preview-event-card" :style="{ borderColor: form.secondaryColor + '60' }">
            <div class="preview-event-name" :style="{ color: form.primaryColor }">Akad Nikah</div>
            <div class="preview-event-time">08:00 - 10:00 WIB</div>
          </div>
          <button class="preview-rsvp-btn" :style="{ background: form.primaryColor }">
            Konfirmasi Kehadiran
          </button>
        </div>
        <div class="preview-accent-bar preview-accent-bar--bottom" :style="{ background: form.accentColor }"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useStore } from 'vuex'
import './section.css'

const store = useStore()
const saving = ref(false)
const saved = ref(false)

const templates = computed(() => store.getters['template/templates'])

const fonts = [
  'Playfair Display', 'Cormorant Garamond', 'Great Vibes',
  'Cinzel', 'EB Garamond', 'Dancing Script', 'Lora', 'Merriweather',
]

const form = ref({
  templateId: 'batik-elegance',
  primaryColor: '#8B4513',
  secondaryColor: '#D2691E',
  accentColor: '#FFD700',
  fontFamily: 'Playfair Display',
})

const gradients = {
  'jawa-klasik': 'linear-gradient(135deg,#FFF8F0,#FFE0BB)',
  'sunda-pasundan': 'linear-gradient(135deg,#F0F7E8,#D8F0C0)',
  'bali-dewata': 'linear-gradient(135deg,#FFF5F5,#FFD0D0)',
  'betawi-jakarta': 'linear-gradient(135deg,#FFFBF0,#FFE8C0)',
  'minang-rantau': 'linear-gradient(135deg,#FDF6F0,#F5DCC0)',
  'bugis-makassar': 'linear-gradient(135deg,#F0F5FA,#C8E0F5)',
  'dayak-borneo': 'linear-gradient(135deg,#FAF5F0,#E8D8C8)',
  'toraja-sulawesi': 'linear-gradient(135deg,#F8F0EA,#EFD8C8)',
  'melayu-riau': 'linear-gradient(135deg,#FFFDE8,#FFF3A0)',
  'papua-cendrawasih': 'linear-gradient(135deg,#F8F0E8,#F0D8C8)',
  'batik-elegance': 'linear-gradient(135deg,#FFF0E0,#FFD0A0)',
}
const icons = {
  'jawa-klasik': '🪷', 'sunda-pasundan': '🌿', 'bali-dewata': '🏵️',
  'betawi-jakarta': '🎪', 'minang-rantau': '👑', 'bugis-makassar': '⚓',
  'dayak-borneo': '🛡️', 'toraja-sulawesi': '🏛️', 'melayu-riau': '✨',
  'papua-cendrawasih': '🦅', 'batik-elegance': '🌺',
}

function tmplGradient(id) { return gradients[id] || 'linear-gradient(135deg,#f3f4f6,#e5e7eb)' }
function tmplIcon(id) { return icons[id] || '💒' }

const selectedTemplateName = computed(() => {
  const t = templates.value.find(t => t.id === form.value.templateId)
  return t ? t.name : form.value.templateId
})

const previewBg = computed(() => ({
  background: gradients[form.value.templateId] || 'linear-gradient(135deg,#f9fafb,#f3f4f6)',
  fontFamily: form.value.fontFamily,
}))

function populateFromStore() {
  const s = store.getters['settings/settings']
  if (s) {
    if (s.templateId) form.value.templateId = s.templateId
    if (s.themeColors) {
      form.value.primaryColor = s.themeColors.primary || form.value.primaryColor
      form.value.secondaryColor = s.themeColors.secondary || form.value.secondaryColor
      form.value.accentColor = s.themeColors.accent || form.value.accentColor
    }
    if (s.fontFamily) form.value.fontFamily = s.fontFamily
  }
}

async function handleSave() {
  saving.value = true
  try {
    await store.dispatch('settings/saveSettings', {
      templateId: form.value.templateId,
      themeColors: {
        primary: form.value.primaryColor,
        secondary: form.value.secondaryColor,
        accent: form.value.accentColor,
      },
      fontFamily: form.value.fontFamily,
    })
    saved.value = true
    setTimeout(() => { saved.value = false }, 2500)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    store.dispatch('settings/fetchSettings'),
    store.dispatch('template/fetchTemplates'),
  ])
  populateFromStore()
})
</script>
