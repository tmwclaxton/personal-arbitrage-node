import './bootstrap';
import '../css/app.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import {createPinia} from 'pinia';
import Dropdown from "vue-simple-search-dropdown"; // Import Pinia

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        const pinia = createPinia(); // Create Pinia instance

        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(pinia) // Use Pinia
            .use(ZiggyVue)
            .use(Dropdown)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
// <script type="text/javascript" src="node_modules/vuejs/dist/vue.min.js"></script>
// <script type="text/javascript"
//         src="node_modules/vue-simple-search-dropdown/dist/vue-simple-search-dropdown.min.js"></script>

