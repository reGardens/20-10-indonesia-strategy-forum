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
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

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
    whatsapp: props.user.whatsapp || "",
    email: props.user.email || "",
    password: "",
    role: props.user.roles?.[0]?.name || "member",
});

async function submit() {
    const confirmed = await confirmSave();
    if (!confirmed) return;
    form.put(`/cms/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="t('users.edit_user')" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header with Back Button -->
        <div class="flex items-center justify-between pb-4 border-b border-border">
            <div class="flex items-center gap-3">
                <Link href="/cms/users">
                    <Button variant="outline" size="sm" class="h-8 w-8 p-0">
                        <IconComponent name="ChevronLeft" :size="16" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        {{ t("users.edit_user") }}
                    </h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Mengubah data akun pengguna #{{ props.user.id }}.
                    </p>
                </div>
            </div>
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
                    <CardTitle class="text-base font-semibold text-foreground">Informasi Akun Pengguna</CardTitle>
                    <CardDescription class="text-xs text-muted-foreground">
                        Identitas pengguna, data kontak, peran otorisasi, dan password (kosongkan jika tidak diubah).
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="first_name" class="text-xs font-medium text-foreground">Nama Depan (First Name)</Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                placeholder="Nama depan"
                                class="text-xs"
                            />
                            <p
                                v-if="form.errors.first_name"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.first_name }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="last_name" class="text-xs font-medium text-foreground">Nama Belakang (Last Name)</Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                placeholder="Nama belakang"
                                class="text-xs"
                            />
                            <p
                                v-if="form.errors.last_name"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="name" class="text-xs font-medium text-foreground">{{ t("users.name") }} *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                :placeholder="t('users.name')"
                                class="text-xs"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="role" class="text-xs font-medium text-foreground">{{ t("users.role") }} *</Label>
                            <Select v-model="form.role">
                                <SelectTrigger class="text-xs">
                                    <SelectValue
                                        :placeholder="t('users.select_role')"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.name"
                                    >
                                        {{ role.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.role"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.role }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="whatsapp" class="text-xs font-medium text-foreground">No. WhatsApp</Label>
                        <Input
                            id="whatsapp"
                            v-model="form.whatsapp"
                            placeholder="08123456789"
                            class="text-xs"
                        />
                        <p
                            v-if="form.errors.whatsapp"
                            class="text-xs text-destructive mt-1"
                        >
                            {{ form.errors.whatsapp }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border">
                        <div class="space-y-1.5">
                            <Label for="email" class="text-xs font-medium text-foreground">{{ t("users.email") }} *</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :placeholder="t('users.email')"
                                class="text-xs"
                                required
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password" class="text-xs font-medium text-foreground">Password Baru (Opsional)</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Kosongkan bila tidak ingin mengubah password"
                                class="text-xs"
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-xs text-destructive mt-1"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Link href="/cms/users">
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
