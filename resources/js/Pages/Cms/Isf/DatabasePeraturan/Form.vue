<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: {
    type: Number,
    default: null,
  },
})

const isEdit = computed(() => Boolean(props.id))

const form = ref({
  judul: '',
  nomor: '',
  tahun: new Date().getFullYear(),
  kategori: 'Undang-Undang (UU)',
  tentang: '',
  url_dokumen: '',
  lembaga: 'Pemerintah Pusat',
  urutan: 0,
  status: 'terbit',
})

const KATEGORI_OPTIONS = [
  'Undang-Undang (UU)',
  'Peraturan Pemerintah (PP)',
  'Peraturan Presiden (Perpres)',
  'Peraturan Menteri (Permen)',
  'Keputusan Presiden (Keppres)',
  'Rekomendasi Kebijakan',
]

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.databasePeraturan.get(props.id)
    form.value = {
      judul: data.judul || '',
      nomor: data.nomor || '',
      tahun: Number(data.tahun) || new Date().getFullYear(),
      kategori: data.kategori || 'Undang-Undang (UU)',
      tentang: data.tentang || '',
      url_dokumen: data.url_dokumen || '',
      lembaga: data.lembaga || 'Pemerintah Pusat',
      urutan: Number(data.urutan) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data peraturan.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  const payload = {
    ...form.value,
    tahun: form.value.tahun ? Number(form.value.tahun) : null,
    urutan: Number(form.value.urutan) || 0,
  }

  try {
    if (isEdit.value) {
      await cmsResources.databasePeraturan.update(props.id, payload)
      alertSuccess('Data peraturan berhasil diperbarui.')
    } else {
      await cmsResources.databasePeraturan.create(payload)
      alertSuccess('Data peraturan baru berhasil ditambahkan.')
    }
    router.visit('/cms/database-peraturan')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan data peraturan.'))
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="isEdit ? 'Edit Peraturan — Indonesia Strategy Forum' : 'Tambah Peraturan — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/database-peraturan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Data Peraturan' : 'Tambah Data Peraturan' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data regulasi #${props.id}` : 'Tambahkan dokumen peraturan perundang-undangan atau rekomendasi kebijakan.' }}
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/database-peraturan">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSubmit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Peraturan' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat formulir peraturan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Form Content -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Identitas Dokumen Regulasi</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Nama resmi dokumen hukum, nomor urut, tahun penetapan, dan instansi pembuat.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Judul Regulasi -->
          <div class="space-y-1.5">
            <Label for="judul" class="text-xs font-medium text-foreground">Judul Lengkap Dokumen / Regulasi *</Label>
            <Input
              id="judul"
              v-model="form.judul"
              placeholder="Contoh: Undang-Undang Republik Indonesia Nomor 1 Tahun 2024"
              required
              class="text-xs"
            />
            <p v-if="errors.judul" class="text-xs text-red-600 font-medium">{{ errors.judul[0] }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Kategori -->
            <div class="space-y-1.5">
              <Label for="kategori" class="text-xs font-medium text-foreground">Kategori Bentuk Regulasi *</Label>
              <Select v-model="form.kategori">
                <SelectTrigger id="kategori" class="h-9 text-xs font-medium">
                  <SelectValue placeholder="Pilih Kategori Bentuk Regulasi..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="kat in KATEGORI_OPTIONS"
                    :key="kat"
                    :value="kat"
                    class="text-xs font-medium"
                  >
                    {{ kat }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.kategori" class="text-xs text-red-600 font-medium">{{ errors.kategori[0] }}</p>
            </div>

            <!-- Nomor -->
            <div class="space-y-1.5">
              <Label for="nomor" class="text-xs font-medium text-foreground">Nomor Regulasi</Label>
              <Input
                id="nomor"
                v-model="form.nomor"
                placeholder="Contoh: 1 atau 12/PUU-XXII/2024"
                class="text-xs font-mono"
              />
              <p v-if="errors.nomor" class="text-xs text-red-600 font-medium">{{ errors.nomor[0] }}</p>
            </div>

            <!-- Tahun -->
            <div class="space-y-1.5">
              <Label for="tahun" class="text-xs font-medium text-foreground">Tahun Penetapan</Label>
              <Input
                id="tahun"
                v-model.number="form.tahun"
                type="number"
                min="1945"
                max="2099"
                placeholder="2024"
                class="text-xs font-mono"
              />
              <p v-if="errors.tahun" class="text-xs text-red-600 font-medium">{{ errors.tahun[0] }}</p>
            </div>
          </div>

          <!-- Tentang / Pokok Pengaturan -->
          <div class="space-y-1.5">
            <Label for="tentang" class="text-xs font-medium text-foreground">Tentang / Hal Yang Diatur</Label>
            <textarea
              id="tentang"
              v-model="form.tentang"
              rows="3"
              placeholder="Contoh: Perubahan Kedua atas Undang-Undang Nomor 11 Tahun 2008 tentang Informasi dan Transaksi Elektronik"
              class="w-full rounded-md border border-input bg-background p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.tentang" class="text-xs text-red-600 font-medium">{{ errors.tentang[0] }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- URL Dokumen / Sumber Resmi -->
            <div class="space-y-1.5">
              <Label for="url_dokumen" class="text-xs font-medium text-foreground">Tautan Dokumen Resmi (PDF / peraturan.bpk.go.id)</Label>
              <Input
                id="url_dokumen"
                v-model="form.url_dokumen"
                placeholder="https://peraturan.bpk.go.id/Details/... atau link file PDF resmi"
                class="text-xs font-mono"
              />
              <p v-if="errors.url_dokumen" class="text-xs text-red-600 font-medium">{{ errors.url_dokumen[0] }}</p>
            </div>

            <!-- Lembaga Penerbit -->
            <div class="space-y-1.5">
              <Label for="lembaga" class="text-xs font-medium text-foreground">Instansi / Lembaga Pembuat</Label>
              <Input
                id="lembaga"
                v-model="form.lembaga"
                placeholder="Contoh: Pemerintah Pusat / DPR RI / Mahkamah Konstitusi"
                class="text-xs"
              />
              <p v-if="errors.lembaga" class="text-xs text-red-600 font-medium">{{ errors.lembaga[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-border">
            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="urutan" class="text-xs font-medium text-foreground">Urutan Tampil</Label>
              <Input
                id="urutan"
                v-model.number="form.urutan"
                type="number"
                min="0"
                class="text-xs font-mono"
              />
              <p v-if="errors.urutan" class="text-xs text-red-600 font-medium">{{ errors.urutan[0] }}</p>
            </div>

            <!-- Status Publikasi -->
            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium text-foreground">Status Publikasi *</Label>
              <StatusSelectWithBadge
                id="status"
                v-model="form.status"
              />
              <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom action buttons -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/database-peraturan">
          <Button type="button" variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Peraturan' : 'Simpan Peraturan') }}
        </Button>
      </div>
    </form>
  </div>
</template>

