<script setup>
import { computed, ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { alertSuccess, alertError } from '@/Composables/useAlert'
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
  CardContent,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save: baseSave, load } = useCmsForm({
  resource: cmsResources.iklan,
  id: props.id,
  indexHref: '/cms/iklan',
  defaults: {
    placement: '',        // default tidak memilih salah satu kategori
    type: 'banner',       // 'banner' | 'video'
    title: '',
    kicker: '',       // kosong → "SPONSOR RESMI" (diisi server)
    target_url: '',   // kosong → /register (diisi server)
    image_url: '',
    video_url: '',
    video_duration: 30,
    is_skippable: true,
    skip_delay_seconds: 10,
    interval_minutes: '',     // kosong = default 30 menit
    first_delay_seconds: '',  // kosong = default 5 detik
    status: 'active',         // active | paused | expired
    // Jadwal: tanggal (date picker) + jam; digabung menjadi start_at / end_at saat disimpan.
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    start_at: '',
    end_at: '',
    // Jam tayang harian (kosong = sepanjang hari).
    active_hours_start: '',
    active_hours_end: '',
    order: 1
  }
})

const statusOptions = [
  { value: 'active', label: 'Aktif' },
  { value: 'paused', label: 'Dijeda' },
  { value: 'expired', label: 'Kedaluwarsa' }
]

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

// Isian waktu boleh kosong (= default); angka 0 tetap dipertahankan.
function optionalNumber(value) {
  return value === '' || value === null || value === undefined ? '' : Number(value)
}

// "2026-11-15" + "08:30" → "2026-11-15 08:30"; tanggal kosong → '' (tanpa batas).
function joinDateTime(date, time, fallbackTime) {
  if (!date) return ''
  return `${date} ${time || fallbackTime}`
}

const scheduleError = computed(() => err('start_at') || err('end_at'))
const activeHoursError = computed(() => err('active_hours_start') || err('active_hours_end'))

async function handleSave() {
  if (!form.placement) {
    alertError('Silakan pilih kategori penempatan iklan terlebih dahulu.')
    return
  }

  form.start_at = joinDateTime(form.start_date, form.start_time, '00:00')
  form.end_at = joinDateTime(form.end_date, form.end_time, '23:59')

  // Sanitasi payload ketat berdasarkan kategori penempatan yang aktif
  if (form.placement === 'spanduk') {
    form.video_url = ''
    form.video_duration = 30
    form.interval_minutes = ''
    form.first_delay_seconds = ''
    form.is_skippable = true
    form.skip_delay_seconds = 0
    form.type = 'banner'
  } else if (isVideoType.value) {
    // Popup video hanya memerlukan video
    form.image_url = ''
    form.type = 'video'
    form.is_skippable = form.is_skippable !== false
    if (form.is_skippable) {
      form.skip_delay_seconds = form.skip_delay_seconds === '' || form.skip_delay_seconds === null ? 10 : Number(form.skip_delay_seconds)
      form.video_duration = ''
    } else {
      form.video_duration = Number(form.video_duration) || 30
      form.skip_delay_seconds = 10
    }
    form.interval_minutes = optionalNumber(form.interval_minutes)
    form.first_delay_seconds = optionalNumber(form.first_delay_seconds)
  } else {
    // Popup gambar hanya memerlukan image
    form.video_url = ''
    form.video_duration = ''
    form.is_skippable = true
    form.skip_delay_seconds = form.skip_delay_seconds === '' || form.skip_delay_seconds === null ? 10 : Number(form.skip_delay_seconds)
    form.type = 'banner'
    form.interval_minutes = optionalNumber(form.interval_minutes)
    form.first_delay_seconds = optionalNumber(form.first_delay_seconds)
  }

  await baseSave()
}

onMounted(async () => {
  if (isEdit.value) return
  try {
    const res = await cmsResources.iklan.list()
    const list = Array.isArray(res) ? res : (res?.data || res?.items || [])
    const maxOrder = list.reduce((max, it) => Math.max(max, Number(it.order) || 0), 0)
    form.order = maxOrder + 1
  } catch {
    // Fallback
  }
})

