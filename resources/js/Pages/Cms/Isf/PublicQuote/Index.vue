<script setup>
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
const tipeFilter = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const perPage = ref(10)
let searchTimeout = null

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return items.value.slice(start, start + perPage.value)
})

const tipeOptions = [
  { value: '', label: 'Semua Tipe Penutur' },
  { value: 'tokoh', label: 'Tokoh Publik' },
  { value: 'netizen', label: 'Warganet / Netizen' }
]

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'aktif', label: 'Aktif' },
  { value: 'arsip', label: 'Arsip' }
]

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kutipanPublik.list({
      q: search.value || undefined,
      tokoh_tipe: tipeFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat kutipan publik.')
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

function onTipeChange() {
  currentPage.value = 1
  loadData()
}

function onStatusChange() {
  currentPage.value = 1
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus kutipan dari "${item.tokoh_nama}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.kutipanPublik.delete(item.id)
    alertSuccess('Kutipan publik berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus kutipan.'))
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
  <Head title="Kelola Kutipan Publik — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Kutipan Publik</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kumpulan kutipan tajam dari tokoh bangsa dan warganet terkait isu strategis nasional, sumber resmi, dan profil terkait.
        </p>
      </div>
      <Link href="/cms/kutipan-publik/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Kutipan</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          placeholder="Cari kutipan, tokoh, isu..."
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
          <label for="filter-tipe" class="text-xs text-muted-foreground whitespace-nowrap">Tipe:</label>
          <FilterSelect
            id="filter-tipe"
            v-model="tipeFilter"
            placeholder="Semua Tipe"
            :options="tipeOptions"
            @change="onTipeChange"
          />
        </div>

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
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat kutipan publik..." />
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
            <TableHead class="min-w-[200px]">Tokoh / Penutur</TableHead>
            <TableHead class="min-w-[320px]">Kutipan & Sumber</TableHead>
            <TableHead class="w-28">Isu Terkait</TableHead>
            <TableHead class="w-28">Tanggal</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data kutipan publik ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in paginatedItems"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 + (currentPage - 1) * perPage }}
            </TableCell>

            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug">
                {{ item.tokoh_nama }}
              </div>
              <div class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                {{ item.tokoh_jabatan || '-' }}
              </div>
              <div class="flex items-center gap-1.5 mt-1">
                <Badge variant="outline" class="text-[10px] px-1.5 py-0 font-normal">
                  {{ item.tokoh_tipe === 'tokoh' ? 'Tokoh' : 'Netizen' }}
                </Badge>
                <span v-if="item.tokoh_id" class="text-[10px] text-emerald-600 font-medium">
                  ✓ Profil Terhubung
                </span>
              </div>
            </TableCell>

            <TableCell>
              <p class="text-xs text-foreground italic line-clamp-3 leading-relaxed">
                "{{ item.kutipan }}"
              </p>
              <div v-if="item.sumber_nama" class="mt-1 text-[11px] text-muted-foreground">
                Sumber:
                <a
                  v-if="item.sumber_url"
                  :href="item.sumber_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-red-600 hover:underline ml-1 font-medium"
                >
                  {{ item.sumber_nama }} ↗
                </a>
                <span v-else class="ml-1">{{ item.sumber_nama }}</span>
              </div>
            </TableCell>

            <TableCell>
              <span class="text-xs font-mono text-muted-foreground">
                #{{ item.isu_terkait }}
              </span>
            </TableCell>

            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ formatDate(item.tanggal_kutipan) }}
            </TableCell>

            <TableCell class="text-center">
              <span
                v-if="item.status === 'aktif'"
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
              >
                Aktif
              </span>
              <span
                v-else
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-neutral-500/10 text-neutral-600 border border-neutral-500/20"
              >
                Arsip
              </span>
            </TableCell>

            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/kutipan-publik/${item.id}/edit`">
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
