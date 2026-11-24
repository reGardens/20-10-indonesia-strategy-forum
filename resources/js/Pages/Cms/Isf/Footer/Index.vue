<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { footerService, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmAction } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  RichTextEditor,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const activeTab = ref('syarat-ketentuan') // 'syarat-ketentuan' | 'privacy-policy'

const DEFAULT_FORM = {
  terms_conditions: '<h2>1. Ketentuan Umum</h2><p>Selamat datang di <strong>Indonesia Strategy Forum (ISF)</strong>. Dengan mengakses dan menggunakan portal ini, Anda menyatakan setuju untuk terikat pada Syarat dan Ketentuan penggunaan yang berlaku.</p><h2>2. Hak Kekayaan Intelektual</h2><p>Seluruh materi publikasi, analisis kebijakan, infografik, basis data peraturan, dan dokumentasi di situs ini dilindungi hak cipta. Pengutipan materi untuk kepentingan akademis dan non-komersial wajib menyertakan atribusi sumber resmi ISF.</p><h2>3. Penafian Tanggung Jawab</h2><p>Informasi dan analisis yang disajikan bertujuan sebagai referensi strategis dan edukasi publik, serta bukan merupakan nasihat hukum atau investasi resmi.</p>',
  privacy_policy: '<h2>1. Kebijakan Privasi ISF</h2><p><strong>Indonesia Strategy Forum (ISF)</strong> berkomitmen penuh menjaga kerahasiaan dan keamanan data pribadi seluruh pembaca, anggota portal, dan mitra institusi.</p><h2>2. Data yang Dikumpulkan</h2><p>Kami hanya mengumpulkan data yang diberikan secara sadar oleh pengguna, seperti nama, kontak surat elektronik (email), instansi, dan sektor kerja saat melakukan registrasi akun anggota atau berlangganan buletin.</p><h2>3. Penggunaan & Keamanan Informasi</h2><p>Data yang dihimpun digunakan semata-mata untuk kebutuhan autentikasi layanan, pengiriman briefing kebijakan berkala, dan peningkatan kualitas konten analitik. Kami tidak memperjualbelikan data pribadi kepada pihak ketiga.</p>',
}

const form = ref(JSON.parse(JSON.stringify(DEFAULT_FORM)))

async function fetchFooter() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await footerService.get()
    if (data) {
      form.value = {
        terms_conditions: data.terms_conditions ?? DEFAULT_FORM.terms_conditions,
        privacy_policy: data.privacy_policy ?? DEFAULT_FORM.privacy_policy,
      }
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat dokumen footer.')
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await footerService.update(form.value)
    alertSuccess('Dokumen legalitas footer berhasil disimpan.')
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyimpan dokumen footer.'))
  } finally {
    saving.value = false
  }
}

async function handleResetDefault() {
  const confirmed = await confirmAction({
    title: 'Reset Dokumen Legalitas',
    text: 'Kembalikan teks naskah ke template default ISF? Perubahan yang belum disimpan akan digantikan.',
    confirmText: 'Ya, Reset ke Default',
  })
  if (confirmed) {
    form.value = JSON.parse(JSON.stringify(DEFAULT_FORM))
    alertSuccess('Formulir telah dikembalikan ke template default.')
  }
}

onMounted(() => {
  fetchFooter()
})
</script>

<template>
  <Head title="Kelola Footer — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header Standar CMS -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="LayoutBottombar" :size="24" class="text-red-500" />
          Kelola Footer
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola dokumen hukum dan legalitas footer publik: Syarat &amp; Ketentuan dan Kebijakan Privasi.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="text-xs h-9"
          :disabled="saving || loading"
          @click="handleResetDefault"
        >
          <IconComponent name="Refresh" :size="14" class="mr-1.5" />
          Reset Default
        </Button>
        <Button
          class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm"
          :disabled="saving || loading"
          @click="handleSave"
        >
          <IconComponent
            v-if="saving"
            name="Refresh"
            :size="14"
            class="mr-1.5 animate-spin"
          />
          <IconComponent
            v-else
            name="DeviceFloppy"
            :size="14"
            class="mr-1.5"
          />
          {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error States -->
    <LoadingIndicator v-if="loading" text="Memuat dokumen footer..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="fetchFooter" />

    <!-- Content Tabs -->
    <div v-else class="space-y-6">
      <!-- Tab Navigation -->
      <div class="flex border-b border-border gap-2 overflow-x-auto pb-0">
        <button
          type="button"
          @click="activeTab = 'syarat-ketentuan'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition-all -mb-px shrink-0"
          :class="
            activeTab === 'syarat-ketentuan'
              ? 'border-red-600 text-red-600 dark:text-red-400 font-semibold'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
        >
          <IconComponent name="FileText" :size="15" />
          <span>Syarat &amp; Ketentuan</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'privacy-policy'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition-all -mb-px shrink-0"
          :class="
            activeTab === 'privacy-policy'
              ? 'border-red-600 text-red-600 dark:text-red-400 font-semibold'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
        >
          <IconComponent name="ShieldLock" :size="15" />
          <span>Kebijakan Privasi</span>
        </button>
      </div>

      <!-- Tab 1: Syarat & Ketentuan -->
      <div v-show="activeTab === 'syarat-ketentuan'" class="space-y-4">
        <Card class="border border-border/80 shadow-xs">
          <CardHeader class="pb-3 flex flex-row items-center justify-between">
            <div>
              <CardTitle class="text-base font-semibold">Naskah Syarat &amp; Ketentuan</CardTitle>
              <CardDescription class="text-xs">
                Dokumen legalitas syarat dan ketentuan penggunaan portal yang dapat dibaca publik pada halaman /syarat-ketentuan.
              </CardDescription>
            </div>
            <a
              href="/syarat-ketentuan"
              target="_blank"
              class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium inline-flex items-center gap-1"
            >
              <span>Buka Halaman Publik</span>
              <IconComponent name="ExternalLink" :size="12" />
            </a>
          </CardHeader>
          <CardContent class="space-y-3">
            <RichTextEditor
              v-model="form.terms_conditions"
              placeholder="Tuliskan naskah lengkap syarat & ketentuan penggunaan di sini..."
            />
          </CardContent>
        </Card>
      </div>

      <!-- Tab 2: Kebijakan Privasi -->
      <div v-show="activeTab === 'privacy-policy'" class="space-y-4">
        <Card class="border border-border/80 shadow-xs">
          <CardHeader class="pb-3 flex flex-row items-center justify-between">
            <div>
              <CardTitle class="text-base font-semibold">Naskah Kebijakan Privasi (Privacy Policy)</CardTitle>
              <CardDescription class="text-xs">
                Dokumen perlindungan data pribadi dan privasi pengunjung yang dapat dibaca publik pada halaman /privacy-policy.
              </CardDescription>
            </div>
            <a
              href="/privacy-policy"
              target="_blank"
              class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium inline-flex items-center gap-1"
            >
              <span>Buka Halaman Publik</span>
              <IconComponent name="ExternalLink" :size="12" />
            </a>
          </CardHeader>
          <CardContent class="space-y-3">
            <RichTextEditor
              v-model="form.privacy_policy"
              placeholder="Tuliskan naskah lengkap kebijakan privasi dan perlindungan data di sini..."
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
