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
const sectorFilter = ref('')
const statusLabelFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

const sectors = [
  { slug: '', label: 'Semua Sektor' },
  { slug: 'ekonomi', label: 'Ekonomi' },
  { slug: 'kesehatan', label: 'Kesehatan' },
  { slug: 'pendidikan', label: 'Pendidikan' },
  { slug: 'pertahanan', label: 'Pertahanan' },
  { slug: 'lingkungan', label: 'Lingkungan' },
]

const statusLabels = [
  { slug: '', label: 'Semua Status Progres' },
  { slug: 'tercapai', label: 'Tercapai' },
  { slug: 'dalam_proses', label: 'Dalam Proses' },
  { slug: 'tertunda', label: 'Tertunda' },
  { slug: 'perlu_evaluasi', label: 'Perlu Evaluasi' },
]

function getSectorBadge(sector) {
  const map = {
    ekonomi: { label: 'Ekonomi', color: 'bg-blue-100 text-blue-800 border-blue-200' },
    kesehatan: { label: 'Kesehatan', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
    pendidikan: { label: 'Pendidikan', color: 'bg-amber-100 text-amber-800 border-amber-200' },
    pertahanan: { label: 'Pertahanan', color: 'bg-slate-100 text-slate-800 border-slate-200' },
    lingkungan: { label: 'Lingkungan', color: 'bg-teal-100 text-teal-800 border-teal-200' },
  }
  return map[sector] || { label: sector || '-', color: 'bg-neutral-100 text-neutral-800 border-neutral-200' }
}

function getProgressBadge(label) {
  const map = {
    tercapai: { label: 'Tercapai', color: 'bg-emerald-100 text-emerald-800 border-emerald-300' },
    dalam_proses: { label: 'Dalam Proses', color: 'bg-blue-100 text-blue-800 border-blue-300' },
    tertunda: { label: 'Tertunda', color: 'bg-amber-100 text-amber-800 border-amber-300' },
    perlu_evaluasi: { label: 'Perlu Evaluasi', color: 'bg-rose-100 text-rose-800 border-rose-300' },
  }
  return map[label] || { label: label || '-', color: 'bg-neutral-100 text-neutral-800 border-neutral-300' }
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

function onSectorChange(sector) {
  sectorFilter.value = sector
  loadData()
}

function onStatusLabelChange(label) {
  statusLabelFilter.value = label
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = statusFilter.value === status ? '' : status
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
    alertError(errorMessage(err, 'Gagal menghapus program monitoring kebijakan.'))
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Monitoring Kebijakan — CMS ISF" />

  <div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
          Monitoring Kebijakan (Janji vs Realisasi)
        </h1>
        <p class="text-sm text-neutral-500 mt-1">
          Pelacakan capaian program strategis pemerintah, target janji vs realisasi di lapangan, progres capaian, dan sumber data resmi.
        </p>
      </div>
      <Link href="/cms/monitoring-kebijakan/create">
        <Button class="inline-flex items-center gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Program</span>
        </Button>
      </Link>
    </div>

    <!-- Filters Toolbar -->
    <div class="p-4 bg-white rounded-xl border border-neutral-200/80 shadow-2xs space-y-3">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
          <IconComponent
            name="IconSearch"
            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none"
          />
          <Input
            v-model="search"
            @input="onSearchInput"
            placeholder="Cari nama program, janji, realisasi..."
            class="pl-9 text-xs"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Status Label Select -->
          <select
            v-model="statusLabelFilter"
            @change="onStatusLabelChange(statusLabelFilter)"
            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none"
          >
            <option v-for="sl in statusLabels" :key="sl.slug" :value="sl.slug">
              {{ sl.label }}
            </option>
          </select>

          <!-- Publish Status Toggle Buttons -->
          <div class="flex items-center gap-1 shrink-0">
            <Button
              size="sm"
              :variant="statusFilter === '' ? 'default' : 'outline'"
              @click="onStatusChange('')"
              class="text-xs h-8 px-2.5"
            >
              Semua
            </Button>
            <Button
              size="sm"
              :variant="statusFilter === 'terbit' ? 'default' : 'outline'"
              @click="onStatusChange('terbit')"
              class="text-xs h-8 px-2.5"
            >
              Terbit
            </Button>
            <Button
              size="sm"
              :variant="statusFilter === 'draft' ? 'default' : 'outline'"
              @click="onStatusChange('draft')"
              class="text-xs h-8 px-2.5"
            >
              Draft
            </Button>
          </div>
        </div>
      </div>

      <!-- Sector Filter Buttons -->
      <div class="pt-2 border-t border-neutral-100 flex items-center gap-1.5 overflow-x-auto pb-1 isf-no-scrollbar">
        <button
          v-for="s in sectors"
          :key="s.slug"
          type="button"
          @click="onSectorChange(s.slug)"
          :class="[
            'px-3 py-1 rounded-full text-xs font-medium transition-all shrink-0 cursor-pointer',
            sectorFilter === s.slug
              ? 'bg-neutral-900 text-white font-semibold'
              : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
          ]"
        >
          {{ s.label }}
        </button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-2xs overflow-hidden">
      <LoadingIndicator v-if="loading" message="Memuat data monitoring kebijakan..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="m-6"
      />

      <div v-else-if="items.length === 0" class="py-16 text-center text-neutral-500">
        <IconComponent name="IconChecklist" class="w-10 h-10 mx-auto text-neutral-300 mb-2" />
        <p class="text-sm font-medium text-neutral-700">Tidak ada program monitoring kebijakan ditemukan</p>
        <p class="text-xs text-neutral-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead>Nama Program Pemerintah</TableHead>
              <TableHead class="w-32">Sektor</TableHead>
              <TableHead class="w-48">Progres Realisasi</TableHead>
              <TableHead class="w-36 text-center">Status Evaluasi</TableHead>
              <TableHead class="w-32">Pembaruan</TableHead>
              <TableHead class="w-20 text-center">Urutan</TableHead>
              <TableHead class="w-24 text-center">Status</TableHead>
              <TableHead class="w-28 text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id" class="hover:bg-neutral-50/70">
              <TableCell class="text-center text-xs text-neutral-400 font-mono">
                {{ idx + 1 }}
              </TableCell>
              <TableCell>
                <div class="font-medium text-sm text-neutral-900 line-clamp-1">
                  {{ item.program_name }}
                </div>
                <div v-if="item.source_title" class="text-[11px] text-neutral-500 mt-0.5 flex items-center gap-1">
                  <span>Sumber: {{ item.source_title }}</span>
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
                    <span class="font-bold text-neutral-900 font-mono">{{ item.progress_percent }}%</span>
                  </div>
                  <div class="w-full bg-neutral-200 rounded-full h-1.5 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="item.progress_percent >= 100 ? 'bg-emerald-500' : 'bg-isf-red'"
                      :style="{ width: `${Math.min(100, Math.max(0, item.progress_percent))}%` }"
                    ></div>
                  </div>
                </div>
              </TableCell>
              <TableCell class="text-center">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border',
                    getProgressBadge(item.status_label).color
                  ]"
                >
                  {{ getProgressBadge(item.status_label).label }}
                </span>
              </TableCell>
              <TableCell class="text-xs text-neutral-600 whitespace-nowrap">
                {{ item.last_updated_date || '-' }}
              </TableCell>
              <TableCell class="text-center text-xs font-mono text-neutral-600">
                {{ item.order ?? 0 }}
              </TableCell>
              <TableCell class="text-center">
                <StatusBadge :status="item.status" />
              </TableCell>
              <TableCell class="text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <Link :href="`/cms/monitoring-kebijakan/${item.id}/edit`">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-neutral-600 hover:text-neutral-900">
                      <IconComponent name="IconEdit" class="w-4 h-4" />
                    </Button>
                  </Link>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                    @click="handleDelete(item)"
                  >
                    <IconComponent name="IconTrash" class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </div>
</template>

