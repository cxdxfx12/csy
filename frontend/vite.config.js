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
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        rewrite: (path) => {
          const rest = path.replace(/^\/api/, '');
          const segments = rest.split('/').filter(Boolean);
          if (segments.length === 1) {
            return '/index.php/api' + rest;
          }
          return '/index.php' + rest;
        }
      },
      '/index.php': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true
      }
    }
  }
})
