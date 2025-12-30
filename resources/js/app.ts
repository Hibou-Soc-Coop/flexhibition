import '../css/app.css';
import localeMessages from './translations.json';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import ElementPlus from 'element-plus';
import ElementTiptapPlugin from 'element-tiptap';
import 'element-tiptap/lib/style.css';
import { createI18n } from 'vue-i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {

        const splitUrl = window.location.pathname.split('/');
        const languages = props.initialPage.props.languages as Array<{ code: string, name: string }>;
        let lang = null;

        if (languages.some(lang => lang.code === splitUrl[splitUrl.length - 1])) {
            lang = splitUrl.pop();
        }
        const defaultLocale = lang ? lang : (localStorage.getItem('lang') || 'it');

        const i18n = createI18n({
            locale: defaultLocale, // Imposta la lingua predefinita
            fallbackLocale: 'it', // Imposta la lingua di fallback
            messages: localeMessages, // Carica i messaggi di traduzione
        });
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ElementPlus)
            .use(ElementTiptapPlugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
// initializeTheme();
