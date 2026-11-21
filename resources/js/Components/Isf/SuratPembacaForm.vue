<script setup>
/**
 * SuratPembacaForm.vue — Form pengajuan "Buat Artikel" oleh Member ISF (Opini Publik).
 *
 * Mengimplementasikan field dan komponen yang sama persis dengan form CMS Artikel:
 * - Select Kategori & Sub-Kategori via komponen UI CMS (Select, SelectTrigger, SelectValue, SelectContent, SelectItem)
 * - Data Kategori & Sub-Kategori sinkron dengan CMS (activeCategories, getSubcategoriesFor)
 * - Placeholder Ringkasan Eksekutif (Summary) sama persis dengan CMS
 * - Upload Foto Penulis 1:1 dengan fitur Drag & Drop (tanpa input tempel URL)
 * - Upload Gambar Sampul 16:9 dengan fitur Drag & Drop
 * - Tooltip penjelasan pada estimasi Waktu Baca
 * - DatePicker komponen ISF pada Blok Fakta Kunci (bukan native input)
 * - Auto-save ke LocalStorage agar input tersimpan jika tidak sengaja refresh
 * - Tata letak rapi, footer tombol mentok ke bawah (docked), dan indikator scrollbar rapi
 */
import { ref, reactive, computed, watch, onMounted } from 'vue'
import http from '@/api/http'
import { submitSuratPembaca } from '@/api/services/isfPublicService'
import { useIsfSession } from '@/Composables/useIsfSession'
import { activeCategories as CATEGORIES, loadCategories, getSubcategoriesFor } from '@/Pages/Cms/Isf/options'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
  DatePicker,
} from '@/Components/UI'
import {
  IconFileText,
  IconSend,
  IconRefresh,
  IconCheck,
  IconAlertCircle,
  IconX,
  IconShieldCheck,
  IconLoader2,
  IconUser,
  IconPhoto,
  IconPlus,
  IconTrash,
  IconClock,
  IconInfoCircle,
} from '@tabler/icons-vue'

const props = defineProps({
  isModal: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submitted', 'close'])

const { session, isLoggedIn } = useIsfSession()

const STORAGE_KEY = 'isf_buat_artikel_draft'

const form = reactive({
  title: '',
  slug: '',
  category: 'kebijakan',
  subcategory: '',
  summary: '',
  author_name: '',
  author_role: '',
  sender_city: '',
  email_pengirim: '',
  author_avatar: '',
  reading_minutes: 5,
  image_url: '',
  image_alt: '',
  link_url: '',
  fact_blocks: [],
  body: '',
  captcha_key: '',
  captcha_answer: '',
})

const errors = ref({})
const submitting = ref(false)
const submittedSuccess = ref(false)
const successMessage = ref('')
const isSlugManual = ref(false)

// Drag and drop states & file input refs (CMS style)
const avatarInputRef = ref(null)
const coverInputRef = ref(null)

const isDraggingAvatar = ref(false)
const uploadingAvatar = ref(false)
const avatarError = ref('')

const isDraggingCover = ref(false)
const uploadingCover = ref(false)
const coverError = ref('')

function triggerAvatarInput() {
  avatarInputRef.value?.click()
}

function triggerCoverInput() {
  coverInputRef.value?.click()
}

function removeAvatar() {
  form.author_avatar = ''
  avatarError.value = ''
}

function removeCover() {
  form.image_url = ''
  form.image_alt = ''
  coverError.value = ''
}

// Tooltip state for reading time
const showReadingTooltip = ref(false)

// Captcha state
const captchaKey = ref('')
const captchaImage = ref('')
const captchaLoading = ref(false)

// Subcategories computed from CMS options
const availableSubcategories = computed(() => getSubcategoriesFor(form.category))

const subcategoryValue = computed({
  get: () => form.subcategory || '__NONE__',
  set: (val) => {
    form.subcategory = val === '__NONE__' ? '' : val
  },
})

function generateSlug(val) {
  if (!val) return ''
  return val
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '')
}

function onTitleInput() {
  if (!isSlugManual.value || !form.slug) {
    form.slug = generateSlug(form.title)
  }
  clearError('title')
}

function onSlugInput() {
  if (!form.slug || !form.slug.trim()) {
    isSlugManual.value = false
    form.slug = generateSlug(form.title)
  } else {
    isSlugManual.value = form.slug !== generateSlug(form.title)
  }
  clearError('slug')
}

// Fact block management
function addFactBlock() {
  if (form.fact_blocks.length >= 10) return
  form.fact_blocks.push({
    claim: '',
    sourceTitle: '',
    sourceUrl: '',
    sourceDate: '',
  })
}

function removeFactBlock(idx) {
  form.fact_blocks.splice(idx, 1)
}

// 1:1 Avatar Process File (shared between click upload and drag-and-drop)
async function processAvatarFile(file) {
  if (!file) return
  avatarError.value = ''

  if (!file.type.startsWith('image/')) {
    avatarError.value = 'File harus berupa gambar (JPG, PNG, WebP).'
    return
  }
  if (file.size > 3 * 1024 * 1024) {
    avatarError.value = 'Ukuran gambar maksimal 3 MB.'
    return
  }

  uploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res = await http.post('/isf/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data?.url) {
      form.author_avatar = res.data.url
    } else {
      avatarError.value = 'Gagal mengunggah gambar.'
    }
  } catch (err) {
    // Local data URL fallback
    const reader = new FileReader()
    reader.onload = (e) => {
      form.author_avatar = e.target.result
    }
    reader.readAsDataURL(file)
  } finally {
    uploadingAvatar.value = false
  }
}

