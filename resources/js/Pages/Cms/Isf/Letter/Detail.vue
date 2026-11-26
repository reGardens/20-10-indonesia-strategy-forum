<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
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
    return roles.map((r) => (typeof r === 'string' ? r : (r?.name || ''))).filter(Boolean)
  }
  return []
})

const canApprove = computed(() => {
  return authRoles.value.includes('admin') || authRoles.value.includes('super-admin')
})

const isPending = computed(() => {
  if (!item.value) return false
  const mod = item.value.moderation_status
  const st = item.value.status
  if (mod === 'disetujui' || st === 'disetujui' || st === 'terbit') return false
  if (mod === 'ditolak' || st === 'ditolak') return false
  return true
})

const renderedBody = computed(() => {
  const content = item.value?.body || ''
  if (!content) return ''
  if (/<[a-z][\s\S]*>/i.test(content)) {
    return content
  }
  return content.replace(/\n/g, '<br>')
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
    if (item.value) {
      item.value.moderation_status = 'disetujui'
      item.value.status = 'disetujui'
    }
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
    if (item.value) {
      item.value.moderation_status = 'ditolak'
      item.value.status = 'ditolak'
      item.value.moderation_notes = rejectNotes.value
    }
    alertSuccess(res.message || 'Artikel by member telah ditolak.')
    showRejectModal.value = false
    await fetchLetter()
  } catch (err) {
    rejectError.value = err?.response?.data?.message || 'Gagal menolak artikel.'
  } finally {
    actionLoading.value = false
  }
}

