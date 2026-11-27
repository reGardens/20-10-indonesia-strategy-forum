<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Label,
  Badge,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const pageProps = usePage()
const authRoles = computed(() => {
  const raw = pageProps.props?.auth?.user?.roles || []
  return raw.map((r) => (typeof r === 'string' ? r : r.name))
})
const isOnlyMember = computed(() => {
  return (
    authRoles.value.includes('member') &&
    !authRoles.value.includes('super-admin') &&
    !authRoles.value.includes('admin')
  )
})

// State Form Report (Sisi Member)
const categoryOptions = [
  { value: 'kendala_teknis', label: 'Kendala Akun & Akses Teknis' },
  { value: 'survei', label: 'Masalah Pengisian / Kuisioner Survei' },
  { value: 'tokoh_rating', label: 'Masalah Rating & Ulasan Tokoh' },
  { value: 'konten_naskah', label: 'Laporan Konten & Naskah Member' },
  { value: 'lainnya', label: 'Saran, Masukan & Pengaduan Lainnya' },
]
const reportForm = ref({
  category: 'kendala_teknis',
  subject: '',
  message: '',
})
const attachmentFile = ref(null)
const attachmentPreview = ref('')
const submittingReport = ref(false)

function handleFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) {
    attachmentFile.value = null
    attachmentPreview.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    alertError('Ukuran file maksimal adalah 5MB.')
    e.target.value = ''
    return
  }
  attachmentFile.value = file
  attachmentPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : ''
}

async function handleSubmitReport() {
  if (!reportForm.value.subject.trim() || !reportForm.value.message.trim()) {
    alertError('Harap lengkapi subjek dan detail pesan laporan.')
    return
  }
  submittingReport.value = true
  try {
    const formData = new FormData()
    formData.append('category', reportForm.value.category)
    formData.append('subject', reportForm.value.subject)
    formData.append('message', reportForm.value.message)
    if (attachmentFile.value) {
      formData.append('attachment', attachmentFile.value)
    }
    const res = await http.post('/cms/laporan-member', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    alertSuccess(res.data?.message || 'Report Anda berhasil dikirim ke Admin & Super Admin.')
    reportForm.value = { category: 'kendala_teknis', subject: '', message: '' }
    attachmentFile.value = null
    attachmentPreview.value = ''
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengirim report. Silakan coba kembali.')
  } finally {
    submittingReport.value = false
  }
}

const reports = ref([])
const loading = ref(true)
const errorMsg = ref('')

const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 })

// Modal review / update state
const selectedReport = ref(null)
const updateStatus = ref('in_progress')
const adminNotes = ref('')
const updating = ref(false)

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await http.get('/cms/laporan-member', {
      params: {
        page: page.value,
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
        search: search.value || undefined,
      },
    })
    reports.value = res.data?.data || []
    meta.value = {
      current_page: res.data?.meta?.current_page || res.data?.current_page || 1,
      last_page: res.data?.meta?.last_page || res.data?.last_page || 1,
      total: res.data?.meta?.total || res.data?.total || 0,
      per_page: res.data?.meta?.per_page || res.data?.per_page || 10,
    }
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar laporan member.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

let debounceTimer = null
watch([search, statusFilter, categoryFilter], () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    page.value = 1
    loadData()
  }, 300)
})

function openDetailModal(report) {
  selectedReport.value = report
  updateStatus.value = report.status || 'in_progress'
  adminNotes.value = report.admin_notes || ''
}

function closeDetailModal() {
  selectedReport.value = null
}

async function handleUpdateStatus() {
  if (!selectedReport.value) return
  updating.value = true
  try {
    const res = await http.put(`/cms/laporan-member/${selectedReport.value.id}`, {
      status: updateStatus.value,
      admin_notes: adminNotes.value,
    })
    alertSuccess('Status laporan berhasil diperbarui.')
    closeDetailModal()
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal memperbarui status laporan.')
  } finally {
    updating.value = false
  }
}

