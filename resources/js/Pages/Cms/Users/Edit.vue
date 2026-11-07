<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import { confirmSave } from "@/Composables/useAlert";
import {
    Button,
    Input,
    Label,
    Select,
    SelectTrigger,
    SelectContent,
    SelectItem,
    SelectValue,
} from "@/Components/UI";

defineOptions({ layout: CmsLayout });
const { t } = useI18n();

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
});

const form = useForm({
    name: props.user.name || "",
    first_name: props.user.first_name || "",
    last_name: props.user.last_name || "",
    zip_code: props.user.zip_code || "",
    email: props.user.email || "",
    password: "",
    role: props.user.roles?.[0]?.name || "user",
});

async function submit() {
    const confirmed = await confirmSave();
    if (!confirmed) return;
    form.put(`/cms/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="t('users.edit_user')" />

    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-border">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ t("users.edit_user") }}
            </h1>
            <div class="flex items-center gap-2">
                <Link href="/cms/users">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="first_name">Nama Depan (First Name)</Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            placeholder="Nama depan"
                        />
                        <p v-if="form.errors.first_name" class="text-sm text-destructive">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="last_name">Nama Belakang (Last Name)</Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            placeholder="Nama belakang"
                        />
                        <p v-if="form.errors.last_name" class="text-sm text-destructive">
                            {{ form.errors.last_name }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="name">{{ t("users.name") }}</Label>
                        <Input id="name" v-model="form.name" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="zip_code">Kode Pos (Zip Code)</Label>
                        <Input
                            id="zip_code"
                            v-model="form.zip_code"
                            placeholder="Kode pos"
                        />
                        <p v-if="form.errors.zip_code" class="text-sm text-destructive">
                            {{ form.errors.zip_code }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="email">{{ t("users.email") }}</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <p
                        v-if="form.errors.email"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        :placeholder="t('users.password_placeholder')"
                    />
                    <p
                        v-if="form.errors.password"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>
            </form>
        </div>
    </div>
</template>
