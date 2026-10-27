<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
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
let searchTimeout = null

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kategori.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar kategori.')
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

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.name || 'Kategori')
  if (!confirmed) return

  try {
    await cmsResources.kategori.remove(item.id)
    alertSuccess('Kategori berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus kategori.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Kategori — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Category" :size="24" class="text-red-500" />
          Kelola Kategori Konten
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola daftar kategori (Politik, Ekonomi, Kebijakan, Hukum, RUU, dll.) yang digunakan pada Artikel, Infografik, Isu Mingguan, dan Banner.
        </p>
      </div>
      <div>
        <Link href="/cms/kategori/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Kategori</span>
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
          placeholder="Cari nama, slug, deskripsi..."
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
    <LoadingIndicator v-if="loading" text="Memuat daftar kategori..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader class="bg-muted/40">
          <TableRow>
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-48 text-xs">Nama Kategori</TableHead>
            <TableHead class="w-36 text-xs">Slug</TableHead>
            <TableHead class="text-xs">Deskripsi</TableHead>
            <TableHead class="w-20 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-28 text-center text-xs">Status</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-12 text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-2">
                <IconComponent name="Category" :size="32" class="text-muted-foreground/40" />
                <p>Belum ada kategori yang sesuai.</p>
                <Link href="/cms/kategori/create">
                  <Button
                    variant="outline"
                    size="sm"
                    class="mt-2 text-xs"
                  >
                    Tambah Kategori Pertama
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
              <div class="flex items-center gap-2">
                <span
                  class="w-3 h-3 rounded-full shrink-0"
                  :style="{ backgroundColor: item.color || '#ef4444' }"
                ></span>
                <span class="font-semibold text-xs text-foreground">
                  {{ item.name }}
                </span>
              </div>
            </TableCell>
            <TableCell>
              <span class="font-mono text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded">
                {{ item.slug }}
              </span>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground max-w-sm line-clamp-1">
              {{ item.description || '-' }}
            </TableCell>
            <TableCell class="text-center text-xs font-mono font-bold text-foreground">
              {{ item.order || 0 }}
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status || 'terbit'" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/kategori/${item.id}/edit`">
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
