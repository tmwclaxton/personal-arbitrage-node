import './bootstrap';
import '../css/app.css';

import {createApp, h} from 'vue';
import {createInertiaApp, Head, Link} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import {createPinia} from 'pinia';

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {fab} from "@fortawesome/free-brands-svg-icons";
import {far} from "@fortawesome/free-regular-svg-icons";
import {fas} from "@fortawesome/free-solid-svg-icons";

library.add(fas, far, fab);



const appName = import.meta.env.VITE_APP_NAME || 'LAS Trading Kit';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        const pinia = createPinia(); // Create Pinia instance

        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(pinia) // Use Pinia
            .use(ZiggyVue)
            .component('font-awesome-icon', FontAwesomeIcon)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
// <script type="text/javascript" src="node_modules/vuejs/dist/vue.min.js"></script>
// <script type="text/javascript"
//         src="node_modules/vue-simple-search-dropdown/dist/vue-simple-search-dropdown.min.js"></script>

