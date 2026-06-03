import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
import { resolve } from 'path'

export default defineConfig({
  base: '/admin/',
  plugins: [
    vue(),
    AutoImport({ resolvers: [ElementPlusResolver()] }),
    Components({ resolvers: [ElementPlusResolver()] }),
  ],
  resolve: {
    alias: { '@': resolve(__dirname, 'src') },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        rewrite: (path) => {
          // 去掉 /api 后剩余的路由段
          const rest = path.replace(/^\/api/, '');
          const segments = rest.split('/').filter(Boolean);
          // 单段路径(如 /api/login)需保留 api 前缀 → /index.php/api/login
          // PHP 路由至少需要 2 段才能匹配 模块/控制器
          if (segments.length === 1) {
            return '/index.php/api' + rest;
          }
          // 多段路径(如 /api/staff/repair)正常去掉 api 前缀
          return '/index.php' + rest;
        },
      },
    },
  },
})
