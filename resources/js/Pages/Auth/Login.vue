<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Button, Input, Label } from "@/Components/UI";
import { useI18n } from "@/Composables/useI18n";
import { useDarkMode } from "@/Composables/useDarkMode";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

const { t, locale, toggleLocale } = useI18n();
const { isDark, toggleDarkMode } = useDarkMode();

const page = usePage();
const flashError = computed(() => page.props.flash?.error);

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
        <!-- Left Panel — Branding (emerald gradient) -->
        <div
            class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 relative items-center justify-center"
        >
            <!-- Decorative circles -->
            <div
                class="absolute top-0 left-0 w-72 h-72 bg-emerald-500/20 rounded-full -translate-x-1/2 -translate-y-1/2"
            />
            <div
                class="absolute bottom-0 right-0 w-96 h-96 bg-emerald-400/10 rounded-full translate-x-1/3 translate-y-1/3"
            />

            <div class="relative z-10 text-center text-white px-8">
                <!-- Logo -->
                <div
                    class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-xl mb-6"
                >
                    <span class="text-emerald-600 text-4xl font-bold">C</span>
                </div>
                <h1 class="text-3xl font-bold mb-3">CMS</h1>
                <p
                    class="text-emerald-100 text-sm max-w-xs mx-auto leading-relaxed"
                >
                    Kelola konten, pengguna, dan konfigurasi landing page dari
                    satu dashboard.
                </p>
            </div>
        </div>

        <!-- Right Panel — Login Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center bg-background dark:bg-[hsl(160,30%,4%)] relative px-6"
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
                    <IconComponent v-if="isDark" name="Sun" class="h-3.5 w-3.5" />
                    <IconComponent v-else name="Moon" class="h-3.5 w-3.5" />
                    <span>{{ isDark ? "Light Mode" : "Dark Mode" }}</span>
                </button>
            </div>

            <div class="w-full max-w-sm">
                <!-- Mobile logo (shown on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div
                        class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-emerald-600 shadow-lg mb-3"
                    >
                        <span class="text-white text-2xl font-bold">C</span>
                    </div>
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
                        class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">{{
                            t("common.loading")
                        }}</span>
                        <span v-else>{{
                            locale === "id" ? "Masuk" : "Sign In"
                        }}</span>
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
