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
const tipeFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

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
    loadError.value = errorMessage(err, 'Gagal memuat kutipan publik')
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

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus kutipan dari "${item.tokoh_nama}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.kutipanPublik.delete(item.id)
    alertSuccess('Kutipan publik berhasil dihapus')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus kutipan'))
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
  <Head title="Manajemen Kutipan Publik · CMS ISF" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Kutipan Publik</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kumpulan kutipan tajam dari tokoh bangsa dan warganet terkait isu strategis nasional, lengkap dengan sumber dan relasi profil.
        </p>
      </div>
      <Link href="/cms/kutipan-publik/create">
        <Button class="gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Kutipan</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-card rounded-lg border border-border p-4">
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-6">
          <Input
            v-model="search"
            placeholder="Cari kutipan, tokoh, isu..."
            @input="onSearchInput"
          />
        </div>
        <div class="sm:col-span-3">
          <select
            v-model="tipeFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Tipe Penutur</option>
            <option value="tokoh">Tokoh Publik</option>
            <option value="netizen">Warganet / Netizen</option>
          </select>
        </div>
        <div class="sm:col-span-3">
          <select
            v-model="statusFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="arsip">Arsip</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-card rounded-lg border border-border overflow-hidden">
      <LoadingIndicator v-if="loading" text="Memuat kutipan..." />
      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        action-label="Coba Lagi"
        @retry="loadData"
      />
      <div v-else-if="items.length === 0" class="p-8 text-center text-muted-foreground">
        <IconComponent name="IconQuote" class="w-12 h-12 mx-auto mb-2 opacity-40" />
        <p class="font-medium">Belum ada kutipan publik</p>
        <p class="text-xs mt-1">Tambahkan kutipan pertama menggunakan tombol di atas.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead class="w-48">Penutur / Sumber</TableHead>
              <TableHead>Isi Kutipan</TableHead>
              <TableHead class="w-36">Isu Terkait</TableHead>
              <TableHead class="w-28">Tanggal</TableHead>
              <TableHead class="w-24 text-center">Status</TableHead>
              <TableHead class="w-28 text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id">
              <TableCell class="text-center font-mono text-xs text-muted-foreground">
                {{ idx + 1 }}
              </TableCell>

              <TableCell>
                <div class="font-semibold text-foreground text-xs sm:text-sm">
                  {{ item.tokoh_nama }}
                </div>
                <div class="text-[11px] text-muted-foreground truncate max-w-[180px]">
                  {{ item.tokoh_jabatan || '-' }}
                </div>
                <div class="mt-1 flex items-center gap-1.5">
                  <span
                    :class="[
                      'inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider',
                      item.tokoh_tipe === 'tokoh'
                        ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20'
                        : 'bg-sky-500/10 text-sky-600 border border-sky-500/20'
                    ]"
                  >
                    {{ item.tokoh_tipe === 'tokoh' ? 'Tokoh' : 'Netizen' }}
                  </span>
                  <span v-if="item.tokoh_id" class="text-[10px] text-emerald-600 font-medium">
                    ✓ Profil
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
                    class="text-primary hover:underline ml-1"
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
                <div class="flex items-center justify-end gap-1">
                  <Link :href="`/cms/kutipan-publik/${item.id}/edit`">
                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Edit">
                      <IconComponent name="IconEdit" class="w-4 h-4 text-muted-foreground" />
                    </Button>
                  </Link>
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

