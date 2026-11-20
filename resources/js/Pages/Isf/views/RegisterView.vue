<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { currentRedirect, withRedirect } from "@/Utils/authRedirect";
import http from "@/api/http";
import { alertSuccess, alertError } from "@/Composables/useAlert";
import { Button, Input, Label } from "@/Components/UI";
import { IsfSlidePuzzleCaptcha } from "@/Components/Isf";

const router = useRouter();

// ?redirect=/tokoh/... (dari login → "Daftar di sini"): diteruskan ke halaman login setelah daftar,
// supaya pengguna kembali ke halaman asal (mis. form ulasan Indeks Tokoh). Tanpa parameter: tidak berubah.
const redirectTo = currentRedirect();
const loginHref = withRedirect("/login", redirectTo);
const loginRegisteredHref = withRedirect("/login?registered=true", redirectTo);

const form = reactive({
    first_name: "",
    last_name: "",
    whatsapp: "",
    age_requirement: false,
    email: "",
    password: "",
    password_confirmation: "",
    captcha_answer: "",
});

const errors = ref({});
const loading = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const registrationSuccess = ref(false);
const registeredEmail = ref("");
const resendLoading = ref(false);

const captchaKey = ref("");
const puzzleRef = ref(null);

const passwordStrength = computed(() => {
    const pwd = form.password;
    if (!pwd) return { score: 0, text: "", color: "" };
    let score = 0;
    if (pwd.length >= 8) score += 1;
    if (/[a-zA-Z]/.test(pwd) && /\d/.test(pwd)) score += 1;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) score += 1;
    if (pwd.length >= 12) score += 1;

    if (score <= 1) return { score: 1, text: "Lemah", color: "bg-red-500" };
    if (score === 2) return { score: 2, text: "Cukup", color: "bg-amber-500" };
    if (score === 3) return { score: 3, text: "Kuat", color: "bg-emerald-500" };
    return { score: 4, text: "Sangat Kuat", color: "bg-emerald-600" };
});

const passwordsMatch = computed(() => {
    if (!form.password_confirmation) return true;
    return form.password === form.password_confirmation;
});

function togglePassword() {
    showPassword.value = !showPassword.value;
}

function toggleConfirmPassword() {
    showConfirmPassword.value = !showConfirmPassword.value;
}

function clearError(field) {
    if (errors.value[field]) {
        delete errors.value[field];
    }
}

function onPuzzleVerified({ key, answer }) {
    captchaKey.value = key;
    form.captcha_answer = answer;
    clearError("captcha_answer");
}

function onPuzzleReset() {
    captchaKey.value = "";
    form.captcha_answer = "";
}

async function handleResendVerification() {
    if (!registeredEmail.value || resendLoading.value) return;
    resendLoading.value = true;
    try {
        const response = await http.post("/isf/email/resend-verification", {
            email: registeredEmail.value,
        });
        alertSuccess(response.data?.message || "Tautan verifikasi baru telah dikirim.");
    } catch (err) {
        alertError(err.response?.data?.message || "Gagal mengirim ulang email verifikasi.");
    } finally {
        resendLoading.value = false;
    }
}

