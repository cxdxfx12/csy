import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import 'element-plus/dist/index.css'
import './assets/premium.css'
import * as ElIcons from '@element-plus/icons-vue'
import App from './App.vue'
import router from './router'
import './utils/request'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(ElementPlus, { locale: zhCn })

// 注册所有图标
for (const [key, component] of Object.entries(ElIcons)) app.component(key, component)

app.mount('#app')
