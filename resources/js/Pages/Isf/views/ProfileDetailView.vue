<script setup>
/**
 * ProfileDetailView.vue — Halaman Publik Detail Profile di Landing Page (Issue #93).
 * Menyediakan informasi profil dan form kelengkapan akun bagi seluruh role pengguna
 * dengan layout editorial standar ISF (Navy Header, SectionHeading, Container Gutters).
 */
import { ref, computed, onMounted } from 'vue'
import http from '@/api/http'
import { useIsfSession } from '@/Composables/useIsfSession'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import SectionHeading from '@/Components/Isf/SectionHeading.vue'
import IsfLink from '@/Components/Isf/IsfLink.vue'

const { session, isLoggedIn, updateWhatsApp } = useIsfSession()

const sectionPad = 'px-3 lg:px-4 xl:px-[30px]'

const loading = ref(true)
const saving = ref(false)
const profile = ref({
    name: '',
    first_name: '',
    last_name: '',
    email: '',
    email_verified: false,
    whatsapp: '',
    whatsappDisplay: '',
    whatsappVerified: false,
    initials: '',
    roles: [],
    role_label: 'Anggota',
    created_at: '',
})

const form = ref({
    first_name: '',
    last_name: '',
    whatsapp: '',
})

async function fetchProfile() {
    if (!isLoggedIn.value) {
        loading.value = false
        return
    }

    loading.value = true
    try {
        const res = await http.get('/isf/profile')
        if (res.data?.data) {
            profile.value = res.data.data
            form.value.first_name = res.data.data.first_name || ''
            form.value.last_name = res.data.data.last_name || ''
            form.value.whatsapp = res.data.data.whatsapp || ''
        }
    } catch (e) {
        profile.value.name = session.value.name || 'Pengguna ISF'
        profile.value.whatsapp = session.value.whatsapp || ''
        profile.value.whatsappDisplay = session.value.whatsappDisplay || ''
        profile.value.whatsappVerified = session.value.whatsappVerified || false
        form.value.whatsapp = session.value.whatsapp || ''
    } finally {
        loading.value = false
    }
}

