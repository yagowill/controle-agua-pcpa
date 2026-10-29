// Configuração do Vuetify 3 & Ícones MDI
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import '../css/app.css';
import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

// Tema Institucional PCPA
const pcpaTheme = {
    dark: false,
    colors: {
        primary: '#2b6cb0',    // Azul Institucional PCPA
        secondary: '#212529',  // Grafite (Top Bar SRH)
        accent: '#1e4e82',
        error: '#dc3545',
        info: '#17a2b8',
        success: '#28a745',
        warning: '#ffc107',
        background: '#f8f9fa',
        surface: '#ffffff',
    },
};

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'pcpaTheme',
        themes: { pcpaTheme },
    },
});

const appName = import.meta.env.VITE_APP_NAME || 'SCEA - PCPA';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .mount(el);
    },
    progress: { color: '#2b6cb0' },
});