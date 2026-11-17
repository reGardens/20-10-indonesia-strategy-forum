<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
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
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.istilahHukum,
  id: props.id,
  indexHref: '/cms/istilah-hukum',
  defaults: {
    nama: '',
    slug: '',
    keterangan: '',
    status: 'terbit'
  }
})

function autoSlug() {
  const source = form.nama || ''
  if (!isEdit.value && source && !form.slug) {
    form.slug = source
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
}
</script>

<template>
  <Head :title="(isEdit ? 'Edit Kategori Istilah' : 'Tambah Kategori Istilah') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/istilah-hukum">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kategori Istilah' : 'Tambah Kategori Istilah' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data kategori istilah hukum #${props.id}` : 'Tambahkan kategori/istilah hukum baru untuk pilihan dropdown Glosarium Hukum.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/istilah-hukum">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="animate-spin" />
          <IconComponent v-else name="Check" :size="14" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Istilah' : 'Simpan Istilah') }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data istilah hukum..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content in Card -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Kategori Istilah</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Nama istilah baku dan slug identifikasi untuk Glosarium Hukum.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nama Istilah -->
            <div class="space-y-1.5">
              <Label for="nama" class="text-xs font-medium text-foreground">Nama Istilah / Kategori *</Label>
              <Input
                id="nama"
                v-model="form.nama"
                @blur="autoSlug"
                placeholder="Contoh: Habeas Corpus, Judicial Review..."
                class="h-9 text-xs font-medium"
                required
              />
              <p v-if="err('nama') || err('istilah')" class="text-xs text-red-400 mt-1">
                {{ err('nama') || err('istilah') }}
              </p>
            </div>

            <!-- Slug -->
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium text-foreground">Slug Identifikasi</Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="habeas-corpus (otomatis)"
                class="h-9 text-xs font-mono text-muted-foreground"
              />
              <p v-if="err('slug')" class="text-xs text-red-400 mt-1">{{ err('slug') }}</p>
            </div>
          </div>

          <!-- Keterangan Singkat -->
          <div class="space-y-1.5">
            <Label for="keterangan" class="text-xs font-medium text-foreground">Keterangan / Deskripsi Singkat</Label>
            <textarea
              id="keterangan"
              v-model="form.keterangan"
              rows="3"
              placeholder="Penjelasan ringkas makna istilah..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
            <p v-if="err('keterangan')" class="text-xs text-red-400 mt-1">{{ err('keterangan') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Status Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardContent class="py-4">
          <div class="flex items-center justify-between">
            <div>
              <Label class="text-xs font-medium text-foreground">Status Istilah</Label>
              <p class="text-[11px] text-muted-foreground">Tentukan apakah kategori istilah ini aktif digunakan di sistem.</p>
            </div>
            <StatusSelectWithBadge v-model="form.status" />
          </div>
          <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
        </CardContent>
      </Card>

      <!-- Bottom submit button -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/istilah-hukum">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center gap-1.5"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="animate-spin" />
          <IconComponent v-else name="Check" :size="14" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Istilah' : 'Simpan Istilah') }}
        </Button>
      </div>
    </form>
  </div>
</template>