function handleAvatarUpload(event) {
  const file = event.target?.files?.[0]
  if (file) {
    processAvatarFile(file)
  }
  event.target.value = ''
}

function handleDropAvatar(event) {
  isDraggingAvatar.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) {
    processAvatarFile(file)
  }
}

// 16:9 Cover Process File (shared between click upload and drag-and-drop)
async function processCoverFile(file) {
  if (!file) return
  coverError.value = ''

  if (!file.type.startsWith('image/')) {
    coverError.value = 'File harus berupa gambar (JPG, PNG, WebP).'
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    coverError.value = 'Ukuran gambar maksimal 5 MB.'
    return
  }

  uploadingCover.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res = await http.post('/isf/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data?.url) {
      form.image_url = res.data.url
    } else {
      coverError.value = 'Gagal mengunggah gambar sampul.'
    }
  } catch (err) {
    const reader = new FileReader()
    reader.onload = (e) => {
      form.image_url = e.target.result
    }
    reader.readAsDataURL(file)
  } finally {
    uploadingCover.value = false
  }
}

function handleCoverUpload(event) {
  const file = event.target?.files?.[0]
  if (file) {
    processCoverFile(file)
  }
  event.target.value = ''
}

function handleDropCover(event) {
  isDraggingCover.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) {
    processCoverFile(file)
  }
}

// LocalStorage Auto-Save
function saveDraft() {
  if (typeof window === 'undefined' || !window.localStorage) return
  try {
    const draft = {
      title: form.title,
      slug: form.slug,
      category: form.category,
      subcategory: form.subcategory,
      summary: form.summary,
      author_name: form.author_name,
      author_role: form.author_role,
      sender_city: form.sender_city,
      email_pengirim: form.email_pengirim,
      author_avatar: form.author_avatar,
      reading_minutes: form.reading_minutes,
      image_url: form.image_url,
      image_alt: form.image_alt,
      link_url: form.link_url,
      fact_blocks: form.fact_blocks,
      body: form.body,
    }
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
  } catch (e) {
    // Ignore storage quota / privacy exceptions
  }
}

function loadDraft() {
  if (typeof window === 'undefined' || !window.localStorage) return
  try {
    const saved = window.localStorage.getItem(STORAGE_KEY)
    if (saved) {
      const parsed = JSON.parse(saved)
      if (parsed && typeof parsed === 'object') {
        Object.keys(parsed).forEach((k) => {
          if (k in form && parsed[k] !== undefined && parsed[k] !== null) {
            form[k] = parsed[k]
          }
        })
      }
    }
  } catch (e) {
    // Ignore parse errors
  }
}

function clearDraft() {
  if (typeof window === 'undefined' || !window.localStorage) return
  try {
    window.localStorage.removeItem(STORAGE_KEY)
  } catch (e) {
    // Ignore
  }
}

watch(
  form,
  () => {
    saveDraft()
  },
  { deep: true }
)

async function fetchCaptcha() {
  captchaLoading.value = true
  form.captcha_answer = ''
  try {
    const res = await http.get('/isf/captcha')
    captchaKey.value = res.data?.key || ''
    captchaImage.value = res.data?.image || ''
    form.captcha_key = captchaKey.value
  } catch (err) {
    console.error('Gagal mengambil captcha:', err)
  } finally {
    captchaLoading.value = false
  }
}

