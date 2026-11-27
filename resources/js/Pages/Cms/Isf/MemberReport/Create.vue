<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const categoryOptions = [
  { value: 'kendala_teknis', label: 'Kendala Akun & Akses Teknis' },
  { value: 'survei', label: 'Masalah Pengisian / Kuisioner Survei' },
  { value: 'tokoh_rating', label: 'Masalah Rating & Ulasan Tokoh' },
  { value: 'konten_naskah', label: 'Laporan Konten & Naskah Member' },
  { value: 'lainnya', label: 'Saran, Masukan & Pengaduan Lainnya' },
]

const form = ref({
  category: 'kendala_teknis',
  subject: '',
  message: '',
})

const attachmentFile = ref(null)
const attachmentPreview = ref('')
const saving = ref(false)
const errors = ref({})

function handleFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) {
    attachmentFile.value = null
    attachmentPreview.value = ''
    return
  }

  // 5MB max
  if (file.size > 5 * 1024 * 1024) {
    alertError('Ukuran file maksimal adalah 5MB.')
    e.target.value = ''
    return
  }

  attachmentFile.value = file
  if (file.type.startsWith('image/')) {
    attachmentPreview.value = URL.createObjectURL(file)
  } else {
    attachmentPreview.value = ''
  }
}

async function handleSubmit() {
  if (!form.value.subject.trim() || !form.value.message.trim()) {
    alertError('Harap lengkapi subjek dan detail pesan laporan.')
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('category', form.value.category)
    formData.append('subject', form.value.subject)
    formData.append('message', form.value.message)
    if (attachmentFile.value) {
      formData.append('attachment', attachmentFile.value)
    }

    const res = await http.post('/cms/laporan-member', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    alertSuccess(res.data?.message || 'Laporan Anda berhasil dikirim ke pengelola dan email ISF.')
    
    // Redirect to anggota or laporan index
    setTimeout(() => {
      window.location.href = '/anggota'
    }, 1200)
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
      alertError('Harap periksa kembali isian form Anda.')
    } else {
      alertError(err?.response?.data?.message || 'Gagal mengirim laporan. Silakan coba kembali.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Head title="Kirim Laporan & Pengaduan — CMS ISF" />

  <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/anggota">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            Pusat Pengaduan & Lapor Masalah
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Sampaikan kendala teknis, sanggahan data, atau masukan strategis. Laporan otomatis masuk ke antrean admin dan email resmi ISF.
          </p>
        </div>
      </div>
    </div>

    <!-- Info Box -->
    <div class="p-4 rounded-lg bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 text-blue-900 dark:text-blue-200 text-xs flex items-start gap-3">
      <IconComponent name="InfoCircle" :size="20" class="text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <p class="font-bold">Notifikasi Otomatis Terhubung ke Redaksi</p>
        <p class="text-blue-800 dark:text-blue-300 leading-relaxed">
          Setiap pengaduan yang Anda kirimkan akan menerima nomor tiket pelaporan, tercatat di database tim pengelola, dan langsung memicu notifikasi email ke dewan redaksi ISF untuk tindak lanjut verifikasi.
        </p>
      </div>
    </div>

    <form class="space-y-6" @submit.prevent="handleSubmit">
      <Card>
        <CardHeader class="pb-3 border-b border-border">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="AlertTriangle" :size="18" class="text-red-700" />
            Formulir Rincian Pengaduan
          </CardTitle>
          <CardDescription>
            Pilih kategori yang sesuai agar pengaduan Anda dapat segera diarahkan ke divisi yang tepat.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 pt-4">
          <!-- Kategori -->
          <div>
            <Label for="category" class="text-xs font-semibold">Kategori Masalah <span class="text-red-500">*</span></Label>
            <select
              id="category"
              v-model="form.category"
              class="mt-1 w-full h-9 rounded-md border border-input bg-background px-3 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-ring"
              required
            >
              <option
                v-for="cat in categoryOptions"
                :key="cat.value"
                :value="cat.value"
              >
                {{ cat.label }}
              </option>
            </select>
          </div>

          <!-- Subjek -->
          <div>
            <Label for="subject" class="text-xs font-semibold">Subjek / Judul Ringkas <span class="text-red-500">*</span></Label>
            <Input
              id="subject"
              v-model="form.subject"
              placeholder="e.g. Kendala gagal menyimpan draft kuisioner survei nomor 3"
              class="mt-1 text-xs"
              :class="{ 'border-red-500': errors.subject }"
              required
            />
            <p v-if="errors.subject" class="text-xs text-red-500 mt-1">{{ errors.subject[0] }}</p>
          </div>

          <!-- Pesan -->
          <div>
            <Label for="message" class="text-xs font-semibold">Rincian Lengkap Kendala / Laporan <span class="text-red-500">*</span></Label>
            <textarea
              id="message"
              v-model="form.message"
              rows="6"
              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              placeholder="Jelaskan secara kronologis apa yang Anda alami, peramban/perangkat yang digunakan, dan harapan penyelesaiannya..."
              :class="{ 'border-red-500': errors.message }"
              required
            ></textarea>
            <p v-if="errors.message" class="text-xs text-red-500 mt-1">{{ errors.message[0] }}</p>
          </div>

          <!-- Lampiran -->
          <div>
            <Label for="attachment" class="text-xs font-semibold">Lampiran Bukti / Screenshot (Opsional)</Label>
            <input
              id="attachment"
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              class="mt-1 block w-full text-xs text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-muted file:text-foreground hover:file:bg-muted/80 cursor-pointer"
              @change="handleFileChange"
            />
            <p class="text-[11px] text-muted-foreground mt-1">
              Format yang didukung: JPG, PNG, atau PDF (maksimal 5MB).
            </p>

            <div v-if="attachmentPreview" class="mt-3">
              <p class="text-xs text-muted-foreground mb-1">Pratinjau Lampiran:</p>
              <img :src="attachmentPreview" alt="Pratinjau Bukti" class="max-h-48 rounded border border-border object-contain" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom Actions -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
        <Link href="/anggota">
          <Button type="button" variant="outline" size="sm">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          class="bg-red-700 hover:bg-red-800 text-white font-medium text-xs shadow-sm inline-flex items-center gap-1.5"
          :disabled="saving"
        >
          <IconComponent v-if="saving" name="Refresh" :size="16" class="animate-spin" />
          <IconComponent v-else name="Send" :size="16" />
          <span>{{ saving ? 'Mengirim Laporan...' : 'Kirim Laporan ke Redaksi ISF' }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>
