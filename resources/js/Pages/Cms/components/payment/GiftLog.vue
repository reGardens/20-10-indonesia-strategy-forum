<template>
    <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Log Hadiah</h3>

        <!-- Empty state -->
        <p v-if="gifts.length === 0" class="text-sm text-gray-500">
            Belum ada hadiah yang tercatat.
        </p>

        <!-- Gift table -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Pengirim
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Jumlah
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Metode
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Pesan
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Waktu
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="gift in gifts" :key="gift.id">
                        <td
                            class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap"
                        >
                            {{ gift.senderName }}
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap"
                        >
                            {{ formatCurrency(gift.amount) }}
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap"
                        >
                            {{ gift.method || "-" }}
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate"
                        >
                            {{ gift.message || "-" }}
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap"
                        >
                            {{ formatDate(gift.createdAt) }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:text-red-700 font-medium"
                                @click="confirmDelete(gift)"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total accumulation -->
        <div v-if="gifts.length > 0" class="mt-4 flex justify-end">
            <div class="rounded-lg bg-emerald-50 px-4 py-3">
                <span class="text-sm font-medium text-gray-700"
                    >Total Hadiah:
                </span>
                <span class="text-lg font-bold text-emerald-700">{{
                    formatCurrency(totalGifts)
                }}</span>
            </div>
        </div>

        <!-- Confirm delete dialog -->
        <ConfirmDialog
            :visible="showDeleteDialog"
            title="Hapus Hadiah"
            :message="`Apakah Anda yakin ingin menghapus hadiah dari ${giftToDelete?.senderName || ''}?`"
            confirm-text="Hapus"
            cancel-text="Batal"
            @confirm="handleDelete"
            @cancel="showDeleteDialog = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useStore } from "vuex";
import { formatCurrency, formatDate } from "@/Utils/formatters";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";

const store = useStore();

const showDeleteDialog = ref(false);
const giftToDelete = ref(null);

const gifts = computed(() => store.getters["payments/gifts"]);
const totalGifts = computed(() => store.getters["payments/totalGifts"]);

function confirmDelete(gift) {
    giftToDelete.value = gift;
    showDeleteDialog.value = true;
}

async function handleDelete() {
    if (!giftToDelete.value) return;

    try {
        await store.dispatch("payments/deleteGift", giftToDelete.value.id);
    } catch (err) {
        // Error is handled by the store
    } finally {
        showDeleteDialog.value = false;
        giftToDelete.value = null;
    }
}
</script>
