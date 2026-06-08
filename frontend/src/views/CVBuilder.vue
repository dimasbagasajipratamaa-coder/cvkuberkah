<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

// Active wizard step (1 to 7)
const currentStep = ref(1)

// Selected package details from URL params
const packageName = ref('Paket Basic CV ATS')
const packagePrice = ref(25000)
const packages = ref([])

// CV Form data
const form = reactive({
  full_name: '',
  email: '',
  phone: '',
  address: '',
  linkedin: '',
  photo_url: '',
  font_family: 'Times New Roman, serif',
  about_me_answers: {
    lulusan: '',
    karir: '',
    pengalaman: '',
    skill: '',
    meyakinkan: ''
  },
  education: [
    { school: '', major: '', period: '' }
  ],
  experience: [],
  organization: [],
  certifications: [],
  soft_skills: [],
  hard_skills: []
})

// Skills input tags helper
const softSkillInput = ref('')
const hardSkillInput = ref('')

const isLoading = ref(false)
const error = ref('')

// Fetch available packages from backend
const fetchPackages = async () => {
  try {
    const response = await fetch('/cvkuberkah/api/content.php')
    if (response.ok) {
      const data = await response.json()
      packages.value = data.packages || []
      
      // If we have packages and a packageName query param, make sure the price is synced
      if (route.query.package && packages.value.length > 0) {
        const matchingPkg = packages.value.find(p => p.name === route.query.package)
        if (matchingPkg) {
          packagePrice.value = matchingPkg.price
        }
      }
    }
  } catch (err) {
    console.error('Failed to load packages in builder:', err)
  }
}

const selectPackage = (pkg) => {
  packageName.value = pkg.name
  packagePrice.value = pkg.price
}

// Load query parameters
onMounted(() => {
  fetchPackages()
  
  if (route.query.package) {
    packageName.value = route.query.package
  }
  if (route.query.price) {
    packagePrice.value = parseInt(route.query.price)
  }
  
  // Prefill default email and name from logged in user if available
  const user = JSON.parse(localStorage.getItem('user'))
  if (user) {
    form.full_name = user.name || ''
    form.email = user.email || ''
  }
})

// Auto-compiled Tentang Saya (About me) summary paragraph
const compiledAboutMe = computed(() => {
  const ans = form.about_me_answers
  if (!ans.lulusan && !ans.karir && !ans.pengalaman && !ans.skill && !ans.meyakinkan) {
    return 'Lengkapi data formulir "Tentang Saya" untuk membuat ringkasan profesional Anda secara otomatis.'
  }
  
  const part1 = ans.lulusan ? `Saya adalah lulusan ${ans.lulusan}. ` : ''
  const part2 = ans.karir ? `Saya memiliki ketertarikan karir yang tinggi di bidang ${ans.karir}. ` : ''
  const part3 = ans.pengalaman ? `Memiliki pengalaman di mana saya telah ${ans.pengalaman}. ` : ''
  const part4 = ans.skill ? `Didukung dengan keahlian yang mendalam dalam hal ${ans.skill}, ` : ''
  const part5 = ans.meyakinkan ? `saya berkomitmen tinggi memberikan hasil kerja maksimal karena ${ans.meyakinkan}.` : ''
  
  return `${part1}${part2}${part3}${part4}${part5}`
})

// Photo handler (convert uploaded image to base64)
const handlePhotoUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file foto maksimal adalah 2MB.')
    return
  }
  
  const reader = new FileReader()
  reader.onload = (event) => {
    form.photo_url = event.target.result
  }
  reader.readAsDataURL(file)
}

const removePhoto = () => {
  form.photo_url = ''
}

// Add/Remove array fields
const addEducation = () => form.education.push({ school: '', major: '', period: '' })
const removeEducation = (index) => form.education.splice(index, 1)

const addExperience = () => form.experience.push({ company: '', role: '', jobdesk: '', period: '' })
const removeExperience = (index) => form.experience.splice(index, 1)

const addOrganization = () => form.organization.push({ name: '', role: '', jobdesk: '', period: '' })
const removeOrganization = (index) => form.organization.splice(index, 1)

const addCertification = () => form.certifications.push({ name: '', issuer: '', period: '' })
const removeCertification = (index) => form.certifications.splice(index, 1)

