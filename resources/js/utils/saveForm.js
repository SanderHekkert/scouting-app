import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function withSaveRedirect(data, returnUrl = null) {
    return {
        ...data,
        redirect_back: '1',
        ...(returnUrl ? { return_url: returnUrl } : {}),
    };
}

export function saveFormOptions(extra = {}) {
    return {
        preserveScroll: false,
        ...extra,
    };
}

/**
 * Gedeelde return-URL uit Inertia props (query of sessie via middleware).
 */
export function useSaveRedirect() {
    const page = usePage();
    const returnUrl = computed(() => {
        const value = page.props.returnUrl;
        return typeof value === 'string' && value.trim() !== '' ? value : null;
    });

    function applySaveRedirect(data, override = null) {
        const resolved = override ?? returnUrl.value;
        return withSaveRedirect(data, resolved);
    }

    return {
        returnUrl,
        applySaveRedirect,
        saveFormOptions,
    };
}

/**
 * Voor bewerk-links: expliciete return_url query (optioneel, sessie is meestal genoeg).
 */
export function withReturnUrl(href, returnUrl = null) {
    if (typeof window === 'undefined') {
        return href;
    }

    const target = returnUrl ?? window.location.href;
    const separator = String(href).includes('?') ? '&' : '?';

    return `${href}${separator}return_url=${encodeURIComponent(target)}`;
}
