<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import {
    Button,
    Badge,
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/UI";

defineOptions({ layout: CmsLayout });

const { t } = useI18n();

const props = defineProps({
    roles: { type: Array, required: true },
});

const showDeleteDialog = ref(false);
const roleToDelete = ref(null);

function confirmDelete(role) {
    roleToDelete.value = role;
    showDeleteDialog.value = true;
}

function deleteRole() {
    if (roleToDelete.value) {
        router.delete(`/cms/roles/${roleToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteDialog.value = false;
                roleToDelete.value = null;
            },
        });
    }
}
</script>

<template>
    <Head :title="t('role.title')" />

    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ t("role.title") }}
            </h1>
            <Link href="/cms/roles/create">
                <Button
                    class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600"
                >
                    {{ t("role.add_role") }}
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-border overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>#</TableHead>
                        <TableHead>{{ t("role.name") }}</TableHead>
                        <TableHead>{{ t("role.guard") }}</TableHead>
                        <TableHead>Users</TableHead>
                        <TableHead class="text-right">{{
                            t("common.actions")
                        }}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(role, idx) in roles" :key="role.id">
                        <TableCell class="font-medium">{{ idx + 1 }}</TableCell>
                        <TableCell>
                            <Badge
                                class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300"
                            >
                                {{ role.name }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{
                            role.guard_name
                        }}</TableCell>
                        <TableCell>{{ role.users_count }}</TableCell>
                        <TableCell class="text-right space-x-2">
                            <Link :href="`/cms/roles/${role.id}/edit`">
                                <Button variant="outline" size="sm">{{
                                    t("common.edit")
                                }}</Button>
                            </Link>
                            <Button
                                v-if="role.name !== 'super-admin'"
                                variant="destructive"
                                size="sm"
                                @click="confirmDelete(role)"
                            >
                                {{ t("common.delete") }}
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t("common.delete") }}</DialogTitle>
                    <DialogDescription>{{
                        t("common.confirm_delete")
                    }}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                        >{{ t("common.cancel") }}</Button
                    >
                    <Button variant="destructive" @click="deleteRole">{{
                        t("common.delete")
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
