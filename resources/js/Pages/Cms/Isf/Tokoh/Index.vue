<script setup>
import { useCmsPagination } from '@/Composables/useCmsPagination'
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
  FilterSelect
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const perPage = ref(10)
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 })
const liveId = ref(null)
const featuredId = ref(null)
const politikCount = ref(0)
const settingFeaturedId = ref(null)
const togglingPolitikId = ref(null)
const loading = ref(true)
const errorMsg = ref('')

const search = ref('')
const statusFilter = ref('')
const kategoriFilter = ref('')
const page = useCmsPagination()
let searchTimeout = null

const DEFAULT_LEMBAGA_OPTIONS = [
  { value: '', label: 'Semua Lembaga' },
  { value: 'legislatif', label: 'Legislatif' },
  { value: 'eksekutif', label: 'Eksekutif' },
  { value: 'pakar', label: 'Pakar & Akademisi' }
]
const lembagaOptions = ref([...DEFAULT_LEMBAGA_OPTIONS])

async function loadLembagaOptions() {
  try {
    const res = await cmsResources.kategoriLembaga.list({ per_page: 100, status: 'terbit' })
    const data = Array.isArray(res) ? res : (res?.data || [])
    if (data.length > 0) {
      lembagaOptions.value = [
        { value: '', label: 'Semua Lembaga' },
        ...data.map((item) => ({ value: item.slug, label: item.nama }))
      ]
    }
  } catch (e) {}
}

function getCategoryColor(cat) {
  switch (cat) {
    case 'eksekutif':
      return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900/60'
    case 'legislatif':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/60'
    case 'yudikatif':
      return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900/60'
    case 'pakar':
      return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/60'
    case 'militer':
      return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60'
    case 'pengusaha':
      return 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900/60'
    default:
      return 'bg-muted text-muted-foreground border-border'
  }
}

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.tokoh.list({
      page: page.value,
      per_page: perPage.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined,
      role_category: kategoriFilter.value || undefined
    })
    items.value = res.data || []
    meta.value = {
      current_page: res.current_page || 1,
      last_page: res.last_page || 1,
      total: res.total || 0,
      per_page: res.per_page || perPage.value
    }
    liveId.value = res.live_id || null
    featuredId.value = res.featured_id || null
    politikCount.value = res.politik_count ?? 0
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar sosok.'
  } finally {
    loading.value = false
  }
}

async function handleSetFeatured(item) {
  if (item.is_featured) return
  settingFeaturedId.value = item.id
  try {
    const res = await cmsResources.tokoh.setFeatured(item.id)
    await loadData()
    alertSuccess('"' + item.name + '" berhasil ditetapkan sebagai Sosok Pekan Ini.')
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengubah Sosok Pekan Ini.')
  } finally {
    settingFeaturedId.value = null
  }
}

