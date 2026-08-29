import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

// Merknaam en -kleur komen van de server (data-attributen op <html>, zie
// app.blade.php): één build draait voor EasyInvoice én Lopra.
const brandData = document.documentElement.dataset;
const appName = brandData.brandName || import.meta.env.VITE_APP_NAME || 'EasyInvoice';
const brandColor = brandData.brandColor || '#E8231F';

// PWA: installeerbaar op telefoon/desktop; de service worker cachet alleen
// statische bestanden. Niet in development (zou Vite's hot reload storen).
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

// Vangnet: als een Inertia-bezoek een gewone HTML-pagina terugkrijgt (bijv. een
// Blade-route zoals de homepage, of een sessie die verlopen is), toont Inertia
// standaard een debug-modal met de pagina in een iframe. Dat willen we niet in
// productie: doe in plaats daarvan een normale, volledige paginanavigatie.
router.on('invalid', (event) => {
    event.preventDefault();
    const url = event.detail.response?.config?.url;
    if (url) {
        window.location.href = url;
    } else {
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => title ? `${title} · ${appName}` : appName,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue'),
    ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: brandColor,
        showSpinner: false,
    },
});
