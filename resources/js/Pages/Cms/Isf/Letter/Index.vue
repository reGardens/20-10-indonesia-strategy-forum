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
const statusFilter = ref('')
let searchTimeout = null

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
    loadError.value = errorMessage(err, 'Gagal memuat antrean surat pembaca')
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

async function handleApprove(item) {
  try {
    await cmsResources.suratPembaca.approve(item.id)
    alertSuccess(`Surat "${item.judul_surat}" berhasil disetujui untuk tayang.`)
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyetujui surat'))
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
    alertError(errorMessage(err, 'Gagal menolak surat'))
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus surat pembaca dari "${item.nama_pengirim}"?`
  )
  if (!confirmed) return

  try {
    await cmsResources.suratPembaca.delete(item.id)
    alertSuccess('Surat pembaca berhasil dihapus')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus surat'))
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
  <Head title="Moderasi Surat Pembaca · CMS ISF" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Moderasi Surat Pembaca</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Antrean aspirasi, kritik, dan gagasan masyarakat umum. Hanya surat berstatus disetujui yang tampil di portal publik.
        </p>
      </div>
      <Link href="/cms/surat-pembaca/create">
        <Button class="gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Surat Manual</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-card rounded-lg border border-border p-4">
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-8">
          <Input
            v-model="search"
            placeholder="Cari nama pengirim, kota, atau judul surat..."
            @input="onSearchInput"
          />
        </div>
        <div class="sm:col-span-4">
          <select
            v-model="statusFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Status Moderasi</option>
            <option value="pending">Menunggu Moderasi (Pending)</option>
            <option value="disetujui">Disetujui (Tayang Publik)</option>
            <option value="ditolak">Ditolak</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-card rounded-lg border border-border overflow-hidden">
      <LoadingIndicator v-if="loading" text="Memuat antrean surat pembaca..." />
      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        action-label="Coba Lagi"
        @retry="loadData"
      />
      <div v-else-if="items.length === 0" class="p-8 text-center text-muted-foreground">
        <IconComponent name="IconMail" class="w-12 h-12 mx-auto mb-2 opacity-40" />
        <p class="font-medium">Belum ada kiriman surat pembaca</p>
        <p class="text-xs mt-1">Kiriman pembaca dari halaman landing akan muncul di sini.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead class="w-48">Pengirim & Domisili</TableHead>
              <TableHead>Judul & Ringkasan Naskah</TableHead>
              <TableHead class="w-36">Waktu Masuk</TableHead>
              <TableHead class="w-28 text-center">Status</TableHead>
              <TableHead class="w-44 text-right">Moderasi / Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id">
              <TableCell class="text-center font-mono text-xs text-muted-foreground">
                {{ idx + 1 }}
              </TableCell>

              <TableCell>
                <div class="font-semibold text-foreground text-xs sm:text-sm">
                  {{ item.nama_pengirim }}
                </div>
                <div class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                  <IconComponent name="IconMapPin" class="w-3 h-3 text-muted-foreground" />
                  <span>{{ item.kota_domisili }}</span>
                </div>
                <div class="text-[11px] text-muted-foreground font-mono truncate max-w-[170px]">
                  {{ item.email_pengirim }}
                </div>
              </TableCell>

              <TableCell>
                <div class="font-bold text-foreground text-xs sm:text-sm leading-snug">
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
                <div class="flex items-center justify-end gap-1">
                  <!-- Quick Approve Button -->
                  <Button
                    v-if="item.status !== 'disetujui'"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-emerald-600 hover:bg-emerald-50"
                    title="Setujui Surat"
                    @click="handleApprove(item)"
                  >
                    <IconComponent name="IconCheck" class="w-4 h-4" />
                  </Button>

                  <!-- Quick Reject Button -->
                  <Button
                    v-if="item.status !== 'ditolak'"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-amber-600 hover:bg-amber-50"
                    title="Tolak Surat"
                    @click="handleReject(item)"
                  >
                    <IconComponent name="IconX" class="w-4 h-4" />
                  </Button>

                  <!-- Detail / Edit -->
                  <Link :href="`/cms/surat-pembaca/${item.id}/edit`">
                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Review / Edit">
                      <IconComponent name="IconEdit" class="w-4 h-4 text-muted-foreground" />
                    </Button>
                  </Link>

                  <!-- Delete -->
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-destructive hover:bg-destructive/10"
                    title="Hapus"
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

