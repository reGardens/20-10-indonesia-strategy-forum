<script setup>
import { useCmsPagination } from '@/Composables/useCmsPagination'
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
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
const typeFilter = ref('')
const statusFilter = ref('')
const currentPage = useCmsPagination()
const perPage = ref(10)
let searchTimeout = null

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return items.value.slice(start, start + perPage.value)
})

const typeOptions = [
  { value: '', label: 'Semua Saluran' },
  { value: 'banner', label: 'Display Banner' },
  { value: 'advertorial', label: 'Advertorial & Thought Leadership' },
  { value: 'event', label: 'Executive Roundtable / Event' },
  { value: 'riset', label: 'Sponsorship Riset & Policy Brief' },
  { value: 'lainnya', label: 'Format Lainnya' },
]

function formatDateIndo(val) {
  if (!val) return '-'
  try {
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return val
  }
}

function getTypeBadge(type) {
  const map = {
    banner: { label: 'Display Banner', color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' },
    advertorial: { label: 'Advertorial', color: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' },
    event: { label: 'Roundtable / Event', color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' },
    riset: { label: 'Sponsorship Riset', color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' },
    lainnya: { label: 'Kolaborasi Khusus', color: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20' },
  }
  return map[type] || { label: type || '-', color: 'bg-muted text-muted-foreground border-border' }
}

function getStatusBadge(status) {
  const map = {
    baru: { label: 'Baru', color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', dot: 'bg-blue-500' },
    dihubungi: { label: 'Dihubungi', color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', dot: 'bg-amber-500' },
    selesai: { label: 'Selesai', color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', dot: 'bg-emerald-500' },
    ditolak: { label: 'Ditolak', color: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', dot: 'bg-rose-500' },
  }
  return map[status] || { label: status || '-', color: 'bg-muted text-muted-foreground border-border', dot: 'bg-slate-400' }
}

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kemitraan.list({
      q: search.value || undefined,
      partnership_type: typeFilter.value || undefined,
      status: statusFilter.value || undefined,
      per_page: 100,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar permintaan kemitraan.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadData()
  }, 350)
}

function onTypeChange() {
  currentPage.value = 1
  loadData()
}

function onStatusChange(newStatus) {
  statusFilter.value = newStatus
  currentPage.value = 1
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus proposal kemitraan dari "${item.institution_name}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.kemitraan.delete(item.id)
    alertSuccess('Permintaan kemitraan berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus permintaan kemitraan.'))
  }
}

const exportUrl = computed(() => {
  return cmsResources.kemitraan.exportCsvUrl({
    status: statusFilter.value || undefined,
    partnership_type: typeFilter.value || undefined,
    q: search.value || undefined,
  })
})

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Permintaan Kemitraan — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Permintaan Kemitraan (RFP)</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Daftar pengajuan kemitraan strategis, advertorial, display banner, dan event dari institusi &amp; korporasi.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a :href="exportUrl" download>
          <Button variant="outline" class="font-medium text-sm inline-flex items-center gap-1.5 h-9">
            <IconComponent name="Download" :size="16" />
            <span>Export CSV</span>
          </Button>
        </a>
        <Link href="/cms/kemitraan/create">
          <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5 h-9">
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Kemitraan</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- Filters Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          placeholder="Cari institusi, nama PIC, email, pesan..."
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
          <label for="filter-saluran" class="text-xs text-muted-foreground whitespace-nowrap">Saluran:</label>
          <FilterSelect
            id="filter-saluran"
            v-model="typeFilter"
            placeholder="Semua Saluran"
            :options="typeOptions"
            @change="onTypeChange"
          />
        </div>

        <div class="flex items-center gap-2 overflow-x-auto justify-start">
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
              @click="onStatusChange('baru')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'baru'
                  ? 'bg-blue-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Baru
            </button>
            <button
              type="button"
              @click="onStatusChange('dihubungi')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'dihubungi'
                  ? 'bg-amber-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Dihubungi
            </button>
            <button
              type="button"
              @click="onStatusChange('selesai')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'selesai'
                  ? 'bg-emerald-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Selesai
            </button>
            <button
              type="button"
              @click="onStatusChange('ditolak')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'ditolak'
                  ? 'bg-rose-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Ditolak
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data permintaan kemitraan..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table
        :current-page="currentPage"
        :per-page="perPage"
        :total="items.length"
        @update:current-page="currentPage = $event"
        @update:per-page="perPage = $event; currentPage = 1"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="min-w-[240px]">Institusi &amp; Narahubung</TableHead>
            <TableHead class="w-36">Saluran</TableHead>
            <TableHead class="w-32">Waktu Masuk</TableHead>
            <TableHead class="w-28 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data permintaan kemitraan ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in paginatedItems"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <!-- # -->
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 + (currentPage - 1) * perPage }}
            </TableCell>

            <!-- Institusi & PIC -->
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-1">
                {{ item.institution_name }}
              </div>
              <div class="text-xs text-muted-foreground mt-0.5">
                PIC: {{ item.pic_name }} <span class="text-muted-foreground/60 font-mono">({{ item.email }} · {{ item.phone }})</span>
              </div>
              <div class="text-xs text-muted-foreground/90 mt-1 line-clamp-1 italic">
                "{{ item.message }}"
              </div>
            </TableCell>

            <!-- Saluran Kemitraan -->
            <TableCell>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border whitespace-nowrap',
                  getTypeBadge(item.partnership_type).color
                ]"
              >
                {{ getTypeBadge(item.partnership_type).label }}
              </span>
            </TableCell>

            <!-- Waktu Masuk -->
            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              <div>{{ formatDateIndo(item.created_at) }}</div>
              <div v-if="item.contacted_at" class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">
                Dihubungi: {{ formatDateIndo(item.contacted_at) }}
              </div>
            </TableCell>

            <!-- Status -->
            <TableCell class="text-center">
              <span
                :class="[
                  'inline-flex items-center gap-1 text-[9px] font-medium px-2 py-0.5 rounded-full border capitalize pointer-events-none select-none',
                  getStatusBadge(item.status).color
                ]"
              >
                <span :class="['w-1.5 h-1.5 rounded-full inline-block', getStatusBadge(item.status).dot]" />
                {{ getStatusBadge(item.status).label }}
              </span>
            </TableCell>

            <!-- Aksi (Identik dengan modul ISF lainnya) -->
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/kemitraan/${item.id}/edit`">
                  <Button
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  >
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
