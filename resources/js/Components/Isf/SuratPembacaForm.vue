<script setup>
/**
 * SuratPembacaForm.vue — Form pengiriman surat pembaca untuk masyarakat umum
 * Dilengkapi validasi field, visual captcha, dan status moderasi.
 */
import { ref, reactive, onMounted } from 'vue'
import http from '@/api/http'
import { submitSuratPembaca } from '@/api/services/isfPublicService'
import {
  IconMail,
  IconSend,
  IconRefresh,
  IconCheck,
  IconAlertCircle,
  IconX,
  IconShieldCheck,
  IconLoader2,
} from '@tabler/icons-vue'

const props = defineProps({
  isModal: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submitted', 'close'])

const form = reactive({
  nama_pengirim: '',
  email_pengirim: '',
  kota_domisili: '',
  judul_surat: '',
  isi_surat: '',
  captcha_key: '',
  captcha_answer: '',
})

const errors = ref({})
const submitting = ref(false)
const submittedSuccess = ref(false)
const successMessage = ref('')

// Captcha state
const captchaKey = ref('')
const captchaImage = ref('')
const captchaLoading = ref(false)

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
  form.nama_pengirim = ''
  form.email_pengirim = ''
  form.kota_domisili = ''
  form.judul_surat = ''
  form.isi_surat = ''
  form.captcha_answer = ''
  errors.value = {}
  submittedSuccess.value = false
  successMessage.value = ''
  fetchCaptcha()
}

async function handleSubmit() {
  errors.value = {}
  form.captcha_key = captchaKey.value

  // Basic client-side pre-validation
  const errs = {}
  if (!form.nama_pengirim.trim()) errs.nama_pengirim = ['Nama lengkap wajib diisi.']
  if (!form.email_pengirim.trim()) {
    errs.email_pengirim = ['Alamat email wajib diisi.']
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_pengirim)) {
    errs.email_pengirim = ['Format email tidak valid.']
  }
  if (!form.kota_domisili.trim()) errs.kota_domisili = ['Kota domisili wajib diisi.']
  if (!form.judul_surat.trim()) {
    errs.judul_surat = ['Judul surat wajib diisi.']
  } else if (form.judul_surat.trim().length < 5) {
    errs.judul_surat = ['Judul surat minimal 5 karakter.']
  }
  if (!form.isi_surat.trim()) {
    errs.isi_surat = ['Isi surat wajib diisi.']
  } else if (form.isi_surat.trim().length < 20) {
    errs.isi_surat = ['Isi surat minimal 20 karakter agar gagasan tersampaikan dengan jelas.']
  }
  if (!form.captcha_answer.trim()) {
    errs.captcha_answer = ['Kode verifikasi keamanan wajib diisi.']
  }

  if (Object.keys(errs).length > 0) {
    errors.value = errs
    return
  }

  submitting.value = true

  try {
    const res = await submitSuratPembaca({
      nama_pengirim: form.nama_pengirim.trim(),
      email_pengirim: form.email_pengirim.trim(),
      kota_domisili: form.kota_domisili.trim(),
      judul_surat: form.judul_surat.trim(),
      isi_surat: form.isi_surat.trim(),
      captcha_key: form.captcha_key,
      captcha_answer: form.captcha_answer.trim(),
    })

    submittedSuccess.value = true
    successMessage.value = res.message || 'Surat pembaca Anda berhasil dikirim dan masuk ke antrean moderasi redaksi.'
    emit('submitted', res.data)
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data?.errors || { general: [err.response.data?.message] }
    } else {
      errors.value = {
        general: [err.response?.data?.message || 'Terjadi kesalahan sistem saat mengirim surat. Silakan coba lagi.'],
      }
    }
    // Refresh captcha on failure
    fetchCaptcha()
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchCaptcha()
})
</script>

