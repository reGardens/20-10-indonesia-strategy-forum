import { computed } from 'vue';
import { useAuth } from './useAuth';

/**
 * Composable untuk permission checking di frontend.
 * Menggunakan useAuth() secara internal untuk mengakses role dan permission user.
 *
 * @returns {{
 *   hasRole: (role: string) => import('vue').ComputedRef<boolean>,
 *   hasAnyRole: (roles: string[]) => import('vue').ComputedRef<boolean>,
 *   can: (permission: string) => import('vue').ComputedRef<boolean>,
 *   isSuperAdmin: import('vue').ComputedRef<boolean>,
 *   isAdmin: import('vue').ComputedRef<boolean>
 * }}
 *
 * @example
 * const { hasRole, can, isSuperAdmin, isAdmin } = usePermission();
 * if (isSuperAdmin.value) {
 *   // show admin panel
 * }
 * const canEdit = can('edit-articles');
 */
export function usePermission() {
    const { userRoles, userPermissions } = useAuth();

    /**
     * Check if the current user has a specific role.
     *
     * @param {string} role - The role name to check
     * @returns {import('vue').ComputedRef<boolean>} Computed boolean indicating if user has the role
     *
     * @example
     * const isEditor = hasRole('admin');
     */
    function hasRole(role) {
        return computed(() => userRoles.value.includes(role));
    }

    /**
     * Check if the current user has any of the given roles.
     *
     * @param {string[]} roles - Array of role names to check
     * @returns {import('vue').ComputedRef<boolean>} Computed boolean indicating if user has any of the roles
     *
     * @example
     * const canManageContent = hasAnyRole(['super-admin', 'admin']);
     */
    function hasAnyRole(roles) {
        return computed(() => roles.some((role) => userRoles.value.includes(role)));
    }

    /**
     * Check if the current user has a specific permission.
     *
     * @param {string} permission - The permission name to check
     * @returns {import('vue').ComputedRef<boolean>} Computed boolean indicating if user has the permission
     *
     * @example
     * const canDelete = can('delete-articles');
     */
    function can(permission) {
        return computed(() => userPermissions.value.includes(permission));
    }

    /** @type {import('vue').ComputedRef<boolean>} Shortcut to check if user is super-admin */
    const isSuperAdmin = computed(() => userRoles.value.includes('super-admin'));

    /** @type {import('vue').ComputedRef<boolean>} Shortcut to check if user is admin or super-admin */
    const isAdmin = computed(() =>
        userRoles.value.includes('admin') || userRoles.value.includes('super-admin')
    );

    return {
        hasRole,
        hasAnyRole,
        can,
        isSuperAdmin,
        isAdmin,
    };
}
