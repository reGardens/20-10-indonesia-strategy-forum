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
  question: '',
  slug: '',
  description: '',
  options: [
    { key: 'opt_1', label: '' },
    { key: 'opt_2', label: '' },
  ],
  start_date: new Date().toISOString().slice(0, 10),
  end_date: '',
  is_featured: false,
  status: 'aktif',
})

const existingPoll = ref(null)
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function autoSlug() {
  if (!isEdit.value && form.value.question && !form.value.slug) {
    form.value.slug = form.value.question
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
}

function addOption() {
  const nextIdx = form.value.options.length + 1
  form.value.options.push({
    key: `opt_${Date.now()}_${nextIdx}`,
    label: '',
  })
}

function removeOption(idx) {
  if (form.value.options.length <= 2) {
    alertError('Minimal harus ada 2 opsi jawaban.')
    return
  }
  form.value.options.splice(idx, 1)
}

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.readerPoll.get(props.id)
    existingPoll.value = data
    // Format options for form
    let opts = []
    if (Array.isArray(data.options)) {
      opts = data.options.map((opt, i) => {
        if (typeof opt === 'string') {
          return { key: `opt_${i + 1}`, label: opt }
        }
        return { key: opt.key || `opt_${i + 1}`, label: opt.label || opt.text || '' }
      })
    }
    if (opts.length < 2) {
      opts.push({ key: 'opt_1', label: '' })
      opts.push({ key: 'opt_2', label: '' })
    }

    form.value = {
      question: data.question || '',
      slug: data.slug || '',
      description: data.description || '',
      options: opts,
      start_date: data.start_date ? data.start_date.slice(0, 10) : '',
      end_date: data.end_date ? data.end_date.slice(0, 10) : '',
      is_featured: Boolean(data.is_featured),
      status: data.status || 'aktif',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat detail polling')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  errors.value = {}

  // Filter out blank options
  const cleanOptions = form.value.options
    .map(o => ({ key: o.key, label: o.label.trim() }))
    .filter(o => o.label.length > 0)

  if (cleanOptions.length < 2) {
    errors.value = { options: ['Minimal harus mengisi 2 opsi jawaban.'] }
    return
  }

  saving.value = true
  try {
    const payload = {
      question: form.value.question.trim(),
      slug: form.value.slug.trim(),
      description: form.value.description ? form.value.description.trim() : null,
      options: cleanOptions,
      start_date: form.value.start_date || null,
      end_date: form.value.end_date || null,
      is_featured: form.value.is_featured,
      status: form.value.status,
    }

    if (isEdit.value) {
      await cmsResources.readerPoll.update(props.id, payload)
      alertSuccess('Polling berhasil diperbarui')
    } else {
      await cmsResources.readerPoll.create(payload)
      alertSuccess('Polling berhasil ditambahkan')
    }

    router.visit('/cms/reader-poll')
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data?.errors || {}
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan polling'))
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
  <Head :title="isEdit ? 'Edit Reader Poll · CMS ISF' : 'Tambah Reader Poll · CMS ISF'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          {{ isEdit ? 'Edit Reader Poll' : 'Tambah Reader Poll' }}
        </h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Jajak pendapat publik interaktif dengan label ketat non-ilmiah.
        </p>
      </div>
      <Link href="/cms/reader-poll">
        <Button variant="outline" size="sm" class="inline-flex items-center gap-1.5">
          <IconComponent name="ArrowLeft" :size="14" />
          <span>Kembali</span>
        </Button>
      </Link>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat data polling..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      action-label="Coba Lagi"
      @retry="loadData"
    />

    <!-- Existing Results Box if Editing -->
    <div
      v-if="isEdit && existingPoll && existingPoll.total_votes > 0"
      class="bg-card rounded-lg border border-border p-5 space-y-3"
    >
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-foreground flex items-center gap-2">
          <IconComponent name="ChartBar" :size="16" class="text-primary" />
          Rekapitulasi Suara Terkini
        </h3>
        <span class="text-xs font-mono font-bold text-primary">
          Total: {{ existingPoll.total_votes }} Suara
        </span>
      </div>
      <div class="space-y-2">
        <div
          v-for="opt in existingPoll.options"
          :key="opt.key"
          class="space-y-1"
        >
          <div class="flex justify-between text-xs">
            <span class="font-medium text-foreground">{{ opt.label }}</span>
            <span class="font-mono text-muted-foreground">
              {{ opt.votes || 0 }} suara ({{ opt.percentage || 0 }}%)
            </span>
          </div>
          <div class="w-full bg-secondary h-2 rounded-full overflow-hidden">
            <div
              class="bg-primary h-full rounded-full transition-all"
              :style="{ width: `${opt.percentage || 0}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form v-if="!loading && !loadError" @submit.prevent="handleSubmit" class="space-y-6">
      <div class="bg-card rounded-lg border border-border p-6 space-y-5">
        <!-- Question -->
        <div>
          <Label for="question">Pertanyaan Polling *</Label>
          <Input
            id="question"
            v-model="form.question"
            placeholder="Contoh: Apakah Anda setuju dengan efisiensi anggaran Kementerian..."
            @blur="autoSlug"
            :class="errors.question ? 'border-destructive' : ''"
          />
          <p v-if="errors.question" class="text-xs text-destructive mt-1">
            {{ errors.question[0] }}
          </p>
        </div>

        <!-- Slug -->
        <div>
          <Label for="slug">Slug URL *</Label>
          <Input
            id="slug"
            v-model="form.slug"
            placeholder="efisiensi-anggaran-kementerian"
            :class="errors.slug ? 'border-destructive' : ''"
          />
          <p v-if="errors.slug" class="text-xs text-destructive mt-1">
            {{ errors.slug[0] }}
          </p>
        </div>

        <!-- Description -->
        <div>
          <Label for="description">Keterangan / Konteks Isu (Opsional)</Label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            placeholder="Berikan ringkasan latar belakang mengapa isu ini penting ditanyakan ke publik..."
            class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          ></textarea>
        </div>

        <!-- Dynamic Options -->
        <div class="space-y-3 pt-2 border-t border-border">
          <div class="flex items-center justify-between">
            <Label>Opsi Jawaban Polling * (Minimal 2 Opsi)</Label>
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="addOption"
              class="gap-1 text-xs"
            >
              <IconComponent name="Plus" :size="14" />
              <span>Tambah Opsi</span>
            </Button>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="(opt, idx) in form.options"
              :key="idx"
              class="flex items-center gap-2"
            >
              <span class="w-6 text-xs text-center font-mono text-muted-foreground">{{ idx + 1 }}.</span>
              <Input
                v-model="opt.label"
                :placeholder="`Teks opsi jawaban ${idx + 1}...`"
                class="flex-1"
              />
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-9 w-9 text-destructive hover:bg-destructive/10 shrink-0"
                :disabled="form.options.length <= 2"
                @click="removeOption(idx)"
              >
                <IconComponent name="Trash" :size="15" />
              </Button>
            </div>
          </div>
          <p v-if="errors.options" class="text-xs text-destructive mt-1">
            {{ errors.options[0] }}
          </p>
        </div>

        <!-- Dates & Status Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-border">
          <!-- Start Date -->
          <div>
            <Label for="start_date">Tanggal Mulai</Label>
            <Input
              id="start_date"
              v-model="form.start_date"
              type="date"
            />
          </div>

          <!-- End Date -->
          <div>
            <Label for="end_date">Tanggal Selesai (Opsional)</Label>
            <Input
              id="end_date"
              v-model="form.end_date"
              type="date"
            />
          </div>

          <!-- Status -->
          <div>
            <Label for="status">Status Publikasi</Label>
            <select
              id="status"
              v-model="form.status"
              class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="aktif">Aktif (Bisa Divote)</option>
              <option value="selesai">Selesai (Hanya Hasil)</option>
              <option value="draf">Draf (Tersimpan)</option>
            </select>
          </div>
        </div>

        <!-- Featured Toggle -->
        <div class="flex items-center gap-3 pt-2">
          <input
            id="is_featured"
            v-model="form.is_featured"
            type="checkbox"
            class="h-4 w-4 rounded border-input text-primary focus:ring-ring"
          />
          <Label for="is_featured" class="cursor-pointer text-xs sm:text-sm">
            Tampilkan sebagai Polling Utama (Featured) di Landing Page
          </Label>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
        <Link href="/cms/reader-poll">
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
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Buat Polling') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

