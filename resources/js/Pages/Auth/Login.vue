<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Button, Input, Label } from "@/Components/UI";
import { useI18n } from "@/Composables/useI18n";
import { useDarkMode } from "@/Composables/useDarkMode";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import IsfLogo from "@/Components/Isf/IsfLogo.vue";

const { t, locale, toggleLocale } = useI18n();
const { isDark, toggleDarkMode } = useDarkMode();

const page = usePage();
const flashError = computed(() => page.props.flash?.error);

const isJustRegistered = computed(() => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        return params.get("registered") === "true";
    }
    return false;
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

function submit() {
    form.post("/login", {
        onFinish: () => {
            form.reset("password");
        },
    });
}
</script>

<template>
    <Head :title="t('auth.login')" />

    <div class="h-screen overflow-hidden flex">
        <!-- Left Panel — Branding (ISF navy + logo) -->
        <div
            class="hidden lg:flex lg:w-1/2 bg-isf-navy relative items-center justify-center overflow-hidden"
        >
            <!-- Decorative circles -->
            <div
                class="absolute top-0 left-0 w-72 h-72 bg-isf-red/10 rounded-full -translate-x-1/2 -translate-y-1/2"
            />
            <div
                class="absolute bottom-0 right-0 w-96 h-96 bg-isf-panel rounded-full translate-x-1/3 translate-y-1/3"
            />

            <div class="relative z-10 text-center text-isf-white px-8">
                <!-- Logo -->
                <IsfLogo
                    size="xl"
                    class="mb-6 h-40 w-40 rounded-2xl shadow-xl"
                />
                <h1 class="text-3xl font-bold mb-3">
                    Indonesia Strategy Forum
                </h1>
                <p
                    class="text-isf-silver text-sm max-w-xs mx-auto leading-relaxed"
                >
                    Kelola konten publik, portal anggota, dan pengguna dari satu
                    dashboard.
                </p>
            </div>
        </div>

        <!-- Right Panel — Login Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center bg-background relative px-6"
        >
            <!-- Top right: dark mode toggle -->
            <div class="absolute top-4 right-4 flex items-center gap-2">
                <button
                    @click="toggleLocale"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border border-border hover:bg-accent transition-colors uppercase"
                >
                    {{ locale === "id" ? "EN" : "ID" }}
                </button>
                <button
                    @click="toggleDarkMode"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-border hover:bg-accent transition-colors"
                >
                    <IconComponent
                        v-if="isDark"
                        name="Sun"
                        class="h-3.5 w-3.5"
                    />
                    <IconComponent v-else name="Moon" class="h-3.5 w-3.5" />
                    <span>{{ isDark ? "Light Mode" : "Dark Mode" }}</span>
                </button>
            </div>

            <div class="w-full max-w-sm">
                <!-- Mobile logo (shown on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <IsfLogo size="lg" class="mb-3 shadow-lg" />
                </div>

                <!-- Heading -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-foreground">
                        {{ locale === "id" ? "Selamat datang" : "Welcome" }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ t("auth.login_subtitle") }}
                    </p>
                </div>

                <!-- Flash Error -->
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive"
                >
                    {{ flashError }}
                </div>

                <!-- Registration Success Banner -->
                <div
                    v-if="isJustRegistered"
                    class="mb-4 rounded-lg border border-emerald-500/50 bg-emerald-500/10 p-3 text-sm text-emerald-700 dark:text-emerald-400 flex items-center gap-2"
                >
                    <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ locale === "id" ? "Pendaftaran anggota berhasil! Silakan masuk dengan akun Anda." : "Registration successful! Please sign in with your account." }}</span>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-sm">{{
                            t("auth.email")
                        }}</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="admin@example.com"
                            autocomplete="email"
                            required
                            class="h-11"
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <Label for="password" class="text-sm">{{
                            t("auth.password")
                        }}</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            class="h-11"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <Button
                        type="submit"
                        class="w-full h-11 bg-primary hover:bg-isf-red-dark text-primary-foreground font-semibold"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">{{
                            t("common.loading")
                        }}</span>
                        <span v-else>{{
                            locale === "id" ? "Masuk" : "Sign In"
                        }}</span>
                    </Button>

                    <!-- Link to Register if user has no account -->
                    <div class="text-center text-sm text-muted-foreground pt-4 border-t border-border">
                        <span>{{ locale === "id" ? "Belum memiliki akun anggota?" : "Don't have a member account?" }}</span>
                        <a
                            href="/register"
                            class="ml-1.5 font-semibold text-isf-red hover:underline underline-offset-4 transition-colors"
                        >
                            {{ locale === "id" ? "Daftar di sini" : "Register here" }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
