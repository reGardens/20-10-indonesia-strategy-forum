<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, chartOptions } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { CATEGORIES, STATUSES } from '@/Pages/Cms/Isf/options'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
import StringListEditor from '@/Pages/Cms/components/shared/StringListEditor.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
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
const availableCharts = ref([])

onMounted(async () => {
  try {
    availableCharts.value = await chartOptions()
  } catch (e) {
    // optional charts
  }
})

const faktaKunciFields = [
  { key: 'claim', label: 'Klaim / Poin Fakta', type: 'textarea', span: 'sm:col-span-2 lg:col-span-3', required: true },
  { key: 'sourceTitle', label: 'Nama Sumber', type: 'text', required: true },
  { key: 'sourceUrl', label: 'URL Sumber (https://)', type: 'url', required: true },
  { key: 'sourceDate', label: 'Tanggal Sumber', type: 'date', required: true }
]

const linimasaFields = [
  { key: 'date', label: 'Tanggal / Periode', type: 'date', required: true },
  { key: 'label', label: 'Peristiwa / Perkembangan', type: 'text', span: 'sm:col-span-2', required: true }
]

const aktorFields = [
  { key: 'name', label: 'Nama Tokoh / Entitas', type: 'text', required: true },
  { key: 'role', label: 'Peran / Afiliasi', type: 'text', required: true },
  { key: 'stance', label: 'Sikap / Posisi Kebijakan', type: 'text', required: true, placeholder: 'e.g. Progresif / Netral / Penentang' },
  { key: 'to', label: 'Tautan Profil (/tokoh/... atau URL)', type: 'url', span: 'sm:col-span-2 lg:col-span-3' }
]

const complianceFields = [
  { key: 'kewajiban', label: 'Kewajiban Kepatuhan', type: 'text', required: true },
  { key: 'tenggat', label: 'Tenggat Waktu', type: 'date', required: true },
  { key: 'regulasi', label: 'Rujukan Regulasi', type: 'text', required: true }
]

const sektoralFields = [
  { key: 'sektor', label: 'Sektor Industri', type: 'text', required: true, placeholder: 'e.g. Perbankan / Energi' },
  { key: 'dampak', label: 'Uraian Dampak Regulasi', type: 'textarea', span: 'sm:col-span-2', required: true }
]

const reaksiFields = [
  { key: 'pihak', label: 'Pihak / Asosiasi', type: 'text', required: true, placeholder: 'e.g. APINDO / KADIN' },
  { key: 'reaksi', label: 'Respon & Kekhawatiran', type: 'textarea', span: 'sm:col-span-2', required: true }
]

const tahapanFields = [
  { key: 'date', label: 'Estimasi Tanggal', type: 'date', required: true },
  { key: 'label', label: 'Agenda / Milestone Regulasi', type: 'text', span: 'sm:col-span-2', required: true }
]

const pantauFields = [
  { key: 'label', label: 'Indikator yang Harus Dipantau', type: 'text', span: 'sm:col-span-2', required: true },
  { key: 'to', label: 'Tautan Rujukan / Tracking', type: 'url' }
]

const faqFields = [
  { key: 'question', label: 'Pertanyaan Praktis', type: 'text', span: 'sm:col-span-2 lg:col-span-3', required: true },
  { key: 'answer', label: 'Jawaban & Panduan Aksi', type: 'textarea', span: 'sm:col-span-2 lg:col-span-3', required: true }
]

const relatedFields = [
  { key: 'title', label: 'Judul Artikel Terkait', type: 'text', span: 'sm:col-span-2', required: true },
  { key: 'to', label: 'URL / Path Target', type: 'url' },
  { key: 'publishedAt', label: 'Tanggal Publikasi', type: 'date', required: true }
]

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.isu,
  id: props.id,
  indexHref: '/cms/isu',
  defaults: {
    slug: '',
    title: '',
    category: 'hukum',
    published_at: new Date().toISOString().slice(0, 10),
    summary: '',
    author: 'Tim Intelijen Kebijakan ISF',
    teaser: '',
    is_weekly: false,
    ringkasan: '',
    visual_chart_id: null,
    visual_image_alt: '',
    visual_image_url: '',
    fakta_kunci: [],
    linimasa: [],
    aktor: [],
    grc_governance: '',
    grc_risk_level: 'Tinggi',
    grc_risk_description: '',
    grc_compliance: [],
    dampak_sektoral: [],
    dampak_reaksi: [],
    selanjutnya_tahapan: [],
    selanjutnya_pantau: [],
    metodologi_text: '',
    metodologi_signals: ['Pernyataan Resmi Regulator', 'Draf Regulasi Publik', 'Dengar Pendapat Parlemen'],
    faq: [],
    related: [],
    status: 'draf'
  }
})

