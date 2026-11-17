<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
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

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.kursiParlemen,
  id: props.id,
  indexHref: '/cms/kursi-parlemen',
  defaults: {
    periode: '2024-2029',
    lembaga: 'DPR',
    partai: '',
    singkatan: '',
    jumlah_kursi: 0,
    warna_partai: '#E10600',
    logo: '',
    urutan: 0,
    status: 'terbit'
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Kursi Parlemen' : 'Tambah Kursi Parlemen') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kursi-parlemen">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kursi Parlemen' : 'Tambah Kursi Parlemen' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data kursi parlemen #${props.id}` : 'Tambahkan komposisi kursi parlemen untuk visualisasi infografis interaktif.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kursi-parlemen">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Kursi' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data kursi parlemen..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Lembaga & Periode</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Tentukan lembaga parlemen dan periode masa jabatan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Lembaga -->
            <div class="space-y-1.5">
              <Label for="lembaga" class="text-xs font-medium text-foreground">Lembaga Parlemen *</Label>
              <Select v-model="form.lembaga">
                <SelectTrigger id="lembaga" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Lembaga..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="DPR" class="text-xs font-medium">DPR RI (Dewan Perwakilan Rakyat)</SelectItem>
                  <!-- DPD & MPR disembunyikan sesuai instruksi -->
                </SelectContent>
              </Select>
              <p v-if="err('lembaga')" class="text-xs text-red-400 mt-1">{{ err('lembaga') }}</p>
            </div>

            <!-- Periode -->
            <div class="space-y-1.5">
              <Label for="periode" class="text-xs font-medium text-foreground">Periode Masa Jabatan *</Label>
              <Input
                id="periode"
                v-model="form.periode"
                placeholder="Contoh: 2024-2029"
                class="h-9 text-xs font-mono"
              />
              <p v-if="err('periode')" class="text-xs text-red-400 mt-1">{{ err('periode') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Data Partai & Perolehan Kursi</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Nama partai politik / fraksi, jumlah perolehan kursi, dan warna representasi grafis.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nama Partai -->
            <div class="space-y-1.5">
              <Label for="partai" class="text-xs font-medium text-foreground">Nama Partai / Fraksi *</Label>
              <Input
                id="partai"
                v-model="form.partai"
                placeholder="Contoh: Partai Demokrasi Indonesia Perjuangan"
                class="h-9 text-xs"
              />
              <p v-if="err('partai')" class="text-xs text-red-400 mt-1">{{ err('partai') }}</p>
            </div>

            <!-- Singkatan -->
            <div class="space-y-1.5">
              <Label for="singkatan" class="text-xs font-medium text-foreground">Singkatan / Label Ringkas</Label>
              <Input
                id="singkatan"
                v-model="form.singkatan"
                placeholder="Contoh: PDI Perjuangan, Golkar, Gerindra..."
                class="h-9 text-xs"
              />
              <p v-if="err('singkatan')" class="text-xs text-red-400 mt-1">{{ err('singkatan') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Jumlah Kursi -->
            <div class="space-y-1.5">
              <Label for="jumlah_kursi" class="text-xs font-medium text-foreground">Jumlah Perolehan Kursi *</Label>
              <Input
                id="jumlah_kursi"
                type="number"
                min="0"
                max="10000"
                v-model.number="form.jumlah_kursi"
                class="h-9 text-xs font-semibold"
              />
              <p v-if="err('jumlah_kursi')" class="text-xs text-red-400 mt-1">{{ err('jumlah_kursi') }}</p>
            </div>
          </div>

          <!-- Upload Logo Partai — persis seperti Banner & menu lain (ImageUploadTab) -->
          <div class="space-y-1.5 pt-3 border-t border-border">
            <Label class="text-xs font-medium text-foreground">Logo Partai</Label>
            <ImageUploadTab
              v-model="form.logo"
              aspect-class="aspect-square max-w-[200px]"
              placeholder="https://... atau unggah berkas logo partai"
              preview-label="Pratinjau Logo Partai"
            />
            <p v-if="err('logo')" class="text-xs text-red-400 mt-1">{{ err('logo') }}</p>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

