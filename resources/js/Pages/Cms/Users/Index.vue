<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import { confirmDelete } from "@/Composables/useAlert";
import {
    Button,
    Badge,
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/UI";

defineOptions({ layout: CmsLayout });
const { t } = useI18n();

const props = defineProps({
    users: { type: Object, required: true },
});

async function handleDelete(user) {
    const confirmed = await confirmDelete(user.name);
    if (!confirmed) return;
    router.delete(`/cms/users/${user.id}`);
}

function formatDate(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}
</script>

<template>
    <Head :title="t('users.title')" />

    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ t("users.title") }}
            </h1>
            <Link href="/cms/users/create">
                <Button
                    class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600"
                >
                    {{ t("users.add_user") }}
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-border overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{{ t("users.name") }}</TableHead>
                        <TableHead>{{ t("users.email") }}</TableHead>
                        <!-- <TableHead>{{ t("users.role") }}</TableHead> -->
                        <TableHead>{{ t("users.created_at") }}</TableHead>
                        <TableHead class="text-right">{{
                            t("common.actions")
                        }}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="font-medium">{{
                            user.name
                        }}</TableCell>
                        <TableCell>{{ user.email }}</TableCell>
                        <!-- <TableCell>
                            <Badge
                                v-for="role in user.roles"
                                :key="role.id"
                                class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300"
                            >
                                {{ role.name }}
                            </Badge>
                        </TableCell> -->
                        <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                        <TableCell class="text-right space-x-2">
                            <template v-if="user.email !== 'superadmin@gmail.com' && !user.roles?.some(r => r.name === 'super-admin')">
                                <Link :href="`/cms/users/${user.id}/edit`">
                                    <Button variant="outline" size="sm">{{
                                        t("common.edit")
                                    }}</Button>
                                </Link>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="handleDelete(user)"
                                    >{{ t("common.delete") }}</Button
                                >
                            </template>
                            <span v-else class="text-xs text-muted-foreground italic px-3">Protected</span>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell
                            colspan="5"
                            class="text-center py-8 text-muted-foreground"
                            >{{ t("common.no_data") }}</TableCell
                        >
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-center gap-2"
        >
            <template v-for="link in users.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 transition-colors',
                        link.active
                            ? 'border border-input bg-background'
                            : 'hover:bg-accent',
                    ]"
                    preserve-scroll
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </div>
    </div>
</template>
