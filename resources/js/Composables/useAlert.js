/**
 * useAlert Composable
 * Global SweetAlert2 wrapper untuk notifikasi dan confirmation modal.
 * Digunakan di semua halaman CMS.
 */
import Swal from 'sweetalert2';

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

/**
 * Show success notification toast.
 * @param {string} message
 */
export function alertSuccess(message = 'Berhasil disimpan!') {
    Toast.fire({ icon: 'success', title: message });
}

/**
 * Show error notification toast.
 * @param {string} message
 */
export function alertError(message = 'Terjadi kesalahan!') {
    Toast.fire({ icon: 'error', title: message });
}

/**
 * Show warning notification toast.
 * @param {string} message
 */
export function alertWarning(message = 'Perhatian!') {
    Toast.fire({ icon: 'warning', title: message });
}

/**
 * Show confirmation modal before action.
 * @param {object} options
 * @param {string} options.title - Modal title
 * @param {string} options.text - Modal description
 * @param {string} options.confirmText - Confirm button text
 * @param {string} options.cancelText - Cancel button text
 * @returns {Promise<boolean>} true if confirmed
 */
export async function confirmAction({
    title = 'Konfirmasi',
    text = 'Apakah Anda yakin ingin melanjutkan?',
    confirmText = 'Ya, Lanjutkan',
    cancelText = 'Batal',
    icon = 'question',
} = {}) {
    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#059669', // emerald-600
        cancelButtonColor: '#6b7280', // gray-500
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
    });

    return result.isConfirmed;
}

/**
 * Show confirmation for save action.
 * @returns {Promise<boolean>}
 */
export async function confirmSave() {
    return confirmAction({
        title: 'Simpan Data',
        text: 'Apakah Anda yakin ingin menyimpan perubahan?',
        confirmText: 'Ya, Simpan',
        icon: 'question',
    });
}

/**
 * Show confirmation for delete action.
 * @param {string} itemName
 * @returns {Promise<boolean>}
 */
export async function confirmDelete(itemName = 'item ini') {
    return confirmAction({
        title: 'Hapus Data',
        text: `Apakah Anda yakin ingin menghapus ${itemName}? Tindakan ini tidak dapat dibatalkan.`,
        confirmText: 'Ya, Hapus',
        icon: 'warning',
    });
}

export function useAlert() {
    return {
        alertSuccess,
        alertError,
        alertWarning,
        confirmAction,
        confirmSave,
        confirmDelete,
    };
}