function clearError(field) {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

function resetForm() {
  form.title = ''
  form.slug = ''
  form.category = 'kebijakan'
  form.subcategory = ''
  form.summary = ''
  form.author_role = ''
  form.author_avatar = ''
  form.reading_minutes = 5
  form.image_url = ''
  form.image_alt = ''
  form.link_url = ''
  form.fact_blocks = []
  form.body = ''
  form.captcha_answer = ''
  errors.value = {}
  submittedSuccess.value = false
  successMessage.value = ''
  clearDraft()
  fetchCaptcha()
}

async function handleSubmit() {
  errors.value = {}
  form.captcha_key = isLoggedIn.value ? null : captchaKey.value

  const errs = {}
  if (!form.title.trim()) errs.title = ['Judul artikel wajib diisi.']
  else if (form.title.trim().length < 5) errs.title = ['Judul artikel minimal 5 karakter.']

  if (!form.author_name.trim()) errs.author_name = ['Nama lengkap penulis wajib diisi.']
  if (!form.email_pengirim.trim()) {
    errs.email_pengirim = ['Alamat email wajib diisi.']
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_pengirim)) {
    errs.email_pengirim = ['Format email tidak valid.']
  }
  if (!form.body.trim()) {
    errs.body = ['Konten lengkap artikel wajib diisi.']
  } else if (form.body.trim().length < 20) {
    errs.body = ['Konten artikel minimal 20 karakter agar gagasan tersampaikan dengan jelas.']
  }

  if (form.image_url && !form.image_alt.trim()) {
    errs.image_alt = ['Deskripsi gambar (alt text) disarankan diisi jika mengunggah gambar sampul.']
  }

  if (!isLoggedIn.value && !form.captcha_answer.trim()) {
    errs.captcha_answer = ['Verifikasi keamanan wajib diisi.']
  }

  if (Object.keys(errs).length > 0) {
    errors.value = errs
    return
  }

  submitting.value = true
  try {
    const payload = {
      nama_pengirim: form.author_name.trim(),
      email_pengirim: form.email_pengirim.trim(),
      kota_domisili: form.sender_city.trim() || null,
      judul_surat: form.title.trim(),
      slug: form.slug.trim() || generateSlug(form.title.trim()),
      isi_surat: form.body.trim(),
      author_role: form.author_role.trim() || null,
      category: form.category || 'kebijakan',
      subcategory: form.subcategory || null,
      summary: form.summary.trim() || null,
      author_avatar: form.author_avatar || null,
      image_url: form.image_url || null,
      image_alt: form.image_alt.trim() || null,
      reading_minutes: Number(form.reading_minutes) || 5,
      link_url: form.link_url.trim() || null,
      fact_blocks: form.fact_blocks && form.fact_blocks.length > 0 ? form.fact_blocks : null,
      captcha_key: isLoggedIn.value ? null : form.captcha_key,
      captcha_answer: isLoggedIn.value ? null : (form.captcha_answer ? form.captcha_answer.trim() : null),
    }

    const res = await submitSuratPembaca(payload)
    submittedSuccess.value = true
    successMessage.value =
      res?.message ||
      'Artikel Anda berhasil dikirim ke Dewan Redaksi ISF dan sedang dalam antrean kurasi.'
    clearDraft()
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('isf_popup_buat_artikel_open')
    }
    emit('submitted', res?.data)
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      errors.value = {
        general: [err.response?.data?.message || 'Terjadi kesalahan saat mengirim artikel. Silakan coba lagi.'],
      }
    }
    fetchCaptcha()
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadCategories()
  loadDraft()
  if (session.value?.name && !form.author_name) {
    form.author_name = session.value.name
  }
  if (session.value?.email && !form.email_pengirim) {
    form.email_pengirim = session.value.email
  }
  fetchCaptcha()
})
</script>

