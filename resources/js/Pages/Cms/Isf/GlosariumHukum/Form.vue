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
  istilah: '',
  slug: '',
  abjad: '',
  definisi: '',
  dasar_hukum: '',
  istilah_terkait: [],
  urutan: 0,
  status: 'terbit',
})

const rawRelatedTerms = ref('')
const masterIstilahList = ref([])
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function syncAbjad() {
  if (form.value.istilah && !form.value.abjad) {
    form.value.abjad = form.value.istilah.trim().charAt(0).toUpperCase()
  }
}

function onSelectMasterIstilah(chosen) {
  if (!chosen) return
  form.value.istilah = chosen
  const found = masterIstilahList.value.find((t) => (t.istilah || t.nama) === chosen)
  if (found) {
    form.value.abjad = found.abjad || chosen.charAt(0).toUpperCase()
    if (found.keterangan && !form.value.definisi) {
      form.value.definisi = found.keterangan
    }
  } else {
    syncAbjad()
  }
}

function addRelatedTerm(termName) {
  const current = rawRelatedTerms.value
    ? rawRelatedTerms.value.split(',').map((s) => s.trim()).filter(Boolean)
    : []
  if (!current.includes(termName)) {
    current.push(termName)
    rawRelatedTerms.value = current.join(', ')
  }
}

async function loadMasterIstilah() {
  try {
    const res = await cmsResources.istilahHukum.list({ per_page: 200 })
    masterIstilahList.value = Array.isArray(res) ? res : (res?.data || [])
  } catch {}
}

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.glosariumHukum.get(props.id)
    form.value = {
      istilah: data.istilah || '',
      slug: data.slug || '',
      abjad: data.abjad || '',
      definisi: data.definisi || '',
      dasar_hukum: data.dasar_hukum || '',
      istilah_terkait: Array.isArray(data.istilah_terkait) ? data.istilah_terkait : [],
      urutan: Number(data.urutan) || 0,
      status: data.status || 'terbit',
    }
    rawRelatedTerms.value = form.value.istilah_terkait.join(', ')
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data istilah glosarium.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  // Parse comma-separated related terms
  const related = rawRelatedTerms.value
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean)

  const payload = {
    ...form.value,
    abjad: form.value.abjad ? form.value.abjad.toUpperCase().charAt(0) : (form.value.istilah.trim().charAt(0).toUpperCase() || 'A'),
    istilah_terkait: related,
    urutan: Number(form.value.urutan) || 0,
  }

  try {
    if (isEdit.value) {
      await cmsResources.glosariumHukum.update(props.id, payload)
      alertSuccess('Istilah glosarium berhasil diperbarui.')
    } else {
      await cmsResources.glosariumHukum.create(payload)
      alertSuccess('Istilah glosarium baru berhasil ditambahkan.')
    }
    router.visit('/cms/glosarium-hukum')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan istilah glosarium.'))
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadMasterIstilah()
  loadData()
})
</script>

