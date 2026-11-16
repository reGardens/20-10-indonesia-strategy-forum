<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Label,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: {
    type: Number,
    default: null,
  },
})

const isEdit = computed(() => Boolean(props.id))

const form = ref({
  title: '',
  slug: '',
  sector: 'ekonomi',
  author: '',
  institution: '',
  executive_summary: '',
  key_recommendations: [''],
  pdf_path: '',
  pdf_size: '',
  published_at: new Date().toISOString().slice(0, 10),
  order: 0,
  status: 'terbit',
})

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

const sectorOptions = [
  { value: 'ekonomi', label: 'Ekonomi & Fiskal' },
  { value: 'kesehatan', label: 'Kesehatan Masyarakat' },
  { value: 'pendidikan', label: 'Pendidikan & Riset' },
  { value: 'pertahanan', label: 'Pertahanan & Keamanan' },
  { value: 'lingkungan', label: 'Lingkungan & Energi' },
]

function autoSlug() {
  if (!isEdit.value && form.value.title && !form.value.slug) {
    form.value.slug = form.value.title
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
}

function addRecommendation() {
  form.value.key_recommendations.push('')
}

function removeRecommendation(idx) {
  if (form.value.key_recommendations.length > 1) {
    form.value.key_recommendations.splice(idx, 1)
  } else {
    form.value.key_recommendations = ['']
  }
}

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.policyBrief.get(props.id)
    let recs = []
    if (Array.isArray(data.key_recommendations)) {
      recs = data.key_recommendations
    } else if (typeof data.key_recommendations === 'string') {
      try {
        const parsed = JSON.parse(data.key_recommendations)
        if (Array.isArray(parsed)) recs = parsed
      } catch {
        recs = [data.key_recommendations]
      }
    }
    if (recs.length === 0) recs = ['']

    form.value = {
      title: data.title || '',
      slug: data.slug || '',
      sector: data.sector || 'ekonomi',
      author: data.author || '',
      institution: data.institution || '',
      executive_summary: data.executive_summary || '',
      key_recommendations: recs,
      pdf_path: data.pdf_path || '',
      pdf_size: data.pdf_size || '',
      published_at: data.published_at ? data.published_at.slice(0, 10) : '',
      order: Number(data.order) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data policy brief.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  const cleanRecommendations = form.value.key_recommendations
    .map(r => r.trim())
    .filter(Boolean)

  const payload = {
    ...form.value,
    key_recommendations: cleanRecommendations,
    order: Number(form.value.order) || 0,
  }

  try {
    if (isEdit.value) {
      await cmsResources.policyBrief.update(props.id, payload)
      alertSuccess('Policy brief berhasil diperbarui.')
    } else {
      await cmsResources.policyBrief.create(payload)
      alertSuccess('Policy brief baru berhasil ditambahkan.')
    }
    router.visit('/cms/policy-brief')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
      alertError('Harap periksa kembali inputan form yang bertanda merah.')
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan policy brief.'))
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="`${isEdit ? 'Edit' : 'Tambah'} Policy Brief — CMS ISF`" />

  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2 text-xs text-neutral-500">
        <Link href="/cms/policy-brief" class="hover:text-neutral-900 transition-colors">
          Policy Brief
        </Link>
        <span>/</span>
        <span class="text-neutral-900 font-medium">
          {{ isEdit ? 'Edit Policy Brief' : 'Tambah Baru' }}
        </span>
      </div>
      <Link href="/cms/policy-brief">
        <Button variant="outline" size="sm" class="text-xs">
          Kembali
        </Button>
      </Link>
    </div>

    <!-- Main Card Form -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-2xs p-6">
      <div class="mb-6 pb-4 border-b border-neutral-100">
        <h1 class="text-xl font-bold tracking-tight text-neutral-900">
          {{ isEdit ? 'Edit Policy Brief' : 'Form Policy Brief Baru' }}
        </h1>
        <p class="text-xs text-neutral-500 mt-1">
          Lengkapi data riset kebijakan, ringkasan eksekutif, rekomendasi kunci, dan tautan berkas dokumen PDF.
        </p>
      </div>

      <LoadingIndicator v-if="loading" message="Memuat formulir..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="mb-6"
      />

      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Judul & Slug -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5 md:col-span-2">
            <Label for="title" class="text-xs font-semibold text-neutral-700">
              Judul Riset Kebijakan <span class="text-red-500">*</span>
            </Label>
            <Input
              id="title"
              v-model="form.title"
              @blur="autoSlug"
              placeholder="Contoh: Evaluasi Alokasi APBN untuk Transformasi Transisi Energi Terbarukan"
              required
              class="text-sm font-medium"
            />
            <p v-if="errors.title" class="text-xs text-red-600 font-medium">{{ errors.title[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="slug" class="text-xs font-semibold text-neutral-700">
              Slug URL (opsional, auto)
            </Label>
            <Input
              id="slug"
              v-model="form.slug"
              placeholder="evaluasi-alokasi-apbn-transisi-energi"
              class="text-xs font-mono"
            />
            <p v-if="errors.slug" class="text-xs text-red-600 font-medium">{{ errors.slug[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="sector" class="text-xs font-semibold text-neutral-700">
              Sektor Kebijakan <span class="text-red-500">*</span>
            </Label>
            <select
              id="sector"
              v-model="form.sector"
              required
              class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <option v-for="s in sectorOptions" :key="s.value" :value="s.value">
                {{ s.label }}
              </option>
            </select>
            <p v-if="errors.sector" class="text-xs text-red-600 font-medium">{{ errors.sector[0] }}</p>
          </div>
        </div>

        <!-- Penulis & Lembaga -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="author" class="text-xs font-semibold text-neutral-700">
              Penulis / Peneliti <span class="text-red-500">*</span>
            </Label>
            <Input
              id="author"
              v-model="form.author"
              placeholder="Contoh: Dr. Rizal Ramli & Tim Riset ISF"
              required
              class="text-xs"
            />
            <p v-if="errors.author" class="text-xs text-red-600 font-medium">{{ errors.author[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="institution" class="text-xs font-semibold text-neutral-700">
              Afiliasi Lembaga
            </Label>
            <Input
              id="institution"
              v-model="form.institution"
              placeholder="Contoh: ISF Economic Center & FEB UI"
              class="text-xs"
            />
            <p v-if="errors.institution" class="text-xs text-red-600 font-medium">{{ errors.institution[0] }}</p>
          </div>
        </div>

        <!-- Ringkasan Eksekutif -->
        <div class="space-y-1.5">
          <Label for="executive_summary" class="text-xs font-semibold text-neutral-700">
            Ringkasan Eksekutif <span class="text-red-500">*</span>
          </Label>
          <textarea
            id="executive_summary"
            v-model="form.executive_summary"
            rows="5"
            placeholder="Tuliskan analisis mendalam ringkas mengenai latar belakang, temuan empiris, dan urgensi kebijakan..."
            required
            class="w-full rounded-md border border-neutral-300 p-3 text-xs leading-relaxed text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 font-sans"
          ></textarea>
          <p v-if="errors.executive_summary" class="text-xs text-red-600 font-medium">{{ errors.executive_summary[0] }}</p>
        </div>

        <!-- Rekomendasi Kunci Kebijakan -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <Label class="text-xs font-semibold text-neutral-700">
              Poin Rekomendasi Kunci Kebijakan
            </Label>
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="addRecommendation"
              class="text-[11px] h-7 px-2.5 inline-flex items-center gap-1"
            >
              <IconComponent name="IconPlus" class="w-3.5 h-3.5" />
              <span>Tambah Rekomendasi</span>
            </Button>
          </div>
          <div class="space-y-2">
            <div
              v-for="(rec, idx) in form.key_recommendations"
              :key="idx"
              class="flex items-center gap-2"
            >
              <span class="w-6 text-center text-xs font-mono font-bold text-neutral-400">
                {{ idx + 1 }}.
              </span>
              <Input
                v-model="form.key_recommendations[idx]"
                placeholder="Tuliskan butir rekomendasi kebijakan yang konkrit..."
                class="text-xs flex-1"
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="removeRecommendation(idx)"
                class="h-8 w-8 p-0 text-neutral-400 hover:text-red-600 hover:bg-red-50"
              >
                <IconComponent name="IconTrash" class="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>
          <p v-if="errors.key_recommendations" class="text-xs text-red-600 font-medium">
            {{ errors.key_recommendations[0] }}
          </p>
        </div>

        <!-- Dokumen PDF & Ukuran File -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="pdf_path" class="text-xs font-semibold text-neutral-700">
              Tautan / Lokasi Berkas Dokumen PDF <span class="text-red-500">*</span>
            </Label>
            <Input
              id="pdf_path"
              v-model="form.pdf_path"
              placeholder="/storage/policy-briefs/isf-brief-ekonomi-2026.pdf atau https://..."
              required
              class="text-xs font-mono"
            />
            <p v-if="errors.pdf_path" class="text-xs text-red-600 font-medium">{{ errors.pdf_path[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="pdf_size" class="text-xs font-semibold text-neutral-700">
              Label Ukuran Berkas PDF
            </Label>
            <Input
              id="pdf_size"
              v-model="form.pdf_size"
              placeholder="Contoh: 2.4 MB PDF"
              class="text-xs"
            />
            <p v-if="errors.pdf_size" class="text-xs text-red-600 font-medium">{{ errors.pdf_size[0] }}</p>
          </div>
        </div>

        <!-- Tanggal Terbit, Urutan, Status -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-neutral-100">
          <div class="space-y-1.5">
            <Label for="published_at" class="text-xs font-semibold text-neutral-700">
              Tanggal Terbit <span class="text-red-500">*</span>
            </Label>
            <Input
              id="published_at"
              v-model="form.published_at"
              type="date"
              required
              class="text-xs"
            />
            <p v-if="errors.published_at" class="text-xs text-red-600 font-medium">{{ errors.published_at[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="order" class="text-xs font-semibold text-neutral-700">
              Urutan Prioritas
            </Label>
            <Input
              id="order"
              v-model.number="form.order"
              type="number"
              min="0"
              placeholder="0"
              class="text-xs font-mono"
            />
            <p v-if="errors.order" class="text-xs text-red-600 font-medium">{{ errors.order[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="status" class="text-xs font-semibold text-neutral-700">
              Status Publikasi <span class="text-red-500">*</span>
            </Label>
            <select
              id="status"
              v-model="form.status"
              required
              class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <option value="terbit">Terbit (Aktif)</option>
              <option value="draft">Draft (Arsip)</option>
            </select>
            <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-neutral-100">
          <Link href="/cms/policy-brief">
            <Button type="button" variant="outline" class="text-xs">
              Batal
            </Button>
          </Link>
          <Button
            type="submit"
            :disabled="saving"
            class="text-xs font-semibold inline-flex items-center gap-2"
          >
            <IconComponent v-if="saving" name="IconRefresh" class="w-4 h-4 animate-spin" />
            <IconComponent v-else name="IconDeviceFloppy" class="w-4 h-4" />
            <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Policy Brief' : 'Simpan Policy Brief') }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

