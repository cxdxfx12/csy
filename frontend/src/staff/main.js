import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import { createPinia } from 'pinia'
import App from './App.vue'
import GlobalToast from '@/shared/components/GlobalToast.vue'
import LoginView from './views/LoginView.vue'
import HomeView from './views/HomeView.vue'
import RepairView from './views/RepairView.vue'
import MeterView from './views/MeterView.vue'
import ChargeView from './views/ChargeView.vue'
import PatrolView from './views/PatrolView.vue'
import VisitorView from './views/VisitorView.vue'
import OrderView from './views/OrderView.vue'
import ComplaintView from './views/ComplaintView.vue'
import ProfileView from './views/ProfileView.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: LoginView },
  { path: '/home', component: HomeView },
  { path: '/repair', component: RepairView },
  { path: '/meter', component: MeterView },
  { path: '/charge', component: ChargeView },
  { path: '/patrol', component: PatrolView },
  { path: '/visitor', component: VisitorView },
  { path: '/order', component: OrderView },
  { path: '/complaint', component: ComplaintView },
  { path: '/profile', component: ProfileView }
]

const router = createRouter({ history: createWebHashHistory(), routes })
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('staff_token')
  if (to.path !== '/login' && !token) next('/login')
  else next()
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.component('GlobalToast', GlobalToast)
app.mount('#app')
