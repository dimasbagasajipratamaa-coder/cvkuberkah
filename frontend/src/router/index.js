import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import Dashboard from '../views/Dashboard.vue'
import CVBuilder from '../views/CVBuilder.vue'
import AdminDashboard from '../views/AdminDashboard.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: {
      title: 'Jasa Pembuatan CV ATS Friendly & Surat Lamaran Kerja - CV Kuberkah',
      description: 'Jasa pembuatan CV ATS-friendly dan Surat Lamaran Kerja profesional terbaik di Indonesia. Bantu tingkatkan peluang lolos screening HRD BUMN & Swasta. Cepat, praktis, dan terjangkau!'
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { 
      guestOnly: true,
      title: 'Masuk - CV Kuberkah',
      description: 'Masuk ke akun CV Kuberkah Anda untuk mengedit, memverifikasi pembayaran, atau mengunduh CV ATS Anda.'
    }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { 
      guestOnly: true,
      title: 'Daftar Akun Baru - CV Kuberkah',
      description: 'Daftar akun CV Kuberkah baru untuk mulai menyusun CV ATS profesional secara cepat dan mudah.'
    }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { 
      requiresAuth: true,
      title: 'Dashboard Pelanggan - CV Kuberkah',
      description: 'Pantau status pesanan CV ATS Anda dan lakukan cetak/unduh PDF secara langsung.'
    }
  },
  {
    path: '/builder',
    name: 'CVBuilder',
    component: CVBuilder,
    meta: { 
      requiresAuth: true,
      title: 'Buat CV ATS Baru - CV Kuberkah',
      description: 'Lengkapi biodata dan dapatkan ringkasan diri Tentang Saya otomatis untuk CV ATS profesional Anda.'
    }
  },
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { 
      requiresAuth: true, 
      requiresAdmin: true,
      title: 'Panel Admin - CV Kuberkah',
      description: 'Halaman manajemen transaksi, analitik pendapatan, dan kelola konten halaman customer.'
    }
  },
  // Catch-all redirect to Home
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  // Using hash or history? If Apache is serving we can use hash or history.
  // Standard WebHistory works great with our .htaccess rewrite!
  // Note: /cvkuberkah/ is the subfolder in Apache, so the base must be set.
  history: createWebHistory('/cvkuberkah/'),
  routes
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  const userString = localStorage.getItem('user')
  const user = userString ? JSON.parse(userString) : null

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!user) {
      next({ name: 'Login' })
    } else if (to.matched.some(record => record.meta.requiresAdmin) && user.role !== 'admin') {
      // User is not an admin, send them to standard dashboard
      next({ name: 'Dashboard' })
    } else {
      next()
    }
  } else if (to.matched.some(record => record.meta.guestOnly)) {
    if (user) {
      if (user.role === 'admin') {
        next({ name: 'AdminDashboard' })
      } else {
        next({ name: 'Dashboard' })
      }
    } else {
      next()
    }
  } else {
    next()
  }
})

// SEO metadata injection on route transition
router.afterEach((to) => {
  const defaultTitle = 'Jasa Pembuatan CV ATS Friendly & Surat Lamaran Kerja - CV Kuberkah'
  const defaultDesc = 'Jasa pembuatan CV ATS-friendly dan Surat Lamaran Kerja profesional terbaik di Indonesia. Bantu tingkatkan peluang lolos screening HRD BUMN & Swasta. Cepat, praktis, dan terjangkau!'
  
  document.title = to.meta.title || defaultTitle
  
  const descMeta = document.querySelector('meta[name="description"]')
  if (descMeta) {
    descMeta.setAttribute('content', to.meta.description || defaultDesc)
  }
})

export default router
