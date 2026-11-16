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
const kategoriFilter = ref('')
const page = ref(1)
let searchTimeout = null

const ROLE_CATEGORIES = [
  { value: '', label: 'Semua Kategori' },
  { value: 'eksekutif', label: 'Eksekutif' },
  { value: 'legislatif', label: 'Legislatif' },
  { value: 'yudikatif', label: 'Yudikatif' },
  { value: 'pakar', label: 'Pakar' },
  { value: 'militer', label: 'Militer' },
  { value: 'pengusaha', label: 'Pengusaha' }
]

function getCategoryColor(cat) {
  switch (cat) {
    case 'eksekutif':
      return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900/60'
    case 'legislatif':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/60'
    case 'yudikatif':
      return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900/60'
    case 'pakar':
      return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/60'
    case 'militer':
      return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60'
    case 'pengusaha':
      return 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900/60'
    default:
      return 'bg-muted text-muted-foreground border-border'
  }
}

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.tokoh.list({
      page: page.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined,
      role_category: kategoriFilter.value || undefined
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
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar tokoh.'
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

function onKategoriChange(val) {
  kategoriFilter.value = val
  page.value = 1
  loadData()
}

function onPageChange(newPage) {
  page.value = newPage
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.name)
  if (!confirmed) return
  try {
    await cmsResources.tokoh.remove(item.id)
    alertSuccess('Tokoh berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus tokoh.')
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Tokoh — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Tokoh</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola profil figur pengambil kebijakan, skor sorotan, policy matrix, dan tokoh pekan ini.
        </p>
      </div>
      <Link href="/cms/tokoh/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Tokoh</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col gap-3">
      <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
        <div class="relative w-full sm:w-80">
          <Input
            v-model="search"
            @input="onSearchInput"
            placeholder="Cari nama atau jabatan tokoh..."
            class="pl-9 h-9 text-xs"
          />
          <IconComponent
            name="Search"
            :size="16"
            class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
          />
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto justify-start py-0.5">
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

      <!-- Kategori Peran Filter Bar -->
      <div class="flex items-center gap-2 overflow-x-auto w-full justify-start pb-1">
        <span class="text-xs text-muted-foreground whitespace-nowrap font-medium">Peran:</span>
        <div class="flex gap-1 overflow-x-auto">
          <button
            v-for="cat in ROLE_CATEGORIES"
            :key="cat.value"
            type="button"
            @click="onKategoriChange(cat.value)"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors whitespace-nowrap',
              kategoriFilter === cat.value
                ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-950 font-semibold'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            {{ cat.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat daftar tokoh..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-[35%]">Tokoh & Profil</TableHead>
            <TableHead class="w-[18%]">Peran & Skor</TableHead>
            <TableHead class="w-[20%]">Jabatan / Posisi</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Sorotan</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data tokoh ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center text-xs font-mono text-muted-foreground">
              {{ (meta.current_page - 1) * meta.per_page + idx + 1 }}
            </TableCell>
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-2">
                {{ item.name }}
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5 truncate">
                /sosok/{{ item.slug }}
              </div>
            </TableCell>
            <TableCell>
              <div class="flex flex-col gap-1 items-start">
                <span
                  class="text-[11px] font-semibold px-2 py-0.5 rounded border capitalize"
                  :class="getCategoryColor(item.role_category)"
                >
                  {{ item.role_category || 'Tokoh' }}
                </span>
                <span class="text-[11px] font-mono font-medium text-muted-foreground">
                  Skor: <span class="font-bold text-foreground">{{ item.influence_score ?? item.skor_value ?? '-' }}</span>/100
                </span>
              </div>
            </TableCell>
            <TableCell class="text-xs text-foreground font-medium">
              <div class="line-clamp-2">{{ item.position }}</div>
            </TableCell>
            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell>
              <div class="flex flex-col items-start gap-1">
                <span
                  v-if="item.is_influential_weekly"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-red-600 text-white font-bold"
                >
                  ★ Tokoh Minggu Ini
                </span>
                <LiveBadge v-if="item.id === liveId" label="Pekan Ini Tayang" />
                <span
                  v-else-if="item.is_featured"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium"
                >
                  Flag Featured
                </span>
                <span
                  v-if="item.show_in_politik"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-red-500/10 text-red-600 font-semibold border border-red-500/20"
                >
                  Profil Politik
                </span>
                <span v-if="!item.is_featured && !item.show_in_politik && item.id !== liveId && !item.is_influential_weekly" class="text-xs text-muted-foreground">-</span>
              </div>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/tokoh/${item.id}/edit`">
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
