<template>
  <div class="payment-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Pembayaran & Hadiah</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat data pembayaran..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- Bank Accounts Section -->
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Rekening Bank</h2>
          <button
            v-if="!showBankForm"
            type="button"
            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
            @click="openAddBankForm"
          >
            Tambah Rekening
          </button>
        </div>

        <!-- Bank account list -->
        <div
          v-if="bankAccounts.length === 0 && !showBankForm"
          class="rounded-lg border border-gray-200 bg-white p-5"
        >
          <p class="text-sm text-gray-500">
            Belum ada rekening bank. Tambahkan rekening untuk menerima hadiah
            digital.
          </p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="account in bankAccounts"
            :key="account.id"
            class="rounded-lg border border-gray-200 bg-white p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-900">
                {{ account.bankName }}
              </p>
              <p class="text-sm text-gray-600">{{ account.accountNumber }}</p>
              <p class="text-xs text-gray-500">
                a.n. {{ account.accountHolder }}
              </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                type="button"
                class="text-sm text-emerald-600 hover:text-emerald-700 font-medium"
                @click="openEditBankForm(account)"
              >
                Edit
              </button>
              <button
                type="button"
                class="text-sm text-red-600 hover:text-red-700 font-medium"
                @click="confirmDeleteAccount(account)"
              >
                Hapus
              </button>
            </div>
          </div>
        </div>

        <!-- Bank account form (add/edit) -->
        <BankAccountForm
          v-if="showBankForm"
          :account="editingAccount"
          @submit="handleBankFormSubmit"
          @cancel="closeBankForm"
        />
      </section>

      <!-- QRIS Section -->
      <QrisUploader />

      <!-- Gift Log Section -->
      <GiftLog />

      <!-- Success message -->
      <p v-if="successMessage" class="text-sm text-green-600" role="status">
        {{ successMessage }}
      </p>
    </template>

    <!-- Confirm delete dialog -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      title="Hapus Rekening Bank"
      :message="`Apakah Anda yakin ingin menghapus rekening ${accountToDelete?.bankName || ''}?`"
      confirm-text="Hapus"
      cancel-text="Batal"
      @confirm="handleDeleteAccount"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";
import BankAccountForm from "@/Pages/Cms/components/payment/BankAccountForm.vue";
import QrisUploader from "@/Pages/Cms/components/payment/QrisUploader.vue";
import GiftLog from "@/Pages/Cms/components/payment/GiftLog.vue";

const store = useStore();

const showBankForm = ref(false);
const editingAccount = ref(null);
const showDeleteDialog = ref(false);
const accountToDelete = ref(null);
const successMessage = ref("");

const loading = computed(() => store.getters["payments/isLoading"]);
const error = computed(() => store.getters["payments/error"]);
const bankAccounts = computed(() => store.getters["payments/bankAccounts"]);

async function loadData() {
  await store.dispatch("payments/fetchPayments");
}

function openAddBankForm() {
  editingAccount.value = null;
  showBankForm.value = true;
}

function openEditBankForm(account) {
  editingAccount.value = { ...account };
  showBankForm.value = true;
}

function closeBankForm() {
  showBankForm.value = false;
  editingAccount.value = null;
}

async function handleBankFormSubmit(data) {
  try {
    if (editingAccount.value) {
      await store.dispatch("payments/updateBankAccount", {
        id: editingAccount.value.id,
        data,
      });
      showSuccess("Rekening bank berhasil diperbarui");
    } else {
      await store.dispatch("payments/addBankAccount", data);
      showSuccess("Rekening bank berhasil ditambahkan");
    }
    closeBankForm();
  } catch (err) {
    // Error is handled by the store
  }
}

function confirmDeleteAccount(account) {
  accountToDelete.value = account;
  showDeleteDialog.value = true;
}

async function handleDeleteAccount() {
  if (!accountToDelete.value) return;

  try {
    await store.dispatch(
      "payments/deleteBankAccount",
      accountToDelete.value.id,
    );
    showSuccess("Rekening bank berhasil dihapus");
  } catch (err) {
    // Error is handled by the store
  } finally {
    showDeleteDialog.value = false;
    accountToDelete.value = null;
  }
}

function showSuccess(message) {
  successMessage.value = message;
  setTimeout(() => {
    successMessage.value = "";
  }, 3000);
}

onMounted(() => {
  loadData();
});
</script>
