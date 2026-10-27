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
  CardContent
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const statFields = [
  { key: 'label', label: 'Label Statistik', type: 'text', required: true, placeholder: 'e.g. Kebijakan Diinisiasi' },
  { key: 'value', label: 'Nilai Angka', type: 'number', required: true }
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
    photo_alt: '',
    photo_url: '',
    stats: [
      { label: 'Kebijakan Diinisiasi', value: 12 },
      { label: 'Rapat Paripurna', value: 48 },
      { label: 'Keterlibatan Publik', value: 85 },
      { label: 'Sorotan Regulasi', value: 34 }
    ],
    skor_value: 82,
    skor_period: 'Pekan 41 / 2026',
    skor_explanation: '',
    matrix_columns: ['Inisiasi', 'Pembahasan', 'Implementasi'],
    matrix_rows: [],
    related_articles: [],
    calendar_year: 2026,
    calendar_month: 10,
    calendar_events: [],
    teaser: '',
    is_featured: false,
    status: 'draf'
  }
})

const slugCustomized = ref(false)

function generateSlug(val) {
  if (!val) return ''
  return val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '')
}

function onNameInput(e) {
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
  // Adjust involvement array length in each row to match column count
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
            {{ isEdit ? `Mengubah profil ${form.name || ''}` : 'Isi formulir berikut untuk menerbitkan figur pembuat kebijakan.' }}
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
      <!-- Identitas Tokoh -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Identitas Figur</CardTitle>
          <CardDescription class="text-xs">Nama lengkap, jabatan formal, foto profil, dan status publikasi.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="name" class="text-xs font-medium">Nama Lengkap & Gelar <span class="text-red-500">*</span></Label>
              <Input
                id="name"
                v-model="form.name"
                @input="onNameInput"
                placeholder="e.g. Budi Santoso, S.H., M.H."
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
                placeholder="Dibuat otomatis dari nama lengkap & gelar..."
                class="h-9 font-mono text-xs"
              />
              <p class="text-[11px] text-muted-foreground">Otomatis mengikuti nama lengkap & gelar. Bisa disesuaikan manual.</p>
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="position" class="text-xs font-medium">Jabatan / Lembaga <span class="text-red-500">*</span></Label>
              <Input
                id="position"
                v-model="form.position"
                placeholder="e.g. Menteri Hukum dan HAM RI"
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
                placeholder="Foto Budi Santoso saat rapat kabinet"
                class="h-9 text-xs"
              />
              <p v-if="err('photo_alt')" class="text-xs text-red-500 font-medium">{{ err('photo_alt') }}</p>
            </div>
          </div>

          <div class="pt-2">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_featured"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Tokoh Pekan Ini (Featured on Beranda)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan profil tokoh ini di section "Pekan Ini" halaman depan Beranda.</div>
              </div>
            </label>
          </div>

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
        </CardContent>
      </Card>

      <!-- Statistik & Skor Sorotan -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Skor Sorotan & Metrik</CardTitle>
          <CardDescription class="text-xs">Skor indeks aktivitas publik dan 4 metrik performa kebijakan.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="skor_value" class="text-xs font-medium">Nilai Skor (0 - 100) <span class="text-red-500">*</span></Label>
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

            <div class="space-y-1.5 sm:col-span-2">
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
            <Label for="skor_explanation" class="text-xs font-medium">Penjelasan / Rasional Skor <span class="text-red-500">*</span></Label>
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
            description="Tepat 4 metrik ringkas yang tampil di header profil."
            addLabel="Tambah Metrik"
            :max="8"
          />
        </CardContent>
      </Card>

      <!-- Policy Matrix -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Jejak Kebijakan (Policy Matrix)</CardTitle>
          <CardDescription class="text-xs">Matriks keterlibatan tokoh pada tiap tahapan kebijakan publik.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Matrix Columns -->
          <StringListEditor
            :modelValue="form.matrix_columns"
            @update:modelValue="onMatrixColumnsChange"
            label="Kolom Tahapan Kebijakan"
            description="Daftar kolom tahapan, e.g. Inisiasi, Pembahasan, Implementasi."
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
                  <Input v-model="row.kebijakan" placeholder="e.g. RUU KUHP Baru" class="h-8 text-xs" />
                </div>
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">Keterangan Tahap</Label>
                  <Input v-model="row.tahap" placeholder="e.g. Diundangkan" class="h-8 text-xs" />
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
        </CardContent>
      </Card>

      <!-- Artikel Terkait & Kalender Agenda -->
      <RepeatableRows
        v-model="form.related_articles"
        :fields="relatedArticleFields"
        legend="Artikel Terkait Tokoh"
        description="Daftar analisis atau berita yang merujuk pada peran figur ini."
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

      <!-- Bottom buttons removed per requirement (top bar only) -->
    </form>
  </div>
</template>
