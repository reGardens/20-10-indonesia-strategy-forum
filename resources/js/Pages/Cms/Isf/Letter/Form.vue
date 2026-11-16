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
  nama_pengirim: '',
  email_pengirim: '',
  kota_domisili: '',
  judul_surat: '',
  isi_surat: '',
  catatan_redaksi: '',
  status: 'pending',
})

const letterDetails = ref(null)
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.suratPembaca.get(props.id)
    letterDetails.value = data
    form.value = {
      nama_pengirim: data.nama_pengirim || '',
      email_pengirim: data.email_pengirim || '',
      kota_domisili: data.kota_domisili || '',
      judul_surat: data.judul_surat || '',
      isi_surat: data.isi_surat || '',
      catatan_redaksi: data.catatan_redaksi || '',
      status: data.status || 'pending',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat detail surat pembaca')
  } finally {
    loading.value = false
  }
}

async function handleSetStatus(newStatus) {
  form.value.status = newStatus
  await handleSubmit()
}

async function handleSubmit() {
  errors.value = {}
  saving.value = true
  try {
    const payload = {
      nama_pengirim: form.value.nama_pengirim.trim(),
      email_pengirim: form.value.email_pengirim.trim(),
      kota_domisili: form.value.kota_domisili.trim(),
      judul_surat: form.value.judul_surat.trim(),
      isi_surat: form.value.isi_surat.trim(),
      catatan_redaksi: form.value.catatan_redaksi ? form.value.catatan_redaksi.trim() : null,
      status: form.value.status,
    }

    if (isEdit.value) {
      await cmsResources.suratPembaca.update(props.id, payload)
      alertSuccess('Surat pembaca berhasil diperbarui / dimoderasi')
    } else {
      await cmsResources.suratPembaca.create(payload)
      alertSuccess('Surat pembaca manual berhasil ditambahkan')
    }

    router.visit('/cms/surat-pembaca')
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data?.errors || {}
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan surat pembaca'))
    }
  } finally {
    saving.value = false
  }
}

