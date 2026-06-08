<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const isLoading = ref(false)

// Google modal state
const showGoogleModal = ref(false)
const customGoogleName = ref('')
const customGoogleEmail = ref('')

const handleRegister = async () => {
  error.value = ''
  
  if (!name.value || !email.value || !password.value) {
    error.value = 'Harap isi semua kolom wajib.'
    return
  }
  
  if (password.value.length < 6) {
    error.value = 'Password harus minimal 6 karakter.'
    return
  }
  
  if (password.value !== confirmPassword.value) {
    error.value = 'Konfirmasi password tidak cocok.'
    return
  }
  
  isLoading.value = true
  try {
    const response = await fetch('/cvkuberkah/api/auth.php?action=register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name: name.value,
        email: email.value,
        password: password.value
      })
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.error || 'Pendaftaran gagal.')
    }
    
    // Save user state in localStorage
    localStorage.setItem('user', JSON.stringify(data.user))
    
    // Check if redirect parameters exist
    const selectedPkg = route.query.package
    const selectedPrice = route.query.price
    if (selectedPkg && selectedPrice) {
      router.push({ 
        name: 'CVBuilder', 
        query: { package: selectedPkg, price: selectedPrice } 
      })
    } else {
      router.push({ name: 'Dashboard' })
    }
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const handleGoogleSelect = async (account) => {
  showGoogleModal.value = false
  isLoading.value = true
  error.value = ''
  try {
    const response = await fetch('/cvkuberkah/api/auth.php?action=google_login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(account)
    })
    
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.error || 'Login Google gagal.')
    }
    
    localStorage.setItem('user', JSON.stringify(data.user))
    
    // Check if redirect parameters exist
    const selectedPkg = route.query.package
    const selectedPrice = route.query.price
    if (selectedPkg && selectedPrice) {
      router.push({ 
        name: 'CVBuilder', 
        query: { package: selectedPkg, price: selectedPrice } 
      })
    } else {
      router.push({ name: 'Dashboard' })
    }
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const handleCustomGoogleSubmit = () => {
  if (!customGoogleName.value || !customGoogleEmail.value) return
  handleGoogleSelect({
    name: customGoogleName.value,
    email: customGoogleEmail.value
  })
}
</script>

<template>
  <div class="auth-container">
    <div class="glass-card auth-card">
      <h2 class="auth-title">Daftar di <span>CV Kuberkah</span></h2>
      <p class="auth-subtitle">Buat akun untuk mulai membuat CV ATS profesional Anda.</p>
      
      <div v-if="error" class="alert-danger">{{ error }}</div>
      
      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <input 
            v-model="name" 
            type="text" 
            id="name" 
            class="form-input" 
            placeholder="Masukkan nama lengkap Anda" 
            required 
          />
        </div>
        
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input 
            v-model="email" 
            type="email" 
            id="email" 
            class="form-input" 
            placeholder="Masukkan email aktif" 
            required 
          />
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input 
            v-model="password" 
            type="password" 
            id="password" 
            class="form-input" 
            placeholder="Minimal 6 karakter" 
            required 
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="confirmPassword">Konfirmasi Password</label>
          <input 
            v-model="confirmPassword" 
            type="password" 
            id="confirmPassword" 
            class="form-input" 
            placeholder="Ulangi password" 
            required 
          />
        </div>
        
        <button type="submit" class="btn btn-primary auth-btn" :disabled="isLoading">
          {{ isLoading ? 'Sedang Mendaftarkan...' : 'Daftar' }}
        </button>

        <div class="google-divider">atau</div>

        <button type="button" @click="showGoogleModal = true" class="btn google-btn">
          <svg class="google-icon-svg" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
          </svg>
          Daftar dengan Google
        </button>
      </form>
      
      <div class="auth-footer">
        Sudah punya akun? <router-link :to="{ name: 'Login' }">Masuk di sini</router-link>
      </div>
    </div>

    <!-- MOCK GOOGLE ACCOUNT OVERLAY MODAL -->
    <div v-if="showGoogleModal" class="modal-overlay">
      <div class="modal-card google-modal">
        <div class="google-modal-header">
          <div class="google-brand">
            <span>G</span><span>o</span><span>o</span><span>g</span><span>l</span><span>e</span>
          </div>
          <div class="google-modal-title">Pilih Akun</div>
          <div class="google-modal-sub">untuk melanjutkan ke CV Kuberkah</div>
        </div>
        
        <div class="google-accounts-list">
          <div @click="handleGoogleSelect({ name: 'Ahmad Budiman', email: 'ahmad.budiman@gmail.com' })" class="google-account-item">
            <div class="google-avatar">AB</div>
            <div class="google-account-info">
              <span class="google-account-name">Ahmad Budiman</span>
              <span class="google-account-email">ahmad.budiman@gmail.com</span>
            </div>
          </div>
          
          <div @click="handleGoogleSelect({ name: 'Siti Aminah', email: 'siti.aminah@gmail.com' })" class="google-account-item">
            <div class="google-avatar">SA</div>
            <div class="google-account-info">
              <span class="google-account-name">Siti Aminah</span>
              <span class="google-account-email">siti.aminah@gmail.com</span>
            </div>
          </div>
        </div>
        
        <div class="google-custom-form">
          <h4>Atau daftar dengan akun Google lain:</h4>
          <div class="form-group">
            <input v-model="customGoogleName" type="text" class="form-input" placeholder="Nama Lengkap" />
          </div>
          <div class="form-group">
            <input v-model="customGoogleEmail" type="email" class="form-input" placeholder="Email Google" />
          </div>
          <button @click="handleCustomGoogleSubmit" class="btn btn-primary btn-sm" style="width:100%" :disabled="!customGoogleName || !customGoogleEmail">
            Lanjutkan
          </button>
        </div>
        
        <button @click="showGoogleModal = false" class="btn btn-text" style="width:100%; margin-top: 1rem">Batal</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 80px);
  background: linear-gradient(135deg, hsl(210, 40%, 96%) 0%, hsl(243, 80%, 97%) 100%);
  padding: 2rem;
}
.auth-card {
  width: 100%;
  max-width: 450px;
  box-shadow: var(--shadow-lg);
  padding: 3rem 2.5rem;
}
.auth-title {
  font-size: 1.8rem;
  text-align: center;
  margin-bottom: 0.5rem;
}
.auth-title span {
  color: var(--primary);
}
.auth-subtitle {
  text-align: center;
  color: var(--text-muted);
  font-size: 0.95rem;
  margin-bottom: 2rem;
}
.auth-btn {
  width: 100%;
  padding: 0.85rem;
  margin-top: 1rem;
}
.auth-footer {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.9rem;
  color: var(--text-muted);
}
.auth-footer a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover {
  text-decoration: underline;
}
.alert-danger {
  background-color: hsl(346, 100%, 95%);
  color: var(--danger);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  border: 1px solid hsl(346, 100%, 90%);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}
.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}
</style>
