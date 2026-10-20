import { useForm as inertiaUseForm } from '@inertiajs/vue3';

/**
 * Thin wrapper around Inertia's useForm with convenience methods
 * for common CRUD operations.
 *
 * @param {object} initialData - Initial form data object
 * @returns {object} Inertia form object extended with convenience methods:
 *   - submitCreate(routeName, options?) — POST to the given route
 *   - submitUpdate(routeName, id, options?) — PUT to the given route with id
 *   - submitDelete(routeName, id, options?) — DELETE to the given route with id
 *
 * @example
 * const form = useCmsForm({ title: '', content: '' });
 * // Create
 * form.submitCreate('cms.articles.store');
 * // Update
 * form.submitUpdate('cms.articles.update', articleId);
 * // Delete
 * form.submitDelete('cms.articles.destroy', articleId);
 */
export function useCmsForm(initialData) {
    const form = inertiaUseForm(initialData);

    /**
     * Submit form as a POST request for creating a new resource.
     * Automatically sets forceFormData if the form contains file uploads.
     *
     * @param {string} routeName - The named route to post to
     * @param {object} [options={}] - Additional Inertia request options
     *
     * @example
     * form.submitCreate('cms.articles.store');
     * form.submitCreate('cms.articles.store', { onSuccess: () => alert('Created!') });
     */
    form.submitCreate = function (routeName, options = {}) {
        const url = route(routeName);
        const hasFiles = Object.values(form.data()).some(
            (value) => value instanceof File
        );

        form.post(url, {
            forceFormData: hasFiles,
            ...options,
        });
    };

    /**
     * Submit form as a PUT request for updating an existing resource.
     * Automatically sets forceFormData if the form contains file uploads.
     *
     * @param {string} routeName - The named route to put to
     * @param {number|string} id - The resource ID
     * @param {object} [options={}] - Additional Inertia request options
     *
     * @example
     * form.submitUpdate('cms.articles.update', article.id);
     */
    form.submitUpdate = function (routeName, id, options = {}) {
        const url = route(routeName, id);
        const hasFiles = Object.values(form.data()).some(
            (value) => value instanceof File
        );

        form.put(url, {
            forceFormData: hasFiles,
            ...options,
        });
    };

    /**
     * Submit a DELETE request for destroying a resource.
     *
     * @param {string} routeName - The named route to delete
     * @param {number|string} id - The resource ID
     * @param {object} [options={}] - Additional Inertia request options
     *
     * @example
     * form.submitDelete('cms.articles.destroy', article.id);
     */
    form.submitDelete = function (routeName, id, options = {}) {
        const url = route(routeName, id);

        form.delete(url, {
            ...options,
        });
    };

    return form;
}
