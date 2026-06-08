<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const logoSrc = ref(import.meta.env.DEV ? '/logo.png' : '/cvkuberkah/dist/logo.png')

const packages = ref([])
const templates = ref([])
const testimonials = ref([])
const companyProfile = ref({
  name: "CV Kuberkah",
  tagline: "Partner Karir Terpercaya Anda",
  description: "CV Kuberkah membantu Anda membuat CV ATS-friendly dan Surat Lamaran Kerja secara profesional, praktis, dan terjangkau.",
  whatsapp: "6289656111199",
  email: "support@cvkuberkah.com",
  address: "Gedung Karir Indonesia, Depok, Jawa Barat",
  instagram: "@cv.kuberkah"
})

const isLoading = ref(true)

const fetchHomeContent = async () => {
  try {
    const response = await fetch('/cvkuberkah/api/content.php')
    if (response.ok) {
      const data = await response.json()
      packages.value = data.packages || []
      templates.value = data.templates || []
      testimonials.value = data.testimonials || []
      if (data.company_profile) {
        companyProfile.value = data.company_profile
      }
    }
  } catch (error) {
    console.error('Failed to load landing contents:', error)
  } finally {
    isLoading.value = false
  }
}

const selectPackage = (pkg) => {
  const user = localStorage.getItem('user')
  if (!user) {
    // If guest, redirect to register with package intent
    router.push({ name: 'Register', query: { package: pkg.name, price: pkg.price } })
  } else {
    // If logged in, redirect to builder with selected package
    router.push({ name: 'CVBuilder', query: { package: pkg.name, price: pkg.price } })
  }
}

onMounted(() => {
  fetchHomeContent()
})
</script>

