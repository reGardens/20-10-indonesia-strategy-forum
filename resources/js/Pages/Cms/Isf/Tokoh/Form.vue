<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
import StringListEditor from '@/Pages/Cms/components/shared/StringListEditor.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
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
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const ROLE_OPTIONS = [
  { value: 'eksekutif', label: 'Eksekutif' },
  { value: 'legislatif', label: 'Legislatif' },
  { value: 'yudikatif', label: 'Yudikatif' },
  { value: 'pakar', label: 'Pakar & Akademisi' },
  { value: 'militer', label: 'Militer & Hankam' },
  { value: 'pengusaha', label: 'Pengusaha' },
]

const AVAILABLE_ISSUES = [
  'politik', 'hukum', 'ekonomi', 'kebijakan', 'pertahanan', 'sosial', 'energi', 'pendidikan', 'keamanan', 'digital'
]

const statFields = [
  { key: 'label', label: 'Label Statistik', type: 'text', required: true, placeholder: 'e.g. Kebijakan Diinisiasi' },
  { key: 'value', label: 'Nilai Angka / Persen', type: 'text', required: true, placeholder: 'e.g. 12 atau 85%' }
]

const careerFields = [
  { key: 'year', label: 'Tahun / Periode', type: 'text', required: true, placeholder: 'e.g. 2024 - Sekarang' },
  { key: 'title', label: 'Posisi / Pencapaian', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Ketua Komisi Hukum DPR RI' },
  { key: 'description', label: 'Keterangan / Dampak Kebijakan', type: 'text', span: 'sm:col-span-3', placeholder: 'Keterangan peran...' }
]

const educationFields = [
  { key: 'degree', label: 'Gelar / Tingkat', type: 'text', required: true, placeholder: 'e.g. Sarjana Hukum (S.H.)' },
  { key: 'institution', label: 'Institusi / Universitas', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Universitas Indonesia' },
  { key: 'year', label: 'Tahun Lulus', type: 'text', placeholder: 'e.g. 1998' }
]

const positionsFields = [
  { key: 'period', label: 'Periode Menjabat', type: 'text', required: true, placeholder: 'e.g. 2019 - 2024' },
  { key: 'position', label: 'Nama Jabatan', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Anggota Komisi III DPR RI' },
  { key: 'institution', label: 'Lembaga / Organisasi', type: 'text', span: 'sm:col-span-3', placeholder: 'e.g. Dewan Perwakilan Rakyat' }
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
  { key: 'title', label: 'Judul Kontroversi / Isu Hukum', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Polemik Pasal Imunitas Diskresi' },
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

const calendarEventFields = [
  { key: 'date', label: 'Tanggal Agenda', type: 'date', required: true },
  { key: 'title', label: 'Keterangan Agenda', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'Rapat Kerja Komisi VII DPR RI...' }
]

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.tokoh,
  id: props.id,
  indexHref: '/cms/tokoh',
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
    stats: [
      { label: 'Kebijakan Diinisiasi', value: '12' },
      { label: 'Rapat Paripurna', value: '48' },
      { label: 'Keterlibatan Publik', value: '85%' },
      { label: 'Sorotan Regulasi', value: '34' }
    ],
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
  <Head :title="(isEdit ? 'Edit Tokoh' : 'Tambah Tokoh') + ' — Indonesia Strategy Forum'" />

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
            {{ isEdit ? 'Edit Tokoh' : 'Tambah Tokoh Baru' }}
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
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Tokoh' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data tokoh..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- 1. Identitas Figur -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Identitas & Klasifikasi Figur</CardTitle>
          <CardDescription class="text-xs">Nama lengkap, peran kategori lembaga, jabatan, foto profil, dan status publikasi.</CardDescription>
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
              <Label for="role_category" class="text-xs font-medium">Kategori Peran <span class="text-red-500">*</span></Label>
              <Select v-model="form.role_category">
                <SelectTrigger id="role_category" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Kategori Peran..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="opt in ROLE_OPTIONS"
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
          <div class="pt-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_influential_weekly"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Tokoh Paling Berpengaruh Minggu Ini</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan sebagai banner Hero di halaman utama Indeks Sosok.</div>
              </div>
            </label>

            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_featured"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Tokoh Pekan Ini (Beranda)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan profil tokoh ini di section "Pekan Ini" halaman depan Beranda.</div>
              </div>
            </label>

            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.show_in_politik"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Profil Tokoh Politik (/politik)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan di widget profil rubrik Politik.</div>
              </div>
            </label>
          </div>

          <!-- Summary Analysis 1 kalimat -->
          <div class="space-y-1.5">
            <Label for="summary_analysis" class="text-xs font-medium">Ringkasan Analisis ISF (1 Kalimat)</Label>
            <textarea
              id="summary_analysis"
              v-model="form.summary_analysis"
              rows="2"
              placeholder="e.g. Tokoh kunci dalam percepatan pembahasan revisi perundang-undangan strategis dan penegakan hukum nasional."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p class="text-[11px] text-muted-foreground">Ditampilkan pada card profil direktori dan ringkasan hero indeks sosok.</p>
            <p v-if="err('summary_analysis')" class="text-xs text-red-500 font-medium">{{ err('summary_analysis') }}</p>
          </div>

          <!-- Teaser Beranda -->
          <div v-if="form.is_featured" class="space-y-1.5">
            <Label for="teaser" class="text-xs font-medium">Teaser Singkat untuk Beranda</Label>
            <textarea
              id="teaser"
              v-model="form.teaser"
              rows="2"
              placeholder="Teaser pengantar sorotan kebijakan figur ini..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('teaser')" class="text-xs text-red-500 font-medium">{{ err('teaser') }}</p>
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
            <p class="text-[11px] text-muted-foreground">Pilih isu kebijakan yang diasosiasikan dengan figur untuk filter dan rekomendasi.</p>
          </div>

          <!-- Aliases untuk pencocokan otomatis berita (Tab 4) -->
          <div class="pt-2">
            <StringListEditor
              v-model="form.aliases"
              label="Alias & Nama Alternatif (Matching Berita Otomatis)"
              description="Kata kunci atau sebutan tokoh yang dipakai sistem untuk menarik artikel berita terkait otomatis ke Tab Berita & Aktivitas."
              placeholder="e.g. Budi Santoso, Dr. Budi Santoso, Komisi III..."
              addLabel="Tambah Alias"
            />
          </div>
        </CardContent>
      </Card>

      <!-- 2. Tab Profil & Rekam Jejak (Tab 1) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Profil, Rekam Jejak & Pendidikan (Tab 1)</CardTitle>
          <CardDescription class="text-xs">Biografi naratif mendalam, timeline kronologis karier, pendidikan, dan riwayat jabatan formal.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Bio Naratif -->
          <div class="space-y-1.5">
            <Label for="bio" class="text-xs font-medium">Biografi Naratif Lengkap</Label>
            <textarea
              id="bio"
              v-model="form.bio"
              rows="4"
              placeholder="Uraikan latar belakang tokoh, perjalanan kepemimpinan, dan fokus kebijakan..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('bio')" class="text-xs text-red-500 font-medium">{{ err('bio') }}</p>
          </div>

          <!-- Timeline Karier -->
          <RepeatableRows
            v-model="form.career_timeline"
            :fields="careerFields"
            legend="Linimasa Karier & Pengalaman"
            description="Perjalanan rekam jejak karier kronologis tokoh dari masa ke masa."
            addLabel="Tambah Linimasa Karier"
          />

          <!-- Pendidikan -->
          <RepeatableRows
            v-model="form.education"
            :fields="educationFields"
            legend="Riwayat Pendidikan"
            description="Jenjang pendidikan formal dan universitas tempat belajar."
            addLabel="Tambah Pendidikan"
          />

          <!-- Riwayat Jabatan -->
          <RepeatableRows
            v-model="form.positions_history"
            :fields="positionsFields"
            legend="Riwayat Jabatan Formal"
            description="Daftar portofolio jabatan publik atau struktural yang pernah diemban."
            addLabel="Tambah Riwayat Jabatan"
          />
        </CardContent>
      </Card>

      <!-- 3. Tab Analisis ISF: Skor Pengaruh & Policy Matrix (Tab 2) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Analisis ISF: Skor Pengaruh & Jejak Kebijakan (Tab 2)</CardTitle>
          <CardDescription class="text-xs">Skor Pengaruh ISF, skor indeks sorotan berkala, matriks jejak kebijakan publik, dan 4 metrik performa.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
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
                placeholder="Pekan 41 / 2026 (12 - 18 Oktober)"
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

          <!-- 4 Stats Repeatable -->
          <RepeatableRows
            v-model="form.stats"
            :fields="statFields"
            legend="4 Metrik Statistik Kunci"
            description="Tepat 4 metrik ringkas yang tampil di header profil dan tab analisis."
            addLabel="Tambah Metrik"
            :max="8"
          />

          <!-- Policy Matrix Section -->
          <div class="pt-4 border-t border-border space-y-4">
            <h3 class="text-sm font-semibold text-foreground">Jejak Kebijakan (Policy Matrix)</h3>
            <!-- Matrix Columns -->
            <StringListEditor
              :modelValue="form.matrix_columns"
              @update:modelValue="onMatrixColumnsChange"
              label="Kolom Tahapan Kebijakan"
              description="Daftar kolom tahapan regulasi, e.g. Inisiasi, Pembahasan, Implementasi."
              placeholder="Tambah nama kolom..."
              addLabel="Tambah Kolom"
            />

            <!-- Matrix Rows -->
            <div class="space-y-3 pt-2">
              <div class="flex items-center justify-between">
                <Label class="text-xs font-semibold text-foreground">Baris Kebijakan</Label>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  @click="addMatrixRow"
                  class="border-dashed border-red-500/50 text-red-600 dark:text-red-400 text-xs"
                >
                  <IconComponent name="Plus" :size="14" class="mr-1" />
                  Tambah Kebijakan
                </Button>
              </div>

              <div v-if="form.matrix_rows.length === 0" class="text-xs text-muted-foreground py-4 text-center border border-dashed border-border rounded-lg">
                Belum ada baris kebijakan. Klik "Tambah Kebijakan" di atas.
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
                    <Label class="text-[11px] text-muted-foreground">Nama Kebijakan</Label>
                    <Input v-model="row.kebijakan" placeholder="e.g. RUU Perampasan Aset Tindak Pidana" class="h-8 text-xs" />
                  </div>
                  <div class="space-y-1">
                    <Label class="text-[11px] text-muted-foreground">Keterangan Tahap</Label>
                    <Input v-model="row.tahap" placeholder="e.g. Pembahasan Tingkat I" class="h-8 text-xs" />
                  </div>
                </div>

                <!-- Involvement checkboxes per column -->
                <div class="space-y-1 pt-1">
                  <div class="text-[11px] text-muted-foreground font-medium">Keterlibatan di Kolom:</div>
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

      <!-- 4. Tab Jejaring & Afiliasi (Tab 3) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Jejaring, Sekutu & Oposan (Tab 3)</CardTitle>
          <CardDescription class="text-xs">Pemetaan relasi kelembagaan, partai, koalisi/sekutu, serta pengkritik atau poros oposisi.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Afiliasi Formal -->
          <RepeatableRows
            v-model="form.affiliations"
            :fields="affiliationFields"
            legend="Afiliasi Lembaga, Partai & Organisasi"
            description="Organisasi, partai politik, atau paguyuban resmi tempat figur bernaung."
            addLabel="Tambah Afiliasi"
          />

          <!-- Sekutu & Mitra -->
          <RepeatableRows
            v-model="form.allies"
            :fields="alliesFields"
            legend="Sekutu & Mitra Strategis"
            description="Tokoh atau institusi mitra koalisi dalam meloloskan atau mendorong agenda kebijakan."
            addLabel="Tambah Sekutu"
          />

          <!-- Oposan & Kritik -->
          <RepeatableRows
            v-model="form.critics"
            :fields="criticsFields"
            legend="Oposan & Pengkritik Kebijakan"
            description="Aktor atau kelompok masyarakat yang secara aktif memberikan perlawanan atau kritik terhadap figur."
            addLabel="Tambah Oposan / Kritik"
          />
        </CardContent>
      </Card>

      <!-- 5. Tab Kutipan & Kontroversi (Tab 5) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Kutipan Kunci & Rekam Jejak Kontroversi (Tab 5)</CardTitle>
          <CardDescription class="text-xs">Kutipan pernyataan publik yang berdampak luas dan timeline kasus atau kontroversi kebijakan.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- Kutipan -->
          <RepeatableRows
            v-model="form.quotes"
            :fields="quotesFields"
            legend="Koleksi Kutipan Pernyataan Kunci"
            description="Pernyataan resmi di parlemen, media, atau forum kebijakan."
            addLabel="Tambah Kutipan"
          />

          <!-- Kontroversi -->
          <RepeatableRows
            v-model="form.controversies"
            :fields="controversiesFields"
            legend="Rekam Jejak Kontroversi & Kasus"
            description="Dinamika kasus etika, hukum, atau friksi regulasi yang melibatkan tokoh."
            addLabel="Tambah Kasus / Kontroversi"
          />
        </CardContent>
      </Card>

      <!-- 6. Artikel Terkait & Kalender Agenda -->
      <RepeatableRows
        v-model="form.related_articles"
        :fields="relatedArticleFields"
        legend="Artikel Terkait Kurasi Manual"
        description="Analisis mendalam ISF yang secara khusus disematkan ke figur ini (di luar feed berita otomatis)."
        addLabel="Tambah Artikel Terkait"
      />

      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Kalender & Agenda Kerja</CardTitle>
          <CardDescription class="text-xs">Agenda sidang, pertemuan regulasi, dan forum tokoh bulan ini.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="calendar_year" class="text-xs font-medium">Tahun Kalender <span class="text-red-500">*</span></Label>
              <Input
                id="calendar_year"
                type="number"
                v-model.number="form.calendar_year"
                min="2020"
                max="2100"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5">
              <Label for="calendar_month" class="text-xs font-medium">Bulan (1 - 12) <span class="text-red-500">*</span></Label>
              <Input
                id="calendar_month"
                type="number"
                v-model.number="form.calendar_month"
                min="1"
                max="12"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <RepeatableRows
            v-model="form.calendar_events"
            :fields="calendarEventFields"
            legend="Daftar Agenda Spesifik"
            addLabel="Tambah Agenda"
          />
        </CardContent>
      </Card>
    </form>
  </div>
</template>
