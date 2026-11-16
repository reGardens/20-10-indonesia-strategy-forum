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
const topicFilter = ref('')
const regionFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

const topics = [
  { value: '', label: 'Semua Isu' },
  { value: 'politik', label: 'Politik' },
  { value: 'ekonomi', label: 'Ekonomi' },
  { value: 'sosial', label: 'Sosial' },
  { value: 'hukum', label: 'Hukum' },
]

const regions = [
  { value: '', label: 'Semua Wilayah' },
  { value: 'nasional', label: 'Nasional' },
  { value: 'jawa', label: 'Pulau Jawa' },
  { value: 'sumatera', label: 'Sumatera' },
  { value: 'kalimantan', label: 'Kalimantan' },
  { value: 'sulawesi', label: 'Sulawesi' },
  { value: 'bali_nusra', label: 'Bali & Nusra' },
  { value: 'maluku_papua', label: 'Maluku & Papua' },
]

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.survei.list({
      q: search.value || undefined,
      topic: topicFilter.value || undefined,
      region: regionFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar survei')
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
    `Apakah Anda yakin ingin menghapus laporan survei "${item.title}"?`
  )
  if (!confirmed) return

  try {
    await cmsResources.survei.delete(item.id)
    alertSuccess('Laporan survei berhasil dihapus')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus survei'))
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
  <Head title="Manajemen Survei & Riset Opini Publik · CMS ISF" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Survei Opini Publik</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola rilis survei opini publik ISF dan lembaga kredibel lainnya, metodologi, data segmen visualisasi, dan laporan PDF.
        </p>
      </div>
      <Link href="/cms/survei/create">
        <Button class="gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Survei Baru</span>
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-card rounded-lg border border-border p-4">
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-5">
          <Input
            v-model="search"
            placeholder="Cari judul, lembaga, temuan..."
            @input="onSearchInput"
          />
        </div>
        <div class="sm:col-span-3">
          <select
            v-model="topicFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option v-for="t in topics" :key="t.value" :value="t.value">
              {{ t.label }}
            </option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <select
            v-model="regionFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option v-for="r in regions" :key="r.value" :value="r.value">
              {{ r.label }}
            </option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <select
            v-model="statusFilter"
            @change="loadData"
            class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Status</option>
            <option value="terbit">Terbit</option>
            <option value="draft">Draft</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-card rounded-lg border border-border overflow-hidden">
      <LoadingIndicator v-if="loading" text="Memuat data survei..." />
      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        action-label="Coba Lagi"
        @retry="loadData"
      />
      <div v-else-if="items.length === 0" class="p-8 text-center text-muted-foreground">
        <IconComponent name="IconChartBar" class="w-12 h-12 mx-auto mb-2 opacity-40" />
        <p class="font-medium">Belum ada data survei</p>
        <p class="text-xs mt-1">Tambahkan survei pertama menggunakan tombol di atas.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead class="w-64">Judul & Lembaga Pelaksana</TableHead>
              <TableHead class="w-24">Isu</TableHead>
              <TableHead class="w-28">Wilayah</TableHead>
              <TableHead class="w-36">Headline Metric</TableHead>
              <TableHead class="w-32">Sampel & MoE</TableHead>
              <TableHead class="w-24 text-center">Status</TableHead>
              <TableHead class="w-24 text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id">
              <TableCell class="text-center font-mono text-xs text-muted-foreground">
                {{ idx + 1 }}
              </TableCell>

              <TableCell>
                <div class="font-bold text-foreground text-xs sm:text-sm line-clamp-2 leading-snug">
                  {{ item.title }}
                </div>
                <div class="text-[11px] text-muted-foreground flex items-center gap-1.5 mt-1 font-space-grotesk">
                  <span class="font-semibold text-primary">{{ item.institution }}</span>
                  <span v-if="item.is_isf" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-primary/10 text-primary uppercase">
                    ISF
                  </span>
                </div>
                <div v-if="item.is_featured" class="mt-1">
                  <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-500/10 text-amber-600 border border-amber-500/20">
                    ⭐ Featured di Dashboard
                  </span>
                </div>
              </TableCell>

              <TableCell>
                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-secondary text-secondary-foreground font-mono">
                  {{ item.topic }}
                </span>
              </TableCell>

              <TableCell>
                <span class="text-xs text-muted-foreground capitalize">
                  {{ item.region }}
                </span>
              </TableCell>

              <TableCell>
                <div class="font-bold text-primary font-mono text-base">
                  {{ item.headline_stat_value }}
                </div>
                <div class="text-[11px] text-muted-foreground line-clamp-1">
                  {{ item.headline_stat_label }}
                </div>
              </TableCell>

              <TableCell class="text-xs text-muted-foreground font-mono">
                <div>{{ item.sample_size ? `${item.sample_size.toLocaleString('id-ID')} Rsp.` : '-' }}</div>
                <div class="text-[11px]">MoE: {{ item.margin_of_error || '-' }}</div>
              </TableCell>

              <TableCell class="text-center">
                <span
                  v-if="item.status === 'terbit'"
                  class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
                >
                  Terbit
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
                  <Link :href="`/cms/survei/${item.id}/edit`">
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

