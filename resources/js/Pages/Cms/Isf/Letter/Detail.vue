<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Badge,
  Card,
  CardContent,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, required: true }
})

const page = usePage()
const authRoles = computed(() => {
  const roles = page.props?.auth?.user?.roles || []
  if (Array.isArray(roles)) {
    return roles.map((r) => (typeof r === 'string' ? r : r.name))
  }
  return []
})

const canApprove = computed(() => {
  return authRoles.value.includes('admin') || authRoles.value.includes('super-admin')
})

const item = ref(null)
const loading = ref(true)
const loadError = ref('')
const actionLoading = ref(false)

const showRejectModal = ref(false)
const rejectNotes = ref('')
const rejectError = ref('')

async function fetchLetter() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.suratPembaca.get(props.id)
    item.value = data
  } catch (err) {
    loadError.value = err?.response?.data?.message || 'Gagal memuat detail artikel by member.'
  } finally {
    loading.value = false
  }
}

async function handleApprove() {
  if (!item.value || actionLoading.value) return
  actionLoading.value = true
  try {
    const res = await cmsResources.suratPembaca.approve(item.value.id)
    alertSuccess(res.message || 'Artikel by member berhasil disetujui dan ditayangkan.')
    await fetchLetter()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menyetujui artikel.')
  } finally {
    actionLoading.value = false
  }
}

function openRejectModal() {
  rejectNotes.value = item.value?.moderation_notes || ''
  rejectError.value = ''
  showRejectModal.value = true
}

