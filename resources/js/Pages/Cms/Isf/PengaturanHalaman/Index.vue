<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { settings, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
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
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  Badge,
  DatePicker
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const activeTab = ref('running-text') // 'running-text' | 'media-sorotan'

const form = ref({
  beranda: {
    topBar: {
      badge: 'Sorotan',
      announcement: '',
      info: 'Diperbarui setiap Senin'
    },
    topBars: [],
    mediaSorotan: []
  }
})

// Modal state for Running Text
const textModalOpen = ref(false)
const textEditingIndex = ref(-1)
const textForm = ref({
  id: null,
  badge: 'Sorotan',
  announcement: '',
  info: 'Diperbarui setiap Senin',
  status: 'terbit'
})

// Modal state for Media Sorotan (YouTube / Instagram)
const mediaModalOpen = ref(false)
const mediaEditingIndex = ref(-1)
const mediaForm = ref({
  id: null,
  title: '',
  imageUrl: '',
  mediaUrl: '',
  category: 'YouTube',
  publishedAt: new Date().toISOString().slice(0, 10),
  order: 1,
  status: 'terbit'
})

async function loadSettings() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await settings.get()
    // settings.get() resolves to r.data.data
    const data = res?.beranda ? res : (res?.data?.data || res?.data || res || {})
    const beranda = data.beranda || {}

    form.value.beranda.topBars = Array.isArray(beranda.topBars) ? beranda.topBars : []
    form.value.beranda.mediaSorotan = Array.isArray(beranda.mediaSorotan) ? beranda.mediaSorotan : []
    form.value.beranda.topBar = beranda.topBar || { badge: 'Sorotan', announcement: '', info: '' }
    form.value.beranda.pekanIni = beranda.pekanIni || { dateStart: '', dateEnd: '' }
    form.value.anggota = data.anggota || { name: '', role: '', organization: '', plan: '', sektor: [] }

    // If topBars is empty, but topBar has an announcement, auto-populate topBars from topBar
    if ((!form.value.beranda.topBars || form.value.beranda.topBars.length === 0) && form.value.beranda.topBar?.announcement) {
      form.value.beranda.topBars = [
        {
          id: 1,
          badge: form.value.beranda.topBar.badge || 'Sorotan',
          announcement: form.value.beranda.topBar.announcement,
          info: form.value.beranda.topBar.info || 'Edisi Pekan Ini',
          status: 'terbit'
        }
      ]
    }

    // If default mediaSorotan empty, initialize with defaults
    if (!form.value.beranda.mediaSorotan.length) {
      form.value.beranda.mediaSorotan = [
        {
          id: 1,
          title: 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
          imageUrl: '/images/isf/ruu-kuhp.jpeg',
          mediaUrl: 'https://www.youtube.com',
          category: 'YouTube',
          publishedAt: '2026-10-20',
          order: 1,
          status: 'terbit'
        },
        {
          id: 2,
          title: 'Sorotan Visual: Implikasi UU Energi Terbarukan bagi Sektor Industri',
          imageUrl: '/images/isf/ruu-energi.jpeg',
          mediaUrl: 'https://www.instagram.com',
          category: 'Instagram',
          publishedAt: '2026-10-18',
          order: 2,
          status: 'terbit'
        }
      ]
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat pengaturan halaman.')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await settings.update({
      beranda: form.value.beranda,
      anggota: form.value.anggota
    })
    alertSuccess('Pengaturan halaman berhasil disimpan.')
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyimpan pengaturan halaman.'))
  } finally {
    saving.value = false
  }
}

// Running Text Modal Handlers
function openCreateTextModal() {
  textEditingIndex.value = -1
  textForm.value = {
    id: Date.now(),
    badge: 'Sorotan',
    announcement: '',
    info: 'Diperbarui setiap Senin',
    status: 'terbit'
  }
  textModalOpen.value = true
}

function openEditTextModal(index) {
  textEditingIndex.value = index
  textForm.value = { ...form.value.beranda.topBars[index] }
  textModalOpen.value = true
}

function saveTextModalItem() {
  if (!textForm.value.announcement.trim()) {
    alertError('Teks pengumuman tidak boleh kosong.')
    return
  }
  if (textEditingIndex.value >= 0) {
    form.value.beranda.topBars[textEditingIndex.value] = { ...textForm.value }
  } else {
    form.value.beranda.topBars.push({ ...textForm.value })
  }
  const firstActive = form.value.beranda.topBars.find((i) => i.status === 'terbit') || form.value.beranda.topBars[0]
  if (firstActive) {
    form.value.beranda.topBar = {
      badge: firstActive.badge || 'Sorotan',
      announcement: firstActive.announcement || '',
      info: firstActive.info || ''
    }
  }
  textModalOpen.value = false
  save()
}

