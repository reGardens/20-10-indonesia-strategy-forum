<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { STATUSES } from '@/Pages/Cms/Isf/options'
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

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.banner,
  id: props.id,
  indexHref: '/cms/banner',
  defaults: {
    kicker: '',
    title: '',
    deck: '',
    to: '',
    image_url: '',
    image_label: '',
    order: 0,
    status: 'draf'
  }
})

const kickerSuggestions = ['Anatomi Isu', 'Tokoh Pekan Ini', 'Portal Anggota', 'Sorotan Utama', 'Analisis Kebijakan', 'Riset Khusus']

function pickKicker(text) {
  form.kicker = text
}
</script>

<template>
  <Head :title="(isEdit ? 'Edit Banner Sorotan' : 'Tambah Banner Baru') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/banner">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Banner Sorotan' : 'Tambah Banner Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data banner #${props.id}` : 'Tambahkan slide baru untuk carousel banner sorotan Beranda.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/banner">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Banner' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data banner..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Konten Banner</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Judul, label sorotan (kicker), dan deskripsi ringkas yang tampil pada banner slide.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Kicker -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="kicker" class="text-xs font-medium text-foreground">Kicker (Label Atas)</Label>
              <div class="flex items-center gap-1">
                <span class="text-[10px] text-muted-foreground mr-1">Rekomendasi:</span>
                <button
                  v-for="k in kickerSuggestions"
                  :key="k"
                  type="button"
                  @click="pickKicker(k)"
                  class="text-[10px] bg-muted/60 hover:bg-muted text-muted-foreground hover:text-foreground px-1.5 py-0.5 rounded border border-border transition-colors"
                >
                  {{ k }}
                </button>
              </div>
            </div>
            <Input
              id="kicker"
              v-model="form.kicker"
              placeholder="Contoh: Anatomi Isu, Tokoh Pekan Ini, dll."
              class="h-9 text-xs"
            />
            <p v-if="err('kicker')" class="text-xs text-red-400 mt-1">{{ err('kicker') }}</p>
          </div>

          <!-- Title -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">Judul Utama Banner</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Masukkan judul banner yang menarik..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('title')" class="text-xs text-red-400 mt-1">{{ err('title') }}</p>
          </div>

          <!-- Deck / Description -->
          <div class="space-y-1.5">
            <Label for="deck" class="text-xs font-medium text-foreground">Deskripsi / Ringkasan (Deck)</Label>
            <textarea
              id="deck"
              v-model="form.deck"
              rows="3"
              placeholder="Deskripsi singkat konten banner (1-2 kalimat)..."
              class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring text-foreground"
            ></textarea>
            <p v-if="err('deck')" class="text-xs text-red-400 mt-1">{{ err('deck') }}</p>
          </div>

          <!-- Target Link -->
          <div class="space-y-1.5">
            <Label for="to" class="text-xs font-medium text-foreground">Target URL / Tautan</Label>
            <Input
              id="to"
              v-model="form.to"
              placeholder="Contoh: /isu/kuhp-dan-pelaku-usaha atau /tokoh/budi-santoso atau https://..."
              class="h-9 text-xs font-mono"
            />
            <p class="text-[11px] text-muted-foreground">
              Gunakan path relatif seperti <code>/isu/...</code> atau <code>/anggota</code> untuk halaman internal ISF, atau URL absolut untuk tautan eksternal.
            </p>
            <p v-if="err('to')" class="text-xs text-red-400 mt-1">{{ err('to') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Media & Urutan Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Gambar & Pengaturan Tampilan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Visual latar belakang banner (disarankan rasio 21:9 atau 16:9), label aksesibilitas, urutan slide, dan status publikasi.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Image URL -->
          <div class="space-y-1.5">
            <Label for="image_url" class="text-xs font-medium text-foreground">URL Gambar Banner</Label>
            <Input
              id="image_url"
              v-model="form.image_url"
              placeholder="https://images.unsplash.com/... atau /images/..."
              class="h-9 text-xs font-mono"
            />
            <p v-if="err('image_url')" class="text-xs text-red-400 mt-1">{{ err('image_url') }}</p>
          </div>

          <!-- Image Label / Alt text -->
          <div class="space-y-1.5">
            <Label for="image_label" class="text-xs font-medium text-foreground">Label Gambar (Alt Text)</Label>
            <Input
              id="image_label"
              v-model="form.image_label"
              placeholder="Contoh: Ilustrasi gedung parlemen dan regulasi bisnis..."
              class="h-9 text-xs"
            />
            <p v-if="err('image_label')" class="text-xs text-red-400 mt-1">{{ err('image_label') }}</p>
          </div>

          <!-- Preview Banner -->
          <div class="space-y-1.5 pt-2">
            <span class="text-xs font-medium text-muted-foreground block">Pratinjau Tampilan Banner:</span>
            <div class="relative w-full aspect-[21/9] rounded-lg overflow-hidden border border-border bg-neutral-900 flex items-center justify-center">
              <img
                v-if="form.image_url"
                :src="form.image_url"
                :alt="form.image_label || form.title || 'Preview Banner'"
                class="w-full h-full object-cover opacity-90"
              />
              <div v-else class="text-center p-6 text-neutral-500 space-y-1">
                <IconComponent name="Photo" :size="32" class="mx-auto text-neutral-600" />
                <p class="text-xs">Belum ada URL gambar yang dimasukkan</p>
              </div>

              <!-- Overlay preview simulation -->
              <div
                v-if="form.title || form.kicker"
                class="absolute bottom-3 left-3 max-w-md bg-white text-neutral-900 p-3.5 rounded shadow-lg border border-neutral-300"
              >
                <p class="text-[11px] font-bold uppercase tracking-wider text-red-600">
                  {{ form.kicker || 'KICKER' }}
                </p>
                <h4 class="font-bold text-sm leading-snug mt-1">
                  {{ form.title || 'Judul Banner' }}
                </h4>
                <p v-if="form.deck" class="text-xs text-neutral-600 mt-1 line-clamp-2">
                  {{ form.deck }}
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Urutan / Order -->
            <div class="space-y-1.5">
              <Label for="order" class="text-xs font-medium text-foreground">Urutan Rotasi (Order)</Label>
              <Input
                id="order"
                type="number"
                min="0"
                max="999"
                v-model.number="form.order"
                class="h-9 text-xs w-full"
              />
              <p class="text-[11px] text-muted-foreground">
                Urutan slide dalam carousel (dimulai dari angka terkecil, misal: 1, 2, 3...).
              </p>
              <p v-if="err('order')" class="text-xs text-red-400 mt-1">{{ err('order') }}</p>
            </div>

            <!-- Status -->
            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium text-foreground">Status Publikasi</Label>
              <select
                id="status"
                v-model="form.status"
                class="w-full h-9 rounded-md border border-input bg-card px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring text-foreground"
              >
                <option v-for="s in STATUSES" :key="s.value" :value="s.value">
                  {{ s.label }}
                </option>
              </select>
              <p class="text-[11px] text-muted-foreground">
                Hanya banner berstatus <strong>Terbit</strong> yang akan muncul di Beranda publik.
              </p>
              <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom submit button -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/banner">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-6 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Banner' }}
        </Button>
      </div>
    </form>
  </div>
</template>