async function handleDelete() {
  if (!item.value) return
  const confirmed = await confirmDelete(item.value.title)
  if (!confirmed) return
  try {
    await cmsResources.suratPembaca.remove(item.value.id)
    alertSuccess('Artikel by member berhasil dihapus.')
    router.visit('/cms/surat-pembaca')
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus artikel.')
  }
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(fetchLetter)
</script>

<template>
  <Head :title="item ? `${item.title} — Detail Artikel by Member` : 'Detail Artikel by Member'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header / Actions (Identik dengan Detail Artikel di modul Artikel) -->
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
            <span>Detail Artikel</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
              by member
            </span>
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            ID #{{ id }} • Pratinjau lengkap naskah dan validasi moderasi member
          </p>
        </div>
      </div>

      <!-- Action Buttons: Setujui, Tolak, Hapus -->
      <div v-if="item" class="flex items-center gap-2">
        <template v-if="isPending">
          <Button
            v-if="canApprove"
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
            type="button"
            variant="destructive"
            size="sm"
            :disabled="actionLoading"
            @click="openRejectModal"
            class="h-9 text-xs font-semibold px-4 gap-1.5"
          >
            <IconComponent name="X" :size="14" />
            Tolak Naskah
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="actionLoading"
            @click="handleDelete"
            class="h-9 text-xs gap-1.5 text-red-500 hover:text-red-600 hover:bg-red-500/10 hover:border-red-500"
          >
            <IconComponent name="Trash" :size="14" />
            Hapus
          </Button>
        </template>
        <span
          v-else-if="item.moderation_status === 'disetujui' || item.status === 'disetujui' || item.status === 'terbit'"
          class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 gap-1.5"
        >
          <IconComponent name="Check" :size="14" />
          Telah Disetujui
        </span>
        <span
          v-else-if="item.moderation_status === 'ditolak' || item.status === 'ditolak'"
          class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 gap-1.5"
        >
          <IconComponent name="X" :size="14" />
          Telah Ditolak
        </span>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat detail artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="fetchLetter" />

    <div v-else-if="item" class="space-y-6">
      <!-- Status Notice jika ditolak (Alasan Ditolak) -->
      <div
        v-if="item.moderation_status === 'ditolak' || item.status === 'ditolak' || (item.moderation_notes && item.moderation_status !== 'disetujui')"
        class="p-4 sm:p-5 rounded-xl bg-red-500/10 border border-red-500/25 text-red-600 dark:text-red-400 text-xs space-y-2 shadow-xs"
      >
        <div class="flex items-center gap-2 font-bold text-sm">
          <IconComponent name="X" :size="16" class="text-red-600 dark:text-red-400 shrink-0" />
          <span>Artikel ini telah ditolak oleh dewan redaksi</span>
        </div>
        <div class="pl-6 space-y-1.5">
          <div class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-300">
            Alasan Penolakan / Catatan Evaluasi Redaksi:
          </div>
          <div class="p-3 rounded-lg bg-card border border-red-500/30 text-xs sm:text-sm font-medium text-foreground whitespace-pre-wrap leading-relaxed shadow-2xs">
            {{ item.moderation_notes || item.catatan_redaksi || 'Tidak ada catatan evaluasi khusus dari redaksi.' }}
          </div>
        </div>
      </div>

      <!-- Main Overview Card (Desain & Struktur 100% Identik dengan Detail Artikel) -->
      <Card class="border border-border/80 shadow-xs">
        <CardContent class="p-6 space-y-5">
          <!-- Cover Image if exists -->
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

          <!-- Badges & Metadata -->
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs font-semibold capitalize">
              {{ item.category || 'kebijakan' }}
            </Badge>
            <Badge v-if="item.subcategory" variant="outline" class="text-xs font-mono">
              /{{ item.subcategory }}
            </Badge>
            <span
              v-if="item.moderation_status === 'disetujui' || item.status === 'terbit'"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
            >
              Terbit
            </span>
            <span
              v-else-if="item.moderation_status === 'ditolak'"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-500/10 text-rose-600 border border-rose-500/20"
            >
              Ditolak
            </span>
            <span
              v-else
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-500/10 text-amber-600 border border-amber-500/20"
            >
              Pending
            </span>
            <span class="text-xs text-muted-foreground ml-auto whitespace-nowrap">
              Waktu Masuk: {{ formatDate(item.created_at) }}
            </span>
          </div>

          <!-- Title -->
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground leading-snug">
              {{ item.title }}
            </h2>
            <div v-if="item.slug" class="flex items-center gap-2 mt-1.5 text-xs text-muted-foreground font-mono">
              <span>Slug:</span>
              <span class="text-foreground bg-muted/60 px-1.5 py-0.5 rounded">{{ item.slug }}</span>
            </div>
          </div>

          <!-- Author Info with 1:1 Photo (Identik dengan Detail Artikel) -->
          <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-muted/30 border border-border/60">
            <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0 bg-muted border border-border/80 flex items-center justify-center">
              <img
                v-if="item.author_avatar"
                :src="item.author_avatar"
                :alt="item.author_name || item.sender_name || 'Penulis'"
                class="w-full h-full object-cover"
              />
              <IconComponent v-else name="User" :size="20" class="text-muted-foreground" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-semibold text-foreground flex items-center gap-2">
                <span>{{ item.author_name || item.sender_name || 'Member ISF' }}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded bg-red-500/10 text-red-600 font-semibold border border-red-500/20">Member</span>
              </div>
              <div class="text-xs text-muted-foreground truncate">
                {{ item.author_role || 'Penulis' }} <span v-if="item.sender_email">• {{ item.sender_email }}</span> • Estimasi Baca: {{ item.reading_minutes || 5 }} Menit
              </div>
            </div>
          </div>

          <!-- Summary (Ringkasan Eksekutif) -->
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

          <!-- Body Content -->
          <div class="pt-4 border-t border-border/70 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Naskah Lengkap Artikel
            </h3>
            <div
              class="prose prose-sm dark:prose-invert max-w-none text-foreground leading-relaxed bg-card/60 p-5 rounded-xl border border-border/60"
              v-html="renderedBody"
            ></div>
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
        <h3 class="text-base font-bold text-foreground">Tolak Naskah Artikel by Member</h3>
        <p class="text-xs text-muted-foreground">
          Berikan catatan atau alasan penolakan naskah (alasan akan dikirimkan ke email penulis dan dicatat di sistem).
        </p>
        <textarea
          v-model="rejectNotes"
          rows="3"
          placeholder="Alasan penolakan (mis. data belum terverifikasi, topik kurang sesuai, dll)..."
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
            :disabled="actionLoading"
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
            class="h-8 text-xs font-semibold inline-flex items-center gap-1.5"
          >
            <svg v-if="actionLoading" class="animate-spin h-3.5 w-3.5 text-white shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ actionLoading ? 'Mengirim Catatan...' : 'Konfirmasi Tolak' }}</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