function onTitleInput(e) {
  if (!isEdit.value && !form.slug) {
    form.slug = e.target.value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)+/g, '')
  }
}
</script>

<template>
  <Head :title="(isEdit ? 'Edit Isu Mingguan' : 'Tambah Isu Baru') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/isu">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Anatomi Isu' : 'Tambah Laporan Anatomi Isu' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Laporan terstruktur 7 kerangka kerja analisis strategis ISF.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/isu">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Isu' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data isu..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- Informasi Utama -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Informasi Utama Isu</CardTitle>
          <CardDescription class="text-xs">Judul, slug unik, kategori, dan penugasan sebagai Isu Pekan Ini.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Isu Strategis <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              @input="onTitleInput"
              placeholder="e.g. Apa yang Berubah Bagi Pelaku Usaha Setelah KUHP Baru Berlaku"
              class="h-9 text-sm"
            />
            <p v-if="err('title')" class="text-xs text-red-500 font-medium">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium">Slug URL <span class="text-red-500">*</span></Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="kuhp-baru-pelaku-usaha"
                class="h-9 font-mono text-xs"
              />
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium">Kategori <span class="text-red-500">*</span></Label>
              <select
                id="category"
                v-model="form.category"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option v-for="cat in CATEGORIES" :key="cat.value" :value="cat.value">
                  {{ cat.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="author" class="text-xs font-medium">Analis / Penulis <span class="text-red-500">*</span></Label>
              <Input
                id="author"
                v-model="form.author"
                placeholder="Tim Riset Regulasi ISF"
                class="h-9 text-xs"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="published_at" class="text-xs font-medium">Tanggal Publikasi <span class="text-red-500">*</span></Label>
              <Input
                id="published_at"
                type="date"
                v-model="form.published_at"
                class="h-9 text-xs"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium">Status <span class="text-red-500">*</span></Label>
              <select
                id="status"
                v-model="form.status"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring font-medium"
              >
                <option v-for="st in STATUSES" :key="st.value" :value="st.value">
                  {{ st.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="summary" class="text-xs font-medium">Lead Summary / Pengantar Isu <span class="text-red-500">*</span></Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              placeholder="Deskripsi singkat isu untuk kartu dan preview..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('summary')" class="text-xs text-red-500 font-medium">{{ err('summary') }}</p>
          </div>

          <div class="pt-2">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-emerald-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_weekly"
                class="mt-0.5 rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Isu Mingguan Utama (Featured on Beranda)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan analisis isu ini di section "Pekan Ini" Beranda publik.</div>
              </div>
            </label>
          </div>

          <div v-if="form.is_weekly" class="space-y-1.5">
            <Label for="teaser" class="text-xs font-medium">Teaser Isu untuk Beranda</Label>
            <textarea
              id="teaser"
              v-model="form.teaser"
              rows="2"
              placeholder="Teaser singkat yang menarik perhatian pembaca di Beranda..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>
        </CardContent>
      </Card>

      <!-- 01 Ringkasan & 02 Visual Kunci -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">01 Ringkasan & 02 Visual Kunci</CardTitle>
          <CardDescription class="text-xs">Uraian ringkasan isu serta grafik atau infografik pengantar.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="ringkasan" class="text-xs font-medium">01 Ringkasan Eksekutif Isu <span class="text-red-500">*</span></Label>
            <textarea
              id="ringkasan"
              v-model="form.ringkasan"
              rows="4"
              placeholder="Uraian analitis komprehensif mengenai latar belakang isu..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring leading-relaxed"
            />
            <p v-if="err('ringkasan')" class="text-xs text-red-500 font-medium">{{ err('ringkasan') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div class="space-y-1.5">
              <Label for="visual_chart_id" class="text-xs font-medium">02 Visual Kunci: Sambungkan Data Grafik</Label>
              <select
                id="visual_chart_id"
                v-model="form.visual_chart_id"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option :value="null">-- Tanpa Data Grafik (Gunakan Gambar) --</option>
                <option v-for="c in availableCharts" :key="c.id" :value="c.id">
                  {{ c.title }} (ID: {{ c.id }})
                </option>
              </select>
            </div>

            <div class="space-y-1.5">
              <Label for="visual_image_url" class="text-xs font-medium">Atau Gambar Alternatif (URL)</Label>
              <Input
                id="visual_image_url"
                v-model="form.visual_image_url"
                placeholder="https://..."
                class="h-9 text-xs font-mono"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- 03 Fakta Kunci & Linimasa -->
      <RepeatableRows
        v-model="form.fakta_kunci"
        :fields="faktaKunciFields"
        legend="03 Fakta Kunci (Key Facts)"
        description="Poin klaim terverifikasi beserta tautan sumber primer."
        addLabel="Tambah Fakta Kunci"
      />

      <RepeatableRows
        v-model="form.linimasa"
        :fields="linimasaFields"
        legend="Linimasa Peristiwa (Timeline)"
        description="Urutan kronologis perkembangan regulasi atau isu."
        addLabel="Tambah Titik Linimasa"
      />

      <!-- 04 Aktor & Posisi -->
      <RepeatableRows
        v-model="form.aktor"
        :fields="aktorFields"
        legend="04 Aktor Kunci & Posisi Regulasi"
        description="Tokoh, kementerian, atau asosiasi industri yang terlibat serta sikap mereka."
        addLabel="Tambah Aktor"
      />

      <!-- 05 Lensa GRC -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">05 Lensa GRC (Governance, Risk, Compliance)</CardTitle>
          <CardDescription class="text-xs">Penilaian tata kelola, level risiko, dan kewajiban kepatuhan.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="grc_risk_level" class="text-xs font-medium">Tingkat Risiko <span class="text-red-500">*</span></Label>
              <Input
                id="grc_risk_level"
                v-model="form.grc_risk_level"
                placeholder="e.g. Tinggi / Moderat / Rendah"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5 sm:col-span-2">
              <Label for="grc_risk_description" class="text-xs font-medium">Uraian Risiko Utama <span class="text-red-500">*</span></Label>
              <Input
                id="grc_risk_description"
                v-model="form.grc_risk_description"
                placeholder="e.g. Ketidakpastian interpretasi pasal pidana korporasi..."
                class="h-9 text-xs"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="grc_governance" class="text-xs font-medium">Catatan Tata Kelola (Governance) <span class="text-red-500">*</span></Label>
            <textarea
              id="grc_governance"
              v-model="form.grc_governance"
              rows="3"
              placeholder="Uraian rekomendasi tata kelola internal bagi direksi/manajemen..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>

          <RepeatableRows
            v-model="form.grc_compliance"
            :fields="complianceFields"
            legend="Kewajiban Kepatuhan (Compliance Obligations)"
            addLabel="Tambah Kewajiban"
          />
        </CardContent>
      </Card>

      <!-- 06 Dampak & Reaksi -->
      <RepeatableRows
        v-model="form.dampak_sektoral"
        :fields="sektoralFields"
        legend="06 Dampak Sektoral Industri"
        description="Pengaruh regulasi terhadap sektor spesifik (Perbankan, Tambang, Kesehatan, dll)."
        addLabel="Tambah Dampak Sektor"
      />

      <RepeatableRows
        v-model="form.dampak_reaksi"
        :fields="reaksiFields"
        legend="Reaksi Pemangku Kepentingan"
        description="Respon publik, asosiasi pelaku usaha, dan lembaga masyarakat sipil."
        addLabel="Tambah Reaksi Pihak"
      />

      <!-- 07 Apa Selanjutnya -->
      <RepeatableRows
        v-model="form.selanjutnya_tahapan"
        :fields="tahapanFields"
        legend="07 Apa Selanjutnya: Tahapan Regulasi Mendatang"
        addLabel="Tambah Tahapan"
      />

      <RepeatableRows
        v-model="form.selanjutnya_pantau"
        :fields="pantauFields"
        legend="Indikator Pemantauan Lanjutan"
        addLabel="Tambah Indikator"
      />

      <!-- Metodologi, FAQ & Artikel Terkait -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Metodologi & Cara Isu Dipilih</CardTitle>
          <CardDescription class="text-xs">Transparansi metodologi editorial riset intelijen kebijakan ISF.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="metodologi_text" class="text-xs font-medium">Uraian Metodologi Riset <span class="text-red-500">*</span></Label>
            <textarea
              id="metodologi_text"
              v-model="form.metodologi_text"
              rows="3"
              placeholder="Penjelasan bagaimana data dan bukti primer dihimpun..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>

          <StringListEditor
            v-model="form.metodologi_signals"
            label="Sinyal Kebijakan yang Diamati"
            description="Sumber data mentah yang dipantau (e.g. Risalah Sidang, Pernyataan Resmi)."
          />
        </CardContent>
      </Card>

      <RepeatableRows
        v-model="form.faq"
        :fields="faqFields"
        legend="Pertanyaan yang Sering Diajukan (FAQ)"
        addLabel="Tambah FAQ"
      />

      <RepeatableRows
        v-model="form.related"
        :fields="relatedFields"
        legend="Artikel Terkait Isu"
        addLabel="Tambah Artikel Terkait"
      />

      <!-- Bottom Actions -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/isu">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-5 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Isu' }}
        </Button>
      </div>
    </form>
  </div>
</template>
