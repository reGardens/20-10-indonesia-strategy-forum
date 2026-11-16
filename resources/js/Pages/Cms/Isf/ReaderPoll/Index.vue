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
  Badge,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  FilterSelect
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const loading = ref(true)
const loadError = ref('')
const search = ref('')
const statusFilter = ref('')
let searchTimeout = null

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'aktif', label: 'Aktif' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'draf', label: 'Draf' },
]

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.readerPoll.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar polling.')
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

function onStatusChange() {
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Apakah Anda yakin ingin menghapus polling "${item.question}"? Semua rekapitulasi suara terkait akan dihapus permanen.`
  )
  if (!confirmed) return

  try {
    await cmsResources.readerPoll.delete(item.id)
    alertSuccess('Polling berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus polling.'))
  }
}

function formatDate(val) {
  if (!val) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }).format(new Date(val))
  } catch {
    return val
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Reader Poll — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Reader Poll (Jajak Pendapat)</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola pertanyaan jajak pendapat publik interaktif, opsi jawaban dinamis, dan pemantauan perolehan suara.
        </p>
      </div>
      <Link href="/cms/reader-poll/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Polling</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          placeholder="Cari pertanyaan polling..."
          @input="onSearchInput"
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 w-full sm:w-auto justify-start py-0.5">
        <div class="flex items-center gap-2">
          <label for="filter-status" class="text-xs text-muted-foreground whitespace-nowrap">Status:</label>
          <FilterSelect
            id="filter-status"
            v-model="statusFilter"
            placeholder="Semua Status"
            :options="statusOptions"
            @change="onStatusChange"
          />
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat polling..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="min-w-[280px]">Pertanyaan & Slug</TableHead>
            <TableHead class="w-32 text-center">Opsi Jawaban</TableHead>
            <TableHead class="w-28 text-center">Total Suara</TableHead>
            <TableHead class="w-36">Periode</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada jajak pendapat ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 }}
            </TableCell>

            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-2">
                {{ item.question }}
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5">
                #poll-{{ item.slug }}
                <span v-if="item.is_featured" class="ml-1 text-[10px] text-red-600 font-semibold">
                  ★ Utama
                </span>
              </div>
            </TableCell>

            <TableCell class="text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-muted text-foreground">
                {{ Array.isArray(item.options) ? item.options.length : 0 }} pilihan
              </span>
            </TableCell>

            <TableCell class="text-center">
              <span class="font-mono font-bold text-foreground">
                {{ item.total_votes || 0 }}
              </span>
              <span class="text-[11px] text-muted-foreground block">suara</span>
            </TableCell>

            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              <div>{{ formatDate(item.start_date) }}</div>
              <div class="text-[11px] text-muted-foreground/80">s/d {{ formatDate(item.end_date) }}</div>
            </TableCell>

            <TableCell class="text-center">
              <span
                v-if="item.status === 'aktif'"
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
              >
                Aktif
              </span>
              <span
                v-else-if="item.status === 'selesai'"
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/10 text-blue-600 border border-blue-500/20"
              >
                Selesai
              </span>
              <span
                v-else
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-neutral-500/10 text-neutral-600 border border-neutral-500/20"
              >
                Draf
              </span>
            </TableCell>

            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/reader-poll/${item.id}/edit`">
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
        </TableBody>
      </Table>
    </div>
  </div>
</template>
