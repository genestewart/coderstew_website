import './setupAxios';
import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Button from 'primevue/button';
import 'primevue/resources/themes/aura-light-green/theme.css';
import 'primevue/resources/primevue.min.css';
import 'primeicons/primeicons.css';
import App from './components/App.vue';
import router from './router';
import pinia from './stores';

const app = createApp(App);
app.use(PrimeVue);
app.use(router);
app.use(pinia);
app.component('Button', Button);
app.mount('#app');
