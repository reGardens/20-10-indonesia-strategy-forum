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
  TableCell
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

const sectors = [
  { slug: '', label: 'Semua Sektor' },
  { slug: 'ekonomi', label: 'Ekonomi' },
  { slug: 'kesehatan', label: 'Kesehatan' },
  { slug: 'pendidikan', label: 'Pendidikan' },
  { slug: 'pertahanan', label: 'Pertahanan' },
  { slug: 'lingkungan', label: 'Lingkungan' },
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

function onSectorChange(sector) {
  sectorFilter.value = sector
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = statusFilter.value === status ? '' : status
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
  <Head title="Policy Brief — CMS ISF" />

  <div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
          Policy Brief
        </h1>
        <p class="text-sm text-neutral-500 mt-1">
          Kelola ringkasan eksekutif riset kebijakan, rekomendasi strategis, dokumen PDF, dan tanggal publikasi.
        </p>
      </div>
      <Link href="/cms/policy-brief/create">
        <Button class="inline-flex items-center gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Policy Brief</span>
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
            placeholder="Cari judul, penulis, lembaga..."
            class="pl-9 text-xs"
          />
        </div>

        <!-- Status Filter Buttons -->
        <div class="flex items-center gap-2 shrink-0">
          <Button
            size="sm"
            :variant="statusFilter === '' ? 'default' : 'outline'"
            @click="onStatusChange('')"
            class="text-xs"
          >
            Semua
          </Button>
          <Button
            size="sm"
            :variant="statusFilter === 'terbit' ? 'default' : 'outline'"
            @click="onStatusChange('terbit')"
            class="text-xs"
          >
            Terbit
          </Button>
          <Button
            size="sm"
            :variant="statusFilter === 'draft' ? 'default' : 'outline'"
            @click="onStatusChange('draft')"
            class="text-xs"
          >
            Draft
          </Button>
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
      <LoadingIndicator v-if="loading" message="Memuat data policy brief..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="m-6"
      />

      <div v-else-if="items.length === 0" class="py-16 text-center text-neutral-500">
        <IconComponent name="IconFileText" class="w-10 h-10 mx-auto text-neutral-300 mb-2" />
        <p class="text-sm font-medium text-neutral-700">Tidak ada data policy brief ditemukan</p>
        <p class="text-xs text-neutral-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead>Judul Riset Kebijakan</TableHead>
              <TableHead class="w-32">Sektor</TableHead>
              <TableHead class="w-48">Penulis & Lembaga</TableHead>
              <TableHead class="w-36">Berkas PDF</TableHead>
              <TableHead class="w-28">Tgl Terbit</TableHead>
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
                <div class="font-medium text-sm text-neutral-900 line-clamp-2">
                  {{ item.title }}
                </div>
                <div class="text-[11px] text-neutral-400 font-mono mt-0.5">
                  /{{ item.slug }}
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
                <div class="text-xs font-medium text-neutral-800">
                  {{ item.author }}
                </div>
                <div v-if="item.institution" class="text-[11px] text-neutral-500 truncate max-w-[180px]">
                  {{ item.institution }}
                </div>
              </TableCell>
              <TableCell>
                <a
                  v-if="item.pdf_path"
                  :href="item.pdf_path"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 font-medium"
                >
                  <IconComponent name="IconFileText" class="w-3.5 h-3.5 shrink-0" />
                  <span class="truncate max-w-[100px]">{{ item.pdf_size || 'PDF' }}</span>
                </a>
                <span v-else class="text-xs text-neutral-400">-</span>
              </TableCell>
              <TableCell class="text-xs text-neutral-600 whitespace-nowrap">
                {{ item.published_at || '-' }}
              </TableCell>
              <TableCell class="text-center text-xs font-mono text-neutral-600">
                {{ item.order ?? 0 }}
              </TableCell>
              <TableCell class="text-center">
                <StatusBadge :status="item.status" />
              </TableCell>
              <TableCell class="text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <Link :href="`/cms/policy-brief/${item.id}/edit`">
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

