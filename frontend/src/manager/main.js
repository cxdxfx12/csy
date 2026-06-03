import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import GlobalToast from '@/shared/components/GlobalToast.vue'
import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: LoginView },
  { path: '/dashboard', component: DashboardView }
]

const router = createRouter({ history: createWebHashHistory(), routes })
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('manager_token')
  if (to.path !== '/login' && !token) next('/login')
  else next()
})

const app = createApp(App)
app.use(router)
app.component('GlobalToast', GlobalToast)
app.mount('#app')
