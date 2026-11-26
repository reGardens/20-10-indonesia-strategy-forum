<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { activeCategories as CATEGORIES, loadCategories } from '@/Pages/Cms/Isf/options'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import Swal from 'sweetalert2'
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

const page = usePage()
const userRoles = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  if (Array.isArray(roles)) {
    return roles.map((r) => (typeof r === 'string' ? r : r.name))
  }
  return []
})
const canApprove = computed(() => {
  return userRoles.value.includes('admin') || userRoles.value.includes('super-admin')
})

function isPending(item) {
  if (!item) return false
  const modStatus = item.moderation_status
  const status = item.status
  if (modStatus === 'disetujui' || status === 'disetujui' || status === 'terbit') return false
  if (modStatus === 'ditolak' || status === 'ditolak') return false
  return true
}

const items = ref([])
const loading = ref(true)
const loadError = ref('')
const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
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
    const res = await cmsResources.suratPembaca.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
      category: categoryFilter.value || undefined,
    })
    items.value = res.data?.data || res.data || []
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat antrean artikel by member.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadData()
  }, 350)
}

function onStatusChange(status) {
  statusFilter.value = status
  currentPage.value = 1
  loadData()
}

function onCategoryChange() {
  currentPage.value = 1
  loadData()
}

async function handleApprove(item) {
  if (!canApprove.value) {
    alertError('Hanya role Admin dan Super Admin yang memiliki wewenang untuk menyetujui artikel.')
    return
  }

  try {
    await cmsResources.suratPembaca.approve(item.id)
    alertSuccess(`Artikel "${item.title || item.judul_surat}" berhasil disetujui untuk tayang.`)
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyetujui artikel.'))
  }
}