<template>
  <div class="home-wrapper">
    <!-- Hero Section -->
    <header class="hero-section">
      <div class="hero-content">
        <span class="hero-tag">🔥 Platform Pembuat CV ATS Terkini</span>
        <h1 class="hero-title">Buat CV ATS & Surat Lamaran Kerja Profesional dalam 5 Menit</h1>
        <p class="hero-subtitle">
          Tembus screening HRD perusahaan impian dengan CV berstandar ATS (Applicant Tracking System). Dilengkapi panduan deskripsi diri otomatis!
        </p>
        <div class="hero-cta">
          <router-link :to="{ name: 'CVBuilder' }" class="btn btn-primary btn-lg">Buat CV Sekarang</router-link>
          <a href="#templates" class="btn btn-outline btn-lg">Lihat Template</a>
        </div>
      </div>
      <div class="hero-graphic">
        <div class="hero-bg-blobs">
          <div class="blob blob-1"></div>
          <div class="blob blob-2"></div>
        </div>
        <div class="glass-card mockup-cv">
          <div class="mockup-header-row">
            <div class="mockup-avatar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="avatar-svg">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="mockup-header-text">
              <div class="mockup-line-name">Dimas Bagas</div>
              <div class="mockup-line-sub">Software Engineer</div>
            </div>
          </div>
          <div class="mockup-divider"></div>
          <div class="mockup-section-title">WORK EXPERIENCE</div>
          <div class="mockup-line-desc">Lead Dev at Techcorp (2024 - Present)</div>
          <div class="mockup-line"></div>
          <div class="mockup-line"></div>
          <div class="mockup-divider"></div>
          <div class="mockup-section-title">SKILLS & CERTIFICATIONS</div>
          <div class="mockup-row">
            <span class="mockup-badge">ATS Verified</span>
            <span class="mockup-badge">Vue.js</span>
            <span class="mockup-badge">PHP</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Templates Section -->
    <section id="templates" class="container templates-section">
      <h2 class="section-title">Pilihan Template ATS & Lamaran</h2>
      <p class="section-subtitle-center">Desain standar rekrutmen nasional maupun internasional, 100% terbaca sistem screening.</p>
      
      <div v-if="isLoading" class="loader">Memuat template...</div>
      <div v-else class="grid-3">
        <div v-for="tpl in templates" :key="tpl.id" class="template-card glass-card">
          <div class="tpl-image-wrapper">
            <img :src="tpl.image" :alt="tpl.name" class="tpl-image" />
            <span class="tpl-badge">{{ tpl.type }}</span>
          </div>
          <h3 class="tpl-name">{{ tpl.name }}</h3>
          <p class="tpl-desc">{{ tpl.description }}</p>
        </div>
      </div>
    </section>

    <!-- Steps Section (Tata Cara Membuat CV) -->
    <section id="workflow-steps" class="workflow-section">
      <div class="container">
        <h2 class="section-title">Tata Cara Membuat CV ATS</h2>
        <p class="section-subtitle-center">Ikuti 5 langkah mudah berikut untuk menyusun CV ATS profesional siap kerja.</p>
        
        <div class="steps-grid">
          <div class="workflow-card glass-card">
            <span class="step-num">01</span>
            <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="8.5" cy="7" r="4" />
              <line x1="20" y1="8" x2="20" y2="14" />
              <line x1="17" y1="11" x2="23" y2="11" />
            </svg>
            <h3>Daftar / Masuk Akun</h3>
            <p>Buat akun CV Kuberkah gratis atau masuk cepat menggunakan akun Google untuk menyimpan data CV Anda.</p>
          </div>
          
          <div class="workflow-card glass-card">
            <span class="step-num">02</span>
            <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
            </svg>
            <h3>Pilih Paket & Desain</h3>
            <p>Tentukan pilihan paket harga (Basic, Premium, atau Bundle) sesuai target karir profesional Anda.</p>
          </div>
          
          <div class="workflow-card glass-card">
            <span class="step-num">03</span>
            <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <polyline points="10 9 9 9 8 9" />
            </svg>
            <h3>Isi Data & Formulir</h3>
            <p>Lengkapi profil diri, kontak, pendidikan, pengalaman kerja/organisasi, serta 5 poin otomatis panduan "Tentang Saya".</p>
          </div>
          
          <div class="workflow-card glass-card">
            <span class="step-num">04</span>
            <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
              <polyline points="9 11 11 13 15 9" />
            </svg>
            <h3>Verifikasi & Bayar WA</h3>
            <p>Periksa pratinjau ATS CV real-time Anda, selesaikan pembayaran, dan klik tombol verifikasi otomatis via WhatsApp Admin.</p>
          </div>
          
          <div class="workflow-card glass-card">
            <span class="step-num">05</span>
            <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="7 10 12 15 17 10" />
              <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            <h3>Cetak / Unduh PDF</h3>
            <p>Setelah status dikonfirmasi aktif oleh admin, cetak langsung atau simpan menjadi file PDF ATS resmi beresolusi tinggi!</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="packages-bg">
      <div class="container">
        <h2 class="section-title">Menu Pilihan Paket</h2>
        <p class="section-subtitle-center">Pilih paket terbaik sesuai kebutuhan karir Anda dengan harga sangat bersahabat.</p>
        
        <div v-if="isLoading" class="loader">Memuat paket...</div>
        <div v-else class="packages-grid">
          <div v-for="pkg in packages" :key="pkg.id" class="package-card glass-card" :class="{'package-featured': pkg.recommended || pkg.id === 'premium'}">
            <span v-if="pkg.recommended" class="featured-badge">Rekomendasi</span>
            <span v-else-if="pkg.id === 'premium'" class="featured-badge">Terpopuler</span>
            
            <h3 class="pkg-name">{{ pkg.name }}</h3>

            <div class="pkg-image-wrapper">
              <img v-if="pkg.image_url" :src="pkg.image_url" class="pkg-image" :alt="pkg.name" />
              <div v-else class="pkg-svg-placeholder">
                <svg v-if="pkg.id === 'basic' || pkg.name.toLowerCase().includes('basic')" class="pkg-svg-icon basic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                  <polyline points="9 15 11 17 15 13" />
                </svg>
                <svg v-else-if="pkg.id === 'premium' || pkg.name.toLowerCase().includes('premium')" class="pkg-svg-icon premium" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="8" r="7" />
                  <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" />
                  <path d="M12 4v8" />
                  <path d="M9 7h6" />
                </svg>
                <svg v-else class="pkg-svg-icon bundle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                  <path d="M2 10h20" />
                  <path d="M12 14v4" />
                  <path d="M10 16h4" />
                </svg>
              </div>
            </div>

            <div class="pkg-price">
              <span class="currency">Rp</span>
              <span class="amount">{{ pkg.price.toLocaleString('id-ID') }}</span>
            </div>
            <ul class="pkg-features">
              <li v-for="(feat, index) in pkg.features" :key="index">
                <span class="check-icon">✓</span> {{ feat }}
              </li>
            </ul>
            <button @click="selectPackage(pkg)" class="btn btn-primary pkg-btn">
              Pilih Paket Ini
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="container testimonials-section">
      <h2 class="section-title">Testimoni Pengguna</h2>
      <p class="section-subtitle-center">Dengarkan kisah sukses mereka yang berhasil melamar kerja menggunakan CV Kuberkah.</p>
      
      <div v-if="isLoading" class="loader">Memuat testimoni...</div>
      <div v-else class="grid-3">
        <div v-for="testi in testimonials" :key="testi.id" class="testi-card glass-card">
          <div class="testi-header">
            <img :src="testi.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80'" :alt="testi.name" class="testi-avatar" />
            <div>
              <h4 class="testi-name">{{ testi.name }}</h4>
              <span class="testi-role">{{ testi.role }}</span>
            </div>
          </div>
          <p class="testi-message">"{{ testi.message }}"</p>
        </div>
      </div>
    </section>

    <!-- Company Profile Footer -->
    <footer id="company-info" class="company-footer">
      <div class="container footer-grid">
        <!-- Column 1: Branding and Socials -->
        <div class="footer-brand-side">
          <img :src="logoSrc" alt="CV Kuberkah Logo" class="footer-logo-img" />
          <p class="footer-tagline">{{ companyProfile.tagline }}</p>
          <p class="footer-desc">{{ companyProfile.description }}</p>
          <div class="footer-socials">
            <a :href="'https://wa.me/' + companyProfile.whatsapp" target="_blank" class="social-icon" aria-label="WhatsApp">💬</a>
            <a href="https://instagram.com" target="_blank" class="social-icon" aria-label="Instagram">📸</a>
            <a href="mailto:support@cvkuberkah.com" class="social-icon" aria-label="Email">✉️</a>
            <a href="https://linkedin.com" target="_blank" class="social-icon" aria-label="LinkedIn">🔗</a>
          </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div class="footer-nav-side">
          <h3>Tautan Berguna</h3>
          <ul class="footer-nav-links">
            <li><a href="/cvkuberkah/#templates">Pilihan Template</a></li>
            <li><a href="/cvkuberkah/#workflow-steps">Tata Cara Pembuatan</a></li>
            <li><a href="/cvkuberkah/#packages">Pilihan Paket Harga</a></li>
            <li><a href="/cvkuberkah/#testimonials">Testimoni Pengguna</a></li>
          </ul>
        </div>

        <!-- Column 3: Contact Details -->
        <div class="footer-contact-side">
          <h3>Hubungi Kami</h3>
          <ul class="footer-contacts">
            <li><strong>📍 Alamat:</strong> {{ companyProfile.address }}</li>
            <li><strong>✉️ Email:</strong> {{ companyProfile.email }}</li>
            <li><strong>💬 WhatsApp Admin:</strong> <a :href="'https://wa.me/' + companyProfile.whatsapp" target="_blank">+{{ companyProfile.whatsapp }}</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 {{ companyProfile.name }}. All rights reserved.</p>
        <span class="footer-trust-tag">🛡️ 100% Terbaca Sistem ATS (ATS-Friendly Verified)</span>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.home-wrapper {
  overflow-x: hidden;
}