async function handleSubmit() {
    errors.value = {};

    // Client-side quick checks
    const newErrors = {};
    if (!form.first_name.trim()) newErrors.first_name = ["Nama depan wajib diisi."];
    if (!form.last_name.trim()) newErrors.last_name = ["Nama belakang wajib diisi."];
    // Nomor WhatsApp Aktif (Issue #42, pengganti kode pos): 08xx / +628xx / 628xx
    const wa = form.whatsapp.replace(/[\s-]/g, "");
    if (!wa) newErrors.whatsapp = ["Nomor WhatsApp wajib diisi."];
    else if (!/^(\+?62|0)8[1-9][0-9]{6,11}$/.test(wa))
        newErrors.whatsapp = ["Gunakan nomor seluler Indonesia aktif, contoh 0812xxxxxxxx."];
    if (!form.age_requirement) newErrors.age_requirement = ["Anda harus menyatakan berusia 18 tahun atau lebih."];
    if (!form.email.trim()) newErrors.email = ["Alamat email wajib diisi."];
    if (!form.password) newErrors.password = ["Kata sandi wajib diisi."];
    else if (form.password.length < 8) newErrors.password = ["Kata sandi minimal 8 karakter."];
    else if (!/^(?=.*[a-zA-Z])(?=.*\d)/.test(form.password)) {
        newErrors.password = ["Kata sandi harus mengandung kombinasi huruf dan angka."];
    }
    if (!form.password_confirmation) {
        newErrors.password_confirmation = ["Verifikasi kata sandi wajib diisi."];
    } else if (form.password !== form.password_confirmation) {
        newErrors.password_confirmation = ["Verifikasi kata sandi tidak cocok dengan kata sandi."];
    }
    if (!form.captcha_answer.trim() || !puzzleRef.value?.isValid) {
        newErrors.captcha_answer = ["Silakan selesaikan verifikasi puzzle terlebih dahulu."];
    }

    if (Object.keys(newErrors).length > 0) {
        errors.value = newErrors;
        return;
    }

    loading.value = true;
    try {
        const response = await http.post("/isf/register", {
            first_name: form.first_name.trim(),
            last_name: form.last_name.trim(),
            whatsapp: form.whatsapp.trim(),
            age_requirement: form.age_requirement,
            email: form.email.trim().toLowerCase(),
            password: form.password,
            password_confirmation: form.password_confirmation,
            captcha_key: captchaKey.value || puzzleRef.value?.getKey(),
            captcha_answer: form.captcha_answer.trim(),
        });

        registeredEmail.value = form.email.trim().toLowerCase();
        registrationSuccess.value = true;
        alertSuccess(response.data?.message || "Pendaftaran akun anggota berhasil!");
    } catch (err) {
        // Refresh puzzle on failed attempt
        puzzleRef.value?.refresh();

        if (err.response?.status === 422 && err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            alertError("Silakan periksa kembali data formulir pendaftaran Anda.");
        } else if (err.response?.status === 429) {
            alertError("Terlalu banyak permintaan pendaftaran. Silakan coba kembali dalam beberapa saat.");
        } else {
            alertError(err.response?.data?.message || "Terjadi kesalahan saat memproses pendaftaran.");
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-[80vh] bg-neutral-50/60 py-10 px-4 sm:px-6 md:px-8 xl:px-12">
        <div class="w-full">
            <!-- Form Card Container -->
            <div class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm sm:p-10">
                <!-- ============================================================= -->
                <!-- BAGIAN 1: HEADER (Politico Pattern)                           -->
                <!-- ============================================================= -->
                <header class="border-b border-neutral-200 pb-6 text-center sm:text-left">
                    <h1 class="font-editorial text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                        Member Registration
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-neutral-600">
                        Daftar sekarang untuk mendapatkan akses analisis kebijakan strategis dan wawasan terkurasi dari ISF.
                    </p>

                    <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-neutral-500">Sudah memiliki akun anggota?</span>
                        <a
                            :href="loginHref"
                            class="font-semibold text-isf-red hover:text-red-700 underline underline-offset-4 transition-colors"
                        >
                            Masuk di sini
                        </a>
                    </div>
                </header>

                <!-- Success Banner (Issue #74: Konfirmasi Verifikasi Email) -->
                <div
                    v-if="registrationSuccess"
                    class="mt-6 rounded-xl bg-white border border-emerald-200 p-6 sm:p-8 text-center shadow-xs"
                    role="alert"
                >
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 mb-4 ring-8 ring-emerald-50">
                        <svg class="h-9 w-9 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 font-editorial">
                        Pendaftaran Berhasil! Cek Email Anda
                    </h2>
                    <p class="mt-2 text-sm text-neutral-600 max-w-lg mx-auto leading-relaxed">
                        Kami telah mengirimkan tautan verifikasi ke <strong class="text-neutral-900 font-semibold">{{ registeredEmail }}</strong>. Silakan periksa kotak masuk (inbox) atau folder spam email Anda, lalu klik tautan aktivasi untuk mengaktifkan akun Portal Anggota ISF Anda.
                    </p>

                    <div class="mt-4 p-3.5 bg-neutral-50 rounded-lg border border-neutral-200 max-w-md mx-auto text-xs text-neutral-600 leading-normal text-left">
                        <div class="font-semibold text-neutral-800 mb-1 flex items-center gap-1.5">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span>Langkah Selanjutnya:</span>
                        </div>
                        <ol class="list-decimal list-inside space-y-1 text-neutral-600">
                            <li>Buka email verifikasi dari <strong>Indonesia Strategy Forum</strong>.</li>
                            <li>Klik tombol <strong>"Verifikasi Email Saya"</strong>.</li>
                            <li>Anda akan langsung diarahkan ke Portal Anggota dalam status aktif.</li>
                        </ol>
                    </div>

                    <!-- Resend option -->
                    <div class="mt-4 text-xs text-neutral-500">
                        Tidak menerima email?
                        <button
                            type="button"
                            :disabled="resendLoading"
                            class="font-semibold text-isf-red hover:underline ml-1 cursor-pointer disabled:opacity-50"
                            @click="handleResendVerification"
                        >
                            {{ resendLoading ? 'Mengirim ulang...' : 'Kirim Ulang Tautan Verifikasi' }}
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a
                            :href="loginRegisteredHref"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-neutral-900 text-white text-xs font-semibold tracking-wide uppercase hover:bg-neutral-800 transition-colors shadow-xs"
                        >
                            Ke Halaman Masuk
                        </a>
                        <button
                            type="button"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 text-xs font-semibold tracking-wide uppercase hover:bg-neutral-50 transition-colors"
                            @click="router.push('/beranda')"
                        >
                            Kembali ke Beranda
                        </button>
                    </div>
                </div>

                <!-- Registration Form -->
                <form
                    v-else
                    class="mt-8 space-y-8"
                    novalidate
                    @submit.prevent="handleSubmit"
                >
                    <!-- ============================================================= -->
                    <!-- BAGIAN 2: GENERAL INFORMATION (Strict Politico Match)         -->
                    <!-- ============================================================= -->
                    <fieldset class="space-y-4">
                        <legend class="text-xs font-bold uppercase tracking-[0.14em] text-neutral-500 pb-2 border-b border-neutral-200 w-full">
                            General Information
                        </legend>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- First Name -->
                            <div class="space-y-1.5">
                                <Label for="registerFirstName" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                    First Name <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                    <span class="sr-only">(Required)</span>
                                </Label>
                                <Input
                                    id="registerFirstName"
                                    v-model="form.first_name"
                                    type="text"
                                    placeholder="Enter First Name"
                                    autocomplete="given-name"
                                    :disabled="loading"
                                    :class="errors.first_name ? 'border-red-500 focus-visible:ring-red-500' : ''"
                                    @input="clearError('first_name')"
                                />
                                <p v-if="errors.first_name" class="text-xs text-red-600 font-medium">
                                    {{ errors.first_name[0] }}
                                </p>
                            </div>

                            <!-- Last Name -->
                            <div class="space-y-1.5">
                                <Label for="registerLastName" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                    Last Name <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                    <span class="sr-only">(Required)</span>
                                </Label>
                                <Input
                                    id="registerLastName"
                                    v-model="form.last_name"
                                    type="text"
                                    placeholder="Enter Last Name"
                                    autocomplete="family-name"
                                    :disabled="loading"
                                    :class="errors.last_name ? 'border-red-500 focus-visible:ring-red-500' : ''"
                                    @input="clearError('last_name')"
                                />
                                <p v-if="errors.last_name" class="text-xs text-red-600 font-medium">
                                    {{ errors.last_name[0] }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 items-start pt-1">
                            <!-- Nomor WhatsApp Aktif (Issue #42, pengganti Zip Code) -->
                            <div class="space-y-1.5">
                                <Label for="registerWhatsapp" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                    Nomor WhatsApp Aktif <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                    <span class="sr-only">(Required)</span>
                                </Label>
                                <Input
                                    id="registerWhatsapp"
                                    v-model="form.whatsapp"
                                    type="tel"
                                    inputmode="tel"
                                    placeholder="0812xxxxxxxx"
                                    autocomplete="tel"
                                    :disabled="loading"
                                    :aria-invalid="errors.whatsapp ? 'true' : 'false'"
                                    :aria-describedby="errors.whatsapp ? 'registerWhatsappError' : 'registerWhatsappHint'"
                                    :class="errors.whatsapp ? 'border-red-500 focus-visible:ring-red-500' : ''"
                                    @input="clearError('whatsapp')"
                                />
                                <p v-if="errors.whatsapp" id="registerWhatsappError" class="text-xs text-red-600 font-medium">
                                    {{ errors.whatsapp[0] }}
                                </p>
                                <p v-else id="registerWhatsappHint" class="text-xs text-neutral-500">
                                    Pastikan nomor aktif dan terhubung dengan WhatsApp.
                                </p>
                            </div>

                            <!-- Age Requirement Checkbox (Politico pattern) -->
                            <div class="space-y-1.5 sm:pt-6">
                                <div class="flex items-start gap-2.5">
                                    <input
                                        id="ageRequirement"
                                        v-model="form.age_requirement"
                                        type="checkbox"
                                        :disabled="loading"
                                        class="mt-1 h-4 w-4 rounded border-neutral-300 text-isf-red focus:ring-isf-red accent-red-600 cursor-pointer"
                                        @change="clearError('age_requirement')"
                                    />
                                    <Label for="ageRequirement" class="text-xs sm:text-sm text-neutral-800 font-medium cursor-pointer leading-tight">
                                        I certify that I am 18 or over <span class="text-isf-red font-bold">*</span>
                                    </Label>
                                </div>
                                <p v-if="errors.age_requirement" class="text-xs text-red-600 font-medium pt-1">
                                    {{ errors.age_requirement[0] }}
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <!-- ============================================================= -->
                    <!-- BAGIAN 3: LOGIN INFORMATION (Strict Politico Match)           -->
                    <!-- ============================================================= -->
                    <fieldset class="space-y-4 pt-2">
                        <legend class="text-xs font-bold uppercase tracking-[0.14em] text-neutral-500 pb-2 border-b border-neutral-200 w-full">
                            Login Information
                        </legend>

                        <!-- Email Address -->
                        <div class="space-y-1.5">
                            <Label for="registerEmail" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                Email Address <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                <span class="sr-only">(Required)</span>
                            </Label>
                            <Input
                                id="registerEmail"
                                v-model="form.email"
                                type="email"
                                placeholder="Enter Email Address"
                                autocomplete="email"
                                :disabled="loading"
                                :class="errors.email ? 'border-red-500 focus-visible:ring-red-500' : ''"
                                @input="clearError('email')"
                            />
                            <p v-if="errors.email" class="text-xs text-red-600 font-medium">
                                {{ errors.email[0] }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <Label for="registerPassword" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                Password <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                <span class="sr-only">(Required)</span>
                            </Label>
                            <div class="relative">
                                <Input
                                    id="registerPassword"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Enter Password"
                                    autocomplete="new-password"
                                    :disabled="loading"
                                    :class="[
                                        'pr-10',
                                        errors.password ? 'border-red-500 focus-visible:ring-red-500' : ''
                                    ]"
                                    @input="clearError('password')"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-600 focus:outline-none"
                                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    @click="togglePassword"
                                >
                                    <!-- Eye icon -->
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <!-- Eye slash icon -->
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div v-if="form.password" class="pt-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 flex-1 rounded-full bg-neutral-200 overflow-hidden">
                                        <div
                                            :class="['h-full transition-all duration-300', passwordStrength.color]"
                                            :style="{ width: `${(passwordStrength.score / 4) * 100}%` }"
                                        />
                                    </div>
                                    <span class="text-[11px] font-medium text-neutral-600">
                                        {{ passwordStrength.text }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-neutral-500 mt-1">
                                    Minimal 8 karakter dengan kombinasi huruf dan angka.
                                </p>
                            </div>
                            <p v-if="errors.password" class="text-xs text-red-600 font-medium">
                                {{ errors.password[0] }}
                            </p>
                        </div>

                        <!-- Verify Password -->
                        <div class="space-y-1.5">
                            <Label for="registerVerifyPassword" class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                Verify Password <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                <span class="sr-only">(Required)</span>
                            </Label>
                            <div class="relative">
                                <Input
                                    id="registerVerifyPassword"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    placeholder="Verify Password"
                                    autocomplete="new-password"
                                    :disabled="loading"
                                    :class="[
                                        'pr-10',
                                        errors.password_confirmation || (!passwordsMatch && form.password_confirmation)
                                            ? 'border-red-500 focus-visible:ring-red-500'
                                            : ''
                                    ]"
                                    @input="clearError('password_confirmation')"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-600 focus:outline-none"
                                    :aria-label="showConfirmPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    @click="toggleConfirmPassword"
                                >
                                    <svg v-if="!showConfirmPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="!passwordsMatch && form.password_confirmation" class="text-xs text-red-600 font-medium">
                                Verifikasi kata sandi tidak cocok.
                            </p>
                            <p v-else-if="errors.password_confirmation" class="text-xs text-red-600 font-medium">
                                {{ errors.password_confirmation[0] }}
                            </p>
                        </div>

                        <!-- ============================================================= -->
                        <!-- SECURITY VERIFICATION: SLIDE PUZZLE CAPTCHA (Issue #74)       -->
                        <!-- ============================================================= -->
                        <div class="space-y-2 pt-2 border-t border-neutral-200">
                            <Label class="text-neutral-800 text-xs sm:text-sm font-semibold">
                                Verifikasi Keamanan <span class="text-isf-red font-bold" aria-hidden="true">*</span>
                                <span class="sr-only">(Required)</span>
                            </Label>
                            <IsfSlidePuzzleCaptcha
                                ref="puzzleRef"
                                v-model="form.captcha_answer"
                                :disabled="loading"
                                @verified="onPuzzleVerified"
                                @reset="onPuzzleReset"
                            />
                            <p v-if="errors.captcha_answer" class="text-xs text-red-600 font-medium pt-1">
                                {{ errors.captcha_answer[0] }}
                            </p>
                        </div>
                    </fieldset>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <Button
                            type="submit"
                            :disabled="loading || registrationSuccess"
                            class="w-full h-11 bg-isf-red hover:bg-red-700 text-white font-ui text-sm font-bold uppercase tracking-[0.08em] shadow-sm transition-colors rounded-md"
                        >
                            <svg
                                v-if="loading"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ loading ? "Memproses Pendaftaran..." : "Register" }}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
