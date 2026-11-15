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
const lembagaFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kursiParlemen.list({
      q: search.value || undefined,
      lembaga: lembagaFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data kursi parlemen.')
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

function onLembagaChange(lembaga) {
  lembagaFilter.value = lembaga
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = status
  loadData()
}

async function handleDelete(item) {
  const label = `${item.partai} (${item.lembaga} ${item.periode})`
  const confirmed = await confirmDelete(label)
  if (!confirmed) return

  try {
    await cmsResources.kursiParlemen.remove(item.id)
    alertSuccess('Data kursi parlemen berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus data kursi parlemen.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Kursi Parlemen — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="BuildingBank" :size="24" class="text-red-500" />
          Peta Kursi Parlemen
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola komposisi kursi DPR, DPD, dan MPR RI per partai/fraksi, warna partai, serta urutan infografis interaktif.
        </p>
      </div>
      <div>
        <Link href="/cms/kursi-parlemen/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Kursi Parlemen</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
      <div class="relative w-full md:w-80">
        <IconComponent name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari partai, singkatan, periode..."
          class="pl-9 h-9 text-xs bg-card/60"
        />
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Filter Lembaga -->
        <div class="flex items-center gap-1.5">
          <span class="text-xs text-muted-foreground whitespace-nowrap">Lembaga:</span>
          <div class="flex gap-1">
            <button
              v-for="lem in ['', 'DPR', 'DPD', 'MPR']"
              :key="lem"
              type="button"
              @click="onLembagaChange(lem)"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                lembagaFilter === lem
                  ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                  : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
              ]"
            >
              {{ lem === '' ? 'Semua' : lem }}
            </button>
          </div>
        </div>

        <!-- Filter Status -->
        <div class="flex items-center gap-1.5">
          <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
          <div class="flex gap-1">
            <button
              v-for="st in [{ val: '', label: 'Semua' }, { val: 'terbit', label: 'Terbit' }, { val: 'draf', label: 'Draf' }]"
              :key="st.val"
              type="button"
              @click="onStatusChange(st.val)"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === st.val
                  ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                  : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
              ]"
            >
              {{ st.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data kursi parlemen..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader class="bg-muted/40">
          <TableRow>
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-24 text-xs">Lembaga</TableHead>
            <TableHead class="w-28 text-xs">Periode</TableHead>
            <TableHead class="text-xs">Partai / Fraksi</TableHead>
            <TableHead class="w-32 text-xs">Warna Partai</TableHead>
            <TableHead class="w-28 text-right text-xs">Jumlah Kursi</TableHead>
            <TableHead class="w-20 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-28 text-center text-xs">Status</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="9" class="text-center py-12 text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-2">
                <IconComponent name="BuildingBank" :size="32" class="text-muted-foreground/40" />
                <p>Belum ada data kursi parlemen yang sesuai filter.</p>
                <Link href="/cms/kursi-parlemen/create">
                  <Button variant="outline" size="sm" class="mt-2 text-xs">
                    Tambah Kursi Pertama
                  </Button>
                </Link>
              </div>
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(item, idx) in items"
            :key="item.id || idx"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center text-xs font-mono text-muted-foreground">
              {{ idx + 1 }}
            </TableCell>
            <TableCell>
              <Badge variant="outline" class="font-bold text-[10px] bg-red-500/10 text-red-600 border-red-500/20">
                {{ item.lembaga }}
              </Badge>
            </TableCell>
            <TableCell class="text-xs font-mono text-muted-foreground">
              {{ item.periode }}
            </TableCell>
            <TableCell>
              <div class="flex flex-col">
                <span class="font-semibold text-xs text-foreground">
                  {{ item.partai }}
                </span>
                <span v-if="item.singkatan" class="text-[11px] text-muted-foreground">
                  {{ item.singkatan }}
                </span>
              </div>
            </TableCell>
            <TableCell>
              <div class="flex items-center gap-2">
                <span
                  class="w-4 h-4 rounded-full border border-border shadow-2xs shrink-0"
                  :style="{ backgroundColor: item.warna_partai || '#E10600' }"
                ></span>
                <span class="font-mono text-xs text-muted-foreground">
                  {{ item.warna_partai || '-' }}
                </span>
              </div>
            </TableCell>
            <TableCell class="text-right font-semibold text-sm text-foreground">
              {{ Number(item.jumlah_kursi).toLocaleString('id-ID') }}
            </TableCell>
            <TableCell class="text-center text-xs text-muted-foreground font-mono">
              {{ item.urutan ?? 0 }}
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status || 'terbit'" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/kursi-parlemen/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  >
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="handleDelete(item)"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
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

