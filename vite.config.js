import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      // Point "@" to the SPA source under resources/js since the project
      // no longer lives in a backend subfolder.  This alias allows
      // clean imports like '@/components/MyComponent.vue'.
      '@': '/resources/js',
    },
  },
});