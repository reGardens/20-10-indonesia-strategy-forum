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
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue,
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
  published_at: new Date().toISOString().slice(0, 10),
  order: 0,
  status: 'terbit',
})

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

const sectorOptions = ref([
  { value: 'ekonomi', label: 'Ekonomi & Fiskal' },
  { value: 'kesehatan', label: 'Kesehatan Masyarakat' },
  { value: 'pendidikan', label: 'Pendidikan & Riset' },
  { value: 'pertahanan', label: 'Pertahanan & Keamanan' },
  { value: 'lingkungan', label: 'Lingkungan & Energi' },
])

async function loadSectors() {
  try {
    const res = await cmsResources.kategoriSektor.list({ per_page: 100 })
    const list = Array.isArray(res) ? res : (res?.data || [])
    if (list.length > 0) {
      sectorOptions.value = list.map((s) => ({ value: s.slug, label: s.nama }))
      if (!form.value.sector && sectorOptions.value.length > 0) {
        form.value.sector = sectorOptions.value[0].value
      }
    }
  } catch {}
}

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
  loadSectors()
  loadData()
})
</script>

<template>
  <Head :title="isEdit ? 'Edit Policy Brief — Indonesia Strategy Forum' : 'Tambah Policy Brief — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/policy-brief">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Policy Brief' : 'Tambah Policy Brief' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data risalah kebijakan #${props.id}` : 'Ringkasan eksekutif riset kebijakan, rekomendasi strategis, dan dokumen PDF resmi.' }}
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/policy-brief">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSubmit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Policy Brief' : 'Simpan Policy Brief') }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir policy brief..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Card -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Utama Dokumen</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Judul riset, identifikasi dokumen, dan pengelompokan sektor kebijakan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Judul -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">
              Judul Riset / Policy Brief *
            </Label>
            <Input
              id="title"
              v-model="form.title"
              @blur="autoSlug"
              placeholder="Contoh: Transisi Energi Berkeadilan: Evaluasi Pensiun Dini PLTU Batubara"
              required
              class="text-xs"
            />
            <p v-if="errors.title" class="text-xs text-red-600 font-medium">{{ errors.title[0] }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Slug -->
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium text-foreground">
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

            <!-- Sektor (Dynamic) -->
            <div class="space-y-1.5">
              <Label for="sector" class="text-xs font-medium text-foreground">
                Sektor Kebijakan *
              </Label>
              <Select v-model="form.sector">
                <SelectTrigger id="sector" class="h-9 text-xs font-medium">
                  <SelectValue placeholder="Pilih Sektor Kebijakan..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="sec in sectorOptions"
                    :key="sec.value"
                    :value="sec.value"
                    class="text-xs font-medium"
                  >
                    {{ sec.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.sector" class="text-xs text-red-600 font-medium">{{ errors.sector[0] }}</p>
            </div>
          </div>

          <!-- Penulis & Lembaga -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="author" class="text-xs font-medium text-foreground">
                Penulis Utama / Tim Peneliti
              </Label>
              <Input
                id="author"
                v-model="form.author"
                placeholder="Contoh: Tim Riset Kebijakan Energi ISF"
                class="text-xs"
              />
              <p v-if="errors.author" class="text-xs text-red-600 font-medium">{{ errors.author[0] }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="institution" class="text-xs font-medium text-foreground">
                Institusi / Afiliasi
              </Label>
              <Input
                id="institution"
                v-model="form.institution"
                placeholder="Contoh: Indonesia Strategy Forum & FEB UI"
                class="text-xs"
              />
              <p v-if="errors.institution" class="text-xs text-red-600 font-medium">{{ errors.institution[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Card 2: Konten & Rekomendasi -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Ringkasan & Rekomendasi Kebijakan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Latar belakang masalah dan butir-butir rekomendasi strategis bagi pengambil keputusan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Executive Summary -->
          <div class="space-y-1.5">
            <Label for="executive_summary" class="text-xs font-medium text-foreground">
              Ringkasan Eksekutif (Executive Summary) *
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
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <Label class="text-xs font-medium text-foreground">
                Rekomendasi Kebijakan (Poin-Poin Strategis)
              </Label>
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addRecommendation"
                class="text-xs inline-flex items-center gap-1 h-8"
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
        </CardContent>
      </Card>

      <!-- Card 3: Dokumen & Status Publikasi -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Tautan Dokumen & Pengaturan Tayang</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Tautan file risalah dokumen dan status publikasi (ukuran berkas telah ditiadakan sesuai instruksi).
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Dokumen PDF (Hanya Link Google Drive) -->
          <div class="space-y-1.5">
            <Label for="pdf_path" class="text-xs font-medium text-foreground">
              Link Google Drive Dokumen PDF *
            </Label>
            <Input
              id="pdf_path"
              v-model="form.pdf_path"
              placeholder="https://drive.google.com/file/d/.../view atau tautan GDrive lainnya"
              required
              class="text-xs font-mono"
            />
            <p class="text-[11px] text-muted-foreground">
              Wajib menggunakan tautan Google Drive file PDF dokumen. Upload file langsung tidak diizinkan.
            </p>
            <p v-if="errors.pdf_path" class="text-xs text-red-600 font-medium">{{ errors.pdf_path[0] }}</p>
          </div>

          <!-- Tanggal Terbit, Urutan, Status -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-border">
            <div class="space-y-1.5">
              <Label for="published_at" class="text-xs font-medium text-foreground">
                Tanggal Terbit *
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
              <Label for="order" class="text-xs font-medium text-foreground">
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
              <Label for="status" class="text-xs font-medium text-foreground">
                Status Publikasi *
              </Label>
              <StatusSelectWithBadge
                id="status"
                v-model="form.status"
              />
              <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom action buttons -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/policy-brief">
          <Button type="button" variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Policy Brief' : 'Simpan Policy Brief') }}
        </Button>
      </div>
    </form>
  </div>
</template>