defineExpose({
  form,
  handlePlacementChange,
  setMediaType,
  statusOptions
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
              <p class="text-[11px] text-muted-foreground">Kosongkan untuk memakai "SPONSOR RESMI".</p>
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
              <p class="text-[11px] text-muted-foreground">Kosongkan untuk memakai /register.</p>
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
            <Select
              :model-value="form.placement || '__NONE__'"
              @update:model-value="(val) => handlePlacementChange(val === '__NONE__' ? '' : val)"
            >
              <SelectTrigger id="placement" class="h-9 text-xs font-medium bg-background border-border">
                <SelectValue placeholder="-- Pilih Kategori Penempatan Iklan --" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__NONE__" disabled class="text-xs font-medium">
                  -- Pilih Kategori Penempatan Iklan --
                </SelectItem>
                <SelectItem value="spanduk" class="text-xs font-medium cursor-pointer">
                  Spanduk Beranda (Slot banner di kolom kiri Beranda)
                </SelectItem>
                <SelectItem value="popup" class="text-xs font-medium cursor-pointer">
                  Modal Popup (Popup promosi saat akses website)
                </SelectItem>
              </SelectContent>
            </Select>
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

              <!-- Konfigurasi Skip Iklan YouTube-Style (Issue #72) -->
              <div class="space-y-3">
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold text-foreground">Opsi Tombol Lewati Iklan (Skip) *</Label>
                  <div class="inline-flex items-center gap-1 p-1 bg-muted/60 rounded-lg border border-border">
                    <button
                      type="button"
                      @click="form.is_skippable = true"
                      :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer inline-flex items-center gap-1.5',
                        form.is_skippable
                          ? 'bg-red-600 text-white shadow-sm'
                          : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
                      ]"
                    >
                      <IconComponent name="ArrowRight" :size="14" />
                      <span>Bisa di-skip (Skippable)</span>
                    </button>
                    <button
                      type="button"
                      @click="form.is_skippable = false"
                      :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer inline-flex items-center gap-1.5',
                        !form.is_skippable
                          ? 'bg-red-600 text-white shadow-sm'
                          : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
                      ]"
                    >
                      <IconComponent name="Lock" :size="14" />
                      <span>Wajib ditonton (Non-skippable)</span>
                    </button>
                  </div>
                  <p class="text-[11px] text-muted-foreground">
                    Tentukan apakah pengunjung diizinkan melewati iklan setelah jeda tertentu atau wajib menonton tayangan video.
                  </p>
                </div>

                <!-- Opsi A: Waktu Jeda Tombol Skip (Hanya jika Bisa di-skip) -->
                <div v-if="form.is_skippable" class="space-y-1.5 max-w-md">
                  <Label for="skip_delay_seconds" class="text-xs font-medium text-foreground">Waktu Jeda Tombol Skip (Detik) *</Label>
                  <div class="flex items-center gap-2">
                    <Input
                      id="skip_delay_seconds"
                      type="number"
                      min="0"
                      max="3600"
                      v-model.number="form.skip_delay_seconds"
                      placeholder="10"
                      class="h-9 text-xs font-mono w-32"
                    />
                    <span class="text-xs text-muted-foreground font-mono">Detik (Default: 10)</span>
                  </div>
                  <p class="text-[11px] text-muted-foreground">
                    Durasi hitung mundur sebelum tombol "Lewati Iklan →" gaya YouTube aktif dan dapat diklik.
                  </p>
                  <p v-if="err('skip_delay_seconds')" class="text-xs text-red-400 mt-1">{{ err('skip_delay_seconds') }}</p>
                </div>

                <!-- Opsi B: Durasi Video (Hanya jika Wajib ditonton) -->
                <div v-else class="space-y-1.5 max-w-md">
                  <Label for="video_duration" class="text-xs font-medium text-foreground">Durasi Video (Detik) *</Label>
                  <div class="flex items-center gap-2">
                    <Input
                      id="video_duration"
                      type="number"
                      min="5"
                      max="86400"
                      v-model.number="form.video_duration"
                      placeholder="30"
                      class="h-9 text-xs font-mono w-32"
                    />
                    <span class="text-xs text-muted-foreground font-mono">Detik (Mendukung hingga video panjang / webinar)</span>
                  </div>
                  <p class="text-[11px] text-muted-foreground">
                    Total durasi penayangan video sebelum popup tertutup otomatis (karena iklan tidak dapat dilewati secara manual).
                  </p>
                  <p v-if="err('video_duration')" class="text-xs text-red-400 mt-1">{{ err('video_duration') }}</p>
                </div>
              </div>

              <!-- Video URL / Embed -->
              <div class="space-y-1.5">
                <Label for="video_url" class="text-xs font-medium text-foreground">Tautan Video (YouTube / MP4)</Label>
                <Input
                  id="video_url"
                  v-model="form.video_url"
                  placeholder="https://www.youtube.com/watch?v=... atau tautan video MP4"
                  class="h-9 text-xs font-mono"
                />
                <p class="text-[11px] text-muted-foreground">
                  Tempel tautan video YouTube atau URL langsung file video.
                </p>
                <p v-if="err('video_url')" class="text-xs text-red-400 mt-1">{{ err('video_url') }}</p>
              </div>
            </div>
          </template>

          <!-- Gambar Banner (Hanya untuk Spanduk Beranda & Modal Popup) -->
          <div v-if="!isVideoType" class="space-y-4">
            <!-- Pengaturan Durasi Tutup Popup Gambar (Khusus Modal Popup) -->
            <div
              v-if="form.placement === 'popup'"
              class="p-4 rounded-lg bg-amber-500/5 border border-amber-500/20 space-y-3"
            >
              <div class="flex items-center gap-2 text-amber-400 text-xs font-bold font-mono">
                <IconComponent name="Clock" :size="16" />
                <span>Pengaturan Durasi Tutup Popup</span>
              </div>
              <div class="space-y-1.5 max-w-md">
                <Label for="banner_skip_delay" class="text-xs font-medium text-foreground">
                  Waktu Jeda Tombol Tutup (Detik) *
                </Label>
                <div class="flex items-center gap-2">
                  <Input
                    id="banner_skip_delay"
                    type="number"
                    min="0"
                    max="3600"
                    v-model.number="form.skip_delay_seconds"
                    placeholder="10"
                    class="h-9 text-xs font-mono w-32"
                  />
                  <span class="text-xs text-muted-foreground font-mono">Detik (Default: 10, 0 = langsung dapat ditutup)</span>
                </div>
                <p class="text-[11px] text-muted-foreground">
                  Durasi hitung mundur sebelum pengunjung diizinkan menutup popup gambar (tombol ✕ aktif dan dapat diklik).
                </p>
                <p v-if="err('skip_delay_seconds')" class="text-xs text-red-400 mt-1">{{ err('skip_delay_seconds') }}</p>
              </div>
            </div>

            <div class="space-y-2">
              <Label class="text-xs font-medium text-foreground">Aset Gambar Iklan *</Label>
              <ImageUploadTab
                v-model="form.image_url"
                :aspect-class="form.placement === 'spanduk' ? 'aspect-[4/3]' : 'aspect-[16/10]'"
                placeholder="https://images.unsplash.com/... atau /images/..."
              />
              <p v-if="err('image_url')" class="text-xs text-red-400 mt-1">{{ err('image_url') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Card 3: Jadwal & Status Penayangan (2 kolom) -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold text-foreground">Jadwal &amp; Status Penayangan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Iklan tayang bila statusnya Aktif, waktu sekarang berada di rentang mulai–selesai, dan di dalam jam tayang harian.
            Iklan yang lewat waktu selesai otomatis menjadi Kedaluwarsa.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Kolom kiri: rentang tanggal + jam mulai/selesai -->
            <div class="space-y-1.5">
              <Label class="text-xs font-medium text-foreground">Rentang Penayangan</Label>
              <IklanCalendarPicker
                v-model:start-date="form.start_date"
                v-model:end-date="form.end_date"
                :is-body="false"
                :error="scheduleError"
                placeholder="Pilih rentang tanggal penayangan (Mulai s/d Selesai)..."
              />
              <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="space-y-1">
                  <Label for="start_time" class="text-xs text-foreground">Jam mulai</Label>
                  <Input
                    id="start_time"
                    type="time"
                    v-model="form.start_time"
                    :disabled="!form.start_date"
                    class="h-9 text-xs font-mono"
                  />
                </div>
                <div class="space-y-1">
                  <Label for="end_time" class="text-xs text-foreground">Jam selesai</Label>
                  <Input
                    id="end_time"
                    type="time"
                    v-model="form.end_time"
                    :disabled="!form.end_date"
                    class="h-9 text-xs font-mono"
                  />
                </div>
              </div>
              <p class="text-[11px] text-muted-foreground">
                Kosongkan tanggal untuk tanpa batas. Jam kosong = mulai 00:00 / selesai 23:59.
              </p>
              <p v-if="scheduleError" class="text-xs text-red-400">{{ scheduleError }}</p>
            </div>

            <!-- Kolom kanan: status & jam tayang harian -->
            <div class="space-y-5">
              <div class="space-y-1.5">
                <Label for="status" class="text-xs font-medium text-foreground">Status Iklan</Label>
                <Select v-model="form.status">
                  <SelectTrigger id="status" class="h-9 text-xs font-medium bg-background border-border">
                    <SelectValue placeholder="Pilih Status..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="opt in statusOptions"
                      :key="opt.value"
                      :value="opt.value"
                      class="text-xs font-medium cursor-pointer"
                    >
                      {{ opt.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
              </div>

              <div class="space-y-1.5">
                <Label class="text-xs font-medium text-foreground">Jam Tayang Harian</Label>
                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                  <Input
                    id="active_hours_start"
                    type="time"
                    v-model="form.active_hours_start"
                    class="h-9 text-xs font-mono"
                  />
                  <span class="text-xs text-muted-foreground">s/d</span>
                  <Input
                    id="active_hours_end"
                    type="time"
                    v-model="form.active_hours_end"
                    class="h-9 text-xs font-mono"
                  />
                </div>
                <p class="text-[11px] text-muted-foreground">
                  Kosongkan untuk tayang sepanjang hari. Boleh melewati tengah malam, misalnya 22:00 s/d 02:00.
                </p>
                <p v-if="activeHoursError" class="text-xs text-red-400">{{ activeHoursError }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

