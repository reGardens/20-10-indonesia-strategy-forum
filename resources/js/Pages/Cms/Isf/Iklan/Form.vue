<script setup>
import { computed, ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { alertSuccess, alertError, confirmAction } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
import IklanCalendarPicker from './IklanCalendarPicker.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))
const videoUploading = ref(false)
const existingAds = ref([])

const { form, err, loading, loadError, saving, save: baseSave, load } = useCmsForm({
  resource: cmsResources.iklan,
  id: props.id,
  indexHref: '/cms/iklan',
  defaults: {
    placement: '',        // default tidak memilih salah satu kategori
    type: 'banner',       // 'banner' | 'video'
    title: '',
    kicker: 'SPONSOR RESMI',
    target_url: '/register',
    image_url: '',
    video_url: '',
    video_duration: 30,
    interval_minutes: '',     // kosong = default 30 menit
    first_delay_seconds: '',  // kosong = default 5 detik
    status: 'terbit',
    is_active: true,
    start_date: '',
    end_date: '',
    order: 1
  }
})

function normalizeCategory(placement, type = '') {
  if (placement === 'spanduk' || placement === 'banner') return 'spanduk'
  return 'popup'
}

const placementLabels = {
  spanduk: 'Spanduk Beranda',
  popup: 'Modal Popup'
}

async function handlePlacementChange(val) {
  if (val === form.placement) return

  form.placement = val
  if (val === 'spanduk') {
    form.type = 'banner'
  } else if (!form.type) {
    form.type = 'banner'
  }
}

function setMediaType(type) {
  form.type = type
  if (type === 'video' && !form.video_duration) {
    form.video_duration = 30
  }
}

const isVideoType = computed(() => {
  return form.placement === 'popup' && form.type === 'video'
})

const occupiedBodyRanges = computed(() => {
  if (form.placement !== 'spanduk') return []
  const currentId = props.id ? Number(props.id) : null

  return existingAds.value
    .filter((ad) => {
      const isBody = ad.placement === 'spanduk' || ad.placement === 'banner'
      const isOther = Number(ad.id) !== currentId
      const hasDate = Boolean(ad.start_date)
      return isBody && isOther && hasDate
    })
    .map((ad) => ({
      id: ad.id,
      title: ad.title,
      start_date: ad.start_date,
      end_date: ad.end_date || null
    }))
})

function formatDateShort(dateStr) {
  if (!dateStr) return '-'
  try {
    const parts = dateStr.split('-')
    if (parts.length === 3) {
      const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    }
    return dateStr
  } catch {
    return dateStr
  }
}

function findRangeConflict(startStr, endStr) {
  if (!startStr || form.placement !== 'spanduk') return null
  const sA = new Date(startStr)
  sA.setHours(0, 0, 0, 0)
  const eA = endStr ? new Date(endStr) : new Date('2099-12-31')
  eA.setHours(0, 0, 0, 0)

  for (const range of occupiedBodyRanges.value) {
    const sB = new Date(range.start_date)
    sB.setHours(0, 0, 0, 0)
    const eB = range.end_date ? new Date(range.end_date) : new Date('2099-12-31')
    eB.setHours(0, 0, 0, 0)

    if (sA <= eB && eA >= sB) {
      return range
    }
  }
  return null
}

const dateConflictError = computed(() => {
  if (form.placement !== 'spanduk' || !form.start_date) return ''
  const conflict = findRangeConflict(form.start_date, form.end_date)
  if (conflict) {
    const rangeText = `${formatDateShort(conflict.start_date)} – ${conflict.end_date ? formatDateShort(conflict.end_date) : 'Seterusnya'}`
    return `Tanggal penayangan bertabrakan dengan jadwal iklan body lain "${conflict.title}" (${rangeText}). Iklan body tidak boleh memiliki tanggal tayang ganda.`
  }
  return ''
})

function computeAutoStatus() {
  const today = new Date().toISOString().split('T')[0]
  if (form.end_date && form.end_date < today) {
    return 'draf'
  }
  if (form.start_date && form.start_date > today) {
    return 'draf'
  }
  return 'terbit'
}

function computeAutoIsActive() {
  if (form.placement === 'spanduk') {
    const today = new Date().toISOString().split('T')[0]
    if (form.end_date && form.end_date < today) {
      return false
    }
    if (form.start_date && form.start_date > today) {
      return false
    }
    return true
  }
  return form.is_active !== undefined ? Boolean(form.is_active) : true
}

// Isian waktu boleh kosong (= default); angka 0 tetap dipertahankan.
function optionalNumber(value) {
  return value === '' || value === null || value === undefined ? '' : Number(value)
}

async function handleSave() {
  if (!form.placement) {
    alertError('Silakan pilih kategori penempatan iklan terlebih dahulu.')
    return
  }

  // Validasi tabrakan tanggal iklan body (tidak bisa double)
  if (form.placement === 'spanduk' && dateConflictError.value) {
    alertError(dateConflictError.value)
    return
  }

  // Poin 4: Otomatis tentukan status publikasi dan status aktif berdasarkan hari berjalan
  form.status = computeAutoStatus()
  form.is_active = computeAutoIsActive()

  // Sanitasi payload ketat berdasarkan kategori penempatan yang aktif
  if (form.placement === 'spanduk') {
    form.video_url = ''
    form.video_duration = 30
    form.interval_minutes = 0
    form.first_delay_seconds = ''
    form.type = 'banner'
  } else if (isVideoType.value) {
    // Popup video hanya memerlukan video
    form.image_url = ''
    form.type = 'video'
    form.video_duration = Number(form.video_duration) || 30
    form.interval_minutes = optionalNumber(form.interval_minutes)
  } else {
    // Popup gambar hanya memerlukan image
    form.video_url = ''
    form.video_duration = 30
    form.type = 'banner'
    form.interval_minutes = optionalNumber(form.interval_minutes)
  }

  await baseSave()
}

async function handleVideoUpload(event) {
  const file = event.target.files?.[0]
  if (!file) return

  if (file.size > 50 * 1024 * 1024) {
    alertError('Ukuran file video maksimal 50 MB.')
    return
  }

  const formData = new FormData()
  formData.append('media', file)

  videoUploading.value = true
  try {
    const res = await http.post('/cms/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    const url = res.data?.data?.url || res.data?.url
    if (url) {
      form.video_url = url
      alertSuccess('Video berhasil diunggah.')
    }
  } catch (e) {
    alertError(errorMessage(e, 'Gagal mengunggah video.'))
  } finally {
    videoUploading.value = false
    event.target.value = ''
  }
}

onMounted(async () => {
  try {
    const res = await cmsResources.iklan.list()
    const list = Array.isArray(res) ? res : (res?.data || res?.items || [])
    existingAds.value = list
    if (!isEdit.value) {
      const maxOrder = list.reduce((max, it) => Math.max(max, Number(it.order) || 0), 0)
      form.order = maxOrder + 1
    }
  } catch {
    // Fallback
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Iklan' : 'Tambah Iklan Baru') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/iklan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0 cursor-pointer">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Iklan' : 'Tambah Iklan Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data iklan #${props.id}` : 'Tambahkan kampanye iklan untuk penempatan Spanduk Beranda atau Modal Popup.' }}
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/iklan">
          <Button variant="outline" size="sm" class="h-9 text-xs cursor-pointer">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSave"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center cursor-pointer"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Iklan' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data iklan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="handleSave" class="space-y-6">
      <!-- Card 1: Penempatan & Format -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Identitas &amp; Penempatan Iklan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Atur label kicker sponsor, tautan tujuan, judul kampanye, dan kategori penempatan iklan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Label / Kicker -->
            <div class="space-y-1.5">
              <Label for="kicker" class="text-xs font-medium text-foreground">Label / Kicker Iklan</Label>
              <Input
                id="kicker"
                v-model="form.kicker"
                placeholder="Contoh: SPONSOR RESMI, KEMITRAAN..."
                class="h-9 text-xs font-medium"
              />
              <p v-if="err('kicker')" class="text-xs text-red-400 mt-1">{{ err('kicker') }}</p>
            </div>

            <!-- Target URL -->
            <div class="space-y-1.5">
              <Label for="target_url" class="text-xs font-medium text-foreground">URL Tujuan</Label>
              <Input
                id="target_url"
                v-model="form.target_url"
                placeholder="Contoh: /register atau https://mitra.com"
                class="h-9 text-xs font-mono"
              />
              <p v-if="err('target_url')" class="text-xs text-red-400 mt-1">{{ err('target_url') }}</p>
            </div>
          </div>

          <!-- Judul Iklan / Kampanye -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">Judul Iklan / Kampanye *</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Contoh: Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('title')" class="text-xs text-red-400 mt-1">{{ err('title') }}</p>
          </div>

          <!-- Kategori Penempatan (Select Option di bawah judul iklan) -->
          <div class="space-y-1.5 pt-1">
            <Label for="placement" class="text-xs font-semibold text-foreground">
              Kategori Penempatan Iklan *
            </Label>
            <div class="relative">
              <select
                id="placement"
                :value="form.placement"
                @change="handlePlacementChange($event.target.value)"
                class="w-full h-9 px-3 rounded-md border border-border bg-background text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors cursor-pointer"
              >
                <option value="" disabled>-- Pilih Kategori Penempatan Iklan --</option>
                <option value="spanduk">Spanduk Beranda (Slot banner di kolom kiri Beranda)</option>
                <option value="popup">Modal Popup (Popup promosi saat akses website)</option>
              </select>
            </div>
            <p v-if="err('placement')" class="text-xs text-red-400 mt-1">{{ err('placement') }}</p>
          </div>

          <!-- Waktu Tampil Popup (hanya Modal Popup). Kosong = default. -->
          <div v-if="form.placement === 'popup'" class="space-y-2 pt-1">
            <p class="text-xs font-semibold text-foreground">Waktu Tampil Popup</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 max-w-xl">
              <div class="space-y-1">
                <Label for="first_delay_seconds" class="text-xs text-foreground">Tampil pertama setelah</Label>
                <div class="flex items-center gap-2">
                  <Input
                    id="first_delay_seconds"
                    type="number"
                    min="0"
                    max="3600"
                    v-model="form.first_delay_seconds"
                    placeholder="5"
                    class="h-9 text-xs font-mono w-24"
                  />
                  <span class="text-xs text-muted-foreground">detik</span>
                </div>
                <p class="text-[11px] text-muted-foreground">Sejak halaman dibuka. Hanya untuk iklan urutan pertama.</p>
                <p v-if="err('first_delay_seconds')" class="text-xs text-red-400">{{ err('first_delay_seconds') }}</p>
              </div>
              <div class="space-y-1">
                <Label for="interval_minutes" class="text-xs text-foreground">Tampil lagi setelah</Label>
                <div class="flex items-center gap-2">
                  <Input
                    id="interval_minutes"
                    type="number"
                    min="0"
                    max="1440"
                    v-model="form.interval_minutes"
                    placeholder="30"
                    class="h-9 text-xs font-mono w-24"
                  />
                  <span class="text-xs text-muted-foreground">menit</span>
                </div>
                <p class="text-[11px] text-muted-foreground">Sejak iklan sebelumnya ditutup.</p>
                <p v-if="err('interval_minutes')" class="text-xs text-red-400">{{ err('interval_minutes') }}</p>
              </div>
            </div>
            <p class="text-[11px] text-muted-foreground">Kosongkan untuk memakai bawaan: 5 detik &amp; 30 menit.</p>
          </div>
        </CardContent>
      </Card>

      <!-- Card 2: Materi Media Iklan -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4 space-y-3">
          <div class="flex items-center justify-between">
            <CardTitle class="text-base font-semibold text-foreground">Materi Media Iklan</CardTitle>
          </div>

          <!-- Tab Tipe Media Khusus Modal Popup di atas wording -->
          <div v-if="form.placement === 'popup'" class="inline-flex items-center gap-1 p-1 bg-muted/60 rounded-lg border border-border self-start">
            <button
              type="button"
              @click="setMediaType('banner')"
              :class="[
                'px-3 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer inline-flex items-center gap-1.5',
                form.type === 'banner'
                  ? 'bg-red-600 text-white shadow-sm'
                  : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
              ]"
            >
              <IconComponent name="Photo" :size="14" />
              <span>Gambar / Banner</span>
            </button>
            <button
              type="button"
              @click="setMediaType('video')"
              :class="[
                'px-3 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer inline-flex items-center gap-1.5',
                form.type === 'video'
                  ? 'bg-red-600 text-white shadow-sm'
                  : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
              ]"
            >
              <IconComponent name="Video" :size="14" />
              <span>Video (Durasi)</span>
            </button>
          </div>

          <CardDescription class="text-xs text-muted-foreground">
            {{ form.placement === 'popup' && form.type === 'video'
              ? 'Masukkan tautan video dan durasi tayang sebelum popup dapat ditutup.'
              : 'Unggah atau masukkan tautan media banner gambar sesuai kategori penempatan yang dipilih.' }}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Video Section khusus Modal Popup Duration -->
          <template v-if="isVideoType">
            <div class="p-4 rounded-lg bg-amber-500/5 border border-amber-500/20 space-y-4">
              <div class="flex items-center gap-2 text-amber-400 text-xs font-bold font-mono">
                <IconComponent name="Video" :size="16" />
                <span>Pengaturan Media Video</span>
              </div>

              <!-- Durasi Video -->
              <div class="space-y-1.5">
                <Label for="video_duration" class="text-xs font-medium text-foreground">Durasi Video (Detik) *</Label>
                <div class="flex items-center gap-2 max-w-xs">
                  <Input
                    id="video_duration"
                    type="number"
                    min="5"
                    max="300"
                    v-model.number="form.video_duration"
                    placeholder="30"
                    class="h-9 text-xs font-mono w-32"
                  />
                  <span class="text-xs text-muted-foreground font-mono">Detik (Timer menutup otomatis)</span>
                </div>
                <p class="text-[11px] text-muted-foreground">
                  Iklan video baru akan tertutup atau memunculkan tombol penutup setelah durasi ini tercapai.
                </p>
                <p v-if="err('video_duration')" class="text-xs text-red-400 mt-1">{{ err('video_duration') }}</p>
              </div>

              <!-- Video URL / Embed -->
              <div class="space-y-1.5">
                <Label for="video_url" class="text-xs font-medium text-foreground">Tautan Video (YouTube / MP4)</Label>
                <div class="flex flex-col sm:flex-row gap-2">
                  <Input
                    id="video_url"
                    v-model="form.video_url"
                    placeholder="https://www.youtube.com/watch?v=... atau tautan video MP4"
                    class="h-9 text-xs font-mono flex-1"
                  />
                  <label class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md border border-border bg-card hover:bg-muted text-xs font-medium cursor-pointer shrink-0 transition">
                    <IconComponent v-if="videoUploading" name="Refresh" :size="14" class="animate-spin text-amber-400" />
                    <IconComponent v-else name="Upload" :size="14" />
                    <span>{{ videoUploading ? 'Mengunggah...' : 'Unggah File MP4' }}</span>
                    <input
                      type="file"
                      accept="video/mp4,video/webm"
                      class="hidden"
                      :disabled="videoUploading"
                      @change="handleVideoUpload"
                    />
                  </label>
                </div>
                <p v-if="err('video_url')" class="text-xs text-red-400 mt-1">{{ err('video_url') }}</p>
              </div>
            </div>
          </template>

          <!-- Gambar Banner (Hanya untuk Spanduk Beranda & Modal Popup) -->
          <div v-if="!isVideoType" class="space-y-2">
            <Label class="text-xs font-medium text-foreground">Aset Gambar Iklan *</Label>
            <ImageUploadTab
              v-model="form.image_url"
              :aspect-class="form.placement === 'spanduk' ? 'aspect-[4/3]' : 'aspect-[16/10]'"
              placeholder="https://images.unsplash.com/... atau /images/..."
            />
            <p v-if="err('image_url')" class="text-xs text-red-400 mt-1">{{ err('image_url') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Card 3: Jadwal Penayangan -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold text-foreground">Jadwal Penayangan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            {{ form.placement === 'spanduk'
              ? 'Atur rentang tanggal penayangan iklan body (tanggal yang sudah terpakai dinonaktifkan otomatis).'
              : 'Atur rentang tanggal penayangan popup sponsor (opsional).' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-1.5 max-w-lg">
            <Label class="text-xs font-medium text-foreground">
              Rentang Tanggal Penayangan (Date Range)
            </Label>
            <IklanCalendarPicker
              v-model:start-date="form.start_date"
              v-model:end-date="form.end_date"
              :occupied-ranges="occupiedBodyRanges"
              :is-body="form.placement === 'spanduk'"
              :error="dateConflictError"
              placeholder="Pilih rentang tanggal penayangan (Mulai s/d Selesai)..."
            />
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

