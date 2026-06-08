<script setup>
import { ref, onMounted, reactive } from 'vue'

const activeTab = ref('analytics') // analytics, requests, content
const stats = ref({
  total_users: 0,
  total_requests: 0,
  pending_requests: 0,
  total_revenue: 0,
  monthly_revenue: 0
})

const requests = ref([])
const isLoading = ref(true)
const feedbackMsg = ref('')
const feedbackError = ref('')

// Content Editor configurations
const companyProfile = ref({
  name: '', tagline: '', description: '', whatsapp: '', email: '', address: '', instagram: ''
})
const packages = ref([])
const testimonials = ref([])
const templates = ref([])

// CV inspector modal state
const showInspectModal = ref(false)
const selectedCV = ref(null)

const getHeaders = () => {
  const user = JSON.parse(localStorage.getItem('user'))
  return {
    'Authorization': `Bearer ${user ? user.token : ''}`,
    'Content-Type': 'application/json'
  }
}

const fetchAdminData = async () => {
  isLoading.value = true
  feedbackMsg.value = ''
  feedbackError.value = ''
  try {
    // 1. Fetch dashboard stats
    const statsResp = await fetch('/cvkuberkah/api/admin.php?action=dashboard_stats', {
      headers: getHeaders()
    })
    if (statsResp.ok) {
      const data = await statsResp.json()
      stats.value = data.stats
    }
    
    // 2. Fetch requests list
    const requestsResp = await fetch('/cvkuberkah/api/admin.php?action=list_requests', {
      headers: getHeaders()
    })
    if (requestsResp.ok) {
      requests.value = await requestsResp.json()
    }
    
    // 3. Fetch static contents for editing
    const contentResp = await fetch('/cvkuberkah/api/content.php')
    if (contentResp.ok) {
      const data = await contentResp.json()
      packages.value = data.packages || []
      testimonials.value = data.testimonials || []
      templates.value = data.templates || []
      if (data.company_profile) {
        companyProfile.value = data.company_profile
      }
    }
  } catch (error) {
    feedbackError.value = 'Gagal memuat data admin panel.'
  } finally {
    isLoading.value = false
  }
}

// Verify Payment Action
const verifyPayment = async (cvId, status) => {
  feedbackMsg.value = ''
  feedbackError.value = ''
  try {
    const response = await fetch('/cvkuberkah/api/admin.php?action=verify_payment', {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        cv_id: cvId,
        status: status
      })
    })
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.error || 'Gagal mengubah status pembayaran.')
    }
    feedbackMsg.value = data.message
    fetchAdminData() // Refresh list and stats
  } catch (error) {
    feedbackError.value = error.message
  }
}

// Update Content settings
const saveContent = async (key, value) => {
  feedbackMsg.value = ''
  feedbackError.value = ''
  try {
    const response = await fetch('/cvkuberkah/api/admin.php?action=update_content', {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        key: key,
        value: value
      })
    })
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.error || 'Gagal menyimpan konten.')
    }
    feedbackMsg.value = data.message
  } catch (error) {
    feedbackError.value = error.message
  }
}

// Inspect CV details
const inspectCV = async (cvId) => {
  try {
    const response = await fetch(`/cvkuberkah/api/cv.php?id=${cvId}`, {
      headers: getHeaders()
    })
    if (!response.ok) {
      throw new Error('Gagal mengambil data CV detail.')
    }
    selectedCV.value = await response.json()
    showInspectModal.value = true
  } catch (err) {
    alert(err.message)
  }
}

// Helpers to add/remove packages/testimonials locally in form
const addLocalPackage = () => {
  packages.value.push({ id: 'pkg_' + Date.now(), name: 'Paket Baru', price: 10000, features: [], recommended: false, image_url: '' })
}
const removeLocalPackage = (idx) => {
  packages.value.splice(idx, 1)
}
const handlePackagePhotoUpload = (event, pkg) => {
  const file = event.target.files[0]
  if (!file) return
  
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file foto maksimal 2MB.')
    return
  }
  
  const reader = new FileReader()
  reader.onload = (e) => {
    pkg.image_url = e.target.result // Base64 representation
  }
  reader.readAsDataURL(file)
}
const addLocalTestimonial = () => {
  testimonials.value.push({ id: Date.now(), name: 'Nama Pengguna', role: 'Pekerjaan', message: 'Tulis review...', avatar: '' })
}
const removeLocalTestimonial = (idx) => {
  testimonials.value.splice(idx, 1)
}

