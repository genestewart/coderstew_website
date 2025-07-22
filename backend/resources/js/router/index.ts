import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import AboutView from '../views/AboutView.vue';
import PortfolioView from '../views/PortfolioView.vue';
import ProjectView from '../views/ProjectView.vue';

const routes = [
  { path: '/', name: 'home', component: HomeView },
  { path: '/about', name: 'about', component: AboutView },
  { path: '/portfolio', name: 'portfolio', component: PortfolioView },
  { path: '/portfolio/:slug', name: 'project', component: ProjectView },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
