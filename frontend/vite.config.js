import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  base: '/cvkuberkah/dist/',
  build: {
    outDir: '../dist',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/cvkuberkah/api': {
        target: 'http://127.0.0.1', // Targets local XAMPP Apache
        changeOrigin: true
      }
    }
  }
})
