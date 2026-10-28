<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Badge,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  Pagination
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
const loading = ref(true)
const errorMsg = ref('')
const reordering = ref(false)

const search = ref('')
const statusFilter = ref('')
const page = ref(1)
let searchTimeout = null

const isFiltered = computed(() => Boolean(search.value || statusFilter.value))

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.banner.list({
      page: page.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    const list = res.data || []
    items.value = [...list].sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))
    meta.value = {
      current_page: res.current_page || 1,
      last_page: res.last_page || 1,
      total: res.total || 0,
      per_page: res.per_page || 15
    }
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat banner.'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadData()
  }, 350)
}

function onStatusChange(val) {
  statusFilter.value = val
  page.value = 1
  loadData()
}

function onPageChange(newPage) {
  page.value = newPage
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

    updated.forEach((it, idx) => {
      it.order = idx + 1
    })

    items.value = updated

    const payload = updated.map((it) => ({ id: it.id, order: it.order }))
    await cmsResources.banner.reorder(payload)
    alertSuccess('Urutan banner berhasil diperbarui.')
  } catch (err) {
    items.value = original
    alertError(err?.response?.data?.message || 'Gagal memperbarui urutan banner.')
  } finally {
    reordering.value = false
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title)
  if (!confirmed) return
  try {
    await cmsResources.banner.remove(item.id)
    alertSuccess('Banner berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus banner.')
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Banner Sorotan — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Slideshow" :size="24" class="text-red-500" />
          Kelola Banner Sorotan
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Atur carousel banner utama di bagian atas Beranda publik ISF (nama, gambar, tautan tujuan, dan urutan rotasi).
        </p>
      </div>
      <div>
        <Link href="/cms/banner/create">
          <Button class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm">
            <IconComponent name="Plus" :size="16" class="mr-1.5" />
            Tambah Banner
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
          placeholder="Cari nama banner, tautan..."
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
                ? 'bg-amber-600/20 text-amber-400 border border-amber-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Draf
          </button>
        </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading" text="Memuat banner sorotan..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-lg border border-border bg-card overflow-hidden shadow-sm">
      <Table>
        <TableHeader class="bg-muted/30">
          <TableRow>
            <TableHead class="w-24 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-32">Gambar</TableHead>
            <TableHead>Nama Banner & Tautan</TableHead>
            <TableHead class="w-32">Status</TableHead>
            <TableHead class="w-32 text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="(item, idx) in items"
            :key="item.id"
            class="hover:bg-muted/30 transition-colors"
          >
            <TableCell class="text-center text-xs">
              <div class="inline-flex items-center justify-center gap-1.5">
                <span class="font-mono font-bold text-foreground text-xs min-w-[1.25rem] text-center">
                  {{ item.order ?? (idx + 1) }}
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

            <TableCell>
              <div class="w-24 h-14 rounded overflow-hidden bg-neutral-900 border border-border/60 flex items-center justify-center">
                <img
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <IconComponent
                  v-else
                  name="Photo"
                  :size="20"
                  class="text-muted-foreground/60"
                />
              </div>
            </TableCell>

            <TableCell>
              <div class="space-y-0.5">
                <p class="font-semibold text-sm text-foreground line-clamp-1">
                  {{ item.title }}
                </p>
                <p v-if="item.to" class="text-xs text-muted-foreground font-mono line-clamp-1">
                  {{ item.to }}
                </p>
              </div>
            </TableCell>

            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>

            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/banner/${item.id}/edit`">
                  <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center">
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  @click="handleDelete(item)"
                >
                  <IconComponent name="Trash" :size="14" class="mr-1" />
                  Hapus
                </Button>
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-if="items.length === 0">
            <TableCell colspan="5" class="h-32 text-center text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-1.5">
                <IconComponent name="Slideshow" :size="24" class="text-muted-foreground/60" />
                <span>Tidak ada data banner yang ditemukan.</span>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination -->
      <div
        v-if="meta.last_page > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-border bg-card/40"
      >
        <span class="text-xs text-muted-foreground">
          Menampilkan {{ items.length }} dari {{ meta.total }} banner
        </span>
        <Pagination
          :current-page="meta.current_page"
          :total-pages="meta.last_page"
          @update:current-page="onPageChange"
        />
      </div>
    </div>
  </div>
</template>