function getStatusBadge(status) {
  switch (status) {
    case 'resolved':
      return { label: 'Selesai', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-300' }
    case 'in_progress':
      return { label: 'Diproses', class: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border-blue-300' }
    case 'closed':
      return { label: 'Ditutup', class: 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300 border-neutral-300' }
    case 'pending':
    default:
      return { label: 'Menunggu', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-300' }
  }
}
</script>

<template>
  <Head title="Report & Pengaduan — CMS ISF" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>{{ isOnlyMember ? 'Report' : 'Daftar Report Member' }}</span>
          <Badge v-if="!isOnlyMember" class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          {{ isOnlyMember
            ? 'Laporkan kendala teknis, konten, survei, atau masukan lainnya langsung ke Super Admin dan Admin ISF.'
            : 'Daftar pengaduan dan masukan yang dikirimkan oleh member ISF. Klik Detail untuk meninjau.'
          }}
        </p>
      </div>

      <div v-if="isOnlyMember" class="flex items-center gap-2">
        <a href="/anggota">
          <Button variant="outline" size="sm" class="h-9 text-xs inline-flex items-center gap-1.5">
            <IconComponent name="ChevronLeft" :size="14" />
            Kembali ke Portal Anggota
          </Button>
        </a>
      </div>
    </div>

    <!-- SISI MEMBER: FORM REPORT -->
    <Card v-if="isOnlyMember" class="border-border shadow-sm max-w-4xl">
      <CardHeader class="border-b border-border/50 pb-4">
        <CardTitle class="text-base font-bold">Formulir Report</CardTitle>
        <CardDescription class="text-xs">
          Laporan Anda akan langsung diterima oleh Super Admin &amp; Admin serta dikirimkan ke email resmi ISF.
        </CardDescription>
      </CardHeader>
      <CardContent class="pt-5 space-y-4">
        <div class="space-y-1.5">
          <Label for="member-report-cat" class="text-xs font-bold">Kategori Report *</Label>
          <select
            id="member-report-cat"
            v-model="reportForm.category"
            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
          >
            <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <div class="space-y-1.5">
          <Label for="member-report-subject" class="text-xs font-bold">Subjek / Judul Report *</Label>
          <Input
            id="member-report-subject"
            v-model="reportForm.subject"
            placeholder="Contoh: Kendala pengisian survei atau masukan terkait artikel..."
            class="h-10 text-sm"
          />
        </div>

        <div class="space-y-1.5">
          <Label for="member-report-message" class="text-xs font-bold">Detail Laporan *</Label>
          <textarea
            id="member-report-message"
            v-model="reportForm.message"
            rows="4"
            placeholder="Jelaskan secara detail laporan atau kendala yang Anda alami..."
            class="w-full rounded-md border border-input bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
          ></textarea>
        </div>

        <div class="space-y-1.5">
          <Label for="member-report-file" class="text-xs font-bold">Lampiran Bukti (Opsional, Maks 5MB)</Label>
          <input
            id="member-report-file"
            type="file"
            accept="image/*,.pdf,.doc,.docx"
            @change="handleFileChange"
            class="block w-full text-xs text-muted-foreground file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100"
          />
          <img v-if="attachmentPreview" :src="attachmentPreview" alt="Preview lampiran" class="mt-2 max-h-36 rounded-md border border-border" />
        </div>

        <div class="pt-2 flex justify-end">
          <Button
            type="button"
            @click="handleSubmitReport"
            :disabled="submittingReport"
            class="bg-red-700 hover:bg-red-800 text-white font-bold text-xs px-6 h-10"
          >
            {{ submittingReport ? 'Mengirim Report...' : 'Kirim Report' }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Toolbar Filters (Untuk Admin / Super Admin) -->
    <div v-if="!isOnlyMember" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          placeholder="Cari subjek, pesan, atau nama member..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto justify-start py-0.5">
        <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
        <div class="flex gap-1">
          <button
            v-for="opt in [
              { value: '', label: 'Semua' },
              { value: 'pending', label: 'Menunggu' },
              { value: 'in_progress', label: 'Diproses' },
              { value: 'resolved', label: 'Selesai' },
            ]"
            :key="opt.value"
            type="button"
            @click="statusFilter = opt.value"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors whitespace-nowrap',
              statusFilter === opt.value
                ? 'bg-red-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>
    </div>

    <LoadingIndicator v-if="loading && reports.length === 0" text="Memuat laporan member..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table of Reports -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table
        :current-page="meta.current_page"
        :per-page="meta.per_page"
        :total="meta.total"
        :total-pages="meta.last_page"
        @update:current-page="(p) => { page = p; loadData(); }"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead>Pelapor</TableHead>
            <TableHead>Kategori</TableHead>
            <TableHead class="w-[35%]">Subjek &amp; Pesan</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Waktu</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="reports.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data laporan ditemukan.
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(rep, idx) in reports"
            :key="rep.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 }}
            </TableCell>

            <TableCell class="text-xs">
              <div class="font-semibold text-foreground">{{ rep.user?.name || 'Member' }}</div>
              <div class="text-[11px] text-muted-foreground">{{ rep.user?.email || '-' }}</div>
              <div v-if="rep.user?.whatsapp" class="text-[11px] text-green-700 font-mono">
                WA: {{ rep.user.whatsapp }}
              </div>
            </TableCell>

            <TableCell class="text-xs">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-muted border border-border">
                {{ rep.category_label || rep.category }}
              </span>
            </TableCell>

            <TableCell class="text-xs">
              <p class="font-bold text-foreground leading-snug">{{ rep.subject }}</p>
              <p class="text-muted-foreground text-[11px] line-clamp-2 mt-0.5">{{ rep.message }}</p>
              <div v-if="rep.attachment_path" class="mt-1">
                <a
                  :href="rep.attachment_path"
                  target="_blank"
                  class="inline-flex items-center gap-1 text-[11px] font-semibold text-red-700 hover:underline"
                >
                  <IconComponent name="Download" :size="12" />
                  Lihat Berkas Bukti
                </a>
              </div>
            </TableCell>

            <TableCell class="text-center">
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border',
                  getStatusBadge(rep.status).class
                ]"
              >
                {{ getStatusBadge(rep.status).label }}
              </span>
            </TableCell>

            <TableCell class="text-center text-[11px] text-muted-foreground">
              {{ rep.created_at ? new Date(rep.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
            </TableCell>

            <TableCell class="text-right">
              <Button
                variant="outline"
                size="sm"
                class="h-7 text-xs px-2.5"
                @click="openDetailModal(rep)"
              >
                Detail
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Review / Update Modal Dialog -->
    <div
      v-if="selectedReport"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="closeDetailModal"
    >
      <div class="bg-card border border-border rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-3 border-b border-border pb-3">
          <div>
            <h3 class="text-base font-bold text-foreground">
              Tindak Lanjut Laporan #{{ selectedReport.id }}
            </h3>
            <p class="text-xs text-muted-foreground mt-0.5">
              Dari: {{ selectedReport.user?.name }} ({{ selectedReport.user?.email }})
            </p>
          </div>
          <button
            type="button"
            class="text-muted-foreground hover:text-foreground p-1 rounded-md"
            @click="closeDetailModal"
          >
            <IconComponent name="X" :size="18" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <span class="font-bold text-muted-foreground uppercase text-[10px] tracking-wider">Subjek</span>
            <p class="text-sm font-semibold text-foreground mt-0.5">{{ selectedReport.subject }}</p>
          </div>

          <div>
            <span class="font-bold text-muted-foreground uppercase text-[10px] tracking-wider">Pesan Pelapor</span>
            <p class="text-xs text-foreground bg-muted/40 p-3 rounded-md mt-0.5 whitespace-pre-wrap leading-relaxed">
              {{ selectedReport.message }}
            </p>
          </div>

          <div v-if="selectedReport.attachment_path">
            <span class="font-bold text-muted-foreground uppercase text-[10px] tracking-wider">Berkas Bukti</span>
            <div class="mt-1">
              <a
                :href="selectedReport.attachment_path"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-muted hover:bg-muted/80 text-xs font-semibold text-red-700"
              >
                <IconComponent name="Download" :size="14" />
                Buka / Unduh Lampiran
              </a>
            </div>
          </div>

          <hr class="border-border my-2" />

          <!-- Form Tindak Lanjut -->
          <div class="space-y-3">
            <div>
              <Label class="text-xs font-bold">Pembaruan Status</Label>
              <select
                v-model="updateStatus"
                class="mt-1 w-full h-9 rounded-md border border-input bg-background px-3 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option value="pending">Menunggu Tindak Lanjut (Pending)</option>
                <option value="in_progress">Sedang Ditangani (In Progress)</option>
                <option value="resolved">Selesai Ditangani (Resolved)</option>
                <option value="closed">Ditutup (Closed)</option>
              </select>
            </div>

            <div>
              <Label class="text-xs font-bold">Catatan Tindak Lanjut Tim ISF</Label>
              <textarea
                v-model="adminNotes"
                rows="3"
                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                placeholder="Tuliskan tindakan yang telah diambil atau penjelasan untuk pelapor..."
              ></textarea>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
          <Button variant="outline" size="sm" @click="closeDetailModal">
            Tutup
          </Button>
          <Button
            size="sm"
            class="bg-red-700 hover:bg-red-800 text-white text-xs"
            :disabled="updating"
            @click="handleUpdateStatus"
          >
            {{ updating ? 'Menyimpan...' : 'Simpan Tindak Lanjut' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
