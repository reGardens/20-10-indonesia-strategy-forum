<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { activeCategories as CATEGORIES, loadCategories } from '@/Pages/Cms/Isf/options'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
import Swal from 'sweetalert2'
import { useCmsPagination } from '@/Composables/useCmsPagination'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LiveBadge from '@/Pages/Cms/components/shared/LiveBadge.vue'
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

const inertiaPage = usePage()
const authUser = computed(() => inertiaPage.props.auth?.user)
const isMember = computed(() => {
  const roles = authUser.value?.roles || []
  const roleNames = Array.isArray(roles) ? roles.map((r) => typeof r === 'string' ? r : (r?.name || '')) : []
  const hasMemberRole = roleNames.includes('member')
  const hasStaffRole = roleNames.some((r) => ['admin', 'super-admin', 'editor'].includes(r))
  return hasMemberRole && !hasStaffRole
})

const items = ref([])
const perPage = ref(10)
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 })
const liveId = ref(null)
const runningTextCount = ref(0)
const topRankCount = ref(0)
const togglingRunningTextId = ref(null)
const togglingTopRankId = ref(null)
const loading = ref(true)
const errorMsg = ref('')

const search = ref('')
const statusFilter = ref('')
const byMemberFilter = ref(false)
const categoryFilter = ref('')
const page = useCmsPagination('/cms/artikel')

let searchTimeout = null

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.artikel.list({
      page: page.value,
      per_page: perPage.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined,
      is_by_member: byMemberFilter.value ? 1 : undefined,
      category: categoryFilter.value || undefined
    })
    if (res.last_page && page.value > res.last_page && res.last_page > 0) {
      page.value = res.last_page
      return loadData()
    }
    items.value = res.data || []
    meta.value = {
      current_page: res.current_page || 1,
      last_page: res.last_page || 1,
      total: res.total || 0,
      per_page: res.per_page || perPage.value
    }
    liveId.value = res.live_id || null
    runningTextCount.value = res.running_text_count ?? 0
    topRankCount.value = res.top_rank_count ?? 0
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar artikel.'
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

function onByMemberToggle() {
  byMemberFilter.value = !byMemberFilter.value
  page.value = 1
  loadData()
}

