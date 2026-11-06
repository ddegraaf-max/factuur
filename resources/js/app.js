import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { t, setLocale } from './i18n';

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

// Installeren als app (1.106.2): Chrome, Edge en Android melden met beforeinstallprompt dat
// de app installeerbaar is, vaak al vóór Vue gestart is. We houden die melding hier vast;
// de knop "Installeer als app" in het gebruikersmenu (composables/useInstallApp) toont hem.
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.__installPrompt = event;
    window.dispatchEvent(new Event('installprompt-ready'));
});
window.addEventListener('appinstalled', () => {
    window.__installPrompt = null;
    window.dispatchEvent(new Event('installprompt-done'));
});

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
        // Interfacetaal (markttaal, of de PL/EN-keuze van de gebruiker): $t('Nederlandse tekst') vertaalt in de templates.
        setLocale(props.initialPage?.props?.locale || props.initialPage?.props?.market?.locale || brandData.locale || 'nl');
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.$t = t;
        app.provide('t', t);
        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: brandColor,
        showSpinner: false,
    },
});
