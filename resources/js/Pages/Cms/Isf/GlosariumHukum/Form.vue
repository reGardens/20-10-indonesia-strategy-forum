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
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function syncAbjad() {
  if (form.value.istilah && !form.value.abjad) {
    form.value.abjad = form.value.istilah.trim().charAt(0).toUpperCase()
  }
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
  loadData()
})
</script>

<template>
  <Head :title="isEdit ? 'Edit Istilah Glosarium — Indonesia Strategy Forum' : 'Tambah Istilah Glosarium — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          {{ isEdit ? 'Edit Istilah Glosarium' : 'Tambah Istilah Glosarium' }}
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kamus istilah hukum interaktif, definisi yuridis, dasar hukum, dan istilah terkait.
        </p>
      </div>
      <Link href="/cms/glosarium-hukum">
        <Button variant="outline" size="sm" class="inline-flex items-center gap-1.5">
          <IconComponent name="ArrowLeft" :size="14" />
          <span>Kembali</span>
        </Button>
      </Link>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir istilah..." />

    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Card -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6 bg-card p-6 sm:p-8 rounded-xl border border-border shadow-xs">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
        <!-- Istilah / Term Name (Col 8) -->
        <div class="md:col-span-8 space-y-1.5">
          <Label for="istilah" class="font-medium text-xs">
            Istilah / Konsep Hukum <span class="text-red-500">*</span>
          </Label>
          <Input
            id="istilah"
            v-model="form.istilah"
            @blur="syncAbjad"
            placeholder="Contoh: Kasasi, Inkracht, Mens Rea"
            required
          />
          <p v-if="errors.istilah" class="text-xs text-red-600 mt-1">
            {{ errors.istilah[0] }}
          </p>
        </div>

        <!-- Abjad Letter (Col 4) -->
        <div class="md:col-span-4 space-y-1.5">
          <Label for="abjad" class="font-medium text-xs">
            Huruf Indeks Abjad (A–Z)
          </Label>
          <Input
            id="abjad"
            v-model="form.abjad"
            maxlength="1"
            placeholder="Otomatis dari huruf pertama istilah"
            class="uppercase font-mono text-center font-bold"
          />
          <p v-if="errors.abjad" class="text-xs text-red-600 mt-1">
            {{ errors.abjad[0] }}
          </p>
        </div>

        <!-- Slug (Col 12) -->
        <div class="md:col-span-12 space-y-1.5">
          <Label for="slug" class="font-medium text-xs">
            Slug / Tautan Anchor
          </Label>
          <Input
            id="slug"
            v-model="form.slug"
            placeholder="Dihasilkan otomatis jika dikosongkan (contoh: kasasi)"
          />
          <p class="text-[11px] text-muted-foreground">
            Digunakan untuk tautan langsung di halaman publik: /hukum#istilah-{{ form.slug || 'slug' }}
          </p>
          <p v-if="errors.slug" class="text-xs text-red-600 mt-1">
            {{ errors.slug[0] }}
          </p>
        </div>

        <!-- Definisi Lengkap (Col 12) -->
        <div class="md:col-span-12 space-y-1.5">
          <Label for="definisi" class="font-medium text-xs">
            Definisi & Penjelasan Yuridis <span class="text-red-500">*</span>
          </Label>
          <textarea
            id="definisi"
            v-model="form.definisi"
            rows="4"
            required
            placeholder="Jelaskan arti, ruang lingkup, dan konteks penggunaan istilah hukum ini..."
            class="w-full rounded-md border border-input bg-background text-foreground p-3 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
          ></textarea>
          <p v-if="errors.definisi" class="text-xs text-red-600 mt-1">
            {{ errors.definisi[0] }}
          </p>
        </div>

        <!-- Dasar Hukum (Col 12) -->
        <div class="md:col-span-12 space-y-1.5">
          <Label for="dasar_hukum" class="font-medium text-xs">
            Dasar Hukum / Peraturan Perundang-undangan
          </Label>
          <Input
            id="dasar_hukum"
            v-model="form.dasar_hukum"
            placeholder="Contoh: Pasal 244 KUHAP jo. UU No. 14 Tahun 1985 tentang Mahkamah Agung"
          />
          <p v-if="errors.dasar_hukum" class="text-xs text-red-600 mt-1">
            {{ errors.dasar_hukum[0] }}
          </p>
        </div>

        <!-- Istilah Terkait (Col 12) -->
        <div class="md:col-span-12 space-y-1.5">
          <Label for="istilah_terkait" class="font-medium text-xs">
            Istilah Terkait (Pisahkan dengan koma)
          </Label>
          <Input
            id="istilah_terkait"
            v-model="rawRelatedTerms"
            placeholder="Contoh: Banding, Inkracht, Mahkamah Agung"
          />
          <p class="text-[11px] text-muted-foreground">
            Daftar istilah ini dapat diklik oleh pembaca untuk menelusuri istilah yang berhubungan.
          </p>
          <p v-if="errors.istilah_terkait" class="text-xs text-red-600 mt-1">
            {{ errors.istilah_terkait[0] }}
          </p>
        </div>

        <!-- Urutan Tampil (Col 6) -->
        <div class="md:col-span-6 space-y-1.5">
          <Label for="urutan" class="font-medium text-xs">
            Urutan Prioritas (Angka)
          </Label>
          <Input
            id="urutan"
            type="number"
            v-model.number="form.urutan"
            min="0"
            placeholder="0"
          />
          <p v-if="errors.urutan" class="text-xs text-red-600 mt-1">
            {{ errors.urutan[0] }}
          </p>
        </div>

        <!-- Status Publikasi (Col 6) -->
        <div class="md:col-span-6 space-y-1.5">
          <Label for="status" class="font-medium text-xs">
            Status Publikasi
          </Label>
          <StatusSelectWithBadge
            id="status"
            v-model="form.status"
          />
          <p v-if="errors.status" class="text-xs text-red-600 mt-1">
            {{ errors.status[0] }}
          </p>
        </div>
      </div>

      <!-- Submit / Action Buttons -->
      <div class="pt-5 border-t border-border flex items-center justify-end gap-3">
        <Link href="/cms/glosarium-hukum">
          <Button type="button" variant="outline" size="sm" :disabled="saving">
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
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Istilah' : 'Simpan Istilah') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>