/* Hero Section */
.hero-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6rem 4rem;
  background: linear-gradient(135deg, hsl(210, 40%, 96%) 0%, hsl(243, 80%, 97%) 100%);
  gap: 2rem;
}
.hero-content {
  flex: 1;
  max-width: 650px;
}
.hero-tag {
  display: inline-block;
  padding: 0.4rem 1rem;
  background-color: var(--primary-light);
  color: var(--primary);
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.85rem;
  margin-bottom: 1.5rem;
}
.hero-title {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, var(--text-dark) 30%, var(--primary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hero-subtitle {
  font-size: 1.15rem;
  color: var(--text-muted);
  margin-bottom: 2rem;
}
.hero-cta {
  display: flex;
  gap: 1rem;
}
.hero-cta .btn-lg {
  padding: 1rem 2rem;
  font-size: 1.05rem;
}

.hero-graphic {
  position: relative;
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}
.hero-bg-blobs {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
  z-index: 0;
}
.blob {
  position: absolute;
  width: 250px;
  height: 250px;
  border-radius: 50%;
  filter: blur(75px);
  opacity: 0.45;
  mix-blend-mode: multiply;
  animation: blob-bounce 10s infinite alternate ease-in-out;
}
.blob-1 {
  background: radial-gradient(circle, var(--primary) 0%, rgba(99, 102, 241, 0) 70%);
  top: -20px;
  left: 10px;
}
.blob-2 {
  background: radial-gradient(circle, var(--secondary) 0%, rgba(16, 185, 129, 0) 70%);
  bottom: -20px;
  right: 10px;
  animation-delay: 2s;
}

@keyframes blob-bounce {
  0% {
    transform: translate(0, 0) scale(1);
  }
  100% {
    transform: translate(30px, 30px) scale(1.15);
  }
}

.mockup-cv {
  position: relative;
  z-index: 1;
  width: 290px;
  height: 380px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  transform: rotate(3deg);
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.6s ease;
  animation: float 6s ease-in-out infinite;
  cursor: pointer;
}

.mockup-cv:hover {
  transform: rotate(0deg) translateY(-8px) scale(1.02);
  box-shadow: 0 30px 60px rgba(99, 102, 241, 0.15);
}

@keyframes float {
  0%, 100% {
    transform: rotate(3deg) translateY(0);
  }
  50% {
    transform: rotate(4deg) translateY(-12px);
  }
}

.mockup-header-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mockup-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.avatar-svg {
  width: 24px;
  height: 24px;
}

.mockup-header-text {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: left;
}

.mockup-line-name {
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text-dark);
}

.mockup-line-sub {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.mockup-divider {
  height: 1px;
  background: linear-gradient(90deg, rgba(99,102,241,0.2) 0%, rgba(255,255,255,0) 100%);
  width: 100%;
}

.mockup-section-title {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--primary);
  text-align: left;
}

.mockup-line-desc {
  font-size: 0.8rem;
  color: var(--text-dark);
  font-weight: 600;
  text-align: left;
}

.mockup-line {
  height: 6px;
  background: rgba(0, 0, 0, 0.05);
  width: 90%;
  border-radius: 3px;
}

.mockup-row {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.mockup-badge {
  font-size: 0.7rem;
  padding: 0.25rem 0.6rem;
  background: var(--primary-light);
  color: var(--primary);
  border-radius: 50px;
  font-weight: 600;
}

/* Templates Section */
.section-subtitle-center {
  text-align: center;
  color: var(--text-muted);
  max-width: 600px;
  margin: -2rem auto 3rem;
  font-size: 1.1rem;
}
.template-card {
  padding: 1.25rem;
}
.tpl-image-wrapper {
  position: relative;
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: 1rem;
  aspect-ratio: 4/5;
  background-color: #eee;
}
.tpl-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: var(--transition);
}
.template-card:hover .tpl-image {
  transform: scale(1.05);
}
.tpl-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background: rgba(0, 0, 0, 0.75);
  color: white;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 50px;
  backdrop-filter: blur(4px);
}
.tpl-name {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}
.tpl-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
}

