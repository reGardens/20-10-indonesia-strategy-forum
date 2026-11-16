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
const statusLabelFilter = ref('')
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

const progressStatusOptions = [
  { value: '', label: 'Semua Status Progres' },
  { value: 'tercapai', label: 'Tercapai' },
  { value: 'dalam_proses', label: 'Dalam Proses' },
  { value: 'tertunda', label: 'Tertunda' },
  { value: 'perlu_evaluasi', label: 'Perlu Evaluasi' },
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

function getProgressBadge(label) {
  const map = {
    tercapai: { label: 'Tercapai', color: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
    dalam_proses: { label: 'Dalam Proses', color: 'bg-blue-500/10 text-blue-600 border-blue-500/20' },
    tertunda: { label: 'Tertunda', color: 'bg-amber-500/10 text-amber-600 border-amber-500/20' },
    perlu_evaluasi: { label: 'Perlu Evaluasi', color: 'bg-rose-500/10 text-rose-600 border-rose-500/20' },
  }
  return map[label] || { label: label || '-', color: 'bg-muted text-muted-foreground border-border' }
}

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.monitoringKebijakan.list({
      q: search.value || undefined,
      sector: sectorFilter.value || undefined,
      status_label: statusLabelFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data monitoring kebijakan.')
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

function onStatusLabelChange() {
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = status
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus pelacakan program "${item.program_name}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.monitoringKebijakan.remove(item.id)
    alertSuccess('Program monitoring kebijakan berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus program monitoring.'))
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Monitoring Kebijakan — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Monitoring Kebijakan</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Pantau realisasi janji pemerintah, persentase capaian target, status hambatan, dan evaluasi berkala.
        </p>
      </div>
      <Link href="/cms/monitoring-kebijakan/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Program</span>
        </Button>
      </Link>
    </div>

    <!-- Filters Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari program, janji, realisasi..."
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

        <div class="flex items-center gap-2">
          <label for="filter-status-progres" class="text-xs text-muted-foreground whitespace-nowrap">Progres:</label>
          <FilterSelect
            id="filter-status-progres"
            v-model="statusLabelFilter"
            placeholder="Semua Progres"
            :options="progressStatusOptions"
            @change="onStatusLabelChange"
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
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data monitoring kebijakan..." />
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
            <TableHead class="min-w-[240px]">Program & Janji</TableHead>
            <TableHead class="w-36">Sektor</TableHead>
            <TableHead class="w-32">Progres</TableHead>
            <TableHead class="w-32 text-center">Status Capaian</TableHead>
            <TableHead class="w-28">Update</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="8" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data program monitoring kebijakan ditemukan.
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
                {{ item.program_name }}
              </div>
              <div class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                Janji: {{ item.promise }}
              </div>
              <div v-if="item.source_title" class="text-[11px] text-muted-foreground/80 mt-0.5">
                Sumber: {{ item.source_title }}
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
            <TableCell>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-foreground font-mono">{{ item.progress_percent }}%</span>
                </div>
                <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="item.progress_percent >= 100 ? 'bg-emerald-500' : 'bg-red-600'"
                    :style="{ width: `${Math.min(100, Math.max(0, item.progress_percent))}%` }"
                  ></div>
                </div>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold border',
                  getProgressBadge(item.status_label).color
                ]"
              >
                {{ getProgressBadge(item.status_label).label }}
              </span>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ item.last_updated_date || '-' }}
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/monitoring-kebijakan/${item.id}/edit`">
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
