<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LiveBadge from '@/Pages/Cms/components/shared/LiveBadge.vue'
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
const liveId = ref(null)
const loading = ref(true)
const errorMsg = ref('')

const search = ref('')
const statusFilter = ref('')
const page = ref(1)
let searchTimeout = null

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.dataGrafik.list({
      page: page.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    items.value = res.data || []
    meta.value = {
      current_page: res.current_page || 1,
      last_page: res.last_page || 1,
      total: res.total || 0,
      per_page: res.per_page || 15
    }
    liveId.value = res.live_id || null
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar data grafik.'
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

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title)
  if (!confirmed) return
  try {
    await cmsResources.dataGrafik.remove(item.id)
    alertSuccess('Data grafik berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus data grafik.')
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Data Grafik — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola ISF Insight & Data Grafik</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola dataset grafik interaktif untuk slider ISF Insight di Beranda publik dan Visual Kunci Anatomi Isu.
        </p>
      </div>
      <Link href="/cms/data-grafik/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Grafik Baru</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul grafik..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
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
                ? 'bg-red-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
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
                ? 'bg-emerald-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
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
                ? 'bg-amber-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            Draf
          </button>
        </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat grafik..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-[40%]">Judul Grafik & Analis</TableHead>
            <TableHead>Satuan</TableHead>
            <TableHead class="text-center">Urutan</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Slider Beranda</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada dataset grafik ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="item in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="py-3 text-foreground text-sm">
              <div class="font-semibold">{{ item.title }}</div>
              <div v-if="item.author_name" class="text-xs text-muted-foreground mt-0.5 flex items-center gap-1">
                <span class="text-neutral-400">Analis:</span>
                <span class="font-medium text-foreground/80">{{ item.author_name }}</span>
              </div>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground font-mono">
              {{ item.unit || '-' }}
            </TableCell>
            <TableCell class="text-xs text-center font-mono">
              <span class="inline-block px-2 py-0.5 rounded bg-muted text-muted-foreground">
                {{ item.order ?? 0 }}
              </span>
            </TableCell>
            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell>
              <span
                v-if="item.is_beranda && item.status === 'terbit'"
                class="inline-flex items-center gap-1.5 text-[9.5px] font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Slider Beranda (#{{ item.order ?? 0 }})
              </span>
              <span
                v-else-if="item.is_beranda"
                class="inline-flex items-center gap-1.5 text-[9.5px] font-medium px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                Slider (Status: {{ item.status }})
              </span>
              <span v-else class="text-xs text-muted-foreground">-</span>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/data-grafik/${item.id}/edit`">
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

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="p-4 border-t border-border flex justify-between items-center text-xs text-muted-foreground">
        <div>Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} data)</div>
        <Pagination
          :currentPage="meta.current_page"
          :totalPages="meta.last_page"
          @update:currentPage="onPageChange"
        />
      </div>
    </div>
  </div>
</template>
