<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, mediaSorotanService, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const loading = ref(true)
const loadError = ref('')
const search = ref('')
const statusFilter = ref('')
const reordering = ref(false)
let searchTimeout = null

const isFiltered = computed(() => Boolean(search.value || statusFilter.value))

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.mediaSorotan.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    const list = Array.isArray(res) ? res : (res?.data || [])
    items.value = [...list].sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data media sorotan.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadData()
  }, 350)
}

function onStatusChange(status) {
  statusFilter.value = status
  loadData()
}

async function moveOrder(index, direction) {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= items.value.length) return
  if (reordering.value) return

  if (isFiltered.value) {
    alertError('Harap reset pencarian dan filter status untuk mengubah urutan.')
    return
  }

  reordering.value = true
  const original = [...items.value]

  try {
    const updated = [...items.value]
    const temp = updated[index]
    updated[index] = updated[targetIndex]
    updated[targetIndex] = temp

    // Normalisasi nomor urutan agar 1, 2, 3...
    updated.forEach((it, idx) => {
      it.order = idx + 1
    })

    // Optimistic update
    items.value = updated

    await mediaSorotanService.saveAll(updated)
    alertSuccess('Urutan media sorotan berhasil diperbarui.')
  } catch (err) {
    items.value = original
    alertError(errorMessage(err, 'Gagal memperbarui urutan media sorotan.'))
  } finally {
    reordering.value = false
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title || 'Media Sorotan')
  if (!confirmed) return

  try {
    await cmsResources.mediaSorotan.remove(item.id)
    alertSuccess('Media sorotan berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus media sorotan.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Media Sorotan — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Video" :size="24" class="text-red-500" />
          Kelola Media Sorotan (YouTube / Instagram)
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola video/konten multimedia yang tampil di sebelah kanan Beranda artikel ISF (maksimal 2 sorotan aktif).
        </p>
      </div>
      <div>
        <Link href="/cms/media-sorotan/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Media Sorotan</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <IconComponent name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul, kategori, platform..."
          class="pl-9 h-9 text-xs bg-card/60"
        />
      </div>

      <div class="flex items-center gap-2 overflow-x-auto">
        <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
        <div class="flex gap-1">
          <button
            type="button"
            @click="onStatusChange('')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === ''
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Semua
          </button>
          <button
            type="button"
            @click="onStatusChange('terbit')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === 'terbit'
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Terbit
          </button>
          <button
            type="button"
            @click="onStatusChange('draf')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === 'draf'
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Draft
          </button>
        </div>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data media sorotan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader class="bg-muted/40">
          <TableRow>
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-20 text-xs">Thumbnail</TableHead>
            <TableHead class="text-xs">Judul & Tautan Media</TableHead>
            <TableHead class="w-28 text-xs">Platform</TableHead>
            <TableHead class="w-28 text-xs">Tanggal</TableHead>
            <TableHead class="w-24 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-24 text-center text-xs">Status</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="8" class="text-center py-12 text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-2">
                <IconComponent name="Video" :size="32" class="text-muted-foreground/40" />
                <p>Belum ada media sorotan yang ditambahkan.</p>
                <Link href="/cms/media-sorotan/create">
                  <Button
                    variant="outline"
                    size="sm"
                    class="mt-2 text-xs"
                  >
                    Tambah Media Pertama
                  </Button>
                </Link>
              </div>
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in items"
            :key="item.id || idx"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center text-xs font-mono text-muted-foreground">
              {{ idx + 1 }}
            </TableCell>
            <TableCell>
              <div class="w-14 h-9 rounded bg-muted/60 overflow-hidden border border-border flex items-center justify-center">
                <img
                  v-if="item.imageUrl"
                  :src="item.imageUrl"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                />
                <IconComponent v-else name="Video" :size="16" class="text-muted-foreground/40" />
              </div>
            </TableCell>
            <TableCell class="text-xs">
              <div class="font-medium text-foreground line-clamp-1">
                {{ item.title }}
              </div>
              <a
                v-if="item.mediaUrl"
                :href="item.mediaUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="text-[11px] text-blue-500 hover:underline flex items-center gap-1 mt-0.5 line-clamp-1"
              >
                <span>{{ item.mediaUrl }}</span>
                <IconComponent name="ExternalLink" :size="12" class="shrink-0" />
              </a>
              <span v-else class="text-[11px] text-muted-foreground">Tanpa tautan</span>
            </TableCell>
            <TableCell>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider border',
                  item.category === 'YouTube'
                    ? 'bg-red-500/10 text-red-500 border-red-500/30'
                    : (item.category === 'Instagram'
                        ? 'bg-pink-500/10 text-pink-500 border-pink-500/30'
                        : 'bg-muted text-foreground border-border')
                ]"
              >
                {{ item.category || 'YouTube' }}
              </span>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground font-mono">
              {{ item.publishedAt || '-' }}
            </TableCell>
            <TableCell class="text-center text-xs">
              <div class="inline-flex items-center justify-center gap-1.5">
                <span class="font-mono font-bold text-foreground text-xs min-w-[1.25rem] text-center">
                  {{ item.order || (idx + 1) }}
                </span>
                <div class="inline-flex flex-col gap-0.5">
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Naik'"
                    :disabled="idx === 0 || reordering || isFiltered"
                    @click="moveOrder(idx, -1)"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition"
                  >
                    <IconComponent name="ChevronUp" :size="13" />
                  </button>
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Turun'"
                    :disabled="idx === items.length - 1 || reordering || isFiltered"
                    @click="moveOrder(idx, 1)"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition"
                  >
                    <IconComponent name="ChevronDown" :size="13" />
                  </button>
                </div>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status || 'terbit'" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/media-sorotan/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  >
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="handleDelete(item)"
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
</template>
