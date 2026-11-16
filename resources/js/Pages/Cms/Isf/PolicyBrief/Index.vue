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
const sectorFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

const sectorOptions = [
  { value: '', label: 'Semua Sektor' },
  { value: 'ekonomi', label: 'Ekonomi & Fiskal' },
  { value: 'kesehatan', label: 'Kesehatan Masyarakat' },
  { value: 'pendidikan', label: 'Pendidikan & Riset' },
  { value: 'pertahanan', label: 'Pertahanan & Keamanan' },
  { value: 'lingkungan', label: 'Lingkungan & Energi' },
]

function getSectorBadge(sector) {
  const map = {
    ekonomi: { label: 'Ekonomi & Fiskal', color: 'bg-blue-500/10 text-blue-600 border-blue-500/20' },
    kesehatan: { label: 'Kesehatan', color: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
    pendidikan: { label: 'Pendidikan', color: 'bg-amber-500/10 text-amber-600 border-amber-500/20' },
    pertahanan: { label: 'Pertahanan', color: 'bg-slate-500/10 text-slate-600 border-slate-500/20' },
    lingkungan: { label: 'Lingkungan', color: 'bg-teal-500/10 text-teal-600 border-teal-500/20' },
  }
  return map[sector] || { label: sector || '-', color: 'bg-muted text-muted-foreground border-border' }
}

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.policyBrief.list({
      q: search.value || undefined,
      sector: sectorFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data policy brief.')
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

function onSectorChange() {
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = status
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus policy brief "${item.title}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.policyBrief.remove(item.id)
    alertSuccess('Policy brief berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus policy brief.'))
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Policy Brief — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Policy Brief</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola ringkasan eksekutif riset kebijakan, rekomendasi strategis, dokumen PDF, dan tanggal publikasi.
        </p>
      </div>
      <Link href="/cms/policy-brief/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Policy Brief</span>
        </Button>
      </Link>
    </div>

    <!-- Filters Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul, penulis, lembaga..."
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
          <label for="filter-sektor" class="text-xs text-muted-foreground whitespace-nowrap">Sektor:</label>
          <FilterSelect
            id="filter-sektor"
            v-model="sectorFilter"
            placeholder="Semua Sektor"
            :options="sectorOptions"
            @change="onSectorChange"
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
    </div>

    <!-- Data Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data policy brief..." />
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
            <TableHead class="min-w-[240px]">Judul & Penulis</TableHead>
            <TableHead class="w-36">Sektor</TableHead>
            <TableHead class="w-28">Dokumen</TableHead>
            <TableHead class="w-28">Tanggal</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data policy brief ditemukan.
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
                {{ item.title }}
              </div>
              <div class="text-xs text-muted-foreground mt-0.5">
                {{ item.author || 'Tim Riset ISF' }} <span v-if="item.institution">({{ item.institution }})</span>
              </div>
            </TableCell>
            <TableCell>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border',
                  getSectorBadge(item.sector).color
                ]"
              >
                {{ getSectorBadge(item.sector).label }}
              </span>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground">
              <span v-if="item.pdf_path" class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                <IconComponent name="FileText" :size="13" />
                {{ item.pdf_size || 'PDF' }}
              </span>
              <span v-else class="text-muted-foreground">-</span>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ item.published_at || '-' }}
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/policy-brief/${item.id}/edit`">
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
