<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
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
  Pagination
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
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
    const res = await cmsResources.alert.list({
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
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat alert.'
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
    await cmsResources.alert.remove(item.id)
    alertSuccess('Alert peringatan berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus alert.')
  }
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Alert Peringatan — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Alert Peringatan Dini</span>
          <Badge class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola notifikasi intelijen regulasi B2B untuk Portal Anggota (/anggota).
        </p>
      </div>
      <Link href="/cms/alert/create">
        <Button class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Alert</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul, sektor, atau ringkasan..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <button
          type="button"
          @click="onStatusChange('')"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-medium transition',
            statusFilter === '' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-accent border border-border'
          ]"
        >
          Semua
        </button>
        <button
          type="button"
          @click="onStatusChange('terbit')"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-medium transition',
            statusFilter === 'terbit' ? 'bg-emerald-600 text-white' : 'bg-card text-muted-foreground hover:bg-accent border border-border'
          ]"
        >
          Terbit
        </button>
        <button
          type="button"
          @click="onStatusChange('draf')"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-medium transition',
            statusFilter === 'draf' ? 'bg-slate-700 text-white' : 'bg-card text-muted-foreground hover:bg-accent border border-border'
          ]"
        >
          Draf
        </button>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat alert..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-[40%]">Judul Peringatan</TableHead>
            <TableHead>Prioritas</TableHead>
            <TableHead>Sektor</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Tanggal</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data alert ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="item in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="py-3 font-semibold text-foreground text-sm">
              {{ item.title }}
            </TableCell>
            <TableCell>
              <Badge
                :class="[
                  item.priority === 'merah'
                    ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20'
                    : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                ]"
                class="text-[11px] font-semibold"
              >
                {{ item.priority === 'merah' ? 'Prioritas Tinggi' : 'Perlu Dipantau' }}
              </Badge>
            </TableCell>
            <TableCell class="text-xs text-foreground font-medium">
              {{ item.sektor }}
            </TableCell>
            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell class="text-xs text-muted-foreground">
              {{ formatDate(item.date) }}
            </TableCell>
            <TableCell class="text-right space-x-1">
              <Link :href="`/cms/alert/${item.id}/edit`">
                <Button variant="outline" size="sm" class="h-8 text-xs hover:bg-emerald-500/10 hover:text-emerald-500 hover:border-emerald-500">
                  <IconComponent name="Edit" :size="14" class="mr-1" />
                  Edit
                </Button>
              </Link>
              <Button
                variant="destructive"
                size="sm"
                class="h-8 text-xs"
                @click="handleDelete(item)"
              >
                <IconComponent name="Trash" :size="14" class="mr-1" />
                Hapus
              </Button>
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