<template>
  <Head :title="isEdit ? 'Edit Glosarium Hukum — Indonesia Strategy Forum' : 'Tambah Glosarium Hukum — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/glosarium-hukum">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Glosarium Hukum' : 'Tambah Glosarium Hukum' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data entri glosarium hukum #${props.id}` : 'Tambahkan entri kamus istilah hukum interaktif dan dasar hukum perundang-undangan.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/glosarium-hukum">
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
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Glosarium' : 'Simpan Glosarium') }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data istilah glosarium..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Form Content in Cards -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Istilah & Abjad</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Pilih dari master istilah atau ketik istilah hukum baru beserta inisial abjadnya.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Istilah with dropdown from master istilah -->
            <div class="space-y-1.5 sm:col-span-2">
              <Label for="istilah" class="text-xs font-medium text-foreground">
                Kata / Istilah Hukum <span class="text-red-500">*</span>
              </Label>
              <Select
                :model-value="form.istilah"
                @update:model-value="onSelectMasterIstilah"
              >
                <SelectTrigger id="istilah" class="h-9 text-xs font-medium">
                  <SelectValue placeholder="Pilih Kata / Istilah Hukum..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-if="form.istilah && !masterIstilahList.some((t) => (t.istilah || t.nama) === form.istilah)"
                    :value="form.istilah"
                    class="text-xs font-medium"
                  >
                    {{ form.istilah }} (Tersimpan)
                  </SelectItem>
                  <SelectItem
                    v-for="t in masterIstilahList"
                    :key="t.id"
                    :value="t.istilah || t.nama"
                    class="text-xs font-medium"
                  >
                    {{ t.istilah || t.nama }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.istilah" class="text-xs text-red-600 font-medium">{{ errors.istilah[0] }}</p>
            </div>

            <!-- Inisial Abjad -->
            <div class="space-y-1.5">
              <Label for="abjad" class="text-xs font-medium text-foreground">
                Inisial Abjad <span class="text-red-500">*</span>
              </Label>
              <Input
                id="abjad"
                v-model="form.abjad"
                maxlength="2"
                placeholder="A-Z"
                class="h-9 text-xs font-mono uppercase text-center font-bold"
              />
              <p v-if="errors.abjad" class="text-xs text-red-600 font-medium">{{ errors.abjad[0] }}</p>
            </div>
          </div>

          <!-- Definisi Lengkap -->
          <div class="space-y-1.5">
            <Label for="definisi" class="text-xs font-medium text-foreground">
              Definisi Yuridis / Makna Istilah <span class="text-red-500">*</span>
            </Label>
            <textarea
              id="definisi"
              v-model="form.definisi"
              required
              rows="4"
              placeholder="Jelaskan definisi istilah secara komprehensif, jelas, dan mudah dipahami publik..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring leading-relaxed"
            ></textarea>
            <p v-if="errors.definisi" class="text-xs text-red-600 font-medium">{{ errors.definisi[0] }}</p>
          </div>

          <!-- Dasar Hukum & Istilah Terkait -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Dasar Hukum -->
            <div class="space-y-1.5">
              <Label for="dasar_hukum" class="text-xs font-medium text-foreground">
                Dasar Hukum / Peraturan Rujukan
              </Label>
              <Input
                id="dasar_hukum"
                v-model="form.dasar_hukum"
                placeholder="Contoh: Pasal 77 KUHAP, UU No. 24 Tahun 2003"
                class="h-9 text-xs"
              />
              <p v-if="errors.dasar_hukum" class="text-xs text-red-600 font-medium">{{ errors.dasar_hukum[0] }}</p>
            </div>

            <!-- Istilah Terkait -->
            <div class="space-y-1.5">
              <Label for="istilah_terkait" class="text-xs font-medium text-foreground">
                Istilah Terkait (Pisahkan Koma)
              </Label>
              <Input
                id="istilah_terkait"
                v-model="rawRelatedTerms"
                placeholder="Contoh: KUHAP, Tersangka, Penahanan"
                class="h-9 text-xs"
              />
              <!-- Quick tags from master istilah -->
              <div v-if="masterIstilahList.length > 0" class="flex flex-wrap gap-1 pt-1">
                <span class="text-[10px] text-muted-foreground mr-1">Klik untuk tambah:</span>
                <button
                  v-for="mi in masterIstilahList.slice(0, 6)"
                  :key="mi.id"
                  type="button"
                  @click="addRelatedTerm(mi.istilah)"
                  class="text-[10px] px-1.5 py-0.5 rounded border border-border bg-muted/40 hover:bg-muted text-muted-foreground hover:text-foreground transition"
                >
                  + {{ mi.istilah }}
                </button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Status Publikasi -->
      <Card class="bg-card border-border shadow-sm">
        <CardContent class="py-4">
          <div class="flex items-center justify-between">
            <div>
              <Label class="text-xs font-medium text-foreground">Status Publikasi</Label>
              <p class="text-[11px] text-muted-foreground">Hanya istilah berstatus "Terbit" yang ditampilkan di landing page Glosarium Hukum.</p>
            </div>
            <StatusSelectWithBadge v-model="form.status" />
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>