onMounted(() => {
  fetchAdminData()
})
</script>

<template>
  <div class="container admin-container">
    <div class="admin-header">
      <h1 class="page-title">Panel Kontrol Admin</h1>
      <p class="text-muted">Kelola transaksi, pantau pendapatan, dan atur konten halaman customer.</p>
    </div>

    <!-- Alert notifications -->
    <div v-if="feedbackMsg" class="alert-success">{{ feedbackMsg }}</div>
    <div v-if="feedbackError" class="alert-danger">{{ feedbackError }}</div>

    <!-- Admin Tabs Menu -->
    <div class="admin-tabs no-print">
      <button @click="activeTab = 'analytics'" class="tab-btn" :class="{ 'active': activeTab === 'analytics' }">📈 Analitik & Pendapatan</button>
      <button @click="activeTab = 'requests'" class="tab-btn" :class="{ 'active': activeTab === 'requests' }">📥 Request CV ({{ requests.length }})</button>
      <button @click="activeTab = 'content'" class="tab-btn" :class="{ 'active': activeTab === 'content' }">⚙️ Kelola Konten Halaman</button>
    </div>

    <div v-if="isLoading" class="loader">Memuat data panel admin...</div>

    <!-- 1. ANALYTICS TAB -->
    <div v-else-if="activeTab === 'analytics'" class="tab-content">
      <div class="stats-grid">
        <div class="stat-card glass-card border-indigo">
          <span class="stat-icon">👥</span>
          <h3>Total Customer</h3>
          <p class="stat-value">{{ stats.total_users }}</p>
          <span class="stat-label">Terdaftar aktif</span>
        </div>
        <div class="stat-card glass-card border-amber">
          <span class="stat-icon">⏳</span>
          <h3>Pending Verifikasi</h3>
          <p class="stat-value text-amber">{{ stats.pending_requests }}</p>
          <span class="stat-label">Butuh persetujuan</span>
        </div>
        <div class="stat-card glass-card border-emerald">
          <span class="stat-icon">💰</span>
          <h3>Total Pendapatan</h3>
          <p class="stat-value text-emerald">Rp {{ stats.total_revenue.toLocaleString('id-ID') }}</p>
          <span class="stat-label">Semua pesanan lunas</span>
        </div>
        <div class="stat-card glass-card border-purple">
          <span class="stat-icon">📈</span>
          <h3>Pendapatan 30 Hari</h3>
          <p class="stat-value text-purple">Rp {{ stats.monthly_revenue.toLocaleString('id-ID') }}</p>
          <span class="stat-label">Transaksi sebulan terakhir</span>
        </div>
      </div>
    </div>

    <!-- 2. REQUEST CV TAB -->
    <div v-else-if="activeTab === 'requests'" class="tab-content">
      <div class="cv-table-wrapper glass-card">
        <table class="cv-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Nama di CV</th>
              <th>Paket Pilihan</th>
              <th>Biaya</th>
              <th>Tgl Request</th>
              <th>Pembayaran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="req in requests" :key="req.id">
              <td><strong>#{{ req.id }}</strong></td>
              <td>{{ req.customer_name }}<br><small class="text-muted">{{ req.email }}</small></td>
              <td>{{ req.full_name }}<br><small class="text-muted">{{ req.phone }}</small></td>
              <td>{{ req.package_name }}</td>
              <td>Rp {{ parseFloat(req.price).toLocaleString('id-ID') }}</td>
              <td>{{ new Date(req.created_at).toLocaleDateString('id-ID') }}</td>
              <td>
                <span class="badge" :class="req.payment_status === 'verified' ? 'badge-verified' : 'badge-pending'">
                  {{ req.payment_status === 'verified' ? 'Lunas' : 'Pending' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="inspectCV(req.id)" class="btn btn-outline btn-table">👁️ Data</button>
                  <button 
                    v-if="req.payment_status === 'pending'" 
                    @click="verifyPayment(req.id, 'verified')" 
                    class="btn btn-secondary btn-table"
                  >
                    ✓ Verifikasi
                  </button>
                  <button 
                    v-else 
                    @click="verifyPayment(req.id, 'pending')" 
                    class="btn btn-danger btn-table"
                  >
                    ✕ Batalkan
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 3. MANAGE CONTENT TAB -->
    <div v-else-if="activeTab === 'content'" class="tab-content">
      
      <!-- SUBSECTION: Company profile settings -->
      <div class="glass-card editor-section">
        <h3>Profil Perusahaan & Kontak</h3>
        <p class="section-desc">Atur nomor WhatsApp admin, email, alamat, dan deskripsi branding website.</p>
        
        <form @submit.prevent="saveContent('company_profile', companyProfile)">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Brand</label>
              <input v-model="companyProfile.name" type="text" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Slogan (Tagline)</label>
              <input v-model="companyProfile.tagline" type="text" class="form-input" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">No. WhatsApp Admin (format kode negara tanpa +, contoh: 6289656111199)</label>
              <input v-model="companyProfile.whatsapp" type="text" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Email Support</label>
              <input v-model="companyProfile.email" type="email" class="form-input" required />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Alamat Kantor</label>
            <input v-model="companyProfile.address" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Link Instagram</label>
            <input v-model="companyProfile.instagram" type="text" class="form-input" />
          </div>
          <button type="submit" class="btn btn-primary">Simpan Profil Kontak</button>
        </form>
      </div>

      <!-- SUBSECTION: Packages settings -->
      <div class="glass-card editor-section">
        <div class="title-action-row">
          <h3>Kelola Paket CV ATS</h3>
          <button @click="addLocalPackage" class="btn btn-outline btn-sm">+ Tambah Paket</button>
        </div>
        
        <div v-for="(pkg, idx) in packages" :key="pkg.id" class="dynamic-group">
          <div class="group-header">
            <h4>Paket #{{ idx + 1 }}: {{ pkg.name }}</h4>
            <button @click="removeLocalPackage(idx)" class="btn btn-text btn-danger-text">Hapus Paket</button>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Paket</label>
              <input v-model="pkg.name" type="text" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Harga (Rp)</label>
              <input v-model.number="pkg.price" type="number" class="form-input" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Fitur-fitur Paket (satu baris per fitur)</label>
            <!-- Treat features as newline string inside UI, convert back to array on save -->
            <textarea 
              :value="pkg.features.join('\n')"
              @input="e => pkg.features = e.target.value.split('\n')" 
              class="form-input textarea-input-sm" 
              placeholder="e.g.&#10;Template Standard&#10;Format PDF"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group checkbox-group" style="margin-bottom: 1rem;">
              <label class="form-label" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; text-transform: none;">
                <input v-model="pkg.recommended" type="checkbox" style="width: 18px; height: 18px;" />
                <span>Rekomendasikan Paket Ini (Tampilkan Badge & Efek Khusus)</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Foto / Ilustrasi Paket</label>
            <div class="photo-controls" style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
              <input type="file" @change="e => handlePackagePhotoUpload(e, pkg)" accept="image/*" class="file-input" :id="'pkg-photo-' + pkg.id" style="display: none;" />
              <label :for="'pkg-photo-' + pkg.id" class="btn btn-outline btn-sm" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.85rem;">Pilih Foto Paket</label>
              <button v-if="pkg.image_url" @click="pkg.image_url = ''" class="btn btn-danger btn-sm" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.85rem;">Hapus Foto</button>
              <div v-if="pkg.image_url" class="pkg-preview-thumb" style="width: 50px; height: 50px; border-radius: 4px; border: 1px solid var(--border-color); overflow: hidden; display: flex; justify-content: center; align-items: center; background: white;">
                <img :src="pkg.image_url" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Pratinjau" />
              </div>
            </div>
          </div>
        </div>
        <button @click="saveContent('packages', packages)" class="btn btn-primary">Simpan Semua Paket</button>
      </div>

      <!-- SUBSECTION: Testimonials settings -->
      <div class="glass-card editor-section">
        <div class="title-action-row">
          <h3>Kelola Testimoni Pengguna</h3>
          <button @click="addLocalTestimonial" class="btn btn-outline btn-sm">+ Tambah Testimoni</button>
        </div>
        
        <div v-for="(testi, idx) in testimonials" :key="testi.id" class="dynamic-group">
          <div class="group-header">
            <h4>Review oleh: {{ testi.name }}</h4>
            <button @click="removeLocalTestimonial(idx)" class="btn btn-text btn-danger-text">Hapus Review</button>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Reviewer</label>
              <input v-model="testi.name" type="text" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Pekerjaan / Jabatan</label>
              <input v-model="testi.role" type="text" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Isi Testimoni</label>
            <input v-model="testi.message" type="text" class="form-input" />
          </div>
        </div>
        <button @click="saveContent('testimonials', testimonials)" class="btn btn-primary">Simpan Semua Testimoni</button>
      </div>

    </div>

    <!-- INSPECT CV MODAL -->
    <div v-if="showInspectModal && selectedCV" class="modal-overlay">
      <div class="modal-card inspect-modal-card">
        <div class="modal-header">
          <h3>Detail Data CV: {{ selectedCV.full_name }}</h3>
          <button @click="showInspectModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body inspect-modal-body">
          <div class="data-block">
            <h4>Informasi Kontak</h4>
            <ul class="data-list">
              <li><strong>Nama:</strong> {{ selectedCV.full_name }}</li>
              <li><strong>Email:</strong> {{ selectedCV.email }}</li>
              <li><strong>No. HP:</strong> {{ selectedCV.phone }}</li>
              <li><strong>Alamat:</strong> {{ selectedCV.address }}</li>
              <li><strong>LinkedIn:</strong> {{ selectedCV.linkedin || '-' }}</li>
            </ul>
          </div>
          
          <div class="data-block" v-if="selectedCV.photo_url">
            <h4>Foto Pelamar</h4>
            <img :src="selectedCV.photo_url" class="inspect-photo" />
          </div>

          <div class="data-block">
            <h4>Tentang Saya (Profesional Summary)</h4>
            <p class="summary-box">{{ selectedCV.about_me }}</p>
          </div>

          <div class="data-block">
            <h4>Pendidikan</h4>
            <div v-for="(edu, idx) in selectedCV.education" :key="idx" class="inspect-item">
              <strong>{{ edu.school }}</strong> (Jurusan: {{ edu.major }}) | <em>{{ edu.period }}</em>
            </div>
          </div>

          <div class="data-block" v-if="selectedCV.experience && selectedCV.experience.length > 0">
            <h4>Pengalaman Kerja</h4>
            <div v-for="(exp, idx) in selectedCV.experience" :key="idx" class="inspect-item">
              <strong>{{ exp.company }}</strong> (Role: {{ exp.role }}) | <em>{{ exp.period }}</em>
              <pre class="jobdesk-text">{{ exp.jobdesk }}</pre>
            </div>
          </div>

          <div class="data-block" v-if="selectedCV.organization && selectedCV.organization.length > 0">
            <h4>Pengalaman Organisasi</h4>
            <div v-for="(org, idx) in selectedCV.organization" :key="idx" class="inspect-item">
              <strong>{{ org.name }}</strong> (Role: {{ org.role }}) | <em>{{ org.period }}</em>
              <pre class="jobdesk-text">{{ org.jobdesk }}</pre>
            </div>
          </div>

          <div class="data-block" v-if="selectedCV.certifications && selectedCV.certifications.length > 0">
            <h4>Sertifikasi</h4>
            <div v-for="(cert, idx) in selectedCV.certifications" :key="idx" class="inspect-item">
              <strong>{{ cert.name }}</strong> (Lembaga: {{ cert.issuer }}) | <em>{{ cert.period }}</em>
            </div>
          </div>

          <div class="data-block">
            <h4>Keahlian (Skills)</h4>
            <p><strong>Hard Skills:</strong> {{ selectedCV.hard_skills.join(', ') }}</p>
            <p><strong>Soft Skills:</strong> {{ selectedCV.soft_skills.join(', ') }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-container {
  padding-top: 3rem;
  padding-bottom: 5rem;
}
.admin-header {
  margin-bottom: 2.5rem;
}
.admin-tabs {
  display: flex;
  gap: 1rem;
  border-bottom: 2px solid var(--border-color);
  padding-bottom: 0.5rem;
  margin-bottom: 2.5rem;
}
.tab-btn {
  background: transparent;
  border: none;
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 1rem;
  padding: 0.75rem 1.25rem;
  cursor: pointer;
  color: var(--text-muted);
  border-bottom: 3px solid transparent;
  transition: var(--transition);
}
.tab-btn:hover {
  color: var(--primary);
}
.tab-btn.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
}

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}
.stat-card {
  padding: 2rem;
  position: relative;
  overflow: hidden;
}
.stat-card:hover {
  transform: translateY(-2px);
}
.stat-icon {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
  display: block;
}
.stat-value {
  font-size: 2rem;
  font-weight: 800;
  font-family: var(--font-heading);
  line-height: 1.1;
  margin: 0.5rem 0;
}
.stat-label {
  font-size: 0.85rem;
  color: var(--text-muted);
}
.border-indigo { border-left: 4px solid var(--primary); }
.border-amber { border-left: 4px solid var(--warning); }
.border-emerald { border-left: 4px solid var(--secondary); }
.border-purple { border-left: 4px solid var(--accent); }
.text-amber { color: var(--warning); }
.text-emerald { color: var(--secondary); }
.text-purple { color: var(--accent); }

/* Table styling override */
.cv-table-wrapper {
  overflow-x: auto;
  padding: 0;
}
.cv-table {
  width: 100%;
  border-collapse: collapse;
}
.cv-table th, .cv-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.9rem;
}
.cv-table th {
  background-color: rgba(99, 102, 241, 0.03);
  font-weight: 600;
  text-align: left;
}
.action-buttons {
  display: flex;
  gap: 0.4rem;
}
.btn-table {
  padding: 0.4rem 0.8rem;
  font-size: 0.8rem;
  border-radius: var(--radius-sm);
}

