import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

/**
 * Composable untuk mengelola auth state di frontend.
 * Menggunakan data auth yang di-share oleh HandleInertiaRequests middleware.
 *
 * @returns {{
 *   user: import('vue').ComputedRef<object|null>,
 *   isAuthenticated: import('vue').ComputedRef<boolean>,
 *   userName: import('vue').ComputedRef<string>,
 *   userEmail: import('vue').ComputedRef<string>,
 *   userRoles: import('vue').ComputedRef<string[]>,
 *   userPermissions: import('vue').ComputedRef<string[]>,
 *   logout: () => void
 * }}
 *
 * @example
 * const { user, isAuthenticated, userName, logout } = useAuth();
 * if (isAuthenticated.value) {
 *   console.log(`Hello, ${userName.value}`);
 * }
 */
export function useAuth() {
    const page = usePage();

    /** @type {import('vue').ComputedRef<object|null>} Current authenticated user object */
    const user = computed(() => page.props.auth?.user ?? null);

    /** @type {import('vue').ComputedRef<boolean>} Whether the user is authenticated */
    const isAuthenticated = computed(() => !!user.value);

    /** @type {import('vue').ComputedRef<string>} Current user's name */
    const userName = computed(() => user.value?.name ?? '');

    /** @type {import('vue').ComputedRef<string>} Current user's email */
    const userEmail = computed(() => user.value?.email ?? '');

    /** @type {import('vue').ComputedRef<string[]>} Current user's roles */
    const userRoles = computed(() => user.value?.roles ?? []);

    /** @type {import('vue').ComputedRef<string[]>} Current user's permissions */
    const userPermissions = computed(() => user.value?.permissions ?? []);

    /**
     * Logout the current user by posting to /logout via Inertia router.
     * Redirects to login page after successful logout.
     */
    function logout() {
        router.post('/logout');
    }

    return {
        user,
        isAuthenticated,
        userName,
        userEmail,
        userRoles,
        userPermissions,
        logout,
    };
}
