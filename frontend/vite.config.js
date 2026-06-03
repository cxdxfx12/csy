import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  build: {
    outDir: resolve(__dirname, '../public'),
    emptyOutDir: false,
    rollupOptions: {
      input: {
        staff: resolve(__dirname, 'staff.html'),
        manager: resolve(__dirname, 'manager.html'),
        owner: resolve(__dirname, 'owner.html')
      }
    }
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://dasheng.local',
        changeOrigin: true
      }
    }
  }
})
