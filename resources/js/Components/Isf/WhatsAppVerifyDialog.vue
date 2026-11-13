<script setup>
/**
 * WhatsAppVerifyDialog — dialog global untuk mengisi/memperbarui Nomor WhatsApp Aktif lalu
 * memverifikasinya dengan kode OTP (Issue #42). Dipakai di header publik (menu profil) dan CMS.
 *
 * <WhatsAppVerifyDialog v-model:open="open" :whatsapp="user.whatsapp" :verified="user.whatsappVerified"
 *     @updated="(status) => ..." />
 *
 * Langkah: (1) nomor → "Kirim kode"  (2) kode 6 digit → "Verifikasi" (kirim ulang setelah jeda)  (3) selesai.
 * Prop `verification` false (default, saklar WHATSAPP_VERIFICATION mati): hanya simpan nomor, tanpa OTP.
 * Event "updated" membawa status terbaru { whatsapp, whatsappDisplay, whatsappVerified } setiap kali berubah.
 * Memakai komponen Dialog UI yang sudah ada; warna ditulis eksplisit agar tampil benar di SPA publik
 * (yang tidak memuat variabel tema CMS) maupun di CMS.
 */
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import Dialog from "@/Components/UI/Dialog.vue";
import DialogContent from "@/Components/UI/DialogContent.vue";
import DialogHeader from "@/Components/UI/DialogHeader.vue";
import DialogTitle from "@/Components/UI/DialogTitle.vue";
import DialogDescription from "@/Components/UI/DialogDescription.vue";
import Input from "@/Components/UI/Input.vue";
import Label from "@/Components/UI/Label.vue";
import {
    resendWhatsAppCode,
    saveWhatsApp,
    verifyWhatsAppCode,
} from "@/api/services/isfWhatsAppService";

const props = defineProps({
    open: { type: Boolean, default: false },
    /** Nomor tersimpan (format 628xx), boleh kosong. */
    whatsapp: { type: String, default: "" },
    verified: { type: Boolean, default: false },
    /** Verifikasi OTP aktif? false → dialog hanya menyimpan nomor (tanpa kode). */
    verification: { type: Boolean, default: false },
});
const emit = defineEmits(["update:open", "updated"]);

const step = ref("number"); // number | code | done
const number = ref("");
const code = ref("");
const displayNumber = ref("");
const error = ref("");
const info = ref("");
const busy = ref(false);
const resendIn = ref(0);
let timer = null;

const NUMBER_RE = /^(\+?62|0)8[1-9][0-9]{6,11}$/;

function toLocal(value) {
    // 6281234567890 → 081234567890 (lebih familiar untuk diedit)
    return value && value.startsWith("62") ? `0${value.slice(2)}` : value || "";
}

function startCountdown(seconds) {
    clearInterval(timer);
    resendIn.value = Math.max(0, Number(seconds) || 0);
    if (!resendIn.value) return;
    timer = setInterval(() => {
        resendIn.value = Math.max(0, resendIn.value - 1);
        if (!resendIn.value) clearInterval(timer);
    }, 1000);
}

function reset() {
    number.value = toLocal(props.whatsapp);
    code.value = "";
    error.value = "";
    info.value = "";
    busy.value = false;
    // Selalu mulai dari langkah nomor: user bisa memastikan/mengganti nomor sebelum kode dikirim.
    step.value = "number";
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            reset();
            nextTick(() =>
                document.getElementById("wa-verify-number")?.focus(),
            );
        } else {
            clearInterval(timer);
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => clearInterval(timer));

function applyStatus(data) {
    displayNumber.value = data?.whatsappDisplay || displayNumber.value;
    if (typeof data?.resendIn === "number") startCountdown(data.resendIn);
    emit("updated", {
        whatsapp: data?.whatsapp ?? null,
        whatsappDisplay: data?.whatsappDisplay ?? null,
        whatsappVerified: Boolean(data?.whatsappVerified),
    });
}

