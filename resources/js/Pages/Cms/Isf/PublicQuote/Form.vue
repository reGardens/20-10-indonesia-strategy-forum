<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
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
  SelectValue,
  SelectContent,
  SelectItem,
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
  kutipan: '',
  tokoh_nama: '',
  tokoh_jabatan: '',
  tokoh_tipe: 'tokoh',
  tokoh_id: '',
  isu_terkait: '',
  sumber_nama: '',
  sumber_url: '',
  tanggal_kutipan: new Date().toISOString().slice(0, 10),
  order: 0,
  status: 'aktif',
})

const tokohList = ref([])
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

async function fetchTokohs() {
  try {
    const res = await cmsResources.tokoh.list({ limit: 100 })
    tokohList.value = res.data?.data || res.data || []
  } catch (e) {
    console.warn('Gagal memuat list tokoh:', e)
  }
}

function onTokohChange(val) {
  form.value.tokoh_id = val === '__NONE__' ? '' : val
  if (!form.value.tokoh_id) return
  const found = tokohList.value.find(t => String(t.id) === String(form.value.tokoh_id))
  if (found) {
    if (!form.value.tokoh_nama) form.value.tokoh_nama = found.nama || ''
    if (!form.value.tokoh_jabatan) form.value.tokoh_jabatan = found.jabatan || found.profesi || ''
  }
}