/* Packages Section */
.packages-bg {
  background: linear-gradient(180deg, var(--bg-main) 0%, hsl(210, 40%, 94%) 100%);
  padding: 5rem 0;
}
.packages-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}
.package-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  background: white;
  padding: 3rem 2rem 2.5rem;
}
.package-featured {
  border: 2px solid var(--primary);
  transform: scale(1.05);
  box-shadow: var(--shadow-lg);
}
.package-featured:hover {
  transform: scale(1.05) translateY(-4px);
}
.pkg-image-wrapper {
  width: 100%;
  height: 120px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.pkg-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-color);
  background: var(--bg-main);
  padding: 0.5rem;
}
.pkg-svg-placeholder {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%);
  border-radius: 50%;
  border: 1px solid rgba(99, 102, 241, 0.15);
  color: var(--primary);
  transition: transform 0.4s ease;
}
.package-card:hover .pkg-svg-placeholder {
  transform: scale(1.1) rotate(3deg);
}
.pkg-svg-icon {
  width: 42px;
  height: 42px;
}
.pkg-svg-icon.basic {
  color: #3b82f6;
}
.pkg-svg-icon.premium {
  color: #a855f7;
}
.pkg-svg-icon.bundle {
  color: #10b981;
}
.featured-badge {
  position: absolute;
  top: -15px;
  background: var(--primary);
  color: white;
  padding: 0.35rem 1.25rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
}
.pkg-name {
  font-size: 1.4rem;
  margin-bottom: 1rem;
}
.pkg-price {
  margin-bottom: 1.5rem;
  display: flex;
  align-items: flex-start;
  color: var(--primary);
}
.pkg-price .currency {
  font-weight: 700;
  font-size: 1.1rem;
  margin-top: 0.2rem;
  margin-right: 0.1rem;
}
.pkg-price .amount {
  font-size: 2.8rem;
  font-weight: 800;
  font-family: var(--font-heading);
}
.pkg-features {
  list-style: none;
  width: 100%;
  margin-bottom: 2rem;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.pkg-features li {
  font-size: 0.95rem;
  color: var(--text-dark);
  display: flex;
  gap: 0.5rem;
}
.check-icon {
  color: var(--secondary);
  font-weight: bold;
}
.pkg-btn {
  width: 100%;
  margin-top: auto;
}

/* Testimonials */
.testi-card {
  padding: 2rem;
}
.testi-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.testi-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--primary-light);
}
.testi-name {
  font-size: 1.05rem;
  font-weight: 700;
}
.testi-role {
  font-size: 0.85rem;
  color: var(--text-muted);
}
.testi-message {
  font-size: 0.95rem;
  color: var(--text-dark);
  font-style: italic;
  line-height: 1.5;
}