async function handleDeleteTextItem(index) {
  const item = form.value.beranda.topBars[index]
  const confirmed = await confirmDelete(item.announcement || item.badge || 'Konten Sorotan')
  if (!confirmed) return
  form.value.beranda.topBars.splice(index, 1)
  const firstActive = form.value.beranda.topBars.find((i) => i.status === 'terbit') || form.value.beranda.topBars[0]
  if (firstActive) {
    form.value.beranda.topBar = {
      badge: firstActive.badge || 'Sorotan',
      announcement: firstActive.announcement || '',
      info: firstActive.info || ''
    }
  } else {
    form.value.beranda.topBar = { badge: '', announcement: '', info: '' }
  }
  save()
}

// Media Sorotan Modal Handlers
function openCreateMediaModal() {
  mediaEditingIndex.value = -1
  mediaForm.value = {
    id: Date.now(),
    title: '',
    imageUrl: '',
    mediaUrl: '',
    category: 'YouTube',
    publishedAt: new Date().toISOString().slice(0, 10),
    order: (form.value.beranda.mediaSorotan?.length || 0) + 1,
    status: 'terbit'
  }
  mediaModalOpen.value = true
}

function openEditMediaModal(index) {
  mediaEditingIndex.value = index
  mediaForm.value = { ...form.value.beranda.mediaSorotan[index] }
  mediaModalOpen.value = true
}

function saveMediaModalItem() {
  if (!mediaForm.value.title.trim()) {
    alertError('Judul media tidak boleh kosong.')
    return
  }
  if (!mediaForm.value.mediaUrl.trim()) {
    alertError('Tautan link YouTube / Instagram tidak boleh kosong.')
    return
  }
  if (mediaEditingIndex.value >= 0) {
    form.value.beranda.mediaSorotan[mediaEditingIndex.value] = { ...mediaForm.value }
  } else {
    form.value.beranda.mediaSorotan.push({ ...mediaForm.value })
  }
  mediaModalOpen.value = false
  save()
}

async function handleDeleteMediaItem(index) {
  const item = form.value.beranda.mediaSorotan[index]
  const confirmed = await confirmDelete(item.title || 'Media Sorotan')
  if (!confirmed) return
  form.value.beranda.mediaSorotan.splice(index, 1)
  save()
}

onMounted(loadSettings)
</script>

