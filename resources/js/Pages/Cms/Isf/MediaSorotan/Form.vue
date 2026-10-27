<script setup>
import { computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { activeCategories, loadCategories } from '@/Pages/Cms/Isf/options'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
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
  DatePicker,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.mediaSorotan,
  id: props.id,
  indexHref: '/cms/media-sorotan',
  defaults: {
    title: '',
    imageUrl: '',
    mediaUrl: '',
    category: 'YouTube',
    publishedAt: new Date().toISOString().slice(0, 10),
    order: 1,
    status: 'terbit'
  }
})

onMounted(() => {
  loadCategories()
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Media Sorotan' : 'Tambah Media Sorotan') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/media-sorotan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Media Sorotan' : 'Tambah Media Sorotan Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data media sorotan #${props.id}` : 'Tambahkan media video sorotan (YouTube / Instagram) untuk bagian kanan Beranda artikel.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/media-sorotan">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Media Sorotan' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data media sorotan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Media & Tautan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Judul tayangan, kategori platform (YouTube / Instagram), dan tautan video asli.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Title -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">Judul Media / Tayangan *</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Contoh: Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('title')" class="text-xs text-red-400 mt-1">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Category / Platform -->
            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium text-foreground">Kategori Platform</Label>
              <Select v-model="form.category">
                <SelectTrigger id="category" class="h-9 text-xs">
                  <SelectValue placeholder="Pilih Kategori Platform..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="YouTube" class="text-xs font-medium">YouTube</SelectItem>
                  <SelectItem value="Instagram" class="text-xs font-medium">Instagram</SelectItem>
                  <SelectItem value="Podcast" class="text-xs font-medium">Podcast</SelectItem>
                  <SelectItem value="Diskusi Panel" class="text-xs font-medium">Diskusi Panel</SelectItem>
                  <template v-if="activeCategories && activeCategories.length">
                    <SelectItem
                      v-for="cat in activeCategories"
                      :key="cat.value"
                      :value="cat.label"
                      class="text-xs font-medium"
                    >
                      {{ cat.label }}
                    </SelectItem>
                  </template>
                </SelectContent>
              </Select>
              <p v-if="err('category')" class="text-xs text-red-400 mt-1">{{ err('category') }}</p>
            </div>

            <!-- Published Date -->
            <div class="space-y-1.5">
              <Label class="text-xs font-medium text-foreground">Tanggal Publikasi</Label>
              <DatePicker
                v-model="form.publishedAt"
                placeholder="Pilih tanggal upload..."
                class="w-full"
              />
              <p v-if="err('publishedAt')" class="text-xs text-red-400 mt-1">{{ err('publishedAt') }}</p>
            </div>
          </div>

          <!-- Media URL -->
          <div class="space-y-1.5">
            <Label for="mediaUrl" class="text-xs font-medium text-foreground">Tautan Video / Media (Link Luar)</Label>
            <Input
              id="mediaUrl"
              v-model="form.mediaUrl"
              placeholder="https://www.youtube.com/watch?v=... atau https://www.instagram.com/p/..."
              class="h-9 text-xs font-mono"
            />
            <p class="text-[11px] text-muted-foreground">
              Ketika card media diklik di Beranda, pengguna akan diarahkan langsung ke link ini.
            </p>
            <p v-if="err('mediaUrl')" class="text-xs text-red-400 mt-1">{{ err('mediaUrl') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Thumbnail & Display Settings Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Thumbnail & Pengaturan Tampilan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Visual cover thumbnail video (rasio 16:9), nomor urut tampilan, dan status penayangan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Thumbnail Image Upload -->
          <div class="space-y-1.5">
            <Label class="text-xs font-medium text-foreground">Thumbnail Gambar (16:9)</Label>
            <ImageUploadTab
              v-model="form.imageUrl"
              aspect-class="aspect-[16/9]"
              placeholder="https://... atau /images/..."
            />
            <p v-if="err('imageUrl')" class="text-xs text-red-400 mt-1">{{ err('imageUrl') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Order -->
            <div class="space-y-1.5">
              <Label for="order" class="text-xs font-medium text-foreground">Urutan Tampil (Order)</Label>
              <Input
                id="order"
                type="number"
                min="1"
                max="99"
                v-model.number="form.order"
                class="h-9 text-xs w-full"
              />
              <p class="text-[11px] text-muted-foreground">
                Urutan tampilan card di kolom kanan beranda (maksimal 2 tayangan teratas).
              </p>
              <p v-if="err('order')" class="text-xs text-red-400 mt-1">{{ err('order') }}</p>
            </div>

            <!-- Status with badge -->
            <StatusSelectWithBadge
              id="status-media"
              v-model="form.status"
              label="Status Publikasi"
              :required="true"
            />
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>