/* Footer */
.company-footer {
  background-color: var(--text-dark);
  color: var(--text-light);
  padding: 5rem 0 2rem;
  margin-top: 5rem;
  border-top: 4px solid var(--primary);
}
.footer-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 4rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 3rem;
}
.footer-logo-img {
  height: 54px;
  width: auto;
  object-fit: contain;
  margin-bottom: 1.25rem;
}
.footer-tagline {
  color: var(--secondary);
  font-weight: 600;
  margin-bottom: 0.75rem;
  font-family: var(--font-heading);
}
.footer-desc {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}
.footer-socials {
  display: flex;
  gap: 0.75rem;
}
.social-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 1.1rem;
  transition: var(--transition);
}
.social-icon:hover {
  background: var(--primary);
  transform: translateY(-2px);
}
.footer-nav-side h3,
.footer-contact-side h3 {
  color: white;
  margin-bottom: 1.5rem;
  font-size: 1.25rem;
  font-family: var(--font-heading);
  position: relative;
  padding-bottom: 0.5rem;
}
.footer-nav-side h3::after,
.footer-contact-side h3::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 30px;
  height: 2px;
  background-color: var(--secondary);
}
.footer-nav-links,
.footer-contacts {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.footer-nav-links a {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 0.95rem;
  transition: var(--transition);
}
.footer-nav-links a:hover {
  color: var(--secondary);
  padding-left: 4px;
}
.footer-contacts li {
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.95rem;
}
.footer-contacts a {
  color: var(--secondary);
  text-decoration: none;
  font-weight: 600;
}
.footer-contacts a:hover {
  text-decoration: underline;
}
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 2rem;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.45);
  flex-wrap: wrap;
  gap: 1rem;
}
.footer-trust-tag {
  color: var(--secondary);
  font-weight: 600;
  font-size: 0.85rem;
  font-family: var(--font-heading);
}

.loader {
  text-align: center;
  padding: 3rem;
  font-size: 1.1rem;
  color: var(--text-muted);
}

/* Responsive adjustment */
@media (max-width: 768px) {
  .hero-section {
    flex-direction: column;
    padding: 4rem 2rem;
    text-align: center;
  }
  .hero-cta {
    justify-content: center;
  }
  .hero-graphic {
    display: none;
  }
  .footer-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

/* Steps/Workflow Section */
.workflow-section {
  padding: 6rem 0;
  background: linear-gradient(180deg, var(--bg-main) 0%, var(--bg-main) 100%);
  border-top: 1px solid var(--border-color);
  border-bottom: 1px solid var(--border-color);
}
.steps-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1.5rem;
  margin-top: 3rem;
}
@media (max-width: 1024px) {
  .steps-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (max-width: 768px) {
  .steps-grid {
    grid-template-columns: 1fr;
  }
}
.workflow-card {
  padding: 2.25rem 1.75rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 16px;
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s ease, box-shadow 0.4s ease;
  position: relative;
}
.workflow-card:hover {
  transform: translateY(-6px);
  border-color: rgba(99, 102, 241, 0.3);
  box-shadow: 0 12px 30px rgba(99, 102, 241, 0.08);
}
.step-icon {
  width: 36px;
  height: 36px;
  color: var(--primary);
  margin-bottom: 1.25rem;
}
.step-num {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 800;
  color: rgba(99, 102, 241, 0.15);
}
.workflow-card h3 {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  font-family: var(--font-heading);
  color: var(--text-dark);
}
.workflow-card p {
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.5;
}
</style>
