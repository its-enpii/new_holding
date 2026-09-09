import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Tenant RBAC helper.
 * - no permissions / empty = legacy unrestricted (full access)
 * - includes '*' = full access
 */
export function useCan() {
    const page = usePage();
    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function can(permission) {
        if (!permission) return true;
        const perms = permissions.value;
        if (!Array.isArray(perms) || perms.length === 0) return true;
        if (perms.includes('*')) return true;
        if (Array.isArray(permission)) {
            return permission.some((p) => perms.includes(p));
        }
        return perms.includes(permission);
    }

    function canAll(list) {
        if (!Array.isArray(list) || list.length === 0) return true;
        return list.every((p) => can(p));
    }

    return { can, canAll, permissions };
}
