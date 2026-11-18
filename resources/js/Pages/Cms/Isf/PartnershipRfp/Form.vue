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
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'
import { IconBrandWhatsapp, IconMail, IconClock, IconUserCheck } from '@tabler/icons-vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: {
    type: Number,
    default: null,
  },
})

const isEdit = computed(() => Boolean(props.id))

const form = ref({
  institution_name: '',
  pic_name: '',
  email: '',
  phone: '',
  partnership_type: 'banner',
  status: 'baru',
  message: '',
  internal_notes: '',
})

const proposalDetails = ref(null)
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

const partnershipTypeOptions = [
  { value: 'banner', label: 'Display Banner Strategis' },
  { value: 'advertorial', label: 'Advertorial & Thought Leadership' },
  { value: 'event', label: 'Executive Roundtable / Event' },
  { value: 'riset', label: 'Sponsorship Riset & Policy Brief' },
  { value: 'lainnya', label: 'Format Kolaborasi Lainnya' },
]

const statusOptions = [
  { value: 'baru', label: 'Baru Masuk' },
  { value: 'dihubungi', label: 'Sedang Dihubungi' },
  { value: 'selesai', label: 'Selesai / Jadi Mitra' },
  { value: 'ditolak', label: 'Ditolak' },
]

function formatPhoneForWa(phone) {
  if (!phone) return ''
  let cleaned = String(phone).replace(/\D/g, '')
  if (cleaned.startsWith('0')) {
    cleaned = '62' + cleaned.slice(1)
  }
  return cleaned
}

const whatsappUrl = computed(() => {
  const num = formatPhoneForWa(form.value.phone)
  if (!num) return '#'
  const text = encodeURIComponent(
    `Halo ${form.value.pic_name}, kami dari Tim Kemitraan Indonesia Strategy Forum (ISF) menindaklanjuti proposal kemitraan ${form.value.institution_name}. Apakah ada waktu luang untuk berdiskusi?`
  )
  return `https://wa.me/${num}?text=${text}`
})

