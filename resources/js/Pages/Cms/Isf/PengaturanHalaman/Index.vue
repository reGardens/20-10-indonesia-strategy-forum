<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { settings, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
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

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)

const form = ref({
  beranda: {
    topBar: {
      badge: 'Sorotan',
      announcement: 'Analisis dampak pengesahan regulasi baru bagi dunia usaha dan kepatuhan korporasi.',
      info: 'Diperbarui setiap Senin'
    },
    pekanIni: {
      dateStart: '2026-10-12',
      dateEnd: '2026-10-18'
    }
  },
  anggota: {
    name: 'Anindya Putri',
    role: 'Head of Legal & Compliance',
    organization: 'PT Nusantara Strategic Investama',
    plan: 'Enterprise Intelligence',
    sektor: ['Perbankan & Keuangan', 'Energi & Tambang', 'Infrastruktur']
  }
})

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await settings.get()
    if (data?.beranda) form.value.beranda = data.beranda
    if (data?.anggota) form.value.anggota = data.anggota
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat pengaturan halaman.')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const res = await settings.put(form.value)
    if (res?.beranda) form.value.beranda = res.beranda
    if (res?.anggota) form.value.anggota = res.anggota
    alertSuccess('Pengaturan halaman berhasil disimpan.')
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyimpan pengaturan.'))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Pengaturan Halaman — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          Pengaturan Halaman Publik
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola teks global pada top bar Beranda, rentang tanggal Pekan Ini, dan profil contoh Portal Anggota.
        </p>
      </div>
      <Button
        type="button"
        @click="save"
        :disabled="saving || loading"
        class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 shadow-sm"
      >
        <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
        <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
        {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
      </Button>
    </div>

    <!-- Loading / Error -->
    <LoadingIndicator v-if="loading" text="Memuat pengaturan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- Top Bar Beranda -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Top Bar Pengumuman Beranda</CardTitle>
          <CardDescription class="text-xs">Baris informasi sorotan paling atas di situs publik ISF.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="topbar-badge" class="text-xs font-medium">Label Badge</Label>
              <Input
                id="topbar-badge"
                v-model="form.beranda.topBar.badge"
                placeholder="Sorotan"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5 sm:col-span-2">
              <Label for="topbar-info" class="text-xs font-medium">Keterangan Waktu / Frekuensi</Label>
              <Input
                id="topbar-info"
                v-model="form.beranda.topBar.info"
                placeholder="Diperbarui setiap Senin"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="topbar-announcement" class="text-xs font-medium">Teks Pengumuman Utama</Label>
            <textarea
              id="topbar-announcement"
              v-model="form.beranda.topBar.announcement"
              rows="2"
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Periode Pekan Ini -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Periode Pekan Ini (Beranda)</CardTitle>
          <CardDescription class="text-xs">Rentang tanggal edisi pekan ini yang ditampilkan di header section.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="pekanini-start" class="text-xs font-medium">Tanggal Mulai</Label>
              <Input
                id="pekanini-start"
                type="date"
                v-model="form.beranda.pekanIni.dateStart"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5">
              <Label for="pekanini-end" class="text-xs font-medium">Tanggal Selesai</Label>
              <Input
                id="pekanini-end"
                type="date"
                v-model="form.beranda.pekanIni.dateEnd"
                class="h-9 text-xs"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Profil Contoh Anggota -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Profil Anggota Contoh (/anggota)</CardTitle>
          <CardDescription class="text-xs">Teks profil pengguna yang tampil di sidebar Portal Anggota.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="anggota-name" class="text-xs font-medium">Nama Anggota</Label>
              <Input
                id="anggota-name"
                v-model="form.anggota.name"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5">
              <Label for="anggota-role" class="text-xs font-medium">Jabatan</Label>
              <Input
                id="anggota-role"
                v-model="form.anggota.role"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="anggota-org" class="text-xs font-medium">Perusahaan / Organisasi</Label>
              <Input
                id="anggota-org"
                v-model="form.anggota.organization"
                class="h-9 text-xs"
              />
            </div>
            <div class="space-y-1.5">
              <Label for="anggota-plan" class="text-xs font-medium">Paket Langganan</Label>
              <Input
                id="anggota-plan"
                v-model="form.anggota.plan"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <StringListEditor
            v-model="form.anggota.sektor"
            label="Sektor Industri yang Dipantau"
            description="Tag sektor yang muncul di kartu profil anggota."
          />
        </CardContent>
      </Card>

      <div class="flex items-center justify-end gap-3 pt-2">
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-5 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
        </Button>
      </div>
    </form>
  </div>
</template>