function formatDate(val) {
  if (!val) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(new Date(val))
  } catch {
    return val
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="isEdit ? 'Moderasi Surat Pembaca · CMS ISF' : 'Tambah Surat Manual · CMS ISF'" />

  <div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Link href="/cms/surat-pembaca">
          <Button variant="ghost" size="icon" class="h-9 w-9">
            <IconComponent name="IconArrowLeft" class="w-5 h-5" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Moderasi & Penyuntingan Surat Pembaca' : 'Tambah Surat Pembaca Manual' }}
          </h1>
          <p class="text-sm text-muted-foreground mt-0.5">
            Review isi naskah, lakukan penyuntingan editorial bila perlu, dan tentukan status tayang.
          </p>
        </div>
      </div>

      <!-- Quick Action Buttons on Edit -->
      <div v-if="isEdit" class="flex items-center gap-2">
        <Button
          type="button"
          variant="outline"
          class="border-emerald-600 text-emerald-600 hover:bg-emerald-50 text-xs gap-1.5"
          :disabled="saving || form.status === 'disetujui'"
          @click="handleSetStatus('disetujui')"
        >
          <IconComponent name="IconCheck" class="w-3.5 h-3.5" />
          <span>Setujui (Tayang)</span>
        </Button>
        <Button
          type="button"
          variant="outline"
          class="border-destructive text-destructive hover:bg-destructive/10 text-xs gap-1.5"
          :disabled="saving || form.status === 'ditolak'"
          @click="handleSetStatus('ditolak')"
        >
          <IconComponent name="IconX" class="w-3.5 h-3.5" />
          <span>Tolak</span>
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat naskah surat..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      action-label="Coba Lagi"
      @retry="loadData"
    />

    <!-- Metadata Submitter Box if Edit -->
    <div
      v-if="isEdit && letterDetails"
      class="bg-muted/40 rounded-lg border border-border p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs"
    >
      <div>
        <span class="text-muted-foreground block">Pengirim:</span>
        <strong class="text-foreground text-sm">{{ form.nama_pengirim }}</strong>
        <span class="text-muted-foreground block mt-0.5">{{ form.email_pengirim }}</span>
      </div>
      <div>
        <span class="text-muted-foreground block">Domisili / Kota:</span>
        <strong class="text-foreground">{{ form.kota_domisili }}</strong>
      </div>
      <div>
        <span class="text-muted-foreground block">Waktu Pengiriman:</span>
        <span class="text-foreground font-mono">{{ formatDate(letterDetails.created_at) }}</span>
        <div v-if="letterDetails.reviewed_at" class="mt-1 text-[11px] text-muted-foreground">
          Direview: {{ formatDate(letterDetails.reviewed_at) }}
        </div>
      </div>
    </div>

    <!-- Form -->
    <form v-if="!loading && !loadError" @submit.prevent="handleSubmit" class="space-y-6">
      <div class="bg-card rounded-lg border border-border p-6 space-y-5">
        <!-- Submitter Details -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <Label for="nama_pengirim">Nama Lengkap Pengirim *</Label>
            <Input
              id="nama_pengirim"
              v-model="form.nama_pengirim"
              placeholder="Nama pengirim surat"
              :class="errors.nama_pengirim ? 'border-destructive' : ''"
            />
            <p v-if="errors.nama_pengirim" class="text-xs text-destructive mt-1">
              {{ errors.nama_pengirim[0] }}
            </p>
          </div>

          <div>
            <Label for="email_pengirim">Alamat Email *</Label>
            <Input
              id="email_pengirim"
              v-model="form.email_pengirim"
              type="email"
              placeholder="email@example.com"
              :class="errors.email_pengirim ? 'border-destructive' : ''"
            />
            <p v-if="errors.email_pengirim" class="text-xs text-destructive mt-1">
              {{ errors.email_pengirim[0] }}
            </p>
          </div>

          <div>
            <Label for="kota_domisili">Kota / Domisili *</Label>
            <Input
              id="kota_domisili"
              v-model="form.kota_domisili"
              placeholder="Contoh: Jakarta Selatan"
              :class="errors.kota_domisili ? 'border-destructive' : ''"
            />
            <p v-if="errors.kota_domisili" class="text-xs text-destructive mt-1">
              {{ errors.kota_domisili[0] }}
            </p>
          </div>
        </div>

        <!-- Judul Surat -->
        <div>
          <Label for="judul_surat">Judul Surat Pembaca *</Label>
          <Input
            id="judul_surat"
            v-model="form.judul_surat"
            placeholder="Judul gagasan atau kritik surat"
            :class="errors.judul_surat ? 'border-destructive' : ''"
          />
          <p v-if="errors.judul_surat" class="text-xs text-destructive mt-1">
            {{ errors.judul_surat[0] }}
          </p>
        </div>

        <!-- Isi Surat -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <Label for="isi_surat">Isi Naskah Surat Pembaca *</Label>
            <span class="text-xs text-muted-foreground font-mono">
              {{ form.isi_surat.length }} karakter
            </span>
          </div>
          <textarea
            id="isi_surat"
            v-model="form.isi_surat"
            rows="8"
            placeholder="Tuliskan isi surat pembaca..."
            class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring leading-relaxed"
            :class="errors.isi_surat ? 'border-destructive' : ''"
          ></textarea>
          <p v-if="errors.isi_surat" class="text-xs text-destructive mt-1">
            {{ errors.isi_surat[0] }}
          </p>
        </div>

        <!-- Moderation Status & Editorial Notes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border">
          <!-- Status -->
          <div>
            <Label for="status">Status Moderasi *</Label>
            <select
              id="status"
              v-model="form.status"
              class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="pending">Pending (Menunggu Review)</option>
              <option value="disetujui">Disetujui (Tayang di Portal Publik)</option>
              <option value="ditolak">Ditolak (Tidak Layak Tayang)</option>
            </select>
            <p class="text-[11px] text-muted-foreground mt-1">
              Hanya status "Disetujui" yang akan ditampilkan kepada publik di rubrik Opini Publik.
            </p>
          </div>

          <!-- Editorial Note -->
          <div>
            <Label for="catatan_redaksi">Catatan Redaksi (Internal / Alasan Penolakan)</Label>
            <textarea
              id="catatan_redaksi"
              v-model="form.catatan_redaksi"
              rows="3"
              placeholder="Catatan editor terkait suntingan naskah atau alasan penolakan..."
              class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3">
        <Link href="/cms/surat-pembaca">
          <Button type="button" variant="outline" :disabled="saving">
            Batal
          </Button>
        </Link>
        <Button type="submit" :disabled="saving" class="gap-2">
          <IconComponent v-if="saving" name="IconLoader" class="w-4 h-4 animate-spin" />
          <IconComponent v-else name="IconCheck" class="w-4 h-4" />
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Moderasi' : 'Tambah Surat') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