<template>
  <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl p-6 sm:p-8 text-neutral-100 shadow-xl max-w-2xl mx-auto">
    <!-- Close button for modal -->
    <button
      v-if="isModal"
      type="button"
      @click="emit('close')"
      class="absolute top-5 right-5 text-neutral-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-neutral-800"
      aria-label="Tutup form"
    >
      <IconX class="w-5 h-5" />
    </button>

    <!-- Header -->
    <div class="mb-6">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold font-space-grotesk tracking-wide bg-isf-red/10 text-isf-red border border-isf-red/20 mb-2">
        <IconMail class="w-4 h-4 shrink-0" />
        Suara Pembaca ISF
      </div>
      <h3 class="text-xl sm:text-2xl font-bold font-space-grotesk text-white">
        Kirim Surat Pembaca
      </h3>
      <p class="text-xs sm:text-sm text-neutral-400 mt-1 leading-relaxed">
        Sampaikan pandangan, kritik konstruktif, atau aspirasi Anda terkait kebijakan publik dan dinamika bangsa. Setiap surat akan dikurasi oleh Dewan Redaksi ISF sebelum ditayangkan.
      </p>
    </div>

    <!-- Success View -->
    <div v-if="submittedSuccess" class="py-8 text-center space-y-4">
      <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
        <IconCheck class="w-8 h-8" />
      </div>
      <div>
        <h4 class="text-lg font-bold font-space-grotesk text-white">Terima Kasih, Surat Anda Telah Terkirim!</h4>
        <p class="text-sm text-neutral-300 mt-1 max-w-md mx-auto leading-relaxed">
          {{ successMessage }}
        </p>
      </div>
      <div class="pt-4 flex items-center justify-center gap-3">
        <button
          type="button"
          @click="resetForm"
          class="px-4 py-2 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-xs sm:text-sm font-semibold font-space-grotesk text-white transition-colors"
        >
          Kirim Surat Lain
        </button>
        <button
          v-if="isModal"
          type="button"
          @click="emit('close')"
          class="px-4 py-2 rounded-lg bg-isf-red hover:bg-red-700 text-xs sm:text-sm font-semibold font-space-grotesk text-white transition-colors"
        >
          Selesai
        </button>
      </div>
    </div>

    <!-- Form View -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-4" novalidate>
      <!-- General Error Alert -->
      <div
        v-if="errors.general"
        class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-start gap-2"
      >
        <IconAlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{{ errors.general[0] }}</span>
      </div>

      <!-- Sender Info Grid: Name & Email -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Nama Pengirim -->
        <div>
          <label for="sp_nama" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
            Nama Lengkap <span class="text-isf-red">*</span>
          </label>
          <input
            id="sp_nama"
            v-model="form.nama_pengirim"
            type="text"
            placeholder="Contoh: Dr. Budi Santoso"
            maxlength="150"
            @input="clearError('nama_pengirim')"
            :class="[
              'w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
              errors.nama_pengirim ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
            ]"
          />
          <p v-if="errors.nama_pengirim" class="mt-1 text-[11px] text-red-400">
            {{ errors.nama_pengirim[0] }}
          </p>
        </div>

        <!-- Email Pengirim -->
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
              'w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
              errors.email_pengirim ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
            ]"
          />
          <p v-if="errors.email_pengirim" class="mt-1 text-[11px] text-red-400">
            {{ errors.email_pengirim[0] }}
          </p>
        </div>
      </div>

      <!-- Kota Domisili -->
      <div>
        <label for="sp_kota" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
          Kota / Domisili <span class="text-isf-red">*</span>
        </label>
        <input
          id="sp_kota"
          v-model="form.kota_domisili"
          type="text"
          placeholder="Contoh: Bandung, Jawa Barat"
          maxlength="100"
          @input="clearError('kota_domisili')"
          :class="[
            'w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
            errors.kota_domisili ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
          ]"
        />
        <p v-if="errors.kota_domisili" class="mt-1 text-[11px] text-red-400">
          {{ errors.kota_domisili[0] }}
        </p>
      </div>

      <!-- Judul Surat -->
      <div>
        <label for="sp_judul" class="block text-xs font-semibold font-space-grotesk text-neutral-300 mb-1">
          Judul Gagasan / Masalah <span class="text-isf-red">*</span>
        </label>
        <input
          id="sp_judul"
          v-model="form.judul_surat"
          type="text"
          placeholder="Ringkasan poin opini Anda dalam 5-15 kata"
          maxlength="200"
          @input="clearError('judul_surat')"
          :class="[
            'w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
            errors.judul_surat ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
          ]"
        />
        <p v-if="errors.judul_surat" class="mt-1 text-[11px] text-red-400">
          {{ errors.judul_surat[0] }}
        </p>
      </div>

      <!-- Isi Surat -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="sp_isi" class="text-xs font-semibold font-space-grotesk text-neutral-300">
            Isi Surat & Pandangan <span class="text-isf-red">*</span>
          </label>
          <span class="text-[11px] text-neutral-500 font-mono">
            {{ form.isi_surat.length }} karakter
          </span>
        </div>
        <textarea
          id="sp_isi"
          v-model="form.isi_surat"
          rows="5"
          placeholder="Tuliskan argumen, konteks fakta, dan rekomendasi solusi secara lugas dan beradab..."
          @input="clearError('isi_surat')"
          :class="[
            'w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all leading-relaxed',
            errors.isi_surat ? 'border-red-500' : 'border-neutral-700 hover:border-neutral-600'
          ]"
        ></textarea>
        <p v-if="errors.isi_surat" class="mt-1 text-[11px] text-red-400">
          {{ errors.isi_surat[0] }}
        </p>
      </div>

      <!-- Security Captcha Verification -->
      <div class="p-3.5 rounded-xl bg-neutral-950 border border-neutral-800 space-y-2">
        <label for="sp_captcha" class="flex items-center gap-1.5 text-xs font-semibold font-space-grotesk text-neutral-300">
          <IconShieldCheck class="w-4 h-4 text-emerald-400 shrink-0" />
          Verifikasi Keamanan (Captcha) <span class="text-isf-red">*</span>
        </label>
        <div class="flex flex-wrap items-center gap-3">
          <!-- Captcha Box -->
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
              class="p-1 rounded text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors"
              title="Ganti gambar captcha"
            >
              <IconRefresh :class="['w-4 h-4', captchaLoading ? 'animate-spin' : '']" />
            </button>
          </div>

          <!-- Answer Input -->
          <div class="flex-1 min-w-[140px]">
            <input
              id="sp_captcha"
              v-model="form.captcha_answer"
              type="text"
              maxlength="10"
              placeholder="Ketik karakter di atas"
              @input="clearError('captcha_answer')"
              :class="[
                'w-full px-3.5 py-2.5 rounded-lg bg-neutral-900 border text-xs sm:text-sm text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-isf-red transition-all',
                errors.captcha_answer ? 'border-red-500' : 'border-neutral-700'
              ]"
            />
          </div>
        </div>
        <p v-if="errors.captcha_answer" class="text-[11px] text-red-400">
          {{ errors.captcha_answer[0] }}
        </p>
      </div>

      <!-- Disclaimer & Guidelines -->
      <div class="text-[11px] text-neutral-400 leading-relaxed bg-neutral-950/60 p-3 rounded-lg border border-neutral-800/60">
        <strong class="text-neutral-300">Ketentuan Redaksi:</strong> Surat pembaca tidak boleh mengandung fitnah, ujaran kebencian SARA, atau promosi komersial. Redaksi berhak menyunting judul dan naskah tanpa mengubah substansi pendapat pembaca.
      </div>

      <!-- Action Buttons -->
      <div class="pt-2 flex items-center justify-end gap-3">
        <button
          v-if="isModal"
          type="button"
          @click="emit('close')"
          :disabled="submitting"
          class="px-4 py-2.5 rounded-lg border border-neutral-700 text-xs sm:text-sm font-semibold font-space-grotesk text-neutral-300 hover:text-white hover:bg-neutral-800 transition-colors disabled:opacity-50"
        >
          Batal
        </button>
        <button
          type="submit"
          :disabled="submitting"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-isf-red hover:bg-red-700 text-xs sm:text-sm font-bold font-space-grotesk text-white transition-all shadow-md hover:shadow-red-900/30 disabled:opacity-50 cursor-pointer"
        >
          <IconLoader2 v-if="submitting" class="w-4 h-4 animate-spin shrink-0" />
          <IconSend v-else class="w-4 h-4 shrink-0" />
          <span>{{ submitting ? 'Mengirim...' : 'Kirim Surat Pembaca' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