async function loadData() {
  await fetchTokohs()
  if (!isEdit.value) return

  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.kutipanPublik.get(props.id)
    form.value = {
      kutipan: data.kutipan || '',
      tokoh_nama: data.tokoh_nama || '',
      tokoh_jabatan: data.tokoh_jabatan || '',
      tokoh_tipe: data.tokoh_tipe || 'tokoh',
      tokoh_id: data.tokoh_id ? String(data.tokoh_id) : '',
      isu_terkait: data.isu_terkait || '',
      sumber_nama: data.sumber_nama || '',
      sumber_url: data.sumber_url || '',
      tanggal_kutipan: data.tanggal_kutipan ? data.tanggal_kutipan.slice(0, 10) : new Date().toISOString().slice(0, 10),
      order: data.order || 0,
      status: data.status || 'aktif',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat kutipan')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  errors.value = {}
  saving.value = true
  try {
    const payload = {
      kutipan: form.value.kutipan.trim(),
      tokoh_nama: form.value.tokoh_nama.trim(),
      tokoh_jabatan: form.value.tokoh_jabatan ? form.value.tokoh_jabatan.trim() : null,
      tokoh_tipe: form.value.tokoh_tipe,
      tokoh_id: form.value.tokoh_id ? Number(form.value.tokoh_id) : null,
      isu_terkait: form.value.isu_terkait.trim(),
      sumber_nama: form.value.sumber_nama.trim(),
      sumber_url: form.value.sumber_url ? form.value.sumber_url.trim() : null,
      tanggal_kutipan: form.value.tanggal_kutipan,
      order: Number(form.value.order) || 0,
      status: form.value.status,
    }

    if (isEdit.value) {
      await cmsResources.kutipanPublik.update(props.id, payload)
      alertSuccess('Kutipan publik berhasil diperbarui')
    } else {
      await cmsResources.kutipanPublik.create(payload)
      alertSuccess('Kutipan publik berhasil ditambahkan')
    }

    router.visit('/cms/kutipan-publik')
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data?.errors || {}
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan kutipan publik'))
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
  <Head :title="isEdit ? 'Edit Kutipan Publik · CMS ISF' : 'Tambah Kutipan Publik · CMS ISF'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kutipan-publik">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kutipan Publik' : 'Tambah Kutipan Publik' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Kutipan tajam bernas dari tokoh bangsa atau warga warganet terkait isu strategis nasional.
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kutipan-publik">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSubmit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="animate-spin" />
          <IconComponent v-else name="Check" :size="14" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kutipan') }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat data kutipan..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      action-label="Coba Lagi"
      @retry="loadData"
    />

    <!-- Form -->
    <form v-if="!loading && !loadError" @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Detail Pernyataan & Sumber Kutipan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Teks ucapan, profil tokoh atau warganet, tautan sumber verifikasi, dan status publikasi.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
        <!-- Quote Text -->
        <div>
          <Label for="kutipan">Isi Kutipan *</Label>
          <textarea
            id="kutipan"
            v-model="form.kutipan"
            rows="4"
            placeholder="Tuliskan pernyataan atau kutipan langsung tanpa tanda kutip..."
            class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring font-serif italic text-base leading-relaxed"
            :class="errors.kutipan ? 'border-destructive' : ''"
          ></textarea>
          <p v-if="errors.kutipan" class="text-xs text-destructive mt-1">
            {{ errors.kutipan[0] }}
          </p>
        </div>

        <!-- Speaker Type & Link to Tokoh -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tipe Tokoh -->
          <div>
            <Label for="tokoh_tipe">Kategori Penutur *</Label>
            <Select v-model="form.tokoh_tipe">
              <SelectTrigger id="tokoh_tipe" class="h-10 text-sm font-medium bg-background border-input">
                <SelectValue placeholder="Pilih Kategori Penutur..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="tokoh" class="text-sm font-medium cursor-pointer">
                  Tokoh Publik / Pejabat / Pakar
                </SelectItem>
                <SelectItem value="netizen" class="text-sm font-medium cursor-pointer">
                  Warganet / Suara Netizen / Publik
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Relasi Profil Tokoh ISF -->
          <div>
            <Label for="tokoh_id">Tautkan ke Profil Tokoh ISF (Opsional)</Label>
            <Select
              :model-value="form.tokoh_id ? String(form.tokoh_id) : '__NONE__'"
              @update:model-value="onTokohChange"
            >
              <SelectTrigger id="tokoh_id" class="h-10 text-sm font-medium bg-background border-input">
                <SelectValue placeholder="-- Tidak Ditautkan / Tokoh Baru --" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__NONE__" class="text-sm font-medium cursor-pointer">
                  -- Tidak Ditautkan / Tokoh Baru --
                </SelectItem>
                <SelectItem
                  v-for="t in tokohList"
                  :key="t.id"
                  :value="String(t.id)"
                  class="text-sm font-medium cursor-pointer"
                >
                  {{ t.nama }} ({{ t.jabatan || 'Tokoh' }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-[11px] text-muted-foreground mt-1">
              Jika dipilih, nama penutur akan otomatis terhubung ke halaman profil tokoh di ISF.
            </p>
          </div>
        </div>

        <!-- Author Name & Title -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <Label for="tokoh_nama">Nama Penutur *</Label>
            <Input
              id="tokoh_nama"
              v-model="form.tokoh_nama"
              placeholder="Contoh: Prof. Mahfud MD / @akun_warga"
              :class="errors.tokoh_nama ? 'border-destructive' : ''"
            />
            <p v-if="errors.tokoh_nama" class="text-xs text-destructive mt-1">
              {{ errors.tokoh_nama[0] }}
            </p>
          </div>

          <div>
            <Label for="tokoh_jabatan">Jabatan / Keterangan Penutur</Label>
            <Input
              id="tokoh_jabatan"
              v-model="form.tokoh_jabatan"
              placeholder="Contoh: Guru Besar Hukum Tata Negara / Praktisi Pajak"
              :class="errors.tokoh_jabatan ? 'border-destructive' : ''"
            />
          </div>
        </div>

        <!-- Topic & Date -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <Label for="isu_terkait">Topik / Isu Terkait *</Label>
            <Input
              id="isu_terkait"
              v-model="form.isu_terkait"
              placeholder="Contoh: Reformasi Pajak, IKN, Alutsista"
              :class="errors.isu_terkait ? 'border-destructive' : ''"
            />
            <p v-if="errors.isu_terkait" class="text-xs text-destructive mt-1">
              {{ errors.isu_terkait[0] }}
            </p>
          </div>

          <div>
            <Label for="tanggal_kutipan">Tanggal Pernyataan *</Label>
            <Input
              id="tanggal_kutipan"
              v-model="form.tanggal_kutipan"
              type="date"
              :class="errors.tanggal_kutipan ? 'border-destructive' : ''"
            />
            <p v-if="errors.tanggal_kutipan" class="text-xs text-destructive mt-1">
              {{ errors.tanggal_kutipan[0] }}
            </p>
          </div>
        </div>

        <!-- Source Name & URL -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <Label for="sumber_nama">Nama Media / Forum Sumber *</Label>
            <Input
              id="sumber_nama"
              v-model="form.sumber_nama"
              placeholder="Contoh: Kompas TV, Twitter/X, Forum Debat DPR"
              :class="errors.sumber_nama ? 'border-destructive' : ''"
            />
            <p v-if="errors.sumber_nama" class="text-xs text-destructive mt-1">
              {{ errors.sumber_nama[0] }}
            </p>
          </div>

          <div>
            <Label for="sumber_url">Tautan Sumber (URL Opsional)</Label>
            <Input
              id="sumber_url"
              v-model="form.sumber_url"
              type="url"
              placeholder="https://..."
              :class="errors.sumber_url ? 'border-destructive' : ''"
            />
            <p v-if="errors.sumber_url" class="text-xs text-destructive mt-1">
              {{ errors.sumber_url[0] }}
            </p>
          </div>
        </div>

        <!-- Order & Status Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border">
          <div>
            <Label for="order">Urutan Prioritas Tampil</Label>
            <Input
              id="order"
              v-model="form.order"
              type="number"
              min="0"
            />
          </div>

          <div>
            <Label for="status">Status Publikasi</Label>
            <Select v-model="form.status">
              <SelectTrigger id="status" class="h-10 text-sm font-medium bg-background border-input">
                <SelectValue placeholder="Pilih Status..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="aktif" class="text-sm font-medium cursor-pointer">
                  Aktif (Tampil di Landing Page)
                </SelectItem>
                <SelectItem value="arsip" class="text-sm font-medium cursor-pointer">
                  Arsip (Disembunyikan)
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </CardContent>
    </Card>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
        <Link href="/cms/kutipan-publik">
          <Button type="button" variant="outline" size="sm" :disabled="saving">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent v-if="saving" name="Refresh" :size="16" class="animate-spin" />
          <IconComponent v-else name="DeviceFloppy" :size="16" />
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kutipan') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