async function handleReject(item) {
  const result = await Swal.fire({
    title: 'Tolak Naskah Artikel',
    html: `
      <div class="text-left text-xs text-muted-foreground space-y-1 mb-3">
        <p>Tuliskan alasan penolakan atau catatan evaluasi redaksi untuk artikel <strong>"${item.title || item.judul_surat}"</strong>.</p>
        <p class="text-red-500 font-medium">Catatan ini akan dikirimkan otomatis ke email penulis (${item.sender_email || item.email_pengirim || 'penulis'}). Artikel yang ditolak tidak dapat disetujui lagi.</p>
      </div>
    `,
    input: 'textarea',
    inputPlaceholder: 'Tuliskan alasan penolakan naskah (mis. topik belum sesuai fokus editorial, data rujukan kurang valid, dll)...',
    inputAttributes: {
      'aria-label': 'Alasan penolakan',
      rows: '4',
      class: 'swal2-textarea text-xs'
    },
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return 'Alasan penolakan wajib diisi sebagai catatan evaluasi bagi penulis!'
      }
    },
    showCancelButton: true,
    confirmButtonText: 'Tolak & Kirim Catatan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
  })

  if (!result.isConfirmed) return
  const reason = result.value

  // Tampilkan loading modal selama backend memproses penolakan dan mengirim email SMTP
  Swal.fire({
    title: 'Memproses Penolakan...',
    html: '<p class="text-xs text-muted-foreground mt-1">Menyimpan status dan mengirim catatan evaluasi ke email penulis...</p>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading()
    }
  })

  try {
    await cmsResources.suratPembaca.reject(item.id, reason)
    Swal.close()
    alertSuccess(`Artikel "${item.title || item.judul_surat}" telah ditolak dan catatan evaluasi telah dikirimkan ke email penulis.`)
    loadData()
  } catch (err) {
    Swal.close()
    alertError(errorMessage(err, 'Gagal menolak artikel.'))
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

function categoryBadgeClass(cat) {
  const c = String(cat || '').toLowerCase()
  if (c === 'politik') return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
  if (c === 'hukum') return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
  return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
}

function getInitials(name) {
  if (!name) return 'M'
  return name
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase()
}

onMounted(async () => {
  await loadCategories()
  loadData()
})
</script>

<template>
  <Head title="Moderasi Artikel by Member — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header (No Manual Add Button per requirement) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Moderasi Artikel by Member</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Antrean artikel opini dari kontribusi member terverifikasi. Hanya artikel berstatus disetujui yang tampil di portal publik ISF.
        </p>
      </div>
    </div>

    <!-- Filters: Search + Kategori + Status Pill Buttons (Consistent with other CMS pages) -->
    <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
      <div class="relative w-full md:w-72">
        <Input
          v-model="search"
          placeholder="Cari nama penulis, kota, atau judul artikel..."
          @input="onSearchInput"
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 w-full md:w-auto justify-start md:justify-end py-0.5">
        <!-- Filter Kategori (opsi sama dengan menu artikel) -->
        <div class="flex items-center gap-2">
          <label for="filter-kategori-letter" class="text-xs text-muted-foreground whitespace-nowrap">Kategori:</label>
          <FilterSelect
            id="filter-kategori-letter"
            v-model="categoryFilter"
            placeholder="Select Kategori"
            :options="CATEGORIES"
            @change="onCategoryChange"
          />
        </div>

        <!-- Pill Buttons for Status (Matches other CMS Index pages) -->
        <div class="flex items-center gap-2 overflow-x-auto justify-start py-0.5">
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
              Semua Status
            </button>
            <button
              type="button"
              @click="onStatusChange('pending')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'pending'
                  ? 'bg-amber-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Pending
            </button>
            <button
              type="button"
              @click="onStatusChange('disetujui')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'disetujui'
                  ? 'bg-emerald-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Disetujui
            </button>
            <button
              type="button"
              @click="onStatusChange('ditolak')"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
                statusFilter === 'ditolak'
                  ? 'bg-rose-600 text-white'
                  : 'bg-card text-muted-foreground hover:bg-accent border border-border'
              ]"
            >
              Ditolak
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat antrean artikel by member..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

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
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="w-60 max-w-[240px]">Penulis</TableHead>
            <TableHead class="min-w-[340px]">Artikel & Kategori</TableHead>
            <TableHead class="w-36">Waktu Masuk</TableHead>
            <TableHead class="w-28 text-center">Status</TableHead>
            <TableHead class="text-right min-w-[220px]">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada antrean artikel by member ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in paginatedItems"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 + (currentPage - 1) * perPage }}
            </TableCell>

            <!-- Penulis + Foto Penulis -->
            <TableCell class="py-3 max-w-[240px]">
              <div class="flex items-center gap-3">
                <!-- Square Avatar with subtle rounded-lg -->
                <div class="w-11 h-11 rounded-lg overflow-hidden border border-border bg-muted shrink-0 flex items-center justify-center">
                  <img
                    v-if="item.author_avatar"
                    :src="item.author_avatar"
                    :alt="item.sender_name || 'Penulis'"
                    class="w-full h-full object-cover"
                  />
                  <div
                    v-else
                    class="w-full h-full flex items-center justify-center font-bold text-xs bg-red-600/10 text-red-600 font-space-grotesk"
                  >
                    {{ getInitials(item.sender_name || item.nama_pengirim) }}
                  </div>
                </div>

                <div class="min-w-0 max-w-[170px]">
                  <div class="font-semibold text-foreground text-sm leading-snug truncate max-w-[170px]" :title="item.sender_name || item.nama_pengirim">
                    {{ item.sender_name || item.nama_pengirim }}
                  </div>
                  <div v-if="item.author_role" class="text-[11px] text-muted-foreground truncate max-w-[170px] font-medium" :title="item.author_role">
                    {{ item.author_role }}
                  </div>
                  <div class="text-xs text-muted-foreground mt-0.5 truncate max-w-[170px]">
                    {{ item.sender_city || item.kota_domisili || 'Indonesia' }}
                  </div>
                  <div class="text-[11px] text-muted-foreground font-mono truncate max-w-[170px]" :title="item.sender_email || item.email_pengirim">
                    {{ item.sender_email || item.email_pengirim }}
                  </div>
                </div>
              </div>
            </TableCell>

            <!-- Judul, Kategori & Cuplikan Konten -->
            <TableCell>
              <div class="flex items-start gap-3">
                <div
                  v-if="item.image_url"
                  class="w-16 h-11 rounded-md overflow-hidden border border-border bg-neutral-900 shrink-0 mt-0.5 hidden sm:block"
                >
                  <img
                    :src="item.image_url"
                    :alt="item.image_alt || item.title || 'Sampul'"
                    class="w-full h-full object-cover"
                  />
                </div>

                <div class="space-y-1 min-w-0 flex-1">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                      :class="categoryBadgeClass(item.category)"
                    >
                      {{ item.category || 'Kebijakan' }}
                    </span>
                    <span v-if="item.subcategory" class="text-[10px] text-muted-foreground font-mono">
                      · {{ item.subcategory }}
                    </span>
                    <span v-if="item.reading_minutes" class="text-[10px] text-muted-foreground">
                      · {{ item.reading_minutes }} mnt baca
                    </span>
                  </div>

                  <div>
                    <Link
                      :href="`/cms/surat-pembaca/${item.id}`"
                      class="font-semibold text-foreground text-xs sm:text-sm leading-snug hover:underline hover:text-red-600 transition inline-block"
                      title="Buka Detail Artikel"
                    >
                      {{ item.title || item.judul_surat }}
                    </Link>
                  </div>

                  <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                    {{ item.summary || item.body || item.isi_surat }}
                  </p>

                  <div
                    v-if="(item.moderation_status === 'ditolak' || item.status === 'ditolak') && (item.moderation_notes || item.catatan_redaksi)"
                    class="mt-1 text-[11px] text-rose-600 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20 inline-block font-medium"
                  >
                    Alasan Ditolak: {{ item.moderation_notes || item.catatan_redaksi }}
                  </div>
                  <div
                    v-else-if="item.moderation_notes || item.catatan_redaksi"
                    class="mt-1 text-[11px] text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 inline-block"
                  >
                    Catatan: {{ item.moderation_notes || item.catatan_redaksi }}
                  </div>
                </div>
              </div>
            </TableCell>

            <!-- Waktu Masuk -->
            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ formatDate(item.created_at) }}
            </TableCell>

            <!-- Status Badge (No Animation per requirement) -->
            <TableCell class="text-center">
              <span
                v-if="item.moderation_status === 'disetujui' || item.status === 'disetujui'"
                class="inline-block px-2.5 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
              >
                Disetujui
              </span>
              <span
                v-else-if="item.moderation_status === 'ditolak' || item.status === 'ditolak'"
                class="inline-block px-2.5 py-0.5 text-xs font-semibold rounded bg-rose-500/10 text-rose-600 border border-rose-500/20"
              >
                Ditolak
              </span>
              <span
                v-else
                class="inline-block px-2.5 py-0.5 text-xs font-semibold rounded bg-amber-500/10 text-amber-600 border border-amber-500/20"
              >
                Pending
              </span>
            </TableCell>

            <!-- Aksi: CTA Detail, Setujui (Hanya jika pending), Tolak (Hanya jika pending) -->
            <TableCell class="text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                <!-- CTA Detail mengarah ke page detail artikel by member -->
                <Link :href="`/cms/surat-pembaca/${item.id}`" class="inline-flex">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-semibold text-foreground hover:bg-muted inline-flex items-center cursor-pointer"
                    title="Lihat Detail Artikel"
                    @click="router.visit(`/cms/surat-pembaca/${item.id}`)"
                  >
                    <IconComponent name="Eye" :size="13" class="mr-1" />
                    Detail
                  </Button>
                </Link>

                <!-- Quick Approve Button (Hanya jika pending & belum pernah ditolak/disetujui) -->
                <Button
                  v-if="canApprove && isPending(item)"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-700 hover:border-emerald-500 inline-flex items-center"
                  title="Setujui Artikel"
                  @click="handleApprove(item)"
                >
                  <IconComponent name="Check" :size="13" class="mr-1" />
                  Setujui
                </Button>

                <!-- Status note if not admin/super-admin -->
                <span
                  v-else-if="!canApprove && isPending(item)"
                  class="text-[10px] text-muted-foreground italic px-1"
                  title="Persetujuan hanya dapat dilakukan oleh Admin dan Super Admin"
                >
                  Butuh Admin
                </span>

                <!-- Quick Reject Button (Hanya jika pending & belum pernah disetujui/ditolak) -->
                <Button
                  v-if="isPending(item)"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-amber-600 border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-700 hover:border-amber-500 inline-flex items-center"
                  title="Tolak Artikel"
                  @click="handleReject(item)"
                >
                  <IconComponent name="X" :size="13" class="mr-1" />
                  Tolak
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