<template>
  <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl text-neutral-100 shadow-2xl max-w-4xl mx-auto flex flex-col h-[90vh] max-h-[90vh] overflow-hidden">
    <!-- Close button for modal -->
    <button
      v-if="isModal"
      type="button"
      @click="emit('close')"
      class="absolute top-4 right-4 z-30 text-neutral-400 hover:text-white transition-colors p-1.5 rounded-lg bg-neutral-800/80 hover:bg-neutral-800 border border-neutral-700/60 cursor-pointer"
      aria-label="Tutup form"
    >
      <IconX class="w-5 h-5" />
    </button>

    <!-- HEADER (Pinned at Top) -->
    <div class="shrink-0 p-5 sm:p-6 border-b border-neutral-800 bg-neutral-900/95 backdrop-blur-xs relative z-10">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold font-space-grotesk tracking-wide bg-isf-red/10 text-isf-red border border-isf-red/20 mb-2">
        <IconFileText class="w-4 h-4 shrink-0" />
        <span>Artikel by Member</span>
      </div>
      <h3 class="text-xl sm:text-2xl font-bold font-space-grotesk text-white">
        Buat Artikel
      </h3>
      <p class="text-xs sm:text-sm text-neutral-400 mt-1 leading-relaxed max-w-2xl">
        Kirimkan gagasan, pemikiran strategis, dan analisis kritis Anda untuk dipublikasikan di rubrik Opini Publik Indonesia Strategy Forum setelah kurasi Dewan Redaksi.
      </p>
    </div>

    <!-- SUCCESS VIEW -->
    <div v-if="submittedSuccess" class="flex-1 overflow-y-auto p-6 sm:p-10 flex flex-col items-center justify-center text-center space-y-4">
      <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
        <IconCheck class="w-9 h-9" />
      </div>
      <div>
        <h4 class="text-xl sm:text-2xl font-bold font-space-grotesk text-white">Artikel Anda Berhasil Terkirim!</h4>
        <p class="text-xs sm:text-sm text-neutral-300 mt-2 max-w-lg mx-auto leading-relaxed">
          {{ successMessage }}
        </p>
      </div>
      <div class="pt-6 flex items-center justify-center gap-3">
        <button
          type="button"
          @click="resetForm"
          class="px-4 py-2.5 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-xs sm:text-sm font-semibold font-space-grotesk text-white transition-colors cursor-pointer"
        >
          Kirim Artikel Lain
        </button>
        <button
          v-if="isModal"
          type="button"
          @click="emit('close')"
          class="px-5 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-xs sm:text-sm font-semibold font-space-grotesk text-white transition-colors cursor-pointer"
        >
          Selesai
        </button>
      </div>
    </div>

    <!-- MAIN FORM BODY (Scrollable with Sleek Indicator) -->
    <div v-else class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 isf-custom-scrollbar">
      <!-- General Error Alert -->
      <div
        v-if="errors.general"
        class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-start gap-2"
      >
        <IconAlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{{ errors.general[0] }}</span>
      </div>

      <!-- SECTION 1: INFORMASI UTAMA ARTIKEL -->
      <div class="space-y-4 p-4 sm:p-5 rounded-xl bg-neutral-950/70 border border-neutral-800">
        <div class="flex items-center gap-2 text-xs font-bold font-space-grotesk text-white uppercase tracking-wider pb-2 border-b border-neutral-800/80">
          <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
          <span>1. Informasi Utama Artikel</span>
        </div>

        <!-- Judul Artikel -->
        <div>
          <label for="sp_judul" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
            Judul Artikel <span class="text-isf-red">*</span>
          </label>
          <input
            id="sp_judul"
            v-model="form.title"
            type="text"
            placeholder="e.g. Arah Kebijakan Energi Baru Terbarukan 2026"
            maxlength="300"
            @input="onTitleInput"
            :class="[
              'w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
              errors.title ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
            ]"
          />
          <p v-if="errors.title" class="mt-1 text-[11px] text-red-400">
            {{ errors.title[0] }}
          </p>
        </div>

        <!-- Slug URL & Kategori / Subkategori Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <!-- Slug URL -->
          <div class="sm:col-span-2 md:col-span-1">
            <label for="f_slug" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Slug URL
            </label>
            <input
              id="f_slug"
              v-model="form.slug"
              type="text"
              placeholder="Dibuat otomatis dari judul..."
              maxlength="255"
              @input="onSlugInput"
              class="w-full px-3 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs font-mono text-neutral-300 placeholder-neutral-600 focus:outline-none focus:ring-1 focus:ring-isf-red"
            />
          </div>

          <!-- Kategori (Komponen UI Select sama persis dengan CMS) -->
          <div>
            <label for="sp_cat" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Kategori <span class="text-isf-red">*</span>
            </label>
            <Select v-model="form.category">
              <SelectTrigger id="sp_cat" class="h-10 text-xs sm:text-sm font-medium bg-neutral-900 border-neutral-700 text-white focus:ring-isf-red">
                <SelectValue placeholder="Select Kategori" />
              </SelectTrigger>
              <SelectContent class="bg-neutral-900 border-neutral-700 text-neutral-100 z-50">
                <SelectItem
                  v-for="cat in CATEGORIES"
                  :key="cat.value"
                  :value="cat.value"
                  class="text-xs sm:text-sm cursor-pointer hover:bg-neutral-800 focus:bg-neutral-800 text-neutral-200 focus:text-white"
                >
                  {{ cat.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Sub-Kategori (Komponen UI Select sama persis dengan CMS) -->
          <div>
            <label for="sp_subcat" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Sub-Kategori
            </label>
            <Select v-model="subcategoryValue">
              <SelectTrigger id="sp_subcat" class="h-10 text-xs sm:text-sm font-medium bg-neutral-900 border-neutral-700 text-white focus:ring-isf-red">
                <SelectValue placeholder="Select Sub-Kategori" />
              </SelectTrigger>
              <SelectContent class="bg-neutral-900 border-neutral-700 text-neutral-100 z-50">
                <SelectItem value="__NONE__" class="text-xs sm:text-sm cursor-pointer hover:bg-neutral-800 focus:bg-neutral-800 text-neutral-200 focus:text-white">
                  -- Tanpa Sub-Kategori --
                </SelectItem>
                <SelectItem
                  v-for="sub in availableSubcategories"
                  :key="sub.value"
                  :value="sub.value"
                  class="text-xs sm:text-sm cursor-pointer hover:bg-neutral-800 focus:bg-neutral-800 text-neutral-200 focus:text-white"
                >
                  {{ sub.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Ringkasan Eksekutif (Summary) — Placeholder sama persis dengan CMS Artikel -->
        <div>
          <label for="f_summary" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
            Ringkasan Eksekutif (Summary)
          </label>
          <textarea
            id="f_summary"
            v-model="form.summary"
            rows="2"
            placeholder="Ringkasan singkat artikel (tampil di kartu Beranda / hero)..."
            maxlength="1000"
            class="w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all leading-relaxed"
          ></textarea>
        </div>
      </div>

      <!-- SECTION 2: IDENTITAS PENULIS & FOTO 1:1 -->
      <div class="space-y-4 p-4 sm:p-5 rounded-xl bg-neutral-950/70 border border-neutral-800">
        <div class="flex items-center gap-2 text-xs font-bold font-space-grotesk text-white uppercase tracking-wider pb-2 border-b border-neutral-800/80">
          <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
          <span>2. Identitas Penulis & Foto Profil (Ukuran 1:1)</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Nama Lengkap Penulis -->
          <div>
            <label for="sp_nama" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Nama Lengkap Penulis <span class="text-isf-red">*</span>
            </label>
            <input
              id="sp_nama"
              v-model="form.author_name"
              type="text"
              placeholder="Contoh: Dr. Budi Santoso, M.Si."
              maxlength="150"
              @input="clearError('author_name')"
              :class="[
                'w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
                errors.author_name ? 'border-red-500' : 'border-neutral-700'
              ]"
            />
            <p v-if="errors.author_name" class="mt-1 text-[11px] text-red-400">
              {{ errors.author_name[0] }}
            </p>
          </div>

          <!-- Jabatan / Keahlian / Asal Lembaga -->
          <div>
            <label for="f_role" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Jabatan / Keahlian / Afiliasi
            </label>
            <input
              id="f_role"
              v-model="form.author_role"
              type="text"
              placeholder="Contoh: Analis Kebijakan Fiskal / Peneliti Independen"
              maxlength="150"
              class="w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all"
            />
          </div>

          <!-- Email Penulis -->
          <div>
            <label for="sp_email" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
              Alamat Email (Privat) <span class="text-isf-red">*</span>
            </label>
            <input
              id="sp_email"
              v-model="form.email_pengirim"
              type="email"
              placeholder="email@domain.com"
              maxlength="150"
              @input="clearError('email_pengirim')"
              :class="[
                'w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
                errors.email_pengirim ? 'border-red-500' : 'border-neutral-700'
              ]"
            />
            <p v-if="errors.email_pengirim" class="mt-1 text-[11px] text-red-400">
              {{ errors.email_pengirim[0] }}
            </p>
          </div>

          <!-- Kota Domisili & Waktu Baca (dengan Tooltip Penjelasan) -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="sp_kota" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
                Kota Domisili
              </label>
              <input
                id="sp_kota"
                v-model="form.sender_city"
                type="text"
                placeholder="Bandung, Jawa Barat"
                maxlength="100"
                class="w-full px-3 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs text-white placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-isf-red"
              />
            </div>
            <div>
              <!-- Label Waktu Baca dengan Tooltip -->
              <div class="flex items-center gap-1.5 mb-1 relative">
                <label for="f_read" class="text-xs font-semibold font-space-grotesk text-neutral-300">
                  Waktu Baca (Menit)
                </label>
                <div
                  class="relative inline-flex items-center group cursor-pointer"
                  @mouseenter="showReadingTooltip = true"
                  @mouseleave="showReadingTooltip = false"
                >
                  <IconInfoCircle class="w-3.5 h-3.5 text-neutral-400 hover:text-white transition-colors" />
                  <!-- Tooltip Balloon -->
                  <div
                    v-if="showReadingTooltip"
                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 w-52 p-2 bg-neutral-800 text-neutral-200 text-[11px] rounded-lg shadow-xl border border-neutral-700 z-50 pointer-events-none leading-relaxed text-center"
                  >
                    Estimasi durasi waktu yang dibutuhkan pembaca untuk menyelesaikan artikel (dalam hitungan menit).
                  </div>
                </div>
              </div>
              <input
                id="f_read"
                v-model.number="form.reading_minutes"
                type="number"
                min="1"
                max="60"
                class="w-full px-3 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs text-white focus:outline-none focus:ring-1 focus:ring-isf-red"
              />
            </div>
          </div>
        </div>

        <!-- FOTO PENULIS KECIL UKURAN 1:1 (Persegi) — Drag & Drop Supported (CMS ImageUpload Style) -->
        <div class="p-4 rounded-xl bg-neutral-900/80 border border-neutral-700/80 space-y-3">
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-1.5 text-xs font-semibold font-space-grotesk text-white">
              <IconUser class="w-4 h-4 text-isf-red" />
              <span>Upload Foto Penulis (Ukuran 1:1)</span>
              <span class="text-[11px] text-neutral-400 font-normal">— Rasio persegi 1:1</span>
            </label>
            <span v-if="form.author_avatar" class="text-[10.5px] text-neutral-400 italic">
              Arahkan kursor untuk mengganti atau menghapus
            </span>
          </div>

          <!-- Hidden File Input -->
          <input
            ref="avatarInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarUpload"
          />

          <!-- 1. Kondisi Foto SUDAH ADA: Pratinjau 1:1 dengan hover overlay (Ganti Foto & Hapus) -->
          <div v-if="form.author_avatar" class="flex justify-center">
            <div class="relative w-28 h-28 rounded-xl overflow-hidden border-2 border-neutral-700 bg-neutral-950 group cursor-pointer shadow-md">
              <img
                :src="form.author_avatar"
                alt="Foto Penulis 1:1"
                class="w-full h-full object-cover transition duration-300 group-hover:scale-105"
              />
              <!-- Hover Overlay -->
              <div class="absolute inset-0 bg-neutral-950/75 backdrop-blur-xs flex flex-col items-center justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <button
                  type="button"
                  @click.stop="triggerAvatarInput"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-white text-neutral-900 text-[11px] font-semibold hover:bg-neutral-100 transition shadow-xs cursor-pointer"
                  title="Ganti Foto Penulis"
                >
                  <IconPhoto class="w-3.5 h-3.5" />
                  <span>Ganti</span>
                </button>
                <button
                  type="button"
                  @click.stop="removeAvatar"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-isf-red text-white text-[11px] font-semibold hover:bg-red-700 transition shadow-xs cursor-pointer"
                  title="Hapus Foto Penulis"
                >
                  <IconX class="w-3.5 h-3.5" />
                  <span>Hapus</span>
                </button>
              </div>
            </div>
          </div>

          <!-- 2. Kondisi Foto BELUM ADA: Area Drag-and-Drop Uploader (Sama persis seperti CMS) -->
          <div
            v-else
            @dragover.prevent="isDraggingAvatar = true"
            @dragleave.prevent="isDraggingAvatar = false"
            @drop.prevent="handleDropAvatar"
            @click="triggerAvatarInput"
            :class="[
              'border-2 border-dashed rounded-xl p-5 flex flex-col items-center justify-center cursor-pointer transition-colors',
              isDraggingAvatar
                ? 'border-isf-red bg-red-950/20'
                : 'border-neutral-700 bg-neutral-950/60 hover:border-isf-red/50 hover:bg-neutral-900/60'
            ]"
          >
            <div v-if="uploadingAvatar" class="text-center space-y-1.5 py-1">
              <IconPhoto class="w-6 h-6 mx-auto text-isf-red animate-pulse" />
              <p class="text-xs text-neutral-300 font-medium">Mengunggah foto penulis...</p>
            </div>
            <div v-else class="text-center space-y-1 py-1">
              <IconPhoto class="w-7 h-7 mx-auto text-neutral-400 group-hover:text-isf-red transition-colors" />
              <div class="text-xs font-semibold font-space-grotesk text-white">
                Klik atau seret foto penulis (1:1) ke sini
              </div>
              <p class="text-[11px] text-neutral-400">
                Format persegi 1:1 (JPG, PNG, WebP) — maks. 3 MB
              </p>
            </div>
          </div>
          <p v-if="avatarError" class="text-[11px] text-red-400 font-medium text-center">
            {{ avatarError }}
          </p>
        </div>
      </div>

      <!-- SECTION 3: MEDIA GAMBAR SAMPUL & TAUTAN (16:9) — Drag & Drop CMS Style -->
      <div class="space-y-4 p-4 sm:p-5 rounded-xl bg-neutral-950/70 border border-neutral-800">
        <div class="flex items-center gap-2 text-xs font-bold font-space-grotesk text-white uppercase tracking-wider pb-2 border-b border-neutral-800/80">
          <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
          <span>3. Media Gambar Sampul & Tautan</span>
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-1.5 text-xs font-semibold font-space-grotesk text-white">
              <IconPhoto class="w-4 h-4 text-isf-red" />
              <span>Gambar Sampul Artikel (16:9)</span>
              <span class="text-[11px] text-neutral-400 font-normal">— Opsional</span>
            </label>
            <span v-if="form.image_url" class="text-[10.5px] text-neutral-400 italic">
              Arahkan kursor untuk mengganti atau menghapus
            </span>
          </div>

          <!-- Hidden File Input for Cover -->
          <input
            ref="coverInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleCoverUpload"
          />

          <!-- 1. Kondisi Sampul SUDAH ADA: Pratinjau 16:9 dengan hover overlay (Ganti & Hapus) -->
          <div v-if="form.image_url" class="space-y-3">
            <div class="relative w-full max-w-xl mx-auto aspect-[16/9] rounded-xl overflow-hidden border border-neutral-700 bg-neutral-950 group cursor-pointer shadow-md">
              <img
                :src="form.image_url"
                alt="Sampul Artikel"
                class="w-full h-full object-cover transition duration-300 group-hover:scale-102"
              />
              <!-- Hover Overlay -->
              <div class="absolute inset-0 bg-neutral-950/75 backdrop-blur-xs flex items-center justify-center gap-2.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <button
                  type="button"
                  @click.stop="triggerCoverInput"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-white text-neutral-900 text-xs font-semibold shadow hover:bg-neutral-100 transition cursor-pointer"
                  title="Ganti Gambar Sampul"
                >
                  <IconPhoto class="w-3.5 h-3.5" />
                  <span>Ganti Sampul</span>
                </button>
                <button
                  type="button"
                  @click.stop="removeCover"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-isf-red text-white text-xs font-semibold shadow hover:bg-red-700 transition cursor-pointer"
                  title="Hapus Gambar Sampul"
                >
                  <IconX class="w-3.5 h-3.5" />
                  <span>Hapus</span>
                </button>
              </div>
            </div>

            <!-- Alt text input -->
            <div>
              <input
                v-model="form.image_alt"
                type="text"
                placeholder="Deskripsi singkat gambar untuk aksesibilitas (Alt text)..."
                class="w-full px-3 py-2 rounded-lg bg-neutral-900 border border-neutral-700 text-xs text-white placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-isf-red"
              />
              <p v-if="errors.image_alt" class="mt-1 text-[11px] text-red-400">
                {{ errors.image_alt[0] }}
              </p>
            </div>
          </div>

          <!-- 2. Kondisi Sampul BELUM ADA: Area Drag-and-Drop Uploader (Sama persis seperti CMS) -->
          <div
            v-else
            @dragover.prevent="isDraggingCover = true"
            @dragleave.prevent="isDraggingCover = false"
            @drop.prevent="handleDropCover"
            @click="triggerCoverInput"
            :class="[
              'border-2 border-dashed rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer transition-colors',
              isDraggingCover
                ? 'border-isf-red bg-red-950/20'
                : 'border-neutral-700 bg-neutral-950/60 hover:border-isf-red/50 hover:bg-neutral-900/60'
            ]"
          >
            <div v-if="uploadingCover" class="text-center space-y-2 py-2">
              <IconPhoto class="w-8 h-8 mx-auto text-isf-red animate-pulse" />
              <p class="text-xs text-neutral-300 font-medium">Mengunggah gambar sampul...</p>
            </div>
            <div v-else class="text-center space-y-1.5 py-2">
              <IconPhoto class="w-8 h-8 mx-auto text-neutral-400 group-hover:text-isf-red transition-colors" />
              <div class="text-xs font-semibold font-space-grotesk text-white">
                Klik atau seret gambar sampul (16:9) ke sini
              </div>
              <p class="text-[11px] text-neutral-400">
                JPG, PNG, WebP — rasio 16:9, maks. 5 MB
              </p>
            </div>
          </div>
          <p v-if="coverError" class="text-[11px] text-red-400 font-medium text-center">
            {{ coverError }}
          </p>
        </div>

        <!-- Tautan Arah (Link URL Target) -->
        <div>
          <label for="f_link" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
            Tautan Rujukan / Dokumen Terkait (Opsional)
          </label>
          <input
            id="f_link"
            v-model="form.link_url"
            type="url"
            placeholder="https://... rujukan dokumen kebijakan atau riset primer"
            class="w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border border-neutral-700 text-xs font-mono text-neutral-200 placeholder-neutral-600 focus:outline-none focus:ring-1 focus:ring-isf-red"
          />
        </div>
      </div>

      <!-- SECTION 4: BLOK FAKTA KUNCI (KEY FACT BLOCKS) — Menggunakan DatePicker Existing -->
      <div class="space-y-3 p-4 sm:p-5 rounded-xl bg-neutral-950/70 border border-neutral-800">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-800/80">
          <div class="flex items-center gap-2 text-xs font-bold font-space-grotesk text-white uppercase tracking-wider">
            <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
            <span>4. Blok Fakta Kunci (Opsional)</span>
          </div>
          <button
            type="button"
            @click="addFactBlock"
            :disabled="form.fact_blocks.length >= 10"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-neutral-800 hover:bg-neutral-700 text-white text-[11px] font-semibold font-space-grotesk transition disabled:opacity-40 cursor-pointer"
          >
            <IconPlus class="w-3.5 h-3.5 text-isf-red" />
            <span>Tambah Fakta</span>
          </button>
        </div>

        <p class="text-[11px] text-neutral-400">
          Cantumkan poin fakta atau temuan penting dengan sumber rujukan primer yang mendukung tesis artikel Anda.
        </p>

        <div v-if="form.fact_blocks.length > 0" class="space-y-3 pt-1">
          <div
            v-for="(fb, fIdx) in form.fact_blocks"
            :key="fIdx"
            class="p-3.5 rounded-lg bg-neutral-900/90 border border-neutral-800 space-y-2.5 relative"
          >
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-neutral-300 font-mono">Poin Fakta #{{ fIdx + 1 }}</span>
              <button
                type="button"
                @click="removeFactBlock(fIdx)"
                class="text-neutral-500 hover:text-red-400 p-1 transition cursor-pointer"
                title="Hapus poin fakta"
              >
                <IconTrash class="w-4 h-4" />
              </button>
            </div>

            <textarea
              v-model="fb.claim"
              rows="2"
              placeholder="Klaim atau ringkasan data penting..."
              class="w-full px-3 py-2 rounded bg-neutral-950 border border-neutral-700 text-xs text-white placeholder-neutral-600 focus:outline-none focus:ring-1 focus:ring-isf-red"
            ></textarea>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <input
                v-model="fb.sourceTitle"
                type="text"
                placeholder="Nama Sumber (e.g. BPS 2026)"
                class="px-2.5 py-1.5 rounded bg-neutral-950 border border-neutral-700 text-xs text-white placeholder-neutral-600 focus:outline-none focus:ring-1 focus:ring-isf-red"
              />
              <input
                v-model="fb.sourceUrl"
                type="url"
                placeholder="URL Sumber (https://...)"
                class="px-2.5 py-1.5 rounded bg-neutral-950 border border-neutral-700 text-xs font-mono text-white placeholder-neutral-600 focus:outline-none focus:ring-1 focus:ring-isf-red"
              />
              <!-- DatePicker Component Existing ISF -->
              <DatePicker
                v-model="fb.sourceDate"
                placeholder="dd/mm/yyyy"
                class="bg-neutral-950 border-neutral-700 text-white"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 5: KONTEN LENGKAP ARTIKEL -->
      <div class="space-y-4 p-4 sm:p-5 rounded-xl bg-neutral-950/70 border border-neutral-800">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-800/80">
          <div class="flex items-center gap-2 text-xs font-bold font-space-grotesk text-white uppercase tracking-wider">
            <span class="w-2 h-2 rounded-xs bg-isf-red"></span>
            <span>5. Konten Lengkap Naskah Artikel <span class="text-isf-red">*</span></span>
          </div>
          <span class="text-[11px] text-neutral-400 font-mono">
            {{ form.body.length }} karakter
          </span>
        </div>

        <div>
          <label for="sp_isi" class="sr-only">Konten Lengkap Naskah Artikel</label>
          <textarea
            id="sp_isi"
            v-model="form.body"
            rows="9"
            placeholder="Tuliskan naskah lengkap analisis artikel Anda di sini secara lugas, berbobot, dan berpedoman pada etika intelektual..."
            @input="clearError('body')"
            :class="[
              'w-full px-4 py-3 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all leading-relaxed font-sans',
              errors.body ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
            ]"
          ></textarea>
          <p v-if="errors.body" class="mt-1 text-[11px] text-red-400">
            {{ errors.body[0] }}
          </p>
        </div>
      </div>

      <!-- SECTION 6: CAPTCHA SECURITY (Bagi yang belum login) -->
      <div v-if="!isLoggedIn" class="p-3.5 rounded-xl bg-neutral-950 border border-neutral-800 space-y-2">
        <label for="sp_captcha" class="flex items-center gap-1.5 text-xs font-semibold font-space-grotesk text-neutral-300">
          <IconShieldCheck class="w-4 h-4 text-emerald-400 shrink-0" />
          <span>Verifikasi Keamanan (Captcha) <span class="text-isf-red">*</span></span>
        </label>
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 bg-neutral-900 border border-neutral-700 rounded-lg px-2.5 py-1.5 h-11">
            <img
              v-if="captchaImage && !captchaLoading"
              :src="captchaImage"
              alt="Kode Captcha"
              class="h-8 max-w-[130px] object-contain rounded"
            />
            <span v-else-if="captchaLoading" class="text-xs text-neutral-400 flex items-center gap-1 px-3">
              <IconLoader2 class="w-3.5 h-3.5 animate-spin" /> Memuat...
            </span>
            <button
              type="button"
              @click="fetchCaptcha"
              :disabled="captchaLoading"
              class="p-1 rounded text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors cursor-pointer"
              title="Ganti gambar captcha"
            >
              <IconRefresh :class="['w-4 h-4', captchaLoading ? 'animate-spin' : '']" />
            </button>
          </div>

          <div class="flex-1 min-w-[140px]">
            <input
              id="sp_captcha"
              v-model="form.captcha_answer"
              type="text"
              maxlength="10"
              placeholder="Ketik karakter captcha di atas"
              @input="clearError('captcha_answer')"
              :class="[
                'w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
                errors.captcha_answer ? 'border-red-500' : 'border-neutral-700'
              ]"
            />
            <p v-if="errors.captcha_answer" class="mt-1 text-[11px] text-red-400">
              {{ errors.captcha_answer[0] }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- DOCKED FOOTER (Mentok ke Bawah Tanpa Celah Mengambang) -->
    <div class="shrink-0 bg-neutral-950 border-t border-neutral-800 p-4 sm:px-6 z-20 space-y-2">
      <div class="flex items-center justify-between gap-3">
        <!-- Draft status helper -->
        <div class="text-[11px] text-neutral-400 flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>Draft tersimpan otomatis di browser</span>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-2.5">
          <button
            v-if="isModal"
            type="button"
            @click="emit('close')"
            class="px-4 py-2.5 rounded-lg border border-neutral-700 bg-neutral-900 hover:bg-neutral-800 text-xs sm:text-sm font-semibold font-space-grotesk text-neutral-300 hover:text-white transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="submitting"
            @click="handleSubmit"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 disabled:opacity-60 text-xs sm:text-sm font-bold font-space-grotesk text-white shadow-lg transition-all cursor-pointer"
          >
            <IconLoader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            <IconSend v-else class="w-4 h-4" />
            <span>{{ submitting ? 'Mengirim Artikel...' : 'Kirim Artikel' }}</span>
          </button>
        </div>
      </div>

      <!-- Disclaimer text at bottom of footer -->
      <p class="text-[10px] text-neutral-500 leading-normal text-center sm:text-left pt-1 border-t border-neutral-900">
        Ketentuan Redaksi ISF: Artikel tidak boleh mengandung plagiasi, ujaran kebencian, pencemaran nama baik, atau promosi komersial terselubung. Hak cipta pemikiran tetap milik penulis, dan Dewan Redaksi ISF berhak menyunting judul serta tata bahasa sebelum ditayangkan.
      </p>
    </div>
  </div>
</template>

<style scoped>
/* Refined sleek scrollbar */
.isf-custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.isf-custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(23, 23, 23, 0.6);
  border-radius: 9999px;
}
.isf-custom-scrollbar::-webkit-scrollbar-thumb {
  background: #404040;
  border-radius: 9999px;
}
.isf-custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #525252;
}
</style>