function errorMessage(err, fallback) {
    const data = err?.response?.data;
    if (data) applyStatus(data);
    const first = data?.errors && Object.values(data.errors)[0];
    return (
        (Array.isArray(first) ? first[0] : null) || data?.message || fallback
    );
}

async function submitNumber() {
    error.value = "";
    const cleaned = number.value.replace(/[\s-]/g, "");
    if (!NUMBER_RE.test(cleaned)) {
        error.value =
            "Gunakan nomor seluler Indonesia aktif, contoh 0812xxxxxxxx.";
        return;
    }
    busy.value = true;
    try {
        const data = await saveWhatsApp(cleaned);
        applyStatus(data);
        info.value = data?.message || "";
        if (!props.verification || data?.whatsappVerified) {
            step.value = "done";
        } else {
            step.value = "code";
            nextTick(() => document.getElementById("wa-verify-code")?.focus());
        }
    } catch (err) {
        // 429: kode masih berlaku dari permintaan sebelumnya → tetap lanjut ke langkah kode
        if (err?.response?.status === 429) {
            info.value = errorMessage(err, "");
            step.value = "code";
            nextTick(() => document.getElementById("wa-verify-code")?.focus());
        } else {
            error.value = errorMessage(err, "Nomor gagal disimpan. Coba lagi.");
        }
    } finally {
        busy.value = false;
    }
}

async function submitCode() {
    error.value = "";
    if (!/^\d{6}$/.test(code.value)) {
        error.value = "Kode verifikasi terdiri dari 6 angka.";
        return;
    }
    busy.value = true;
    try {
        const data = await verifyWhatsAppCode(code.value);
        applyStatus(data);
        step.value = "done";
    } catch (err) {
        error.value = errorMessage(err, "Kode gagal diverifikasi. Coba lagi.");
    } finally {
        busy.value = false;
    }
}

async function resend() {
    if (resendIn.value || busy.value) return;
    error.value = "";
    busy.value = true;
    try {
        const data = await resendWhatsAppCode();
        applyStatus(data);
        info.value = data?.message || "Kode baru dikirim.";
        code.value = "";
    } catch (err) {
        error.value = errorMessage(err, "Kode gagal dikirim. Coba lagi.");
    } finally {
        busy.value = false;
    }
}

function changeNumber() {
    step.value = "number";
    error.value = "";
    info.value = "";
    nextTick(() => document.getElementById("wa-verify-number")?.focus());
}

const btn =
    "inline-flex items-center justify-center rounded-md px-4 py-2 font-space-grotesk text-xs font-bold uppercase tracking-[0.1em] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50";
