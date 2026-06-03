import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import { createPinia } from 'pinia'
import App from './App.vue'
import GlobalToast from '@/shared/components/GlobalToast.vue'
import LoginView from './views/LoginView.vue'
import RegisterView from './views/RegisterView.vue'
import HomeView from './views/HomeView.vue'
import RoomView from './views/RoomView.vue'
import BillView from './views/BillView.vue'
import RepairView from './views/RepairView.vue'
import ComplaintView from './views/ComplaintView.vue'
import NoticeView from './views/NoticeView.vue'
import VisitorView from './views/VisitorView.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: LoginView },
  { path: '/register', component: RegisterView },
  { path: '/home', component: HomeView },
  { path: '/room', component: RoomView },
  { path: '/bill', component: BillView },
  { path: '/repair', component: RepairView },
  { path: '/complaint', component: ComplaintView },
  { path: '/notice', component: NoticeView },
  { path: '/visitor', component: VisitorView }
]

const router = createRouter({ history: createWebHashHistory(), routes })
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('owner_token')
  const publicPages = ['/login', '/register']
  if (!publicPages.includes(to.path) && !token) next('/login')
  else next()
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.component('GlobalToast', GlobalToast)
app.mount('#app')