async function handleSave() {
    if (!isLoggedIn.value) return

    saving.value = true
    try {
        const payload = {
            first_name: form.value.first_name,
            last_name: form.value.last_name,
            whatsapp: form.value.whatsapp,
        }
        const res = await http.put('/isf/profile', payload)
        alertSuccess('Profil akun Anda berhasil diperbarui.')

        if (res.data?.data) {
            profile.value = { ...profile.value, ...res.data.data }
            updateWhatsApp({
                whatsapp: res.data.data.whatsapp,
                whatsappDisplay: res.data.data.whatsappDisplay,
                whatsappVerified: res.data.data.whatsappVerified,
            })
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menyimpan perubahan profil.'
        alertError(msg)
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchProfile()
})
</script>

<template>
    <div class="isf-content-layer min-h-screen bg-white text-neutral-900 pb-20">
        <!-- ═══ 1. HEADER HERO RUBRIK (NAVY EDITORIAL KONSISTEN) ═════════════════ -->
        <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-6 sm:pb-8">
            <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
                <!-- Breadcrumb & Kicker -->
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-neutral-300">
                    <router-link to="/beranda" class="text-neutral-400 hover:text-white transition-colors">
                        Beranda
                    </router-link>
                    <span class="text-neutral-500">/</span>
                    <span class="text-isf-red font-bold">Profil</span>
                </div>

                <!-- Rubrik Title & Deskripsi -->
                <div class="mt-3 max-w-4xl">
                    <h1 class="isf-rubrik-title text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white">
                        Detail Profil
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-neutral-300 font-serif leading-relaxed">
                        Kelola data akun, informasi kontak WhatsApp resmi, dan pantau status keanggotaan Anda di Indonesia Strategy Forum.
                    </p>
                </div>
            </div>
        </header>

        <!-- ═══ 2. MAIN CONTENT (CONTAINER KONSISTEN DENGAN HALAMAN LAIN) ════════ -->
        <main class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-8 sm:mt-12 space-y-8 sm:space-y-12">
            <!-- STATE A: BELUM LOGIN (GUEST) -->
            <div v-if="!isLoggedIn && !loading" class="rounded-xl border border-neutral-300 bg-[#fbf9f4] p-8 text-center max-w-2xl mx-auto my-12 shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-isf-red/10 text-isf-red mb-4">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-neutral-900 font-space-grotesk">
                    Akses Terbatas: Silakan Masuk
                </h2>
                <p class="mt-2 text-sm text-neutral-600 font-serif leading-relaxed">
                    Anda belum masuk ke akun Indonesia Strategy Forum. Silakan masuk atau daftarkan akun baru untuk melihat dan melengkapi detail profil Anda.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a
                        href="/login"
                        class="rounded bg-isf-red px-5 py-2.5 font-space-grotesk text-xs font-bold uppercase tracking-wider text-white hover:bg-isf-red-dark transition-colors cursor-pointer"
                    >
                        Masuk ke Akun
                    </a>
                    <IsfLink
                        to="/register"
                        class="rounded border border-neutral-400 bg-white px-5 py-2.5 font-space-grotesk text-xs font-bold uppercase tracking-wider text-neutral-800 hover:bg-neutral-50 transition-colors"
                    >
                        Daftar Anggota Baru
                    </IsfLink>
                </div>
            </div>

            <!-- STATE B: LOADING SKELETON -->
            <div v-else-if="loading" class="animate-pulse space-y-8">
                <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
                    <div class="border-b border-neutral-200 p-4">
                        <div class="h-4 w-40 bg-neutral-200 rounded"></div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="h-6 w-1/3 bg-neutral-200 rounded"></div>
                        <div class="h-4 w-1/2 bg-neutral-200 rounded"></div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden">
                    <div class="border-b border-neutral-200 p-4">
                        <div class="h-4 w-48 bg-neutral-200 rounded"></div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="h-10 w-full bg-neutral-100 rounded"></div>
                        <div class="h-10 w-full bg-neutral-100 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- STATE C: LOGGED IN USER PROFILE (BOX DENGAN BINGKAI ISF KONSISTEN) -->
            <div v-else class="space-y-8 sm:space-y-12">
                <!-- SECTION 1: RINGKASAN AKUN & STATUS KEANGGOTAAN -->
                <section
                    aria-labelledby="profil-ringkasan-heading"
                    class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
                >
                    <div class="border-b border-neutral-200">
                        <SectionHeading
                            id="profil-ringkasan-heading"
                            title="Ringkasan Akun"
                            size="lg"
                            :class="sectionPad"
                        >
                            <template #eyebrow>Identitas Keanggotaan Terdaftar</template>
                        </SectionHeading>
                    </div>

                    <div :class="[sectionPad, 'py-6 sm:py-8']">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                            <!-- Inisial & Nama -->
                            <div class="lg:col-span-6 flex items-center gap-4">
                                <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-xl bg-isf-navy text-xl sm:text-2xl font-bold font-space-grotesk text-white shadow border-2 border-isf-red shrink-0">
                                    {{ profile.initials || (profile.name ? profile.name.slice(0, 2).toUpperCase() : 'ISF') }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-lg sm:text-xl font-bold text-neutral-900 font-space-grotesk leading-tight truncate">
                                            {{ profile.name || 'Pengguna ISF' }}
                                        </h2>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-neutral-900 text-white font-space-grotesk shrink-0">
                                            <span class="h-1.5 w-1.5 rounded-full bg-isf-red"></span>
                                            {{ profile.role_label }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-neutral-500 font-mono mt-1">
                                        {{ profile.email }}
                                    </p>
                                    <p v-if="profile.created_at" class="text-[11px] text-neutral-400 font-mono mt-0.5">
                                        Terdaftar sejak {{ profile.created_at }}
                                    </p>
                                </div>
                            </div>

                            <!-- Status Verifikasi Badges -->
                            <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="p-3.5 rounded-lg border border-neutral-200 bg-neutral-50/80">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 block font-space-grotesk">
                                        Status Email
                                    </span>
                                    <div class="mt-1 flex items-center gap-1.5 text-xs font-bold" :class="profile.email_verified ? 'text-emerald-700' : 'text-amber-700'">
                                        <span>{{ profile.email_verified ? '✓ Terverifikasi' : 'Belum Terverifikasi' }}</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-lg border border-neutral-200 bg-neutral-50/80">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 block font-space-grotesk">
                                        Status WhatsApp
                                    </span>
                                    <div class="mt-1 flex items-center gap-1.5 text-xs font-bold" :class="profile.whatsappVerified ? 'text-emerald-700' : (profile.whatsapp ? 'text-amber-700' : 'text-neutral-500')">
                                        <span>{{ profile.whatsappVerified ? '✓ Terverifikasi' : (profile.whatsapp ? 'Menunggu Verifikasi' : 'Belum Diisi') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2: FORM PENGATURAN INFORMASI PRIBADI & KONTAK -->
                <section
                    aria-labelledby="profil-form-heading"
                    class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
                >
                    <div class="border-b border-neutral-200">
                        <SectionHeading
                            id="profil-form-heading"
                            title="Informasi Pribadi &amp; Kontak Resmi"
                            size="lg"
                            :class="sectionPad"
                        >
                            <template #eyebrow>Kelengkapan Data Profil Pengguna</template>
                        </SectionHeading>
                    </div>

                    <div :class="[sectionPad, 'py-6 sm:py-8']">
                        <form @submit.prevent="handleSave" class="max-w-3xl space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Nama Depan -->
                                <div class="space-y-1.5">
                                    <label for="first_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 font-space-grotesk">
                                        Nama Depan
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        placeholder="Contoh: Rina"
                                        class="w-full rounded border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 focus:border-isf-red focus:outline-none focus:ring-1 focus:ring-isf-red transition-all"
                                    />
                                </div>

                                <!-- Nama Belakang -->
                                <div class="space-y-1.5">
                                    <label for="last_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 font-space-grotesk">
                                        Nama Belakang
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        placeholder="Contoh: Santoso"
                                        class="w-full rounded border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 focus:border-isf-red focus:outline-none focus:ring-1 focus:ring-isf-red transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Email (Read-only) -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700 font-space-grotesk">
                                    Alamat Email (Akun Utama)
                                </label>
                                <input
                                    :value="profile.email"
                                    type="email"
                                    disabled
                                    class="w-full rounded border border-neutral-200 bg-neutral-100 px-3.5 py-2 text-sm text-neutral-500 cursor-not-allowed font-mono"
                                />
                                <p class="text-[11px] text-neutral-500 font-serif">
                                    Alamat email terdaftar tidak dapat diubah secara mandiri. Hubungi redaksi untuk pembaruan email.
                                </p>
                            </div>

                            <!-- Nomor WhatsApp -->
                            <div class="space-y-1.5">
                                <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 font-space-grotesk">
                                    Nomor WhatsApp Resmi
                                </label>
                                <input
                                    id="whatsapp"
                                    v-model="form.whatsapp"
                                    type="text"
                                    placeholder="Contoh: 08123456789 atau 628123456789"
                                    class="w-full rounded border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 focus:border-isf-red focus:outline-none focus:ring-1 focus:ring-isf-red transition-all"
                                />
                                <p class="text-[11px] text-neutral-500 font-serif">
                                    Maksimal 13 digit angka tanpa spasi atau tanda hubung. Digunakan untuk verifikasi keamanan dan briefing berkala.
                                </p>
                            </div>

                            <!-- Tombol Aksi Simpan -->
                            <div class="pt-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-2 rounded bg-isf-red px-6 py-2.5 font-space-grotesk text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-isf-red-dark transition-all focus:outline-none focus:ring-2 focus:ring-isf-red focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    <svg
                                        v-if="saving"
                                        class="h-4 w-4 animate-spin text-white"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>{{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
