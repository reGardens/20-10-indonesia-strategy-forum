<script setup>
import { ref, computed, onMounted } from 'vue'
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
    const res = await cmsResources.runningText.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    // Can be paginated or direct array
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data running text.')
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
  const confirmed = await confirmDelete(item.announcement || item.badge || 'Teks Running')
  if (!confirmed) return

  try {
    await cmsResources.runningText.remove(item.id)
    alertSuccess('Teks running berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus teks running.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Running Text — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Speakerphone" :size="24" class="text-red-500" />
          Kelola Running Text (Teks Sorotan)
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola baris pengumuman berjalan (marquee) di bagian paling atas Beranda publik ISF.
        </p>
      </div>
      <div>
        <Link href="/cms/running-text/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Teks Running</span>
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
          placeholder="Cari pengumuman, badge..."
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
    <LoadingIndicator v-if="loading" text="Memuat data running text..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
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
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-12 text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-2">
                <IconComponent name="Speakerphone" :size="32" class="text-muted-foreground/40" />
                <p>Belum ada data teks running yang sesuai.</p>
                <Link href="/cms/running-text/create">
                  <Button
                    variant="outline"
                    size="sm"
                    class="mt-2 text-xs"
                  >
                    Tambah Teks Pertama
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
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20">
                {{ item.badge || 'Sorotan' }}
              </span>
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
                <Link :href="`/cms/running-text/${item.id}/edit`">
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
