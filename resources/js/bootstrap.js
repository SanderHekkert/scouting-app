import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.error('CSRF-token meta tag ontbreekt; voeg <meta name="csrf-token" …> toe in app.blade.php.');
}

export function syncAxiosCsrfFromMeta() {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta && window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.content;
    }
}
