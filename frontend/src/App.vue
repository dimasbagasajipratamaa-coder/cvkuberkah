<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const currentUser = ref(null)
const isMobileMenuOpen = ref(false)

// Sync auth state and close menu on route changes
watch(
  () => route.path,
  () => {
    const userString = localStorage.getItem('user')
    currentUser.value = userString ? JSON.parse(userString) : null
    isMobileMenuOpen.value = false // Auto-close mobile drawer on nav
  },
  { immediate: true }
)

const logout = () => {
  localStorage.removeItem('user')
  currentUser.value = null
  isMobileMenuOpen.value = false
  router.push({ name: 'Home' })
}

const logoSrc = ref(import.meta.env.DEV ? '/logo.png' : '/cvkuberkah/dist/logo.png')
</script>

<template>
  <div>
    <!-- Navigation Bar (Excluded from Print) -->
    <nav class="navbar no-print">
      <router-link :to="{ name: 'Home' }" class="navbar-brand-img">
        <img :src="logoSrc" alt="CV Kuberkah Logo" class="logo-img" />
      </router-link>
      
      <div class="navbar-links-container">
        <!-- Guest Navigation Links -->
        <ul v-if="!currentUser" class="navbar-links">
          <li><a href="/cvkuberkah/#templates" class="navbar-link">Template</a></li>
          <li><a href="/cvkuberkah/#packages" class="navbar-link">Paket Harga</a></li>
          <li><a href="/cvkuberkah/#testimonials" class="navbar-link">Testimoni</a></li>
          <li><a href="/cvkuberkah/#company-info" class="navbar-link">Tentang Kami</a></li>
        </ul>

        <!-- Customer Navigation Links -->
        <ul v-else-if="currentUser.role === 'customer'" class="navbar-links">
          <li><router-link :to="{ name: 'Dashboard' }" class="navbar-link">Dashboard Saya</router-link></li>
          <li><router-link :to="{ name: 'CVBuilder' }" class="navbar-link">Buat CV ATS</router-link></li>
        </ul>

        <!-- Admin Navigation Links -->
        <ul v-else-if="currentUser.role === 'admin'" class="navbar-links">
          <li><router-link :to="{ name: 'AdminDashboard' }" class="navbar-link">Panel Admin</router-link></li>
        </ul>
      </div>

      <div class="navbar-actions">
        <!-- Not Logged In -->
        <template v-if="!currentUser">
          <router-link :to="{ name: 'Login' }" class="btn btn-text">Masuk</router-link>
          <router-link :to="{ name: 'Register' }" class="btn btn-primary">Daftar Sekarang</router-link>
        </template>
        
        <!-- Logged In -->
        <template v-else>
          <span class="user-greeting">Halo, <strong>{{ currentUser.name }}</strong></span>
          <button @click="logout" class="btn btn-danger btn-sm">Keluar</button>
        </template>
      </div>

      <!-- Hamburger menu button (Visible on Mobile/Tablet only) -->
      <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="hamburger-btn no-print" :class="{ 'is-active': isMobileMenuOpen }" aria-label="Toggle Navigation">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Mobile dropdown menu -->
      <transition name="slide-fade">
        <div v-if="isMobileMenuOpen" class="mobile-menu no-print">
          <ul v-if="!currentUser" class="mobile-menu-links">
            <li><a href="/cvkuberkah/#templates" @click="isMobileMenuOpen = false">Template</a></li>
            <li><a href="/cvkuberkah/#packages" @click="isMobileMenuOpen = false">Paket Harga</a></li>
            <li><a href="/cvkuberkah/#testimonials" @click="isMobileMenuOpen = false">Testimoni</a></li>
            <li><a href="/cvkuberkah/#company-info" @click="isMobileMenuOpen = false">Tentang Kami</a></li>
          </ul>
          <ul v-else-if="currentUser.role === 'customer'" class="mobile-menu-links">
            <li><router-link :to="{ name: 'Dashboard' }" @click="isMobileMenuOpen = false">Dashboard Saya</router-link></li>
            <li><router-link :to="{ name: 'CVBuilder' }" @click="isMobileMenuOpen = false">Buat CV ATS</router-link></li>
          </ul>
          <ul v-else-if="currentUser.role === 'admin'" class="mobile-menu-links">
            <li><router-link :to="{ name: 'AdminDashboard' }" @click="isMobileMenuOpen = false">Panel Admin</router-link></li>
          </ul>
          
          <div class="mobile-menu-actions">
            <template v-if="!currentUser">
              <router-link :to="{ name: 'Login' }" class="btn btn-text" @click="isMobileMenuOpen = false">Masuk</router-link>
              <router-link :to="{ name: 'Register' }" class="btn btn-primary" @click="isMobileMenuOpen = false">Daftar Sekarang</router-link>
            </template>
            <template v-else>
              <button @click="logout" class="btn btn-danger">Keluar</button>
            </template>
          </div>
        </div>
      </transition>
    </nav>

    <!-- Main View Content -->
    <main>
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.user-greeting {
  font-size: 0.9rem;
  color: var(--text-dark);
  margin-right: 0.5rem;
}
.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.85rem;
  border-radius: var(--radius-sm);
}
.navbar-links-container {
  display: flex;
  align-items: center;
}

/* Slide Fade Transition for Mobile Menu */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-15px);
  opacity: 0;
}

/* Hide navigation during print output */
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