async function handleTogglePolitik(item) {
  if (!item.show_in_politik && politikCount.value >= 4) {
    alertError('Harap nonaktifkan salah satu yang dipilih terlebih dahulu.')
    return
  }
  togglingPolitikId.value = item.id
  try {
    const res = await cmsResources.tokoh.togglePolitik(item.id)
    await loadData()
    alertSuccess(res.message || 'Status Profil Tokoh Politik berhasil diperbarui.')
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengubah status widget politik.')
  } finally {
    togglingPolitikId.value = null
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

function onKategoriChange(val) {
  kategoriFilter.value = val
  page.value = 1
  loadData()
}

function onPageChange(newPage) {
  page.value = newPage
  loadData()
}

function onPerPageChange(newPerPage) {
  perPage.value = newPerPage
  page.value = 1
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.name)
  if (!confirmed) return
  try {
    await cmsResources.tokoh.remove(item.id)
    alertSuccess('Sosok berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus sosok.')
  }
}

onMounted(() => {
  loadLembagaOptions()
  loadData()
})
</script>

<template>
  <Head title="Direktori Sosok — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Direktori Sosok</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola profil figur pengambil kebijakan, skor sorotan, policy matrix, sosok pekan ini, dan widget politik.
        </p>
      </div>
      <Link href="/cms/tokoh/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Sosok</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari nama atau jabatan sosok..."
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
          <label for="filter-lembaga" class="text-xs text-muted-foreground whitespace-nowrap">Lembaga:</label>
          <FilterSelect
            id="filter-lembaga"
            v-model="kategoriFilter"
            placeholder="Semua Lembaga"
            :options="lembagaOptions"
            @change="onKategoriChange"
          />
        </div>

        <div class="flex items-center gap-2 overflow-x-auto justify-start">
          <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
          <div class="flex gap-1">
            <button
              type="button"
              @click="onStatusChange('')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === ''
                  ? 'bg-red-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Semua
            </button>
            <button
              type="button"
              @click="onStatusChange('terbit')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'terbit'
                  ? 'bg-emerald-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Terbit
            </button>
            <button
              type="button"
              @click="onStatusChange('draf')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'draf'
                  ? 'bg-amber-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Draf
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat daftar sosok..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table
        :current-page="meta.current_page"
        :per-page="meta.per_page"
        :total="meta.total"
        :total-pages="meta.last_page"
        @update:current-page="onPageChange"
        @update:per-page="onPerPageChange"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-16 text-center text-xs">Foto</TableHead>
            <TableHead class="min-w-[190px] w-[24%]">Sosok & Profil</TableHead>
            <TableHead class="w-28">Lembaga</TableHead>
            <TableHead class="min-w-[150px] w-[18%]">Jabatan / Posisi</TableHead>
            <TableHead class="w-20 text-center">Status</TableHead>
            <TableHead class="w-36 text-center">Sosok Pekan Ini</TableHead>
            <TableHead class="w-40 text-center">
              Widget Politik ({{ politikCount }}/4)
            </TableHead>
            <TableHead class="w-32 text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="9" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data sosok ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center text-xs font-mono text-muted-foreground">
              {{ (meta.current_page - 1) * meta.per_page + idx + 1 }}
            </TableCell>
            <TableCell class="py-2.5 px-2 text-center">
              <div class="w-10 h-10 rounded-full overflow-hidden border border-border bg-muted/60 mx-auto flex items-center justify-center shrink-0 shadow-2xs">
                <img
                  v-if="item.photo_url"
                  :src="item.photo_url"
                  :alt="item.name"
                  class="w-full h-full object-cover object-top"
                  loading="lazy"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/40 bg-muted/30">
                  <IconComponent name="User" :size="18" />
                </div>
              </div>
            </TableCell>
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-2">
                {{ item.name }}
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5 truncate">
                /sosok/{{ item.slug }}
              </div>
            </TableCell>
            <TableCell>
              <span
                class="text-[11px] font-semibold px-2 py-0.5 rounded border capitalize"
                :class="getCategoryColor(item.role_category)"
              >
                {{ item.role_category || 'Tokoh' }}
              </span>
            </TableCell>
            <TableCell class="text-xs text-foreground font-medium">
              <div class="line-clamp-2">{{ item.position }}</div>
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status" />
            </TableCell>
            <!-- Sosok Pekan Ini CTA (1/1) -->
            <TableCell class="text-center py-2.5">
              <div class="inline-flex flex-col items-center gap-1">
                <Button
                  v-if="item.is_featured"
                  size="sm"
                  variant="outline"
                  :disabled="settingFeaturedId === item.id"
                  class="h-7 px-2.5 text-[11px] bg-red-600 text-white font-bold hover:bg-red-700 border-red-600 shadow-xs inline-flex items-center"
                  @click="handleSetFeatured(item)"
                >
                  <IconComponent v-if="settingFeaturedId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>✓ Aktif Pekan Ini</span>
                </Button>
                <Button
                  v-else
                  size="sm"
                  variant="outline"
                  :disabled="settingFeaturedId === item.id"
                  class="h-7 px-2.5 text-[11px] border-dashed border-red-600/60 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 inline-flex items-center font-medium"
                  @click="handleSetFeatured(item)"
                >
                  <IconComponent v-if="settingFeaturedId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>Pilih Pekan Ini</span>
                </Button>
              </div>
            </TableCell>
            <!-- Profil Tokoh Politik CTA (4/4) -->
            <TableCell class="text-center py-2.5">
              <div class="inline-flex flex-col items-center gap-1">
                <Button
                  v-if="item.show_in_politik"
                  size="sm"
                  variant="outline"
                  :disabled="togglingPolitikId === item.id"
                  class="h-7 px-2.5 text-[11px] bg-emerald-600 text-white font-bold hover:bg-emerald-700 border-emerald-600 shadow-xs inline-flex items-center"
                  @click="handleTogglePolitik(item)"
                  title="Klik untuk melepas dari Profil Tokoh Politik"
                >
                  <IconComponent v-if="togglingPolitikId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>✓ Aktif Politik</span>
                </Button>
                <Button
                  v-else
                  size="sm"
                  variant="outline"
                  :disabled="togglingPolitikId === item.id"
                  class="h-7 px-2.5 text-[11px] border-dashed border-emerald-600/60 text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 inline-flex items-center font-medium"
                  @click="handleTogglePolitik(item)"
                  title="Pilih untuk Profil Tokoh Politik"
                >
                  <IconComponent v-if="togglingPolitikId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>Pilih Politik</span>
                </Button>
              </div>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/tokoh/${item.id}/edit`">
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
