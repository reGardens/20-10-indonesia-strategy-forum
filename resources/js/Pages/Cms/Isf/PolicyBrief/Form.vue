<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
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
    form.value = {
      title: data.title || '',
      slug: data.slug || '',
      sector: data.sector || 'ekonomi',
      author: data.author || '',
      institution: data.institution || '',
      executive_summary: data.executive_summary || '',
      key_recommendations: Array.isArray(data.key_recommendations) && data.key_recommendations.length > 0
        ? data.key_recommendations
        : [''],
      pdf_path: data.pdf_path || '',
      pdf_size: data.pdf_size || '',
      published_at: data.published_at ? data.published_at.slice(0, 10) : new Date().toISOString().slice(0, 10),
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
    .map((r) => r.trim())
    .filter((r) => r.length > 0)

  const payload = {
    ...form.value,
    key_recommendations: cleanRecommendations.length > 0 ? cleanRecommendations : ['Belum ada rekomendasi spesifik.'],
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
  <Head :title="isEdit ? 'Edit Policy Brief — Indonesia Strategy Forum' : 'Tambah Policy Brief — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          {{ isEdit ? 'Edit Policy Brief' : 'Tambah Policy Brief' }}
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Ringkasan eksekutif riset kebijakan, rekomendasi strategis, dan dokumen PDF resmi.
        </p>
      </div>
      <Link href="/cms/policy-brief">
        <Button variant="outline" size="sm" class="inline-flex items-center gap-1.5">
          <IconComponent name="ArrowLeft" :size="14" />
          <span>Kembali</span>
        </Button>
      </Link>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir policy brief..." />

    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Card -->
    <div v-else class="bg-card rounded-xl border border-border p-6 sm:p-8 shadow-xs">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Judul & Slug -->
        <div class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="font-medium text-xs">
              Judul Riset / Policy Brief <span class="text-red-500">*</span>
            </Label>
            <Input
              id="title"
              v-model="form.title"
              @blur="autoSlug"
              placeholder="Contoh: Transisi Energi Berkeadilan: Evaluasi Pensiun Dini PLTU Batubara"
              required
            />
            <p v-if="errors.title" class="text-xs text-red-600 font-medium">{{ errors.title[0] }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="slug" class="font-medium text-xs">
                Slug URL / Identifikasi Dokumen
              </Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="transisi-energi-berkeadilan"
                class="font-mono text-xs"
              />
              <p v-if="errors.slug" class="text-xs text-red-600 font-medium">{{ errors.slug[0] }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="sector" class="font-medium text-xs">
                Sektor Kebijakan <span class="text-red-500">*</span>
              </Label>
              <select
                id="sector"
                v-model="form.sector"
                required
                class="w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-xs font-medium focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
              >
                <option v-for="sec in sectorOptions" :key="sec.value" :value="sec.value">
                  {{ sec.label }}
                </option>
              </select>
              <p v-if="errors.sector" class="text-xs text-red-600 font-medium">{{ errors.sector[0] }}</p>
            </div>
          </div>
        </div>

        <!-- Penulis & Lembaga -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="author" class="font-medium text-xs">
              Penulis Utama / Tim Peneliti
            </Label>
            <Input
              id="author"
              v-model="form.author"
              placeholder="Contoh: Tim Riset Kebijakan Energi ISF"
            />
            <p v-if="errors.author" class="text-xs text-red-600 font-medium">{{ errors.author[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="institution" class="font-medium text-xs">
              Institusi / Afiliasi
            </Label>
            <Input
              id="institution"
              v-model="form.institution"
              placeholder="Contoh: Indonesia Strategy Forum & FEB UI"
            />
            <p v-if="errors.institution" class="text-xs text-red-600 font-medium">{{ errors.institution[0] }}</p>
          </div>
        </div>

        <!-- Executive Summary -->
        <div class="space-y-1.5">
          <Label for="executive_summary" class="font-medium text-xs">
            Ringkasan Eksekutif (Executive Summary) <span class="text-red-500">*</span>
          </Label>
          <textarea
            id="executive_summary"
            v-model="form.executive_summary"
            rows="4"
            required
            placeholder="Tuliskan latar belakang masalah, urgensi kebijakan, dan temuan kunci riset..."
            class="w-full rounded-md border border-input bg-background text-foreground p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
          ></textarea>
          <p v-if="errors.executive_summary" class="text-xs text-red-600 font-medium">{{ errors.executive_summary[0] }}</p>
        </div>

        <!-- Rekomendasi Kunci -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <Label class="font-medium text-xs">
              Rekomendasi Kebijakan (Poin-Poin Strategis)
            </Label>
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="addRecommendation"
              class="text-xs inline-flex items-center gap-1"
            >
              <IconComponent name="Plus" :size="13" />
              <span>Tambah Poin</span>
            </Button>
          </div>

          <div class="space-y-2">
            <div
              v-for="(rec, rIdx) in form.key_recommendations"
              :key="rIdx"
              class="flex items-center gap-2"
            >
              <span class="w-6 h-6 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center font-bold text-xs shrink-0">
                {{ rIdx + 1 }}
              </span>
              <Input
                v-model="form.key_recommendations[rIdx]"
                placeholder="Tuliskan butir rekomendasi operasional atau regulasi..."
                class="flex-1 text-xs"
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="removeRecommendation(rIdx)"
                :disabled="form.key_recommendations.length <= 1"
                class="h-8 w-8 p-0 text-red-500 hover:text-red-600 hover:bg-red-500/10 shrink-0"
              >
                <IconComponent name="Trash" :size="14" />
              </Button>
            </div>
          </div>
          <p v-if="errors.key_recommendations" class="text-xs text-red-600 font-medium">{{ errors.key_recommendations[0] }}</p>
        </div>

        <!-- Dokumen PDF & Ukuran File -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="pdf_path" class="font-medium text-xs">
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
            <Label for="pdf_size" class="font-medium text-xs">
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-border">
          <div class="space-y-1.5">
            <Label for="published_at" class="font-medium text-xs">
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
            <Label for="order" class="font-medium text-xs">
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
            <Label for="status" class="font-medium text-xs">
              Status Publikasi <span class="text-red-500">*</span>
            </Label>
            <StatusSelectWithBadge
              id="status"
              v-model="form.status"
            />
            <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
          <Link href="/cms/policy-brief">
            <Button type="button" variant="outline" size="sm">
              Batal
            </Button>
          </Link>
          <Button
            type="submit"
            :disabled="saving"
            class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent v-if="saving" name="Refresh" :size="16" class="animate-spin" />
            <IconComponent v-else name="DeviceFloppy" :size="16" />
            <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Policy Brief' : 'Simpan Policy Brief') }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>
