<script setup>
import { ref, computed, onMounted } from 'vue'
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
const reordering = ref(false)
const currentPage = ref(1)
const perPage = ref(10)
let searchTimeout = null

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return items.value.slice(start, start + perPage.value)
})

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kursiParlemen.list({
      q: search.value || undefined,
      lembaga: 'DPR',
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

async function moveOrder(index, direction) {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= items.value.length) return
  if (reordering.value) return

  if (search.value) {
    alertError('Harap bersihkan pencarian untuk mengubah urutan.')
    return
  }

  reordering.value = true
  const original = [...items.value]

  try {
    const updated = [...items.value]
    const temp = updated[index]
    updated[index] = updated[targetIndex]
    updated[targetIndex] = temp

    updated.forEach((it, idx) => {
      it.urutan = idx + 1
    })

    items.value = updated

    await cmsResources.kursiParlemen.reorder(updated.map((it, idx) => ({ id: it.id, urutan: idx + 1 })))
    alertSuccess('Urutan tampil kursi parlemen berhasil diperbarui.')
  } catch (err) {
    items.value = original
    alertError(errorMessage(err, 'Gagal memperbarui urutan kursi parlemen.'))
  } finally {
    reordering.value = false
  }
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
          Kelola komposisi kursi DPR RI per partai/fraksi, logo resmi, warna partai, serta urutan infografis interaktif.
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
      <div class="text-xs text-muted-foreground">
        Urutan tampilan dapat diubah menggunakan tombol panah pada kolom <strong>#</strong>.
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data kursi parlemen..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
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
            <TableHead class="w-20 text-center text-xs">#</TableHead>
            <TableHead class="w-16 text-center text-xs">Logo</TableHead>
            <TableHead class="w-20 text-xs">Lembaga</TableHead>
            <TableHead class="w-24 text-xs">Periode</TableHead>
            <TableHead class="text-xs">Partai / Fraksi</TableHead>
            <TableHead class="w-32 text-right text-xs">Jumlah Kursi</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-12 text-xs text-muted-foreground">
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
            v-for="(item, idx) in paginatedItems"
            :key="item.id || idx"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <!-- Order # with Up/Down buttons -->
            <TableCell class="text-center text-xs font-mono text-muted-foreground w-20">
              <div class="flex items-center justify-center gap-1.5">
                <span class="font-bold text-foreground">{{ idx + 1 + (currentPage - 1) * perPage }}</span>
                <div class="inline-flex flex-col">
                  <button
                    type="button"
                    :title="search ? 'Reset pencarian untuk mengubah urutan' : 'Pindah Urutan Naik'"
                    :disabled="(idx + (currentPage - 1) * perPage) === 0 || reordering || Boolean(search)"
                    @click="moveOrder(idx + (currentPage - 1) * perPage, -1)"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition"
                  >
                    <IconComponent name="ChevronUp" :size="12" />
                  </button>
                  <button
                    type="button"
                    :title="search ? 'Reset pencarian untuk mengubah urutan' : 'Pindah Urutan Turun'"
                    :disabled="(idx + (currentPage - 1) * perPage) === items.length - 1 || reordering || Boolean(search)"
                    @click="moveOrder(idx + (currentPage - 1) * perPage, 1)"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition"
                  >
                    <IconComponent name="ChevronDown" :size="12" />
                  </button>
                </div>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <div v-if="item.logo" class="w-8 h-8 mx-auto rounded border border-border bg-card p-0.5 flex items-center justify-center">
                <img :src="item.logo" :alt="item.singkatan || item.partai" class="max-w-full max-h-full object-contain" />
              </div>
              <div
                v-else
                class="w-8 h-8 mx-auto rounded border border-border bg-muted/40 flex items-center justify-center text-[10px] font-bold text-muted-foreground font-mono"
                :title="item.singkatan || item.partai"
              >
                {{ (item.singkatan || item.partai || 'PAR').slice(0, 3).toUpperCase() }}
              </div>
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
            <TableCell class="text-right font-semibold text-sm text-foreground">
              {{ Number(item.jumlah_kursi).toLocaleString('id-ID') }}
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/kursi-parlemen/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs inline-flex items-center gap-1 border-border hover:bg-muted"
                  >
                    <IconComponent name="Edit" :size="13" />
                    <span>Edit</span>
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs inline-flex items-center gap-1 text-red-400 hover:text-red-300 hover:bg-red-500/10 border-red-500/20"
                  @click="handleDelete(item)"
                >
                  <IconComponent name="Trash" :size="13" />
                  <span>Hapus</span>
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>