async function submitReject() {
  if (!item.value || actionLoading.value) return
  actionLoading.value = true
  rejectError.value = ''
  try {
    const res = await cmsResources.suratPembaca.reject(item.value.id, rejectNotes.value)
    alertSuccess(res.message || 'Artikel by member telah ditolak.')
    showRejectModal.value = false
    await fetchLetter()
  } catch (err) {
    rejectError.value = err?.response?.data?.message || 'Gagal menolak artikel.'
  } finally {
    actionLoading.value = false
  }
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(fetchLetter)
</script>

<template>
  <Head :title="item ? `${item.title} — Detail Artikel by Member` : 'Detail Artikel by Member'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header / Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/surat-pembaca">
          <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs">
            <IconComponent name="ChevronLeft" :size="14" />
            Kembali
          </Button>
        </Link>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <span>Detail Artikel by Member</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
              by member
            </span>
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            ID #{{ id }} • Tinjauan editorial dan validasi naskah kiriman pembaca
          </p>
        </div>
      </div>

      <!-- Action Buttons: Setujui & Tolak -->
      <div v-if="item" class="flex items-center gap-2">
        <Button
          v-if="canApprove && item.moderation_status !== 'disetujui'"
          type="button"
          size="sm"
          :disabled="actionLoading"
          @click="handleApprove"
          class="h-9 text-xs font-semibold px-4 bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 shadow-xs"
        >
          <IconComponent name="Check" :size="14" />
          {{ actionLoading ? 'Memproses...' : 'Setujui & Terbitkan' }}
        </Button>
        <Button
          v-if="item.moderation_status !== 'ditolak'"
          type="button"
          variant="destructive"
          size="sm"
          :disabled="actionLoading"
          @click="openRejectModal"
          class="h-9 text-xs font-semibold px-4 gap-1.5"
        >
          <IconComponent name="X" :size="14" />
          Tolak Artikel
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat naskah artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="fetchLetter" />

    <div v-else-if="item" class="space-y-6">
      <!-- Status Notice -->
      <div
        v-if="item.moderation_status === 'disetujui'"
        class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs flex items-center justify-between"
      >
        <div class="flex items-center gap-2 font-medium">
          <IconComponent name="Check" :size="16" class="text-emerald-500" />
          <span>Artikel ini telah disetujui dan disinkronkan ke daftar Artikel ISF & Rubrik publik.</span>
        </div>
        <Link href="/cms/artikel" class="text-xs font-semibold underline hover:no-underline">
          Lihat di Tabel Artikel →
        </Link>
      </div>

      <div
        v-else-if="item.moderation_status === 'ditolak'"
        class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs space-y-1"
      >
        <div class="flex items-center gap-2 font-semibold">
          <IconComponent name="X" :size="16" />
          <span>Artikel ini telah ditolak oleh redaksi.</span>
        </div>
        <p v-if="item.moderation_notes" class="text-xs italic pl-6">
          Catatan: "{{ item.moderation_notes }}"
        </p>
      </div>

      <!-- Main Content Card -->
      <Card class="border border-border/80 shadow-xs">
        <CardContent class="p-6 space-y-5">
          <!-- Cover image if available -->
          <div v-if="item.image_url" class="relative rounded-xl overflow-hidden aspect-video max-h-80 w-full bg-muted/40 border border-border/60">
            <img
              :src="item.image_url"
              :alt="item.image_alt || item.title"
              class="w-full h-full object-cover"
            />
            <div
              v-if="item.image_alt"
              class="absolute bottom-2 left-2 bg-black/70 text-white text-[11px] px-2.5 py-1 rounded backdrop-blur-xs"
            >
              Alt: {{ item.image_alt }}
            </div>
          </div>

          <!-- Badges & Category -->
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs font-semibold capitalize">
              {{ item.category || 'opini' }}
            </Badge>
            <Badge v-if="item.subcategory" variant="outline" class="text-xs font-mono">
              /{{ item.subcategory }}
            </Badge>
            <span
              v-if="item.moderation_status === 'disetujui'"
              class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
            >
              Disetujui
            </span>
            <span
              v-else-if="item.moderation_status === 'ditolak'"
              class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20"
            >
              Ditolak
            </span>
            <span
              v-else
              class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
            >
              Pending (Menunggu Review)
            </span>
            <span class="text-xs text-muted-foreground ml-auto whitespace-nowrap">
              Diterima: {{ formatDate(item.created_at) }}
            </span>
          </div>

          <!-- Title & Slug -->
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground leading-snug">
              {{ item.title }}
            </h2>
            <div v-if="item.slug" class="flex items-center gap-2 mt-1.5 text-xs text-muted-foreground font-mono">
              <span>Slug:</span>
              <span class="text-foreground bg-muted/60 px-1.5 py-0.5 rounded">{{ item.slug }}</span>
            </div>
          </div>

          <!-- Penulis Card with 1:1 Square Photo -->
          <div class="flex items-center gap-4 p-4 rounded-xl bg-muted/30 border border-border/70">
            <!-- 1:1 Photo -->
            <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 bg-muted border-2 border-border/80 flex items-center justify-center shadow-xs">
              <img
                v-if="item.author_avatar"
                :src="item.author_avatar"
                :alt="item.author_name || item.sender_name"
                class="w-full h-full object-cover"
              />
              <IconComponent v-else name="User" :size="24" class="text-muted-foreground" />
            </div>

            <div class="min-w-0 flex-1 space-y-0.5">
              <div class="text-sm font-bold text-foreground flex items-center gap-2">
                <span>{{ item.author_name || item.sender_name }}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded bg-red-500/10 text-red-600 font-semibold border border-red-500/20">Penulis Member</span>
              </div>
              <div class="text-xs text-muted-foreground">
                <span v-if="item.author_role">{{ item.author_role }} • </span>
                <span v-if="item.sender_city">{{ item.sender_city }} • </span>
                <span class="font-mono text-foreground">{{ item.sender_email }}</span>
              </div>
              <div class="text-[11px] text-muted-foreground">
                Estimasi Baca: {{ item.reading_minutes || 5 }} Menit
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div v-if="item.summary" class="p-4 rounded-xl bg-muted/40 border-l-4 border-l-red-600 space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Ringkasan Eksekutif</span>
            <p class="text-xs sm:text-sm text-foreground leading-relaxed italic">
              "{{ item.summary }}"
            </p>
          </div>

          <!-- Key Fact Blocks -->
          <div v-if="item.fact_blocks && item.fact_blocks.length > 0" class="space-y-2.5">
            <h3 class="text-xs font-bold font-mono uppercase tracking-wider text-muted-foreground">
              Blok Fakta Kunci ({{ item.fact_blocks.length }})
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div
                v-for="(fact, fIdx) in item.fact_blocks"
                :key="fIdx"
                class="p-3 rounded-lg bg-card border border-border/80 text-xs space-y-1.5"
              >
                <div class="font-semibold text-foreground">
                  {{ fact.claim }}
                </div>
                <div class="text-[11px] text-muted-foreground flex items-center justify-between gap-2 pt-1 border-t border-border/40">
                  <span class="truncate font-medium">{{ fact.sourceTitle }}</span>
                  <a
                    v-if="fact.sourceUrl"
                    :href="fact.sourceUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-red-500 hover:underline shrink-0"
                  >
                    Buka Rujukan ↗
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Link Url -->
          <div v-if="item.link_url" class="text-xs text-muted-foreground">
            <span class="font-medium text-foreground">Tautan Eksternal:</span>
            <a :href="item.link_url" target="_blank" rel="noopener noreferrer" class="ml-1 text-red-500 hover:underline">
              {{ item.link_url }}
            </a>
          </div>

          <!-- Full Body -->
          <div class="pt-4 border-t border-border/70 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Naskah Lengkap Artikel
            </h3>
            <div class="prose prose-sm dark:prose-invert max-w-none text-foreground leading-relaxed whitespace-pre-line bg-card/60 p-5 rounded-xl border border-border/60">
              {{ item.body }}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Modal Penolakan Artikel -->
    <div
      v-if="showRejectModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-card border border-border rounded-xl p-5 max-w-md w-full space-y-4 shadow-xl">
        <h3 class="text-base font-bold text-foreground">Tolak Artikel by Member</h3>
        <p class="text-xs text-muted-foreground">
          Berikan catatan atau alasan penolakan naskah (opsional namun disarankan agar menjadi catatan internal redaksi).
        </p>
        <textarea
          v-model="rejectNotes"
          rows="3"
          placeholder="Alasan penolakan (mis. data belum terverifikasi, plagiasi, dll)..."
          class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
        ></textarea>
        <p v-if="rejectError" class="text-xs text-red-500 font-medium">
          {{ rejectError }}
        </p>
        <div class="flex items-center justify-end gap-2 pt-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="showRejectModal = false"
            class="h-8 text-xs"
          >
            Batal
          </Button>
          <Button
            type="button"
            variant="destructive"
            size="sm"
            :disabled="actionLoading"
            @click="submitReject"
            class="h-8 text-xs font-semibold"
          >
            {{ actionLoading ? 'Menyimpan...' : 'Konfirmasi Tolak' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