const mailtoUrl = computed(() => {
  if (!form.value.email) return '#'
  const subject = encodeURIComponent(`Tindak Lanjut Proposal Kemitraan ISF — ${form.value.institution_name}`)
  return `mailto:${form.value.email}?subject=${subject}`
})

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.kemitraan.get(props.id)
    proposalDetails.value = data
    form.value = {
      institution_name: data.institution_name || '',
      pic_name: data.pic_name || '',
      email: data.email || '',
      phone: data.phone || '',
      partnership_type: data.partnership_type || 'banner',
      status: data.status || 'baru',
      message: data.message || '',
      internal_notes: data.internal_notes || '',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat detail proposal kemitraan.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  const payload = {
    institution_name: form.value.institution_name.trim(),
    pic_name: form.value.pic_name.trim(),
    email: form.value.email.trim(),
    phone: form.value.phone.trim(),
    partnership_type: form.value.partnership_type,
    status: form.value.status,
    message: form.value.message.trim(),
    internal_notes: form.value.internal_notes ? form.value.internal_notes.trim() : null,
  }

  try {
    if (isEdit.value) {
      await cmsResources.kemitraan.update(props.id, payload)
      alertSuccess('Data kemitraan berhasil diperbarui.')
    } else {
      await cmsResources.kemitraan.create(payload)
      alertSuccess('Permintaan kemitraan baru berhasil ditambahkan.')
    }
    router.visit('/cms/kemitraan')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan data kemitraan.'))
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
  <Head :title="isEdit ? 'Edit Permintaan Kemitraan — Indonesia Strategy Forum' : 'Tambah Kemitraan — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kemitraan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Permintaan Kemitraan' : 'Tambah Permintaan Kemitraan' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data proposal kemitraan #${props.id}` : 'Mencatat pengajuan kemitraan strategis baru secara langsung dari dashboard.' }}
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kemitraan">
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
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Kemitraan' : 'Simpan Kemitraan') }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir kemitraan..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Cards -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Card 1: Informasi Institusi & Narahubung -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Institusi &amp; Narahubung (PIC)</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Data identitas korporasi, instansi pemerintah, atau asosiasi dan narahubung resmi.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nama Institusi -->
            <div class="space-y-1.5">
              <Label for="institution_name" class="text-xs font-medium text-foreground">
                Nama Institusi / Perusahaan *
              </Label>
              <Input
                id="institution_name"
                v-model="form.institution_name"
                placeholder="Contoh: PT Solusi Strategis Indonesia / Kementerian X"
                required
                class="text-xs"
              />
              <p v-if="errors.institution_name" class="text-xs text-red-600 font-medium">{{ errors.institution_name[0] }}</p>
            </div>

            <!-- Nama PIC -->
            <div class="space-y-1.5">
              <Label for="pic_name" class="text-xs font-medium text-foreground">
                Nama Lengkap PIC / Narahubung *
              </Label>
              <Input
                id="pic_name"
                v-model="form.pic_name"
                placeholder="Contoh: Dian Pratama, S.H."
                required
                class="text-xs"
              />
              <p v-if="errors.pic_name" class="text-xs text-red-600 font-medium">{{ errors.pic_name[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Email -->
            <div class="space-y-1.5">
              <Label for="email" class="text-xs font-medium text-foreground">
                Alamat Email Resmi *
              </Label>
              <Input
                id="email"
                type="email"
                v-model="form.email"
                placeholder="Contoh: dian.pratama@institusi.go.id"
                required
                class="text-xs"
              />
              <p v-if="errors.email" class="text-xs text-red-600 font-medium">{{ errors.email[0] }}</p>
            </div>

            <!-- Telepon / WhatsApp -->
            <div class="space-y-1.5">
              <Label for="phone" class="text-xs font-medium text-foreground">
                Nomor Telepon / WhatsApp *
              </Label>
              <Input
                id="phone"
                type="tel"
                v-model="form.phone"
                placeholder="Contoh: 081234567890"
                required
                class="text-xs"
              />
              <p v-if="errors.phone" class="text-xs text-red-600 font-medium">{{ errors.phone[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Card 2: Spesifikasi & Uraian Kemitraan -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Spesifikasi &amp; Uraian Kemitraan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Pilihan format saluran kemitraan, status penanganan, dan rincian kebutuhan mitra.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Jenis Kemitraan -->
            <div class="space-y-1.5">
              <Label for="partnership_type" class="text-xs font-medium text-foreground">
                Saluran Kemitraan *
              </Label>
              <Select v-model="form.partnership_type">
                <SelectTrigger id="partnership_type" class="h-9 text-xs font-medium">
                  <SelectValue placeholder="Pilih Jenis Kemitraan..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="opt in partnershipTypeOptions"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs font-medium"
                  >
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.partnership_type" class="text-xs text-red-600 font-medium">{{ errors.partnership_type[0] }}</p>
            </div>

            <!-- Status -->
            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium text-foreground">
                Status Kemitraan *
              </Label>
              <Select v-model="form.status">
                <SelectTrigger id="status" class="h-9 text-xs font-medium">
                  <SelectValue placeholder="Pilih Status..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="st in statusOptions"
                    :key="st.value"
                    :value="st.value"
                    class="text-xs font-medium"
                  >
                    {{ st.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
            </div>
          </div>

          <!-- Pesan / Kebutuhan -->
          <div class="space-y-1.5">
            <Label for="message" class="text-xs font-medium text-foreground">
              Uraian Kebutuhan &amp; Sasaran Strategis *
            </Label>
            <textarea
              id="message"
              v-model="form.message"
              rows="4"
              required
              placeholder="Tuliskan sasaran kemitraan, target pembaca spesifik, periode pelaksanaan, atau topik kebijakan yang ingin diangkat..."
              class="w-full rounded-md border border-input bg-background text-foreground p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.message" class="text-xs text-red-600 font-medium">{{ errors.message[0] }}</p>
          </div>

          <!-- Catatan Internal -->
          <div class="space-y-1.5 pt-2">
            <Label for="internal_notes" class="text-xs font-medium text-foreground">
              Catatan Internal Tim Kemitraan (Hanya Terlihat oleh Admin)
            </Label>
            <textarea
              id="internal_notes"
              v-model="form.internal_notes"
              rows="3"
              placeholder="Catatan perkembangan diskusi dengan PIC, estimasi deal, tindak lanjut berikutnya..."
              class="w-full rounded-md border border-input bg-background text-foreground p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.internal_notes" class="text-xs text-red-600 font-medium">{{ errors.internal_notes[0] }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Card 3: Tindakan Cepat & Metadata (Hanya Saat Edit) -->
      <Card v-if="isEdit" class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Tindakan Narahubung &amp; Riwayat Proposal</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Aksi langsung menghubungi PIC dan rekaman jejak pengajuan proposal kemitraan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3">
            <a
              v-if="form.phone"
              :href="whatsappUrl"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Button
                type="button"
                class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium inline-flex items-center gap-2 h-9"
              >
                <IconBrandWhatsapp class="w-4 h-4" />
                <span>Hubungi via WhatsApp ({{ form.phone }})</span>
              </Button>
            </a>

            <a
              v-if="form.email"
              :href="mailtoUrl"
            >
              <Button
                type="button"
                variant="outline"
                class="text-xs font-medium inline-flex items-center gap-2 h-9"
              >
                <IconMail class="w-4 h-4" />
                <span>Kirim Email Resmi ({{ form.email }})</span>
              </Button>
            </a>
          </div>

          <!-- Metadata Box -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-border text-xs text-muted-foreground">
            <div>
              <span class="font-medium text-foreground block mb-0.5">Waktu Pengajuan:</span>
              <div class="flex items-center gap-1.5">
                <IconClock class="w-3.5 h-3.5" />
                <span>{{ proposalDetails?.created_at_formatted || '-' }}</span>
              </div>
            </div>

            <div>
              <span class="font-medium text-foreground block mb-0.5">Terakhir Dihubungi:</span>
              <div class="flex items-center gap-1.5">
                <IconUserCheck class="w-3.5 h-3.5" />
                <span>{{ proposalDetails?.contacted_at_formatted || 'Belum dihubungi' }}</span>
              </div>
            </div>

            <div>
              <span class="font-medium text-foreground block mb-0.5">Penanggung Jawab:</span>
              <span>{{ proposalDetails?.contacted_by?.name || proposalDetails?.contactedBy?.name || '-' }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

