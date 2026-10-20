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
    name: props.user.name,
    email: props.user.email,
    password: "",
    role: "super-admin",
    // role: props.user.roles?.[0]?.name ?? "",
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
        <h1 class="text-2xl font-bold tracking-tight">
            {{ t("users.edit_user") }}
        </h1>

        <div class="max-w-lg">
            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <Label for="name">{{ t("users.name") }}</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
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

                <!-- <div class="space-y-2">
                    <Label for="role">{{ t("users.role") }}</Label>
                    <Select v-model="form.role">
                        <SelectTrigger
                            ><SelectValue placeholder="Pilih role"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.name"
                                >{{ role.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.role" class="text-sm text-destructive">
                        {{ form.errors.role }}
                    </p>
                </div> -->

                <div class="flex items-center gap-4 pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >{{ t("common.save") }}</Button
                    >
                    <Link href="/cms/users"
                        ><Button type="button" variant="outline">{{
                            t("common.cancel")
                        }}</Button></Link
                    >
                </div>
            </form>
        </div>
    </div>
</template>
