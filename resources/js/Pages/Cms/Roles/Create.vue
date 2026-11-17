<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import {
    Button,
    Input,
    Label,
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });

const { t } = useI18n();

const form = useForm({ name: "" });

function submit() {
    form.post("/cms/roles");
}
</script>

<template>
    <Head :title="t('role.add_role')" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header with Back Button -->
        <div class="flex items-center justify-between pb-4 border-b border-border">
            <div class="flex items-center gap-3">
                <Link href="/cms/roles">
                    <Button variant="outline" size="sm" class="h-8 w-8 p-0">
                        <IconComponent name="ChevronLeft" :size="16" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        {{ t("role.add_role") }}
                    </h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Tambahkan hak akses peran baru untuk pengguna sistem CMS.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link href="/cms/roles">
                    <Button type="button" variant="outline" size="sm" class="h-9 text-xs">
                        {{ t("common.cancel") }}
                    </Button>
                </Link>
                <Button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center gap-1.5"
                >
                    <IconComponent v-if="form.processing" name="Refresh" :size="14" class="animate-spin" />
                    <IconComponent v-else name="Check" :size="14" />
                    {{ t("common.save") }}
                </Button>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card class="bg-card border-border shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-base font-semibold text-foreground">Informasi Peran</CardTitle>
                    <CardDescription class="text-xs text-muted-foreground">
                        Nama peran sistem (contoh: super_admin, editor, penulis, dsb).
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="name" class="text-xs font-medium text-foreground">{{ t("role.name") }} *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :placeholder="t('role.name')"
                            class="text-xs"
                            required
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Link href="/cms/roles">
                    <Button type="button" variant="outline" size="sm" class="h-9 text-xs">
                        {{ t("common.cancel") }}
                    </Button>
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center gap-1.5"
                >
                    <IconComponent v-if="form.processing" name="Refresh" :size="14" class="animate-spin" />
                    <IconComponent v-else name="Check" :size="14" />
                    {{ t("common.save") }}
                </Button>
            </div>
        </form>
    </div>
</template>
