import '../css/app.css';
import '@vueform/multiselect/themes/default.css';
import 'vue-sonner/style.css';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
import '@vuepic/vue-datepicker/dist/main.css';
import './bootstrap';

import { createInertiaApp, router, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h, type DefineComponent } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

const appName = import.meta.env.VITE_APP_NAME || 'Lacakeen';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const vueApp = createApp({
            render: () => [
                h(App, props),
                h(Toaster, { position: 'bottom-right', richColors: true, closeButton: true }),
            ],
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .mount(el);

        const showFlashToast = () => {
            const flash = usePage().props.flash as { success?: string; error?: string } | undefined;
            if (flash?.success) toast.success(flash.success);
            else if (flash?.error) toast.error(flash.error);
        };
        router.on('finish', showFlashToast);
        showFlashToast();

        return vueApp;
    },
    progress: {
        color: '#2563EB',
    },
});
