import './setupAxios';
import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Button from 'primevue/button';
import 'primevue/resources/themes/aura-light-green/theme.css';
import 'primevue/resources/primevue.min.css';
import 'primeicons/primeicons.css';
import App from './components/App.vue';

const app = createApp(App);
app.use(PrimeVue);
app.component('Button', Button);
app.mount('#app');
