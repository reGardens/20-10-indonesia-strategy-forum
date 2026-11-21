<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { useCmsForm } from '@/Composables/useCmsForm'
import { cmsResources } from '@/api/services/isfCmsService'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
import StringListEditor from '@/Pages/Cms/components/shared/StringListEditor.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: {
    type: [Number, String],
    default: null
  }
})

const isEdit = computed(() => Boolean(props.id))

const DEFAULT_LEMBAGA_OPTIONS = [
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
      lembagaOptions.value = data.map((item) => ({ value: item.slug, label: item.nama }))
    }
  } catch (e) {}
}

const AVAILABLE_ISSUES = [
  'politik', 'hukum', 'ekonomi', 'kebijakan', 'pertahanan', 'sosial', 'energi', 'pendidikan', 'keamanan', 'digital'
]

const careerFields = [
  { key: 'year', label: 'Tahun / Periode', type: 'text', required: true, placeholder: 'e.g. 2024 - Sekarang' },
  { key: 'title', label: 'Posisi / Peran Publik', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Ketua Komisi Hukum DPR RI' },
  { key: 'description', label: 'Keterangan / Dampak Kebijakan', type: 'text', span: 'sm:col-span-3', placeholder: 'Keterangan peran...' }
]

const positionsFields = [
  { key: 'period', label: 'Periode Menjabat', type: 'text', required: true, placeholder: 'e.g. 2019 - 2024' },
  { key: 'position', label: 'Nama Jabatan', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Anggota Komisi III DPR RI' },
  { key: 'institution', label: 'Lembaga / Organisasi', type: 'text', span: 'sm:col-span-3', placeholder: 'e.g. Dewan Perwakilan Rakyat' }
]

const educationFields = [
  { key: 'degree', label: 'Gelar / Jenjang', type: 'text', required: true, placeholder: 'e.g. Sarjana Hukum (S.H.)' },
  { key: 'institution', label: 'Institusi / Universitas', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Universitas Indonesia' },
  { key: 'year', label: 'Tahun Lulus', type: 'text', placeholder: 'e.g. 1998' }
]

const affiliationFields = [
  { key: 'name', label: 'Nama Lembaga / Partai / Organisasi', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Partai Demokrasi Strategis' },
  { key: 'type', label: 'Jenis Afiliasi', type: 'text', placeholder: 'e.g. Partai Politik / Ormas' },
  { key: 'role', label: 'Peran / Jabatan', type: 'text', span: 'sm:col-span-3', placeholder: 'e.g. Dewan Pembina' }
]

const alliesFields = [
  { key: 'name', label: 'Nama Sekutu / Mitra Strategis', type: 'text', required: true, placeholder: 'e.g. Dr. Sri Hartati' },
  { key: 'role', label: 'Peran / Afiliasi', type: 'text', placeholder: 'e.g. Menko Perekonomian' },
  { key: 'relation', label: 'Konteks Kerjasama / Hubungan', type: 'text', span: 'sm:col-span-2', placeholder: 'e.g. Kolaborasi harmonisasi RUU Fiskal' }
]

const criticsFields = [
  { key: 'name', label: 'Nama Tokoh Oposan / Pengkritik', type: 'text', required: true, placeholder: 'e.g. Koalisi Advokasi Transparansi' },
  { key: 'role', label: 'Peran / Lembaga', type: 'text', placeholder: 'e.g. Lembaga Swadaya Masyarakat' },
  { key: 'note', label: 'Pokok Kritik / Friksi Kebijakan', type: 'text', span: 'sm:col-span-2', placeholder: 'e.g. Mengkritisi minimnya konsultasi publik' }
]

const quotesFields = [
  { key: 'text', label: 'Teks Kutipan Kunci', type: 'text', span: 'sm:col-span-3', required: true, placeholder: 'Pernyataan kunci figur...' },
  { key: 'context', label: 'Konteks Pidato / Rapat', type: 'text', span: 'sm:col-span-2', placeholder: 'e.g. RDP Komisi III DPR RI' },
  { key: 'date', label: 'Tanggal / Periode', type: 'text', placeholder: 'e.g. 2026-10-14' },
  { key: 'source_url', label: 'Tautan Sumber (opsional)', type: 'url', span: 'sm:col-span-3', placeholder: 'https://...' }
]

const controversiesFields = [
  { key: 'title', label: 'Judul Kontroversi / Isu Kebijakan', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Polemik Pasal Imunitas Diskresi' },
  { key: 'year', label: 'Tahun', type: 'text', placeholder: 'e.g. 2025' },
  { key: 'status', label: 'Status Penanganan', type: 'text', placeholder: 'e.g. Selesai Direvisi' },
  { key: 'description', label: 'Ringkasan Dinamika Isu', type: 'text', span: 'sm:col-span-3', placeholder: 'Uraian kasus dan implikasinya...' }
]

const relatedArticleFields = [
  { key: 'title', label: 'Judul Artikel Terkait', type: 'text', span: 'sm:col-span-2', required: true },
  { key: 'to', label: 'Tautan URL (/isu/...)', type: 'url', required: true },
  { key: 'publishedAt', label: 'Tanggal Publikasi', type: 'date', required: true },
  { key: 'relativeLabel', label: 'Label Relatif (opsional, e.g. 2 hari lalu)', type: 'text' }
]

const { form, loading, saving, loadError, err, load, save } = useCmsForm({
  resource: cmsResources.tokoh,
  id: props.id,
  listPath: '/cms/tokoh',
  entityName: 'Sosok',
  defaults: {
    slug: '',
    name: '',
    position: '',
    role_category: 'legislatif',
    issues: ['politik', 'hukum'],
    summary_analysis: '',
    bio: '',
    career_timeline: [],
    education: [],
    positions_history: [],
    influence_score: 80,
    affiliations: [],
    allies: [],
    critics: [],
    quotes: [],
    controversies: [],
    aliases: [],
    is_influential_weekly: false,
    photo_alt: '',
    photo_url: '',
    stats: [],
    skor_value: 82,
    skor_period: 'Pekan 41 / 2026',
    skor_explanation: '',
    matrix_columns: ['Inisiasi', 'Pembahasan', 'Implementasi'],
    matrix_rows: [],
    related_articles: [],
    calendar_year: 2026,
    calendar_month: 11,
    calendar_events: [],
    teaser: '',
    is_featured: false,
    show_in_politik: false,
    status: 'draf'
  }
})

onMounted(() => {
  loadLembagaOptions()
  if (props.id) {
    load()
  }
})

const slugCustomized = ref(false)

function generateSlug(val) {
  if (!val) return ''
  return val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '')
}

function onNameInput(e) {
  if (props.id) return
  const val = e.target.value
  if (!val || !val.trim()) {
    form.slug = ''
    slugCustomized.value = false
    return
  }
  if (!slugCustomized.value) {
    form.slug = generateSlug(val)
  }
}

function onSlugInput(e) {
  if (!e.target.value || !e.target.value.trim()) {
    slugCustomized.value = false
    form.slug = form.name ? generateSlug(form.name) : ''
  } else {
    slugCustomized.value = form.slug !== generateSlug(form.name)
  }
}

watch(
  () => form.name,
  (newVal) => {
    if (props.id) return
    if (!newVal || !newVal.trim()) {
      form.slug = ''
      slugCustomized.value = false
      return
    }
    if (!slugCustomized.value || !form.slug) {
      form.slug = generateSlug(newVal)
    }
  }
)

function toggleIssue(iss) {
  if (!Array.isArray(form.issues)) {
    form.issues = []
  }
  const idx = form.issues.indexOf(iss)
  if (idx > -1) {
    form.issues.splice(idx, 1)
  } else {
    form.issues.push(iss)
  }
}

function addMatrixRow() {
  const involvement = form.matrix_columns.map(() => false)
  form.matrix_rows.push({
    kebijakan: '',
    tahap: 'Dalam Pembahasan',
    involvement
  })
}

function removeMatrixRow(index) {
  form.matrix_rows.splice(index, 1)
}

function onMatrixColumnsChange(newColumns) {
  form.matrix_columns = newColumns
  form.matrix_rows = form.matrix_rows.map(row => {
    const nextInv = newColumns.map((_, i) => Boolean(row.involvement?.[i]))
    return { ...row, involvement: nextInv }
  })
}
</script>

<template>
  <Head :title="(isEdit ? 'Edit Sosok' : 'Tambah Sosok') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/tokoh">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Sosok' : 'Tambah Sosok Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah profil ${form.name || ''}` : 'Isi formulir berikut untuk menerbitkan figur pembuat kebijakan pada Rubrik Sosok.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/tokoh">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" />
          {{ saving ? 'Menyimpan...' : 'Simpan Sosok' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data sosok..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- 1. Identitas & Klasifikasi Sosok -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Identitas & Klasifikasi Sosok</CardTitle>
          <CardDescription class="text-xs">Nama lengkap, kategori lembaga, jabatan formal, foto profil, skor pengaruh, dan status publikasi.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="name" class="text-xs font-medium">Nama Lengkap & Gelar <span class="text-red-500">*</span></Label>
              <Input
                id="name"
                v-model="form.name"
                @input="onNameInput"
                placeholder="e.g. Dr. Budi Santoso, S.H., M.H."
                class="h-9 text-sm"
              />
              <p v-if="err('name')" class="text-xs text-red-500 font-medium">{{ err('name') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium">Slug URL <span class="text-red-500">*</span></Label>
              <Input
                id="slug"
                v-model="form.slug"
                @input="onSlugInput"
                placeholder="budi-santoso"
                class="h-9 font-mono text-xs"
              />
              <p class="text-[11px] text-muted-foreground">URL profil publik: <code>/sosok/{{ form.slug || 'nama-tokoh' }}</code></p>
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="role_category" class="text-xs font-medium">Kategori Lembaga <span class="text-red-500">*</span></Label>
              <Select v-model="form.role_category">
                <SelectTrigger id="role_category" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Kategori Lembaga..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="opt in lembagaOptions"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="err('role_category')" class="text-xs text-red-500 font-medium">{{ err('role_category') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="position" class="text-xs font-medium">Jabatan / Posisi Formal <span class="text-red-500">*</span></Label>
              <Input
                id="position"
                v-model="form.position"
                placeholder="e.g. Ketua Komisi III DPR RI"
                class="h-9 text-xs"
              />
              <p v-if="err('position')" class="text-xs text-red-500 font-medium">{{ err('position') }}</p>
            </div>

            <StatusSelectWithBadge
              id="status-tokoh"
              v-model="form.status"
              label="Status Publikasi"
              :required="true"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label class="text-xs font-medium">URL Foto Profil</Label>
              <ImageUploadTab
                v-model="form.photo_url"
                aspect-class="aspect-square"
                placeholder="https://..."
                :show-hint="false"
              />
              <p v-if="err('photo_url')" class="text-xs text-red-500 font-medium">{{ err('photo_url') }}</p>
            </div>

            <div v-if="form.photo_url" class="space-y-1.5">
              <Label for="photo_alt" class="text-xs font-medium">Alt Text Foto <span class="text-red-500">*</span></Label>
              <Input
                id="photo_alt"
                v-model="form.photo_alt"
                placeholder="Foto profil tokoh saat memimpin rapat kerja"
                class="h-9 text-xs"
              />
              <p v-if="err('photo_alt')" class="text-xs text-red-500 font-medium">{{ err('photo_alt') }}</p>
            </div>
          </div>

          <!-- Highlight Checkboxes -->
          <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_featured"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Sosok Pekan Ini (Beranda)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan sebagai 1 sosok utama di section "Pekan Ini" halaman depan Beranda (otomatis menggantikan sosok pekan sebelumnya).</div>
              </div>
            </label>

            <div class="space-y-1">
              <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
                <input
                  type="checkbox"
                  v-model="form.show_in_politik"
                  class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
                />
                <div>
                  <div class="text-xs font-semibold text-foreground">Tampilkan di Widget Politik (/politik)</div>
                  <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan kartu profil sosok ini pada section "Profil Tokoh Politik" di halaman publik /politik (maksimal 4 sosok aktif).</div>
                </div>
              </label>
              <p v-if="err('show_in_politik')" class="text-xs text-red-500 font-medium px-1">{{ err('show_in_politik') }}</p>
            </div>
          </div>

          <!-- Skor Pengaruh, Skor Sorotan & Rasional Penjelasan (Dipindahkan dari Tab 2) -->
          <div class="pt-3 border-t border-border space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="space-y-1.5">
                <Label for="influence_score" class="text-xs font-medium">Skor Pengaruh ISF (0 - 100) <span class="text-red-500">*</span></Label>
                <Input
                  id="influence_score"
                  type="number"
                  v-model.number="form.influence_score"
                  min="0"
                  max="100"
                  class="h-9 text-xs font-bold text-red-600"
                />
                <p class="text-[11px] text-muted-foreground">Skor agregat bobot pengaruh politik, regulasi, dan jejaring publik.</p>
                <p v-if="err('influence_score')" class="text-xs text-red-500 font-medium">{{ err('influence_score') }}</p>
              </div>

              <div class="space-y-1.5">
                <Label for="skor_value" class="text-xs font-medium">Skor Sorotan Pekan Ini (0 - 100) <span class="text-red-500">*</span></Label>
                <Input
                  id="skor_value"
                  type="number"
                  v-model.number="form.skor_value"
                  min="0"
                  max="100"
                  class="h-9 text-xs"
                />
                <p v-if="err('skor_value')" class="text-xs text-red-500 font-medium">{{ err('skor_value') }}</p>
              </div>

              <div class="space-y-1.5">
                <Label for="skor_period" class="text-xs font-medium">Periode Pengukuran <span class="text-red-500">*</span></Label>
                <Input
                  id="skor_period"
                  v-model="form.skor_period"
                  placeholder="30 hari terakhir"
                  class="h-9 text-xs"
                />
                <p v-if="err('skor_period')" class="text-xs text-red-500 font-medium">{{ err('skor_period') }}</p>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="skor_explanation" class="text-xs font-medium">Rasional / Penjelasan Skor <span class="text-red-500">*</span></Label>
              <textarea
                id="skor_explanation"
                v-model="form.skor_explanation"
                rows="3"
                placeholder="Rasional penilaian skor aktivitas figur pekan ini..."
                class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
              />
              <p v-if="err('skor_explanation')" class="text-xs text-red-500 font-medium">{{ err('skor_explanation') }}</p>
            </div>
          </div>

          <!-- Catatan Analisis ISF (Ringkasan) -->
          <div class="space-y-1.5 pt-1">
            <Label for="summary_analysis" class="text-xs font-medium">Catatan Analisis ISF (Ringkasan 1 Kalimat)</Label>
            <textarea
              id="summary_analysis"
              v-model="form.summary_analysis"
              rows="2"
              placeholder="e.g. Tokoh kunci dalam percepatan pembahasan revisi perundang-undangan strategis dan penegakan hukum nasional."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p class="text-[11px] text-muted-foreground">Ditampilkan sebagai catatan analisis editorial pada card profil direktori dan box analisis header sosok.</p>
            <p v-if="err('summary_analysis')" class="text-xs text-red-500 font-medium">{{ err('summary_analysis') }}</p>
          </div>

          <!-- Isu / Tag Isu Terkait -->
          <div class="space-y-2 pt-1">
            <Label class="text-xs font-medium">Isu & Sektor Terkait</Label>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="iss in AVAILABLE_ISSUES"
                :key="iss"
                type="button"
                @click="toggleIssue(iss)"
                :class="[
                  'px-2.5 py-1 text-xs rounded-full font-medium transition-all capitalize',
                  Array.isArray(form.issues) && form.issues.includes(iss)
                    ? 'bg-red-600 text-white shadow-xs'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                ]"
              >
                #{{ iss }}
              </button>
            </div>
            <p class="text-[11px] text-muted-foreground">Pilih isu kebijakan yang diasosiasikan dengan sosok untuk filter dan rekomendasi.</p>
          </div>
        </CardContent>
      </Card>

      <!-- 2. Tab 1 — Profil & Rekam Jejak -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tab 1 — Profil & Rekam Jejak</CardTitle>
          <CardDescription class="text-xs">Biografi perjalanan kepemimpinan, linimasa jabatan, riwayat jabatan strategis, dan latar belakang pendidikan formal.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Biografi & Perjalanan Karier -->
          <div class="space-y-1.5">
            <Label for="bio" class="text-xs font-medium">Biografi & Perjalanan Karier</Label>
            <textarea
              id="bio"
              v-model="form.bio"
              rows="4"
              placeholder="Uraikan latar belakang tokoh, perjalanan kepemimpinan, dan fokus kebijakan..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('bio')" class="text-xs text-red-500 font-medium">{{ err('bio') }}</p>
          </div>

          <!-- Linimasa Jabatan & Peran -->
          <RepeatableRows
            v-model="form.career_timeline"
            :fields="careerFields"
            legend="Linimasa Jabatan & Peran"
            description="Kronologi perjalanan jabatan dan peran publik tokoh dari masa ke masa."
            addLabel="Tambah Linimasa"
          />

          <!-- Riwayat Jabatan Strategis -->
          <RepeatableRows
            v-model="form.positions_history"
            :fields="positionsFields"
            legend="Riwayat Jabatan Strategis"
            description="Portofolio jabatan struktural dan posisi publik formal yang pernah diemban."
            addLabel="Tambah Jabatan"
          />

          <!-- Latar Belakang Pendidikan -->
          <RepeatableRows
            v-model="form.education"
            :fields="educationFields"
            legend="Latar Belakang Pendidikan"
            description="Jenjang pendidikan formal dan universitas tempat belajar."
            addLabel="Tambah Pendidikan"
          />
        </CardContent>
      </Card>

      <!-- 3. Tab 2 — Analisis ISF (Policy Matrix) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tab 2 — Analisis ISF (Policy Matrix)</CardTitle>
          <CardDescription class="text-xs">Jejak regulasi kebijakan publik dan pemetaan tahapan perundang-undangan (Policy Matrix).</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Jejak Kebijakan Publik (Policy Matrix) -->
          <div class="space-y-4">
            <!-- Matrix Columns -->
            <StringListEditor
              :modelValue="form.matrix_columns"
              @update:modelValue="onMatrixColumnsChange"
              label="Kolom Tahapan Regulasi"
              description="Daftar kolom tahapan regulasi, e.g. Inisiasi, Pembahasan, Implementasi."
              placeholder="Tambah nama kolom tahapan..."
              addLabel="Tambah Kolom"
            />

            <!-- Matrix Rows -->
            <div class="space-y-3 pt-2">
              <div class="flex items-center justify-between">
                <Label class="text-xs font-semibold text-foreground">Daftar Regulasi & Kebijakan</Label>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  @click="addMatrixRow"
                  class="border-dashed border-red-500/50 text-red-600 dark:text-red-400 text-xs"
                >
                  <IconComponent name="Plus" :size="14" class="mr-1" />
                  Tambah Regulasi
                </Button>
              </div>

              <div v-if="form.matrix_rows.length === 0" class="text-xs text-muted-foreground py-4 text-center border border-dashed border-border rounded-lg">
                Belum ada baris regulasi. Klik "Tambah Regulasi" di atas.
              </div>

              <div
                v-for="(row, rIdx) in form.matrix_rows"
                :key="rIdx"
                class="border border-border bg-card/60 rounded-lg p-3 space-y-3"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-semibold text-foreground">#{{ rIdx + 1 }}</span>
                  <button
                    type="button"
                    @click="removeMatrixRow(rIdx)"
                    class="p-1 rounded text-red-500 hover:bg-red-500/10 text-xs"
                  >
                    <IconComponent name="Trash" :size="14" />
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="space-y-1">
                    <Label class="text-[11px] text-muted-foreground">Nama Regulasi / Kebijakan</Label>
                    <Input v-model="row.kebijakan" placeholder="e.g. RUU Perampasan Aset Tindak Pidana" class="h-8 text-xs" />
                  </div>
                  <div class="space-y-1">
                    <Label class="text-[11px] text-muted-foreground">Keterangan Tahap Pembahasan</Label>
                    <Input v-model="row.tahap" placeholder="e.g. Pembahasan Tingkat I" class="h-8 text-xs" />
                  </div>
                </div>

                <!-- Involvement checkboxes per column -->
                <div class="space-y-1 pt-1">
                  <div class="text-[11px] text-muted-foreground font-medium">Keterlibatan di Kolom Tahapan:</div>
                  <div class="flex flex-wrap gap-4 pt-1">
                    <label
                      v-for="(col, cIdx) in form.matrix_columns"
                      :key="cIdx"
                      class="flex items-center gap-1.5 cursor-pointer text-xs"
                    >
                      <input
                        type="checkbox"
                        v-model="row.involvement[cIdx]"
                        class="rounded border-input text-red-600 focus:ring-red-500 h-3.5 w-3.5"
                      />
                      <span class="text-foreground text-xs">{{ col }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- 4. Tab 3 — Jejaring & Afiliasi -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tab 3 — Jejaring & Afiliasi</CardTitle>
          <CardDescription class="text-xs">Peta jejaring kekuasaan dan afiliasi strategis: konstelasi politik, relasi pengaruh, mitra koalisi, serta poros pengkritik.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Afiliasi Lembaga & Organisasi -->
          <RepeatableRows
            v-model="form.affiliations"
            :fields="affiliationFields"
            legend="Afiliasi Lembaga, Partai & Organisasi"
            description="Institusi, partai politik, atau paguyuban resmi tempat figur bernaung."
            addLabel="Tambah Afiliasi"
          />

          <!-- Sekutu & Mitra Strategis -->
          <RepeatableRows
            v-model="form.allies"
            :fields="alliesFields"
            legend="Sekutu & Mitra Strategis"
            description="Tokoh atau institusi mitra strategis dalam mendorong agenda kebijakan."
            addLabel="Tambah Sekutu"
          />

          <!-- Oposan & Pengkritik Kebijakan -->
          <RepeatableRows
            v-model="form.critics"
            :fields="criticsFields"
            legend="Oposan & Pengkritik Kebijakan"
            description="Aktor atau kelompok masyarakat yang secara aktif memberikan perlawanan atau kritik terhadap figur."
            addLabel="Tambah Oposan / Kritik"
          />
        </CardContent>
      </Card>

      <!-- 5. Tab 4 — Berita & Aktivitas -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tab 4 — Berita & Aktivitas</CardTitle>
          <CardDescription class="text-xs">
            Feed berita dan liputan media terkait: kurasi artikel analisis yang disematkan langsung pada profil sosok ini.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <RepeatableRows
            v-model="form.related_articles"
            :fields="relatedArticleFields"
            legend="Feed Berita & Liputan Media Terkait"
            description="Sematan artikel analisis yang terhubung langsung dengan sosok ini di Tab 4 Berita & Aktivitas."
            addLabel="Tambah Artikel Terkait"
          />
        </CardContent>
      </Card>

      <!-- 6. Tab 5 — Kutipan & Kontroversi -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tab 5 — Kutipan & Kontroversi</CardTitle>
          <CardDescription class="text-xs">Pernyataan kunci kutipan strategis dan dinamika kontroversi peristiwa kritis.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Pernyataan Kunci & Kutipan Strategis -->
          <RepeatableRows
            v-model="form.quotes"
            :fields="quotesFields"
            legend="Pernyataan Kunci & Kutipan Strategis"
            description="Pernyataan resmi di parlemen, media massa, atau forum kebijakan."
            addLabel="Tambah Kutipan"
          />

          <!-- Dinamika Kontroversi & Peristiwa Kritis -->
          <RepeatableRows
            v-model="form.controversies"
            :fields="controversiesFields"
            legend="Dinamika Kontroversi & Peristiwa Kritis"
            description="Catatan peristiwa kontroversi, polemik kebijakan, atau dinamika hukum yang melibatkan sosok."
            addLabel="Tambah Kasus / Kontroversi"
          />
        </CardContent>
      </Card>
    </form>
  </div>
</template>