// Tag management
const addSoftSkill = () => {
  const val = softSkillInput.value.trim()
  if (val && !form.soft_skills.includes(val)) {
    form.soft_skills.push(val)
  }
  softSkillInput.value = ''
}
const removeSoftSkill = (index) => form.soft_skills.splice(index, 1)

const addHardSkill = () => {
  const val = hardSkillInput.value.trim()
  if (val && !form.hard_skills.includes(val)) {
    form.hard_skills.push(val)
  }
  hardSkillInput.value = ''
}
const removeHardSkill = (index) => form.hard_skills.splice(index, 1)

// Submit handler
const submitCV = async () => {
  error.value = ''
  
  // Quick validation (Offset due to 7 steps)
  if (!form.full_name || !form.email || !form.phone || !form.address) {
    error.value = 'Harap isi seluruh data informasi kontak pada Langkah 2.'
    currentStep.value = 2
    return
  }
  if (form.education.length === 0 || !form.education[0].school) {
    error.value = 'Harap isi minimal satu riwayat pendidikan pada Langkah 4.'
    currentStep.value = 4
    return
  }
  if (form.soft_skills.length === 0 || form.hard_skills.length === 0) {
    error.value = 'Harap masukkan minimal satu keahlian soft skill dan hard skill pada Langkah 7.'
    currentStep.value = 7
    return
  }
  
  isLoading.value = true
  
  try {
    const user = JSON.parse(localStorage.getItem('user'))
    const payload = {
      ...form,
      about_me: compiledAboutMe.value,
      package_name: packageName.value,
      price: packagePrice.value
    }
    
    const response = await fetch('/cvkuberkah/api/cv.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${user ? user.token : ''}`
      },
      body: JSON.stringify(payload)
    })
    
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.error || 'Gagal menyimpan data CV.')
    }
    
    // Success redirect
    router.push({ name: 'Dashboard' })
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="builder-layout">
    <!-- WIZARD FORM PANEL (LEFT) -->
    <div class="form-panel glass-card">
      <div class="wizard-header">
        <span class="package-indicator">🎁 {{ packageName }} (Rp {{ packagePrice.toLocaleString('id-ID') }})</span>
        <h2>Buat CV ATS</h2>
        
        <!-- Progress Steps Tracker -->
        <div class="steps-tracker">
          <div v-for="step in 7" :key="step" class="step-node" :class="{ 'active': currentStep === step, 'completed': currentStep > step }" @click="currentStep = step">
            {{ step }}
          </div>
          <div class="progress-bar-line" :style="{ width: ((currentStep - 1) / 6) * 100 + '%' }"></div>
        </div>
      </div>

      <div v-if="error" class="alert-danger">{{ error }}</div>

      <!-- Step Form Bodies -->
      <div class="wizard-body">
        
        <!-- STEP 1: Pilih Paket -->
        <div v-if="currentStep === 1">
          <h3 class="step-title">Langkah 1: Pilih Paket CV Anda</h3>
          <p class="step-desc">Pilih paket terbaik sesuai kebutuhan karir Anda untuk mulai membuat CV ATS.</p>
          
          <div class="packages-selection-grid">
            <div v-for="pkg in packages" :key="pkg.id" 
                 class="pkg-select-card" 
                 :class="{ 'selected': packageName === pkg.name }"
                 @click="selectPackage(pkg)">
              <div class="pkg-select-header">
                <span class="pkg-select-name">{{ pkg.name }}</span>
                <span v-if="packageName === pkg.name" class="pkg-selected-badge">Terpilih</span>
              </div>
              <div class="pkg-select-price">Rp {{ pkg.price.toLocaleString('id-ID') }}</div>
              <ul class="pkg-select-features">
                <li v-for="(feat, idx) in pkg.features" :key="idx">
                  ✓ {{ feat }}
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- STEP 2: Personal Info -->
        <div v-if="currentStep === 2">
          <h3 class="step-title">Langkah 2: Informasi Kontak</h3>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Lengkap</label>
              <input v-model="form.full_name" type="text" class="form-input" placeholder="contoh: Ahmad Budiman" required />
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-input" placeholder="contoh: ahmad@gmail.com" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">No. HP / WhatsApp</label>
              <input v-model="form.phone" type="text" class="form-input" placeholder="contoh: 081234567890" required />
            </div>
            <div class="form-group">
              <label class="form-label">Link LinkedIn (Opsional)</label>
              <input v-model="form.linkedin" type="url" class="form-input" placeholder="contoh: linkedin.com/in/username" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Alamat Lengkap</label>
              <input v-model="form.address" type="text" class="form-input" placeholder="contoh: Jl. Diponegoro No. 10, Jakarta Pusat" required />
            </div>
            <div class="form-group">
              <label class="form-label">Pilihan Font CV ATS</label>
              <select v-model="form.font_family" class="form-input">
                <option value="Times New Roman, serif">Times New Roman (Serif Formal)</option>
                <option value="Arial, sans-serif">Arial (Sans-Serif Modern)</option>
                <option value="Calibri, sans-serif">Calibri (Sans-Serif Bersih)</option>
                <option value="Georgia, serif">Georgia (Serif Elegan)</option>
                <option value="Helvetica, sans-serif">Helvetica (Sans-Serif Netral)</option>
              </select>
            </div>
          </div>
          <div class="form-group photo-uploader-container">
            <label class="form-label">Foto Profil (Opsional)</label>
            <div class="photo-controls">
              <input type="file" @change="handlePhotoUpload" accept="image/*" class="file-input" id="profile-photo-input" />
              <label for="profile-photo-input" class="btn btn-outline btn-sm">Pilih File Foto</label>
              <button v-if="form.photo_url" @click="removePhoto" class="btn btn-danger btn-sm">Hapus Foto</button>
            </div>
          </div>
        </div>

        <!-- STEP 3: Tentang Saya (5 Points) -->
        <div v-if="currentStep === 3">
          <h3 class="step-title">Langkah 3: Tentang Saya (Profil Profesional)</h3>
          <p class="step-desc">Isi 5 pertanyaan di bawah untuk menyusun ringkasan profil Anda secara profesional.</p>
          
          <div class="form-group">
            <label class="form-label">1. Lulusan apa? (Nama Jurusan & Kampus/Sekolah)</label>
            <input v-model="form.about_me_answers.lulusan" type="text" class="form-input" placeholder="contoh: S1 Teknik Informatika dari Universitas Gadjah Mada" />
          </div>
          <div class="form-group">
            <label class="form-label">2. Tertarik berkarir di bidang apa?</label>
            <input v-model="form.about_me_answers.karir" type="text" class="form-input" placeholder="contoh: Mobile App Developer atau UI/UX Specialist" />
          </div>
          <div class="form-group">
            <label class="form-label">3. Punya pengalaman apa?</label>
            <input v-model="form.about_me_answers.pengalaman" type="text" class="form-input" placeholder="contoh: magang selama 6 bulan di startup e-commerce lokal" />
          </div>
          <div class="form-group">
            <label class="form-label">4. Punya skill / keahlian apa?</label>
            <input v-model="form.about_me_answers.skill" type="text" class="form-input" placeholder="contoh: Flutter, Firebase, Git, serta kolaborasi tim yang solid" />
          </div>
          <div class="form-group">
            <label class="form-label">5. Apa kelebihan Anda yang meyakinkan perusahaan?</label>
            <input v-model="form.about_me_answers.meyakinkan" type="text" class="form-input" placeholder="contoh: saya memiliki dedikasi tinggi untuk memecahkan problem kode rumit" />
          </div>
        </div>

        <!-- STEP 4: Pendidikan -->
        <div v-if="currentStep === 4">
          <div class="title-action-row">
            <h3 class="step-title">Langkah 4: Riwayat Pendidikan</h3>
            <button @click="addEducation" class="btn btn-outline btn-sm">+ Tambah</button>
          </div>
          
          <div v-for="(edu, index) in form.education" :key="index" class="dynamic-group glass-card">
            <div class="group-header">
              <h4>Pendidikan #{{ index + 1 }}</h4>
              <button @click="removeEducation(index)" class="btn btn-text btn-danger-text" v-if="form.education.length > 1">Hapus</button>
            </div>
            <div class="form-group">
              <label class="form-label">Nama Institusi Pendidikan (Sekolah/Universitas)</label>
              <input v-model="edu.school" type="text" class="form-input" placeholder="contoh: Universitas Indonesia" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Jurusan / Bidang Studi</label>
                <input v-model="edu.major" type="text" class="form-input" placeholder="contoh: S1 Ilmu Komputer" required />
              </div>
              <div class="form-group">
                <label class="form-label">Periode Waktu (Tahun)</label>
                <input v-model="edu.period" type="text" class="form-input" placeholder="contoh: 2020 - 2024" required />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 5: Pengalaman Organisasi -->
        <div v-if="currentStep === 5">
          <div class="title-action-row">
            <h3 class="step-title">Langkah 5: Pengalaman Organisasi (Opsional)</h3>
            <button @click="addOrganization" class="btn btn-outline btn-sm">+ Tambah</button>
          </div>
          
          <div v-if="form.organization.length === 0" class="no-items-placeholder">
            Belum ada pengalaman organisasi ditambahkan.
          </div>
          
          <div v-for="(org, index) in form.organization" :key="index" class="dynamic-group glass-card">
            <div class="group-header">
              <h4>Organisasi #{{ index + 1 }}</h4>
              <button @click="removeOrganization(index)" class="btn btn-text btn-danger-text">Hapus</button>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Nama Organisasi</label>
                <input v-model="org.name" type="text" class="form-input" placeholder="contoh: Himpunan Mahasiswa IT" />
              </div>
              <div class="form-group">
                <label class="form-label">Role / Jabatan</label>
                <input v-model="org.role" type="text" class="form-input" placeholder="contoh: Ketua Divisi Humas" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Periode Waktu</label>
                <input v-model="org.period" type="text" class="form-input" placeholder="contoh: 2021 - 2022" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Job Desk / Deskripsi Peran (Gunakan baris baru untuk bullet list)</label>
              <textarea v-model="org.jobdesk" class="form-input textarea-input" placeholder="contoh:&#10;- Mengelola hubungan media luar dengan kampus&#10;- Memimpin 10 anggota divisi Humas"></textarea>
            </div>
          </div>
        </div>

        <!-- STEP 6: Pengalaman Kerja -->
        <div v-if="currentStep === 6">
          <div class="title-action-row">
            <h3 class="step-title">Langkah 6: Pengalaman Kerja (Opsional)</h3>
            <button @click="addExperience" class="btn btn-outline btn-sm">+ Tambah</button>
          </div>
          
          <div v-if="form.experience.length === 0" class="no-items-placeholder">
            Belum ada pengalaman kerja ditambahkan.
          </div>
          
          <div v-for="(exp, index) in form.experience" :key="index" class="dynamic-group glass-card">
            <div class="group-header">
              <h4>Pekerjaan #{{ index + 1 }}</h4>
              <button @click="removeExperience(index)" class="btn btn-text btn-danger-text">Hapus</button>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Nama Kantor / Perusahaan</label>
                <input v-model="exp.company" type="text" class="form-input" placeholder="contoh: PT GoTo Gojek Tokopedia" />
              </div>
              <div class="form-group">
                <label class="form-label">Role / Posisi Kerja</label>
                <input v-model="exp.role" type="text" class="form-input" placeholder="contoh: Junior Backend Engineer" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Periode Waktu</label>
                <input v-model="exp.period" type="text" class="form-input" placeholder="contoh: Jan 2024 - Sekarang" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Job Desk / Kontribusi (Gunakan baris baru untuk bullet list)</label>
              <textarea v-model="exp.jobdesk" class="form-input textarea-input" placeholder="contoh:&#10;- Membangun RESTful API dengan NodeJS&#10;- Mengoptimasi query database PostgreSQL hingga 20%"></textarea>
            </div>
          </div>
        </div>

        <!-- STEP 7: Sertifikat & Skills -->
        <div v-if="currentStep === 7">
          <h3 class="step-title">Langkah 7: Sertifikasi & Keahlian</h3>
          
          <!-- Certifications subform -->
          <div class="title-action-row">
            <h4>Sertifikat Keahlian (Opsional)</h4>
            <button @click="addCertification" class="btn btn-outline btn-sm">+ Tambah</button>
          </div>
          <div v-for="(cert, index) in form.certifications" :key="index" class="dynamic-group glass-card compact-group">
            <div class="group-header">
              <h5>Sertifikat #{{ index + 1 }}</h5>
              <button @click="removeCertification(index)" class="btn btn-text btn-danger-text">Hapus</button>
            </div>
            <div class="form-row">
              <input v-model="cert.name" type="text" class="form-input" placeholder="Nama Sertifikat (e.g. TOEFL ITP)" />
              <input v-model="cert.issuer" type="text" class="form-input" placeholder="Lembaga Penerbit (e.g. ETS)" />
              <input v-model="cert.period" type="text" class="form-input" placeholder="Tahun/Periode (e.g. 2023)" />
            </div>
          </div>
          
          <div class="divider-line"></div>
          
          <!-- Soft skills subform -->
          <div class="form-group">
            <label class="form-label">Soft Skills (Tulis satu per satu, tekan Enter/Tambah)</label>
            <div class="tag-input-wrapper">
              <input v-model="softSkillInput" @keydown.enter.prevent="addSoftSkill" type="text" class="form-input" placeholder="e.g. Komunikasi Efektif, Manajemen Waktu" />
              <button @click.prevent="addSoftSkill" class="btn btn-outline btn-sm">Tambah</button>
            </div>
            <div class="tags-container">
              <span v-for="(tag, index) in form.soft_skills" :key="index" class="tag">
                {{ tag }}
                <button @click.prevent="removeSoftSkill(index)" class="tag-remove">&times;</button>
              </span>
            </div>
          </div>

          <!-- Hard skills subform -->
          <div class="form-group">
            <label class="form-label">Hard Skills / Technical Skills (Tulis satu per satu, tekan Enter/Tambah)</label>
            <div class="tag-input-wrapper">
              <input v-model="hardSkillInput" @keydown.enter.prevent="addHardSkill" type="text" class="form-input" placeholder="e.g. Vue.js, PHP, MySQL, Figma" />
              <button @click.prevent="addHardSkill" class="btn btn-outline btn-sm">Tambah</button>
            </div>
            <div class="tags-container">
              <span v-for="(tag, index) in form.hard_skills" :key="index" class="tag tag-accent">
                {{ tag }}
                <button @click.prevent="removeHardSkill(index)" class="tag-remove">&times;</button>
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- Action Navigation Buttons -->
      <div class="wizard-footer">
        <button v-if="currentStep > 1" @click="currentStep--" class="btn btn-outline">Kembali</button>
        <div class="footer-right-actions">
          <button v-if="currentStep < 7" @click="currentStep++" class="btn btn-primary">Lanjutkan</button>
          <button v-else @click="submitCV" class="btn btn-secondary" :disabled="isLoading">
            {{ isLoading ? 'Menyimpan...' : 'Simpan & Proses Pembayaran' }}
          </button>
        </div>
      </div>
    </div>

    <!-- LIVE ATS CV PREVIEW PANEL (RIGHT) -->
    <div class="preview-panel">
      <div class="preview-badge">⚡ Real-time ATS Preview</div>
      <div class="preview-scroll-container">
        <!-- Replicates the exact class used for PDF Printing -->
        <div class="cv-paper-preview" :style="{ fontFamily: form.font_family }">
          <!-- Header -->
          <div :class="form.photo_url ? 'cv-header-photo' : 'cv-header'">
            <div class="cv-header-text">
              <div class="cv-name">{{ form.full_name || 'NAMA LENGKAP ANDA' }}</div>
              <div class="cv-contact">
                <span>{{ form.address || 'Alamat Tempat Tinggal' }}</span>
                <span>{{ form.phone || 'No. HP Aktif' }}</span>
                <span>{{ form.email || 'Email Pelamar' }}</span>
                <span v-if="form.linkedin">{{ form.linkedin }}</span>
              </div>
            </div>
            <div v-if="form.photo_url" class="cv-photo-wrapper">
              <img :src="form.photo_url" class="cv-photo-img" alt="Foto Profil" />
            </div>
          </div>

          <!-- Tentang Saya Summary -->
          <div class="cv-section">
            <div class="cv-section-title">Ringkasan Profesional</div>
            <div class="cv-section-content">
              <p>{{ compiledAboutMe }}</p>
            </div>
          </div>

          <!-- Pendidikan -->
          <div class="cv-section">
            <div class="cv-section-title">Pendidikan</div>
            <div class="cv-section-content">
              <div v-for="(edu, idx) in form.education" :key="idx" class="cv-item">
                <div class="cv-item-header">
                  <span>{{ edu.school || 'Nama Universitas / Sekolah' }}</span>
                  <span>{{ edu.period || 'Periode (e.g. 2020 - 2024)' }}</span>
                </div>
                <div class="cv-item-sub">
                  <span>Jurusan: {{ edu.major || 'Nama Program Studi' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Pengalaman Kerja -->
          <div class="cv-section" v-if="form.experience.length > 0">
            <div class="cv-section-title">Pengalaman Kerja</div>
            <div class="cv-section-content">
              <div v-for="(exp, idx) in form.experience" :key="idx" class="cv-item">
                <div class="cv-item-header">
                  <span>{{ exp.company || 'Nama Perusahaan' }}</span>
                  <span>{{ exp.period || 'Periode Waktu Kerja' }}</span>
                </div>
                <div class="cv-item-sub">
                  <span>Role: {{ exp.role || 'Posisi/Jabatan' }}</span>
                </div>
                <ul class="cv-item-bullets" v-if="exp.jobdesk">
                  <li v-for="(bullet, bidx) in exp.jobdesk.split('\n')" :key="bidx">
                    <span v-if="bullet.trim()">{{ bullet }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Pengalaman Organisasi -->
          <div class="cv-section" v-if="form.organization.length > 0">
            <div class="cv-section-title">Pengalaman Organisasi</div>
            <div class="cv-section-content">
              <div v-for="(org, idx) in form.organization" :key="idx" class="cv-item">
                <div class="cv-item-header">
                  <span>{{ org.name || 'Nama Organisasi' }}</span>
                  <span>{{ org.period || 'Periode Organisasi' }}</span>
                </div>
                <div class="cv-item-sub">
                  <span>Role: {{ org.role || 'Jabatan/Peran' }}</span>
                </div>
                <ul class="cv-item-bullets" v-if="org.jobdesk">
                  <li v-for="(bullet, bidx) in org.jobdesk.split('\n')" :key="bidx">
                    <span v-if="bullet.trim()">{{ bullet }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Sertifikat -->
          <div class="cv-section" v-if="form.certifications.length > 0">
            <div class="cv-section-title">Sertifikasi & Penghargaan</div>
            <div class="cv-section-content">
              <div v-for="(cert, idx) in form.certifications" :key="idx" class="cv-item">
                <div class="cv-item-header">
                  <span>{{ cert.name || 'Nama Sertifikasi' }}</span>
                  <span>{{ cert.period || 'Tahun' }}</span>
                </div>
                <div class="cv-item-sub">
                  <span>Penerbit: {{ cert.issuer || 'Lembaga Penerbit' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Keahlian -->
          <div class="cv-section">
            <div class="cv-section-title">Keahlian (Skills)</div>
            <div class="cv-section-content cv-skills-grid">
              <div class="cv-skills-title">Hard Skills:</div>
              <div>{{ form.hard_skills.length > 0 ? form.hard_skills.join(', ') : 'Daftar Hard Skill Anda' }}</div>
              
              <div class="cv-skills-title">Soft Skills:</div>
              <div>{{ form.soft_skills.length > 0 ? form.soft_skills.join(', ') : 'Daftar Soft Skill Anda' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.builder-layout {
  display: flex;
  height: calc(100vh - 80px);
  overflow: hidden;
  background-color: var(--bg-main);
}
.form-panel {
  flex: 1.2;
  overflow-y: auto;
  border-radius: 0;
  border: none;
  background: white;
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
}
.wizard-header {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1.5rem;
  margin-bottom: 2rem;
}
.package-indicator {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.wizard-header h2 {
  font-size: 1.8rem;
  margin-top: 0.25rem;
  margin-bottom: 1.5rem;
}

/* Tracker Nodes */
.steps-tracker {
  display: flex;
  justify-content: space-between;
  position: relative;
  max-width: 450px;
  margin: 0 auto;
}
.step-node {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: var(--border-color);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  z-index: 2;
  cursor: pointer;
  transition: var(--transition);
}
.step-node.active {
  background-color: var(--primary);
  color: white;
  box-shadow: 0 0 0 4px var(--primary-light);
}
.step-node.completed {
  background-color: var(--secondary);
  color: white;
}
.progress-bar-line {
  position: absolute;
  top: 15px;
  left: 0;
  height: 3px;
  background-color: var(--secondary);
  z-index: 1;
  transition: var(--transition);
}
.steps-tracker::before {
  content: '';
  position: absolute;
  top: 15px;
  left: 0;
  right: 0;
  height: 3px;
  background-color: var(--border-color);
  z-index: 0;
}

.wizard-body {
  flex: 1;
  margin-bottom: 2rem;
}
.step-title {
  font-size: 1.4rem;
  margin-bottom: 0.5rem;
}
.step-desc {
  color: var(--text-muted);
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}
.textarea-input {
  min-height: 120px;
  resize: vertical;
}

/* Dynamic subforms */
.title-action-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
}
.dynamic-group {
  border: 1px solid var(--border-color);
  box-shadow: none;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  border-radius: var(--radius-sm);
  background: var(--bg-main);
}
.dynamic-group:hover {
  transform: none;
  box-shadow: none;
}
.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  border-bottom: 1px dashed var(--border-color);
  padding-bottom: 0.5rem;
}
.btn-danger-text {
  color: var(--danger);
  padding: 0;
}
.btn-danger-text:hover {
  color: var(--danger-hover);
  background: transparent;
}
.no-items-placeholder {
  text-align: center;
  padding: 2rem;
  background: var(--bg-main);
  border: 1px dashed var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  font-size: 0.95rem;
}
.compact-group {
  padding: 1rem;
}
.divider-line {
  height: 1px;
  background-color: var(--border-color);
  margin: 2rem 0;
}

/* Photo style */
.photo-uploader-container {
  background: var(--bg-main);
  padding: 1rem;
  border-radius: var(--radius-sm);
}
.photo-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.file-input {
  display: none;
}

/* Tags styles */
.tag-input-wrapper {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.tag {
  background-color: var(--primary-light);
  color: var(--primary);
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.tag-accent {
  background-color: hsl(142, 100%, 93%);
  color: var(--secondary);
}
.tag-remove {
  background: transparent;
  border: none;
  color: inherit;
  font-weight: bold;
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
}

/* Action footer */
.wizard-footer {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid var(--border-color);
  padding-top: 1.5rem;
}
.footer-right-actions {
  display: flex;
  gap: 1rem;
  margin-left: auto;
}

/* PREVIEW PANEL (RIGHT) */
.preview-panel {
  flex: 1;
  background-color: var(--text-muted);
  display: flex;
  flex-direction: column;
  border-left: 2px solid var(--border-color);
  position: relative;
}
.preview-badge {
  position: absolute;
  top: 15px;
  left: 15px;
  background-color: rgba(0, 0, 0, 0.75);
  color: white;
  padding: 0.4rem 1rem;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 50px;
  z-index: 10;
  backdrop-filter: blur(4px);
}
.preview-scroll-container {
  flex: 1;
  overflow-y: auto;
  padding: 3rem 2rem;
  display: flex;
  justify-content: center;
}
.cv-paper-preview {
  background-color: white;
  width: 100%;
  max-width: 180mm;
  min-height: 250mm;
  padding: 12mm;
  box-sizing: border-box;
  color: #333333;
  font-family: 'Times New Roman', Times, serif;
  font-size: 8.5pt;
  line-height: 1.35;
  box-shadow: var(--shadow-lg);
  align-self: flex-start;
}
.cv-paper-preview .cv-name {
  font-size: 15pt;
}
.cv-paper-preview .cv-contact {
  font-size: 8pt;
}
.cv-paper-preview .cv-section-title {
  font-size: 9.5pt;
  margin-bottom: 6px;
}
.cv-paper-preview .cv-item-header {
  font-size: 8.5pt;
}
.cv-paper-preview .cv-item-sub {
  font-size: 8pt;
}
.cv-paper-preview .cv-item-bullets {
  font-size: 8pt;
}
.cv-paper-preview .cv-photo-img {
  width: 2.2cm;
  height: 2.9cm;
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

@media (max-width: 992px) {
  .builder-layout {
    flex-direction: column;
    height: auto;
    overflow: visible;
  }
  .form-panel {
    flex: none;
    height: auto;
    border-radius: var(--radius-md);
    margin: 2rem 1.5rem;
  }
  .preview-panel {
    flex: none;
    height: 600px;
    border-left: none;
    border-top: 2px solid var(--border-color);
  }
}

/* Package Selection step styles */
.packages-selection-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}
.pkg-select-card {
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  background: white;
  display: flex;
  flex-direction: column;
  position: relative;
  text-align: left;
}
.pkg-select-card:hover {
  transform: translateY(-4px);
  border-color: var(--primary);
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.08);
}
.pkg-select-card.selected {
  border-color: var(--primary);
  background: linear-gradient(180deg, rgba(99, 102, 241, 0.03) 0%, rgba(99, 102, 241, 0) 100%);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.12);
}
.pkg-select-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}
.pkg-select-name {
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--text-dark);
}
.pkg-selected-badge {
  background: var(--primary);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 50px;
}
.pkg-select-price {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--primary);
  margin-bottom: 1rem;
}
.pkg-select-features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-muted);
}
.pkg-select-features li {
  line-height: 1.3;
}
</style>