/* Editor Section */
.editor-section {
  margin-bottom: 2.5rem;
  background: white;
}
.editor-section h3 {
  font-size: 1.4rem;
  margin-bottom: 0.25rem;
}
.section-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}
.dynamic-group {
  border: 1px solid var(--border-color);
  background-color: var(--bg-main);
  padding: 1.5rem;
  border-radius: var(--radius-sm);
  margin-bottom: 1.5rem;
  box-shadow: none;
}
.dynamic-group:hover {
  transform: none;
  box-shadow: none;
}
.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px dashed var(--border-color);
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}
.btn-danger-text {
  color: var(--danger);
  padding: 0;
  background: transparent;
}
.textarea-input-sm {
  min-height: 80px;
  resize: vertical;
}

/* Inspect Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}
.inspect-modal-card {
  width: 100%;
  max-width: 700px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  padding: 2.5rem;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.close-btn {
  font-size: 2rem;
  border: none;
  background: transparent;
  cursor: pointer;
  line-height: 1;
}
.inspect-modal-body {
  overflow-y: auto;
  padding-right: 10px;
}
.data-block {
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
}
.data-block h4 {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
  color: var(--primary);
}
.data-list {
  list-style: none;
}
.data-list li {
  margin-bottom: 0.25rem;
}
.inspect-photo {
  max-width: 120px;
  max-height: 160px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-color);
}
.summary-box {
  background-color: var(--bg-main);
  padding: 1rem;
  border-radius: var(--radius-sm);
  font-style: italic;
  font-size: 0.95rem;
}
.inspect-item {
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}
.jobdesk-text {
  font-family: var(--font-primary);
  font-size: 0.85rem;
  color: var(--text-muted);
  white-space: pre-wrap;
  margin-top: 0.25rem;
  background: var(--bg-main);
  padding: 0.5rem;
  border-radius: var(--radius-sm);
}

/* Alert notifications */
.alert-success {
  background-color: hsl(142, 100%, 95%);
  color: var(--secondary);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  border: 1px solid hsl(142, 100%, 90%);
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

.loader {
  text-align: center;
  padding: 3rem;
  font-size: 1.1rem;
  color: var(--text-muted);
}
</style>