const primaryBtn = `${btn} bg-isf-red text-white hover:bg-isf-red-dark`;
const ghostBtn = `${btn} border border-neutral-300 text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800`;
const title = computed(() =>
    step.value === "done"
        ? props.verification
            ? "Nomor WhatsApp terverifikasi"
            : "Nomor WhatsApp tersimpan"
        : props.whatsapp
          ? "Perbarui Nomor WhatsApp"
          : "Tambahkan Nomor WhatsApp",
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-md rounded-none border-neutral-200 border-t-2 border-t-isf-red bg-white text-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100"
        >
            <!-- Pembungkus: DialogContent di-render lewat portal, atribut di komponennya tidak sampai ke DOM -->
            <div class="grid gap-4" data-testid="wa-verify-dialog">
                <DialogHeader>
                    <DialogTitle class="font-headline text-xl">{{
                        title
                    }}</DialogTitle>
                    <DialogDescription
                        class="text-sm text-neutral-600 dark:text-neutral-400"
                    >
                        <template v-if="step === 'number'">
                            Nomor WhatsApp aktif dipakai untuk keperluan
                            keanggotaan ISF.
                            <template v-if="verification"
                                >Kode verifikasi 6 angka akan dikirim lewat
                                WhatsApp.</template
                            >
                        </template>
                        <template v-else-if="step === 'code'">
                            Masukkan kode 6 angka yang dikirim ke
                            <strong>{{ displayNumber }}</strong
                            >.
                        </template>
                        <template v-else-if="verification"
                            >Terima kasih, nomor WhatsApp Anda sudah aktif dan
                            terverifikasi.</template
                        >
                        <template v-else
                            >Terima kasih, nomor WhatsApp Anda sudah
                            tersimpan.</template
                        >
                    </DialogDescription>
                </DialogHeader>

                <form
                    v-if="step === 'number'"
                    class="space-y-3"
                    novalidate
                    @submit.prevent="submitNumber"
                >
                    <div class="space-y-1.5">
                        <Label
                            for="wa-verify-number"
                            class="text-sm font-semibold"
                            >Nomor WhatsApp Aktif</Label
                        >
                        <Input
                            id="wa-verify-number"
                            v-model="number"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="0812xxxxxxxx"
                            :disabled="busy"
                            :aria-invalid="error ? 'true' : 'false'"
                            :aria-describedby="
                                error ? 'wa-verify-error' : undefined
                            "
                            class="bg-white text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100"
                        />
                    </div>
                    <p
                        v-if="error"
                        id="wa-verify-error"
                        class="text-sm text-isf-red-dark"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                    <div class="flex flex-wrap justify-end gap-2 pt-1">
                        <button
                            type="button"
                            :class="ghostBtn"
                            :disabled="busy"
                            @click="emit('update:open', false)"
                        >
                            Nanti saja
                        </button>
                        <button
                            type="submit"
                            :class="primaryBtn"
                            :disabled="busy"
                            data-testid="wa-send-code"
                        >
                            {{
                                busy
                                    ? verification
                                        ? "Mengirim…"
                                        : "Menyimpan…"
                                    : verification
                                      ? "Kirim kode"
                                      : "Simpan"
                            }}
                        </button>
                    </div>
                </form>

                <form
                    v-else-if="step === 'code'"
                    class="space-y-3"
                    novalidate
                    @submit.prevent="submitCode"
                >
                    <p
                        v-if="info"
                        class="text-sm text-emerald-700 dark:text-emerald-400"
                        role="status"
                    >
                        {{ info }}
                    </p>
                    <div class="space-y-1.5">
                        <Label
                            for="wa-verify-code"
                            class="text-sm font-semibold"
                            >Kode verifikasi</Label
                        >
                        <Input
                            id="wa-verify-code"
                            v-model="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="••••••"
                            :disabled="busy"
                            :aria-invalid="error ? 'true' : 'false'"
                            :aria-describedby="
                                error ? 'wa-verify-error' : undefined
                            "
                            class="bg-white text-center font-mono text-lg tracking-[0.5em] text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100"
                            @input="code = code.replace(/\D/g, '').slice(0, 6)"
                        />
                    </div>
                    <p
                        v-if="error"
                        id="wa-verify-error"
                        class="text-sm text-isf-red-dark"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 text-sm"
                    >
                        <button
                            type="button"
                            class="font-semibold text-isf-red-dark underline-offset-2 hover:underline disabled:cursor-not-allowed disabled:no-underline disabled:opacity-60"
                            :disabled="busy || resendIn > 0"
                            data-testid="wa-resend"
                            @click="resend"
                        >
                            {{
                                resendIn > 0
                                    ? `Kirim ulang dalam ${resendIn} dtk`
                                    : "Kirim ulang kode"
                            }}
                        </button>
                        <button
                            type="button"
                            class="text-neutral-600 underline-offset-2 hover:underline dark:text-neutral-400"
                            :disabled="busy"
                            @click="changeNumber"
                        >
                            Ganti nomor
                        </button>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            :class="primaryBtn"
                            :disabled="busy || code.length !== 6"
                            data-testid="wa-verify-submit"
                        >
                            {{ busy ? "Memeriksa…" : "Verifikasi" }}
                        </button>
                    </div>
                </form>

                <div v-else class="flex justify-end">
                    <button
                        type="button"
                        :class="primaryBtn"
                        @click="emit('update:open', false)"
                    >
                        Selesai
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
