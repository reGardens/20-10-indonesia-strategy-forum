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
const statusFilter = ref('')
let searchTimeout = null

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.readerPoll.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar polling')
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
    `Apakah Anda yakin ingin menghapus polling "${item.question}"? Semua rekapitulasi suara terkait akan dihapus permanen.`
  )
  if (!confirmed) return

  try {
    await cmsResources.readerPoll.delete(item.id)
    alertSuccess('Polling berhasil dihapus')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus polling'))
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
  <Head title="Manajemen Reader Poll · CMS ISF" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Reader Poll (Jajak Pendapat)</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola pertanyaan jajak pendapat publik interaktif, opsi jawaban dinamis, dan pemantauan perolehan suara.
        </p>
      </div>
      <Link href="/cms/reader-poll/create">
        <Button class="gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Polling</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-card rounded-lg border border-border p-4">
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-8">
          <Input
            v-model="search"
            placeholder="Cari pertanyaan polling..."
            @input="onSearchInput"
          />
        </div>
        <div class="sm:col-span-4">
          <select
            v-model="statusFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="selesai">Selesai</option>
            <option value="draft">Draft</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table / List -->
    <div class="bg-card rounded-lg border border-border overflow-hidden">
      <LoadingIndicator v-if="loading" text="Memuat polling..." />
      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        action-label="Coba Lagi"
        @retry="loadData"
      />
      <div v-else-if="items.length === 0" class="p-8 text-center text-muted-foreground">
        <IconComponent name="IconChartBar" class="w-12 h-12 mx-auto mb-2 opacity-40" />
        <p class="font-medium">Belum ada reader poll</p>
        <p class="text-xs mt-1">Klik tombol di atas untuk membuat polling baru.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead>Pertanyaan Polling</TableHead>
              <TableHead class="w-32 text-center">Opsi Jawaban</TableHead>
              <TableHead class="w-28 text-center">Total Suara</TableHead>
              <TableHead class="w-40">Periode Aktif</TableHead>
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
                <div class="font-medium text-foreground">
                  {{ item.question }}
                </div>
                <div v-if="item.description" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                  {{ item.description }}
                </div>
                <div v-if="item.is_featured" class="mt-1">
                  <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    Featured
                  </span>
                </div>
              </TableCell>

              <TableCell class="text-center text-xs">
                <span class="font-bold text-foreground">
                  {{ Array.isArray(item.options) ? item.options.length : 0 }}
                </span>
                <span class="text-muted-foreground ml-1">opsi</span>
              </TableCell>

              <TableCell class="text-center">
                <span class="font-mono font-bold text-foreground">
                  {{ item.total_votes || 0 }}
                </span>
                <span class="text-xs text-muted-foreground block">suara</span>
              </TableCell>

              <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
                <div>{{ formatDate(item.start_date) }}</div>
                <div class="text-[11px] text-muted-foreground/80">s/d {{ formatDate(item.end_date) }}</div>
              </TableCell>

              <TableCell class="text-center">
                <span
                  v-if="item.status === 'aktif'"
                  class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
                >
                  Aktif
                </span>
                <span
                  v-else-if="item.status === 'selesai'"
                  class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/10 text-blue-600 border border-blue-500/20"
                >
                  Selesai
                </span>
                <span
                  v-else
                  class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-neutral-500/10 text-neutral-600 border border-neutral-500/20"
                >
                  Draft
                </span>
              </TableCell>

              <TableCell class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <Link :href="`/cms/reader-poll/${item.id}/edit`">
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