<template>
  <Head title="Pengaturan Halaman — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">Pengaturan Halaman</h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Kelola konten dinamis Beranda: teks sorotan (running text) dan fitur media sorotan (YouTube / Instagram).
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="activeTab === 'running-text'"
          type="button"
          @click="openCreateTextModal"
          class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Teks Sorotan</span>
        </Button>
        <Button
          v-else-if="activeTab === 'media-sorotan'"
          type="button"
          @click="openCreateMediaModal"
          class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Media Sorotan</span>
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat pengaturan halaman..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadSettings" />

    <!-- Content Tabs -->
    <div v-else class="space-y-6">
      <!-- Tab Navigation -->
      <div class="flex border-b border-border gap-2">
        <button
          type="button"
          @click="activeTab = 'running-text'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition-all -mb-px"
          :class="
            activeTab === 'running-text'
              ? 'border-red-600 text-red-600 dark:text-red-400'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
        >
          <IconComponent name="Megaphone" :size="15" />
          <span>Running Text (Teks Sorotan)</span>
          <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-muted">
            {{ form.beranda.topBars?.length || 0 }}
          </span>
        </button>

        <button
          type="button"
          @click="activeTab = 'media-sorotan'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition-all -mb-px"
          :class="
            activeTab === 'media-sorotan'
              ? 'border-red-600 text-red-600 dark:text-red-400'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
        >
          <IconComponent name="Video" :size="15" />
          <span>Media Sorotan (YouTube / Instagram)</span>
          <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-muted">
            {{ form.beranda.mediaSorotan?.length || 0 }}
          </span>
        </button>
      </div>

      <!-- TAB 1: RUNNING TEXT -->
      <div v-if="activeTab === 'running-text'" class="space-y-4">
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="w-12 text-center text-xs">#</TableHead>
                <TableHead class="w-32 text-xs">Badge</TableHead>
                <TableHead class="text-xs">Teks Pengumuman (Running Text)</TableHead>
                <TableHead class="w-48 text-xs">Keterangan / Waktu</TableHead>
                <TableHead class="w-28 text-center text-xs">Status</TableHead>
                <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!form.beranda.topBars || form.beranda.topBars.length === 0">
                <TableCell colspan="6" class="text-center py-10 text-xs text-muted-foreground">
                  Belum ada konten teks running. Klik "Tambah Teks Sorotan" untuk menambahkan.
                </TableCell>
              </TableRow>
              <TableRow
                v-for="(item, idx) in form.beranda.topBars"
                :key="item.id || idx"
                class="hover:bg-muted/30 transition border-b border-border/50"
              >
                <TableCell class="text-center text-xs font-mono text-muted-foreground">
                  {{ idx + 1 }}
                </TableCell>
                <TableCell>
                  <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-[9.5px] font-bold uppercase tracking-wider px-1.5 py-0.5">
                    {{ item.badge || 'Sorotan' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-xs font-medium text-foreground max-w-md">
                  {{ item.announcement }}
                </TableCell>
                <TableCell class="text-xs text-muted-foreground">
                  {{ item.info || '-' }}
                </TableCell>
                <TableCell class="text-center">
                  <StatusBadge :status="item.status || 'terbit'" />
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="openEditTextModal(idx)"
                      class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                    >
                      <IconComponent name="Edit" :size="14" class="mr-1" />
                      Edit
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="handleDeleteTextItem(idx)"
                      class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                    >
                      <IconComponent name="Trash" :size="14" class="mr-1" />
                      Hapus
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- TAB 2: MEDIA SOROTAN (YOUTUBE / INSTAGRAM) -->
      <div v-else-if="activeTab === 'media-sorotan'" class="space-y-4">
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="w-12 text-center text-xs">#</TableHead>
                <TableHead class="w-24 text-xs">Thumbnail</TableHead>
                <TableHead class="text-xs">Judul Media</TableHead>
                <TableHead class="w-28 text-xs">Platform</TableHead>
                <TableHead class="w-56 text-xs">Tautan Link</TableHead>
                <TableHead class="w-32 text-xs">Tgl Upload</TableHead>
                <TableHead class="w-20 text-center text-xs">Urutan</TableHead>
                <TableHead class="w-24 text-center text-xs">Status</TableHead>
                <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!form.beranda.mediaSorotan || form.beranda.mediaSorotan.length === 0">
                <TableCell colspan="9" class="text-center py-10 text-xs text-muted-foreground">
                  Belum ada media sorotan. Klik "Tambah Media Sorotan" untuk menambahkan.
                </TableCell>
              </TableRow>
              <TableRow
                v-for="(item, idx) in form.beranda.mediaSorotan"
                :key="item.id || idx"
                class="hover:bg-muted/30 transition border-b border-border/50"
              >
                <TableCell class="text-center text-xs font-mono text-muted-foreground">
                  {{ idx + 1 }}
                </TableCell>
                <TableCell>
                  <img
                    v-if="item.imageUrl"
                    :src="item.imageUrl"
                    :alt="item.title"
                    class="w-16 h-10 object-cover rounded-xs border border-border"
                  />
                  <div v-else class="w-16 h-10 rounded-xs bg-muted flex items-center justify-center text-[10px] text-muted-foreground">
                    No Img
                  </div>
                </TableCell>
                <TableCell class="text-xs font-medium text-foreground max-w-xs">
                  {{ item.title }}
                </TableCell>
                <TableCell>
                  <span
                    class="inline-flex items-center gap-1 text-[9.5px] font-bold px-1.5 py-0.5 rounded"
                    :class="
                      (item.category || '').toLowerCase().includes('instagram')
                        ? 'bg-pink-500/10 text-pink-600 border border-pink-500/20'
                        : 'bg-red-500/10 text-red-600 border border-red-500/20'
                    "
                  >
                    {{ item.category || 'YouTube' }}
                  </span>
                </TableCell>
                <TableCell class="text-xs font-mono text-muted-foreground max-w-xs truncate">
                  <a :href="item.mediaUrl" target="_blank" class="hover:underline text-blue-500 truncate block">
                    {{ item.mediaUrl }}
                  </a>
                </TableCell>
                <TableCell class="text-xs text-muted-foreground">
                  {{ item.publishedAt || '-' }}
                </TableCell>
                <TableCell class="text-center text-xs font-mono font-bold">
                  {{ item.order || 1 }}
                </TableCell>
                <TableCell class="text-center">
                  <StatusBadge :status="item.status || 'terbit'" />
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="openEditMediaModal(idx)"
                      class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                    >
                      <IconComponent name="Edit" :size="14" class="mr-1" />
                      Edit
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="handleDeleteMediaItem(idx)"
                      class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                    >
                      <IconComponent name="Trash" :size="14" class="mr-1" />
                      Hapus
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Create / Edit Running Text -->
    <div
      v-if="textModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
      @click.self="textModalOpen = false"
    >
      <div class="w-full max-w-lg rounded-xl border border-border bg-card shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border px-5 py-4">
          <h3 class="text-sm font-bold text-foreground">
            {{ textEditingIndex >= 0 ? 'Edit Konten Teks Running' : 'Tambah Konten Teks Running' }}
          </h3>
          <button
            type="button"
            @click="textModalOpen = false"
            class="text-muted-foreground hover:text-foreground text-sm font-bold"
          >
            ✕
          </button>
        </div>
        <div class="p-5 space-y-4">
          <div class="space-y-1.5">
            <Label for="modal-badge" class="text-xs font-medium">Label Badge</Label>
            <Input
              id="modal-badge"
              v-model="textForm.badge"
              placeholder="e.g. Sorotan / Breaking"
              class="h-9 text-xs"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="modal-announcement" class="text-xs font-medium">Teks Pengumuman <span class="text-red-500">*</span></Label>
            <textarea
              id="modal-announcement"
              v-model="textForm.announcement"
              rows="3"
              placeholder="Teks pengumuman yang berjalan..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="modal-info" class="text-xs font-medium">Keterangan / Jadwal Pembaruan</Label>
            <Input
              id="modal-info"
              v-model="textForm.info"
              placeholder="e.g. Diperbarui setiap Senin"
              class="h-9 text-xs"
            />
          </div>

          <StatusSelectWithBadge
            id="modal-text-status"
            v-model="textForm.status"
            label="Status Publikasi"
            :required="true"
          />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-border px-5 py-3 bg-muted/20">
          <Button variant="outline" size="sm" @click="textModalOpen = false">Batal</Button>
          <Button size="sm" class="bg-red-600 hover:bg-red-700 text-white" @click="saveTextModalItem">
            Simpan Konten
          </Button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: Create / Edit Media Sorotan (YouTube / Instagram) -->
    <div
      v-if="mediaModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
      @click.self="mediaModalOpen = false"
    >
      <div class="w-full max-w-xl rounded-xl border border-border bg-card shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-border px-5 py-4 shrink-0">
          <h3 class="text-sm font-bold text-foreground">
            {{ mediaEditingIndex >= 0 ? 'Edit Media Sorotan' : 'Tambah Media Sorotan' }}
          </h3>
          <button
            type="button"
            @click="mediaModalOpen = false"
            class="text-muted-foreground hover:text-foreground text-sm font-bold"
          >
            ✕
          </button>
        </div>
        <div class="p-5 space-y-4 overflow-y-auto flex-1">
          <div class="space-y-1.5">
            <Label for="media-title" class="text-xs font-medium">Judul Media <span class="text-red-500">*</span></Label>
            <Input
              id="media-title"
              v-model="mediaForm.title"
              placeholder="e.g. Diskusi Panel: Dampak UU Energi Terbarukan"
              class="h-9 text-xs"
            />
          </div>

          <div class="space-y-1.5">
            <Label class="text-xs font-medium">Thumbnail Gambar</Label>
            <ImageUploadTab
              v-model="mediaForm.imageUrl"
              aspect-class="aspect-[16/10]"
              placeholder="https://... atau unggah thumbnail"
              preview-label="Pratinjau Thumbnail Video"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="media-url" class="text-xs font-medium">Tautan Link (YouTube / Instagram) <span class="text-red-500">*</span></Label>
            <Input
              id="media-url"
              v-model="mediaForm.mediaUrl"
              placeholder="https://www.youtube.com/watch?v=... atau https://www.instagram.com/p/..."
              class="h-9 text-xs font-mono"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="media-cat" class="text-xs font-medium">Platform / Kategori</Label>
              <div class="relative">
                <select
                  id="media-cat"
                  v-model="mediaForm.category"
                  class="w-full h-9 rounded-md border border-input bg-background pl-3 pr-9 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring appearance-none"
                >
                  <option value="YouTube">YouTube</option>
                  <option value="Instagram">Instagram</option>
                  <option value="Video Riset">Video Riset</option>
                  <option value="Podcast">Podcast</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="media-date" class="text-xs font-medium">Tanggal Upload</Label>
              <DatePicker
                id="media-date"
                v-model="mediaForm.publishedAt"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="media-order" class="text-xs font-medium">Urutan Tampil (Order)</Label>
              <Input
                id="media-order"
                type="number"
                min="1"
                max="2"
                v-model.number="mediaForm.order"
                class="h-9 text-xs font-mono"
              />
              <p class="text-[10px] text-muted-foreground">Maksimal 2 item di beranda.</p>
            </div>
          </div>

          <StatusSelectWithBadge
            id="modal-media-status"
            v-model="mediaForm.status"
            label="Status Publikasi"
            :required="true"
          />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-border px-5 py-3 bg-muted/20 shrink-0">
          <Button variant="outline" size="sm" @click="mediaModalOpen = false">Batal</Button>
          <Button size="sm" class="bg-red-600 hover:bg-red-700 text-white" @click="saveMediaModalItem">
            Simpan Media
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
