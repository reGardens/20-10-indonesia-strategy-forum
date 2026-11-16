<script setup>
import { ref, onMounted } from 'vue'
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
const statusFilter = ref('')
let searchTimeout = null

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'pending', label: 'Pending (Menunggu Review)' },
  { value: 'disetujui', label: 'Disetujui (Tayang)' },
  { value: 'ditolak', label: 'Ditolak' },
]

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.suratPembaca.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat antrean surat pembaca.')
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

async function handleApprove(item) {
  try {
    await cmsResources.suratPembaca.approve(item.id)
    alertSuccess(`Surat "${item.judul_surat}" berhasil disetujui untuk tayang.`)
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyetujui surat.'))
  }
}

async function handleReject(item) {
  const reason = prompt('Masukkan alasan penolakan (opsional):', '')
  if (reason === null) return // Canceled

  try {
    await cmsResources.suratPembaca.reject(item.id, reason)
    alertSuccess(`Surat "${item.judul_surat}" ditolak.`)
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menolak surat.'))
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus surat pembaca dari "${item.nama_pengirim}"?`
  )
  if (!confirmed) return

  try {
    await cmsResources.suratPembaca.delete(item.id)
    alertSuccess('Surat pembaca berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus surat.'))
  }
}

function formatDate(val) {
  if (!val) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
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
  <Head title="Moderasi Surat Pembaca — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Moderasi Surat Pembaca</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Antrean aspirasi, kritik, dan gagasan masyarakat umum. Hanya surat berstatus disetujui yang tampil di portal publik.
        </p>
      </div>
      <Link href="/cms/surat-pembaca/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Surat Manual</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          placeholder="Cari nama pengirim, kota, atau judul surat..."
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
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat antrean surat pembaca..." />
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
            <TableHead class="min-w-[180px]">Pengirim & Asal</TableHead>
            <TableHead class="min-w-[320px]">Judul & Isi Surat</TableHead>
            <TableHead class="w-36">Waktu Masuk</TableHead>
            <TableHead class="w-28 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada antrean surat pembaca ditemukan.
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
              <div class="font-semibold text-foreground text-sm leading-snug">
                {{ item.nama_pengirim }}
              </div>
              <div class="text-xs text-muted-foreground mt-0.5">
                {{ item.kota_domisili || 'Indonesia' }}
              </div>
              <div class="text-[11px] text-muted-foreground font-mono truncate max-w-[170px] mt-0.5">
                {{ item.email_pengirim }}
              </div>
            </TableCell>

            <TableCell>
              <div class="font-semibold text-foreground text-xs sm:text-sm leading-snug">
                {{ item.judul_surat }}
              </div>
              <p class="text-xs text-muted-foreground line-clamp-2 mt-1 leading-relaxed">
                {{ item.isi_surat }}
              </p>
              <div v-if="item.catatan_redaksi" class="mt-1 text-[11px] text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 inline-block">
                Catatan: {{ item.catatan_redaksi }}
              </div>
            </TableCell>

            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ formatDate(item.created_at) }}
            </TableCell>

            <TableCell class="text-center">
              <span
                v-if="item.status === 'disetujui'"
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
              >
                Disetujui
              </span>
              <span
                v-else-if="item.status === 'ditolak'"
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-rose-500/10 text-rose-600 border border-rose-500/20"
              >
                Ditolak
              </span>
              <span
                v-else
                class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-amber-500/10 text-amber-600 border border-amber-500/20 animate-pulse"
              >
                Pending
              </span>
            </TableCell>

            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <!-- Quick Approve Button -->
                <Button
                  v-if="item.status !== 'disetujui'"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2 text-xs font-medium text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-700 hover:border-emerald-500 inline-flex items-center"
                  title="Setujui Surat"
                  @click="handleApprove(item)"
                >
                  <IconComponent name="Check" :size="13" class="mr-1" />
                  Setujui
                </Button>

                <!-- Quick Reject Button -->
                <Button
                  v-if="item.status !== 'ditolak'"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2 text-xs font-medium text-amber-600 border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-700 hover:border-amber-500 inline-flex items-center"
                  title="Tolak Surat"
                  @click="handleReject(item)"
                >
                  <IconComponent name="X" :size="13" class="mr-1" />
                  Tolak
                </Button>

                <!-- Detail / Edit -->
                <Link :href="`/cms/surat-pembaca/${item.id}/edit`">
                  <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center">
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>

                <!-- Delete -->
                <Button
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  title="Hapus"
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