function onCategoryChange() {
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

const settingHeroId = ref(null)

async function handleSetHero(item) {
  settingHeroId.value = item.id
  try {
    await cmsResources.artikel.setHero(item.id)
    alertSuccess(`"${item.title}" berhasil ditayangkan sebagai Hero Beranda.`)
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengubah artikel tayang.')
  } finally {
    settingHeroId.value = null
  }
}

async function handleToggleRunningText(item) {
  if (item.show_in_running_text) {
    const inputOptions = {}
    const count = Math.max(runningTextCount.value, item.running_text_order || 1)
    for (let i = 1; i <= Math.min(5, Math.max(count, 1)); i++) {
      inputOptions[String(i)] = `Running Text ${i}`
    }
    inputOptions['off'] = 'Nonaktifkan / Lepas dari Running Text'

    const { value: choice } = await Swal.fire({
      title: 'Atur Urutan Running Text',
      text: item.title,
      input: 'select',
      inputOptions,
      inputValue: String(item.running_text_order || 1),
      showCancelButton: true,
      confirmButtonText: 'Simpan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#e10600',
    })

    if (!choice) return

    togglingRunningTextId.value = item.id
    try {
      const payload = choice === 'off' ? { action: 'off' } : { order: parseInt(choice, 10) }
      const res = await cmsResources.artikel.toggleRunningText(item.id, payload)
      await loadData()
      alertSuccess(res.message || 'Status Running Text berhasil diperbarui.')
    } catch (err) {
      alertError(err?.response?.data?.message || 'Gagal mengubah status running text.')
    } finally {
      togglingRunningTextId.value = null
    }
  } else {
    if (runningTextCount.value >= 5) {
      alertError('Maksimal 5 running text. Harap nonaktifkan salah satu yang dipilih terlebih dahulu.')
      return
    }

    togglingRunningTextId.value = item.id
    try {
      const res = await cmsResources.artikel.toggleRunningText(item.id, {})
      await loadData()
      alertSuccess(res.message || 'Artikel berhasil ditambahkan ke Running Text.')
    } catch (err) {
      alertError(err?.response?.data?.message || 'Gagal menambahkan artikel ke running text.')
    } finally {
      togglingRunningTextId.value = null
    }
  }
}

async function handleToggleTopRank(item) {
  if (item.show_in_top_rank) {
    const labels = {
      '1': 'Top Artikel 1 (Peringkat 1 - Gold)',
      '2': 'Top Artikel 2 (Peringkat 2 - Red)',
      '3': 'Top Artikel 3 (Peringkat 3 - Silver)',
    }
    const inputOptions = {}
    const count = Math.max(topRankCount.value, item.top_rank_order || 1)
    for (let i = 1; i <= Math.min(3, Math.max(count, 1)); i++) {
      inputOptions[String(i)] = labels[String(i)]
    }
    inputOptions['off'] = 'Nonaktifkan / Lepas dari Top Artikel'

    const { value: choice } = await Swal.fire({
      title: 'Atur Peringkat Top Artikel',
      text: item.title,
      input: 'select',
      inputOptions,
      inputValue: String(item.top_rank_order || 1),
      showCancelButton: true,
      confirmButtonText: 'Simpan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d97706',
    })

    if (!choice) return

    togglingTopRankId.value = item.id
    try {
      const payload = choice === 'off' ? { action: 'off' } : { order: parseInt(choice, 10) }
      const res = await cmsResources.artikel.toggleTopRank(item.id, payload)
      await loadData()
      alertSuccess(res.message || 'Status Top Artikel berhasil diperbarui.')
    } catch (err) {
      alertError(err?.response?.data?.message || 'Gagal mengubah status top artikel.')
    } finally {
      togglingTopRankId.value = null
    }
  } else {
    if (topRankCount.value >= 3) {
      alertError('Maksimal 3 Top Artikel. Harap nonaktifkan salah satu yang dipilih terlebih dahulu.')
      return
    }

    togglingTopRankId.value = item.id
    try {
      const res = await cmsResources.artikel.toggleTopRank(item.id, {})
      await loadData()
      alertSuccess(res.message || 'Artikel berhasil ditambahkan ke Top Artikel.')
    } catch (err) {
      alertError(err?.response?.data?.message || 'Gagal menambahkan artikel ke top artikel.')
    } finally {
      togglingTopRankId.value = null
    }
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title)
  if (!confirmed) return
  try {
    await cmsResources.artikel.remove(item.id)
    alertSuccess('Artikel berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus artikel.')
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
  loadCategories()
  loadData()
})
</script>

<template>
  <Head title="Kelola Artikel — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Artikel</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          {{ isMember ? 'Kelola dan tulis artikel Anda untuk dikurasi redaksi Indonesia Strategy Forum.' : 'Kelola berita, opini kebijakan, artikel hero, serta blok fakta kunci untuk halaman publik.' }}
        </p>
      </div>
      <Link href="/cms/artikel/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Artikel</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul atau slug..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 w-full sm:w-auto justify-start py-0.5">
      <!-- Filter Kategori (opsi sama dengan form artikel) -->
      <div class="flex items-center gap-2">
        <label for="filter-kategori" class="text-xs text-muted-foreground whitespace-nowrap">Kategori:</label>
        <FilterSelect
          id="filter-kategori"
          v-model="categoryFilter"
          placeholder="Select Kategori"
          :options="CATEGORIES"
          @change="onCategoryChange"
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
          <button
            v-if="!isMember"
            type="button"
            @click="onByMemberToggle"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors flex items-center gap-1.5',
              byMemberFilter
                ? 'bg-red-600 text-white shadow-xs'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            <span v-if="byMemberFilter" class="w-1.5 h-1.5 rounded-full bg-white"></span>
            By Member
          </button>
        </div>
      </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat daftar artikel..." />
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
            <TableHead class="w-20 text-center text-xs">Gambar</TableHead>
            <TableHead class="min-w-[280px]">Judul & Slug</TableHead>
            <TableHead class="w-28">Kategori</TableHead>
            <TableHead class="w-24">Status</TableHead>
            <TableHead v-if="!isMember" class="w-32">Penempatan</TableHead>
            <TableHead class="w-28 whitespace-nowrap">Tanggal</TableHead>
            <!-- Kolom Terpisah: Tayang Hero dengan Tooltip Visual -->
            <TableHead v-if="!isMember" class="text-center w-36 whitespace-nowrap">
              <div class="inline-flex items-center justify-center gap-1.5 relative group cursor-pointer select-none">
                <span>Tayang Hero</span>
                <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-muted text-muted-foreground hover:text-foreground text-[10px] font-bold border border-border">?</span>
                
                <!-- Tooltip Popup with Beranda Layout Illustration -->
                <div class="absolute right-0 top-full mt-2 w-72 p-3.5 bg-neutral-900 text-neutral-100 rounded-xl shadow-2xl border border-neutral-700/80 z-50 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-200 text-left whitespace-normal break-words normal-case">
                  <div class="text-xs font-bold font-space-grotesk text-white flex items-center gap-1.5 pb-2 border-b border-neutral-800 whitespace-normal">
                    <span class="w-2 h-2 rounded-xs bg-red-600"></span>
                    <span>Posisi Tayang: Hero Utama Beranda</span>
                  </div>
                  <!-- Visual Layout Diagram -->
                  <div class="my-2.5 p-2 rounded-lg bg-neutral-950 border border-neutral-800 space-y-1.5 font-mono text-[9px] whitespace-normal">
                    <div class="h-4 bg-neutral-800 rounded flex items-center justify-center text-neutral-400">
                      [Navbar / Header ISF]
                    </div>
                    <!-- Highlighted Hero Box -->
                    <div class="p-2 rounded bg-red-950/80 border-2 border-dashed border-red-500 text-center text-red-200 font-bold whitespace-normal">
                      ★ HERO UTAMA BERANDA ★
                      <div class="text-[8px] font-sans font-normal text-red-300 whitespace-normal break-words">Headline paling atas & terbesar di Beranda</div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 pt-0.5">
                      <div class="h-5 bg-neutral-800/80 rounded flex items-center justify-center text-neutral-400 text-[8px] whitespace-normal">
                        Isu Terpopuler
                      </div>
                      <div class="h-5 bg-neutral-800/80 rounded flex items-center justify-center text-neutral-400 text-[8px] whitespace-normal">
                        Berita Terkini
                      </div>
                    </div>
                  </div>
                  <p class="text-[11px] text-neutral-300 leading-relaxed font-sans whitespace-normal break-words">
                    Artikel dengan status <strong class="text-emerald-400 font-semibold">Tayang</strong> otomatis ditampilkan sebagai sorotan utama di layar pertama pengunjung Beranda.
                  </p>
                </div>
              </div>
            </TableHead>
            <!-- Kolom Running Text (Maksimal 5) -->
            <TableHead v-if="!isMember" class="text-center w-40 whitespace-nowrap">
              Running Text ({{ runningTextCount }}/5)
            </TableHead>
            <!-- Kolom Top Artikel (Maksimal 3) -->
            <TableHead v-if="!isMember" class="text-center w-40 whitespace-nowrap">
              Top Artikel ({{ topRankCount }}/3)
            </TableHead>
            <!-- Kolom Aksi (Detail, Edit, Hapus) -->
            <TableHead class="text-right min-w-[200px]">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell :colspan="isMember ? 7 : 11" class="text-center py-10 text-muted-foreground text-sm">
              {{ isMember ? 'Belum ada artikel yang Anda buat. Klik Tambah Artikel untuk mulai menulis naskah.' : 'Tidak ada artikel ditemukan.' }}
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
            <!-- Kolom Gambar yang diupload -->
            <TableCell class="py-2.5 px-2 text-center">
              <div class="w-14 h-10 rounded-md overflow-hidden border border-border bg-muted/60 mx-auto flex items-center justify-center shrink-0">
                <img
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/40 bg-muted/30">
                  <IconComponent name="Image" :size="16" />
                </div>
              </div>
            </TableCell>
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-2 flex flex-wrap items-center gap-1.5">
                <span>{{ item.title }}</span>
                <!-- Badge kecil "by member" di samping value judul/slug jika artikel dibuat oleh member (hanya tampil untuk staf redaksi/admin) -->
                <span
                  v-if="item.is_by_member && !isMember"
                  class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 whitespace-nowrap"
                >
                  by member
                </span>
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5 truncate">
                /{{ item.slug }}
              </div>
            </TableCell>
            <TableCell class="whitespace-nowrap">
              <div class="flex items-center gap-1.5 whitespace-nowrap">
                <Badge
                  variant="outline"
                  class="pointer-events-none select-none bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[9.5px] px-1.5 py-0.5 capitalize font-medium whitespace-nowrap"
                >
                  {{ item.category }}
                </Badge>
                <span
                  v-if="item.subcategory"
                  class="inline-block text-[9.5px] text-muted-foreground font-mono bg-muted/60 px-1 py-0.2 rounded whitespace-nowrap"
                >
                  /{{ item.subcategory }}
                </span>
              </div>
            </TableCell>
            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell v-if="!isMember" class="whitespace-nowrap">
              <div class="flex items-center gap-1 whitespace-nowrap">
                <LiveBadge v-if="item.id === liveId" label="Hero Tayang" />
                <span
                  v-else-if="item.is_hero"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium whitespace-nowrap"
                >
                  Flag Hero
                </span>
                <span
                  v-if="item.show_in_terkini"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium whitespace-nowrap"
                >
                  Terkini
                </span>
                <span
                  v-if="item.show_in_running_text"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-semibold whitespace-nowrap"
                >
                  Running {{ item.running_text_order || '' }}
                </span>
                <span
                  v-if="item.show_in_top_rank"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold whitespace-nowrap"
                >
                  Top {{ item.top_rank_order || '' }}
                </span>
                <span
                  v-if="item.is_popular"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium whitespace-nowrap"
                >
                  Populer
                </span>
                <span
                  v-if="item.id !== liveId && !item.is_hero && !item.show_in_terkini && !item.show_in_running_text && !item.show_in_top_rank && !item.is_popular"
                  class="text-[10px] text-muted-foreground"
                >
                  —
                </span>
              </div>
            </TableCell>

            <!-- Tanggal: 1 line (whitespace-nowrap) -->
            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ formatDate(item.published_at) }}
            </TableCell>

            <!-- Dedicated Column: Tayang Hero -->
            <TableCell v-if="!isMember" class="text-center whitespace-nowrap">
              <span
                v-if="item.id === liveId"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Tayang
              </span>
              <Button
                v-else
                type="button"
                variant="outline"
                size="sm"
                :disabled="settingHeroId === item.id"
                class="h-8 px-2.5 text-xs font-medium text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-700 hover:border-emerald-500 transition-colors inline-flex items-center cursor-pointer"
                @click="handleSetHero(item)"
                title="Tayangkan sebagai Hero Beranda"
              >
                <IconComponent v-if="settingHeroId === item.id" name="Refresh" :size="13" class="mr-1 animate-spin" />
                <IconComponent v-else name="Check" :size="13" class="mr-1 text-emerald-600" />
                Tayangkan
              </Button>
            </TableCell>

            <!-- Dedicated Column: Running Text (X/5) -->
            <TableCell v-if="!isMember" class="text-center whitespace-nowrap py-2.5">
              <div class="inline-flex flex-col items-center gap-1">
                <Button
                  v-if="item.show_in_running_text"
                  type="button"
                  size="sm"
                  variant="outline"
                  :disabled="togglingRunningTextId === item.id"
                  class="h-7 px-2.5 text-[11px] bg-red-600 text-white font-bold hover:bg-red-700 border-red-600 shadow-xs inline-flex items-center cursor-pointer"
                  @click="handleToggleRunningText(item)"
                  title="Klik untuk ubah urutan (1-5) atau nonaktifkan running text"
                >
                  <IconComponent v-if="togglingRunningTextId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>✓ Running Text {{ item.running_text_order || 1 }}</span>
                </Button>
                <Button
                  v-else
                  type="button"
                  size="sm"
                  variant="outline"
                  :disabled="togglingRunningTextId === item.id"
                  class="h-7 px-2.5 text-[11px] border-dashed border-red-600/60 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 inline-flex items-center font-medium cursor-pointer"
                  @click="handleToggleRunningText(item)"
                  title="Pilih untuk pita teks berjalan (maksimal 5)"
                >
                  <IconComponent v-if="togglingRunningTextId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>Pilih Running Text</span>
                </Button>
              </div>
            </TableCell>

            <!-- Dedicated Column: Top Artikel (X/3) -->
            <TableCell v-if="!isMember" class="text-center whitespace-nowrap py-2.5">
              <div class="inline-flex flex-col items-center gap-1">
                <Button
                  v-if="item.show_in_top_rank"
                  type="button"
                  size="sm"
                  variant="outline"
                  :disabled="togglingTopRankId === item.id"
                  class="h-7 px-2.5 text-[11px] bg-amber-600 text-white font-bold hover:bg-amber-700 border-amber-600 shadow-xs inline-flex items-center cursor-pointer"
                  @click="handleToggleTopRank(item)"
                  title="Klik untuk ubah peringkat (1-3) atau nonaktifkan top artikel"
                >
                  <IconComponent v-if="togglingTopRankId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>✓ Top Artikel {{ item.top_rank_order || 1 }}</span>
                </Button>
                <Button
                  v-else
                  type="button"
                  size="sm"
                  variant="outline"
                  :disabled="togglingTopRankId === item.id"
                  class="h-7 px-2.5 text-[11px] border-dashed border-amber-600/60 text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40 inline-flex items-center font-medium cursor-pointer"
                  @click="handleToggleTopRank(item)"
                  title="Pilih untuk widget slider Top Artikel (maksimal 3)"
                >
                  <IconComponent v-if="togglingTopRankId === item.id" name="Refresh" :size="12" class="mr-1 animate-spin" />
                  <span v-else>Pilih Top Artikel</span>
                </Button>
              </div>
            </TableCell>

            <!-- Dedicated Column: Aksi (CTA Detail, Edit, Hapus) -->
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <!-- CTA Detail mengarah ke page detail artikel -->
                <Link :href="`/cms/artikel/${item.id}`">
                  <Button
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium text-foreground hover:bg-muted inline-flex items-center"
                    title="Lihat Detail Artikel"
                  >
                    <IconComponent name="Eye" :size="13" class="mr-1" />
                    Detail
                  </Button>
                </Link>

                <!-- Edit (artikel regular dan artikel member bisa diedit oleh admin & super-admin) -->
                <Link v-if="!isMember" :href="`/cms/artikel/${item.id}/edit`">
                  <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center">
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>

                <!-- Hapus (artikel regular dan artikel member bisa dihapus oleh admin & super-admin) -->
                <Button
                  v-if="!isMember"
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
