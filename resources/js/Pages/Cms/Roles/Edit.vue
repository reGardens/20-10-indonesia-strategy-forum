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
        <div class="flex items-center justify-between pb-4 border-b border-border">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ t("role.edit_role") }}
            </h1>
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
                    class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
                >
                    {{ t("common.save") }}
                </Button>
            </div>
        </div>

        <div class="w-full">
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
            </form>
        </div>
    </div>
</template>
