<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import { Button, Input, Label } from "@/Components/UI";

defineOptions({ layout: CmsLayout });

const { t } = useI18n();

const props = defineProps({
    role: { type: Object, required: true },
});

const form = useForm({ name: props.role.name });

function submit() {
    form.put(`/cms/roles/${props.role.id}`);
}
</script>

<template>
    <Head :title="t('role.edit_role')" />

    <div class="p-6 space-y-6">
        <h1 class="text-2xl font-bold tracking-tight">
            {{ t("role.edit_role") }}
        </h1>

        <div class="max-w-md">
            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <Label for="name">{{ t("role.name") }}</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :placeholder="t('role.name')"
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        {{ t("common.save") }}
                    </Button>
                    <Link href="/cms/roles">
                        <Button type="button" variant="outline">{{
                            t("common.cancel")
                        }}</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
