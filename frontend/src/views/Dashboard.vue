<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const cvRequests = ref([])
const isLoading = ref(true)
const error = ref('')

// Modal state
const showPaymentModal = ref(false)
const activeRequest = ref(null)
const companyProfile = ref(null)

// CV Preview Modal state
const showPreviewModal = ref(false)
const selectedCVDetails = ref(null)
const activePreviewTab = ref('cv')
const translateToEnglish = ref(false)

const getHeaders = () => {
  const user = JSON.parse(localStorage.getItem('user'))
  return {
    'Authorization': `Bearer ${user ? user.token : ''}`,
    'Content-Type': 'application/json'
  }
}

const fetchDashboardData = async () => {
  isLoading.value = true
  error.value = ''
  try {
    // 1. Fetch CV list
    const cvResponse = await fetch('/cvkuberkah/api/cv.php', {
      headers: getHeaders()
    })
    if (!cvResponse.ok) {
      throw new Error('Gagal mengambil daftar CV Anda.')
    }
    cvRequests.value = await cvResponse.json()
    
    // 2. Fetch Company profile (for WhatsApp number)
    const contentResp = await fetch('/cvkuberkah/api/content.php')
    if (contentResp.ok) {
      const data = await contentResp.json()
      companyProfile.value = data.company_profile
    }
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const openPaymentModal = (req) => {
  activeRequest.value = req
  showPaymentModal.value = true
}

const closePaymentModal = () => {
  showPaymentModal.value = false
  activeRequest.value = null
}

const openWhatsApp = () => {
  if (!activeRequest.value || !companyProfile.value) return
  
  const user = JSON.parse(localStorage.getItem('user'))
  const waNumber = companyProfile.value.whatsapp || '6285143606723'
  
  const text = `Halo Admin *CV Kuberkah*,\n\nSaya ingin mengajukan verifikasi pembayaran untuk pesanan CV ATS saya.\n\n*Detail Pesanan:*\n- *Nama Customer:* ${user.name}\n- *Email:* ${user.email}\n- *ID Request:* #${activeRequest.value.id}\n- *Nama Paket:* ${activeRequest.value.package_name}\n- *Total Transfer:* Rp ${parseFloat(activeRequest.value.price).toLocaleString('id-ID')}\n\nMohon untuk diperiksa dan diaktifkan akses download-nya. Terima kasih! 🙏`
  
  const encodedText = encodeURIComponent(text)
  const waUrl = `https://api.whatsapp.com/send?phone=${waNumber}&text=${encodedText}`
  
  window.open(waUrl, '_blank')
}

// Fetch single CV details for print preview
const previewCV = async (reqId) => {
  isLoading.value = true
  try {
    const response = await fetch(`/cvkuberkah/api/cv.php?id=${reqId}`, {
      headers: getHeaders()
    })
    if (!response.ok) {
      throw new Error('Gagal mengambil detail CV.')
    }
    selectedCVDetails.value = await response.json()
    activePreviewTab.value = 'cv'
    translateToEnglish.value = false
    showPreviewModal.value = true
  } catch (err) {
    alert(err.message)
  } finally {
    isLoading.value = false
  }
}

const printCV = () => {
  window.print()
}

const downloadWord = () => {
  if (!selectedCVDetails.value) return

  const cv = selectedCVDetails.value
  
  if (activePreviewTab.value === 'cover_letter' && cv.cover_letter) {
    // Download Cover Letter instead!
    const fontFamily = cv.font_family || 'Times New Roman, serif'
    const fullName = cv.full_name || ''
    
    // We can parse or rebuild the cover letter answers
    const ans = cv.cover_letter_answers || {}
    const kota = ans.kota_pembuatan || ''
    const tanggal = ans.tanggal_pembuatan || ''
    const posisi = ans.posisi_dilamar || ''
    const penerima = ans.penerima_surat || ''
    const perusahaan = ans.nama_perusahaan || ''
    const alamat = ans.alamat_perusahaan || ''
    const email = cv.email || ''
    const phone = cv.phone || ''
    const address = cv.address || ''
    const linkedin = cv.linkedin ? ` | ${cv.linkedin}` : ''

    const isEng = translateToEnglish.value
    
    const clHtml = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <title>${isEng ? 'Cover Letter' : 'Surat Lamaran'} - ${fullName}</title>
  <style>
    @page {
      size: 8.5in 11in;
      margin: 0.79in 0.79in 0.79in 0.79in;
    }
    body {
      font-family: ${fontFamily.split(',')[0].trim()};
      font-size: 11pt;
      line-height: 1.5;
      color: #333333;
    }
    p {
      margin: 0 0 10pt 0;
      text-align: justify;
      text-indent: 36pt;
    }
  </style>
</head>
<body>
  <!-- Centered Title -->
  <div style="text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; margin-bottom: 25px;">
    ${isEng ? 'COVER LETTER' : 'SURAT LAMARAN KERJA'}
  </div>

  <div style="margin-bottom: 15px; font-size: 11pt;">${kota}, ${tanggal}</div>
  
  <div style="font-weight: bold; margin-bottom: 15px; font-size: 11pt;">
    ${isEng ? 'Subject: Job Application –' : 'Hal: Lamaran Pekerjaan –'} ${posisi}
  </div>
  
  <div style="margin-bottom: 20px; line-height: 1.4; font-size: 11pt;">
    <strong>${isEng ? 'To:' : 'Yth.'} ${penerima}</strong><br>
    ${perusahaan}<br>
    ${alamat}
  </div>
  
  <div style="margin-bottom: 10px; font-size: 11pt;">${isEng ? 'Dear Hiring Team,' : 'Dengan hormat,'}</div>
  
  <p>
    ${isEng 
      ? `Based on the job vacancy information published through ${ans.sumber_info || '[LinkedIn/Situs Resmi]'}, I intend to apply for the ${posisi} position at ${perusahaan}. My brief personal biodata details are as follows:` 
      : `Berdasarkan informasi lowongan pekerjaan yang dipublikasikan melalui ${ans.sumber_info || '[LinkedIn/Situs Resmi Perusahaan]'}, saya bermaksud untuk mengajukan diri guna mengisi posisi ${posisi} di ${perusahaan}. Adapun kualifikasi biodata singkat saya adalah sebagai berikut:`
    }
  </p>

  <!-- Biodata table inside Word letter -->
  <div style="margin-left: 36pt; margin-top: 10px; margin-bottom: 15px;">
    <table cellpadding="3" cellspacing="0" style="border: none; border-collapse: collapse; font-size: 11pt; font-family: ${fontFamily};">
      <tr>
        <td style="font-weight: bold; border: none; width: 120px; padding-left: 0;">${isEng ? 'Name' : 'Nama'}</td>
        <td style="border: none;">:</td>
        <td style="border: none;">${fullName}</td>
      </tr>
      <tr>
        <td style="font-weight: bold; border: none; padding-left: 0;">${isEng ? 'Address' : 'Alamat'}</td>
        <td style="border: none;">:</td>
        <td style="border: none;">${address}</td>
      </tr>
      <tr>
        <td style="font-weight: bold; border: none; padding-left: 0;">${isEng ? 'Phone/WA' : 'No. HP/WhatsApp'}</td>
        <td style="border: none;">:</td>
        <td style="border: none;">${phone}</td>
      </tr>
      <tr>
        <td style="font-weight: bold; border: none; padding-left: 0;">Email</td>
        <td style="border: none;">:</td>
        <td style="border: none;">${email}</td>
      </tr>
      ${linkedin ? `
      <tr>
        <td style="font-weight: bold; border: none; padding-left: 0;">LinkedIn</td>
        <td style="border: none;">:</td>
        <td style="border: none;">${linkedin.replace('|', '').trim()}</td>
      </tr>` : ''}
    </table>
  </div>
  
  <p>
    ${isEng 
      ? `I am a professional with ${ans.tahun_pengalaman || '[X]'} years of experience in the field of ${ans.bidang_keahlian || '[Keahlian]'}. In my previous role at ${ans.perusahaan_sebelumnya || '[Perusahaan Sebelumnya]'} as ${ans.jabatan_terakhir || '[Jabatan Terakhir]'}, I was fully responsible for ${ans.jobdesc_singkat || '[Jobdesk]'}. One of my greatest achievements was successfully ${ans.prestasi || '[Prestasi Terbaik]'} by implementing data-driven strategies.`
      : `Saya merupakan seorang profesional yang berpengalaman selama ${ans.tahun_pengalaman || '[X]'} tahun di bidang ${ans.bidang_keahlian || '[Sebutkan Bidang Keahlian Utama]'}. Pada peran saya sebelumnya di ${ans.perusahaan_sebelumnya || '[Nama Perusahaan Sebelumnya]'} sebagai ${ans.jabatan_terakhir || '[Jabatan Terakhir]'}, saya bertanggung jawab penuh dalam ${ans.jobdesc_singkat || '[Sebutkan core job desc utama singkat]'}. Salah satu pencapaian terbesar saya adalah berhasil ${ans.prestasi || '[Sebutkan Prestasi Terbaik]'} dengan menerapkan strategi berbasis data.`
    }
  </p>
  
  <p>
    ${isEng
      ? `In addition to practical experience, I have also mastered technical skills and the use of modern tools such as ${ans.tools_kunci || '[Tools Kunci]'}. I am known as an adaptive, goal-oriented individual with excellent communication and team collaboration skills, which are crucial to supporting the performance of the ${posisi} position at your company.`
      : `Selain pengalaman praktis, saya juga menguasai keahlian teknis serta penggunaan instrumen kerja modern seperti ${ans.tools_kunci || '[Sebutkan 2-3 Tools / Keterampilan Teknis khusus yang relevan]'}. Saya dikenal sebagai individu yang adaptif, berorientasi pada target, serta memiliki kemampuan komunikasi dan kolaborasi tim yang sangat baik, yang mana aspek-aspek tersebut sangat krusial untuk menunjang performa posisi ${posisi} di perusahaan Bapak/Ibu.`
    }
  </p>
  
  <p>
    ${isEng
      ? `For further consideration, I have attached my latest Curriculum Vitae (CV) and other supporting documents. I hope to be given the opportunity to attend an interview, so that I can explain in more detail my qualifications, vision, and the tangible contribution I am ready to provide for ${perusahaan}. Thank you for your time, attention, and the opportunity.`
      : `Sebagai bahan pertimbangan lebih lanjut, bersama surat ini saya lampirkan berkas Curriculum Vitae (CV) terbaru beserta dokumen pendukung lainnya. Besar harapan saya untuk diberikan kesempatan menghadiri sesi wawancara, agar saya dapat memaparkan lebih mendalam mengenai kualifikasi, visi, dan bentuk kontribusi nyata yang siap saya berikan untuk ${perusahaan}. Terima kasih atas waktu, perhatian, dan kesempatan yang Bapak/Ibu berikan.`
    }
  </p>
  
  <!-- Right Aligned Signature block inside Word using Table -->
  <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: none; margin-top: 30px;">
    <tr>
      <td width="60%" style="border: none;"></td>
      <td width="40%" align="left" style="border: none; font-size: 11pt; line-height: 1.4;">
        <div style="margin-bottom: 50px;">${isEng ? 'Sincerely,' : 'Hormat saya,'}</div>
        <strong>${fullName}</strong>
      </td>
    </tr>
  </table>
</body>
</html>
    `
    const blob = new Blob(['\ufeff' + clHtml], { type: 'application/msword;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${isEng ? 'Cover_Letter' : 'Surat_Lamaran'}_${fullName.replace(/\s+/g, '_')}.doc`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    return
  }

  const isEng = translateToEnglish.value
  const fontFamily = cv.font_family || 'Times New Roman, serif'
  
  const fullName = cv.full_name || ''
  const email = cv.email || ''
  const phone = cv.phone || ''
  const address = cv.address || ''
  const linkedin = cv.linkedin || ''
  const photoUrl = cv.photo_url || ''
  
  let aboutMe = cv.about_me || ''
  if (isEng && cv.about_me_answers) {
    const ans = cv.about_me_answers
    const part1 = ans.lulusan ? `I am a graduate of ${ans.lulusan}. ` : ''
    const part2 = ans.karir ? `I have a strong career interest in the field of ${ans.karir}. ` : ''
    const part3 = ans.pengalaman ? `With a background in ${ans.pengalaman}, ` : ''
    const part4 = ans.skill ? `and expertise in ${ans.skill}, ` : ''
    const part5 = ans.meyakinkan ? `I am highly committed to delivering maximum results because ${ans.meyakinkan}.` : ''
    aboutMe = `${part1}${part2}${part3}${part4}${part5}`
  }
  
  const educationList = cv.education || []
  const experienceList = cv.experience || []
  const organizationList = cv.organization || []
  const certificationsList = cv.certifications || []
  const softSkills = cv.soft_skills || []
  const hardSkills = cv.hard_skills || []

  // Create table rows for education
  const educationHtml = educationList.map(edu => `
    <div style="margin-bottom: 8px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0; padding: 0; border: none;">
        <tr>
          <td align="left" style="font-size: 11pt; font-weight: bold; font-family: ${fontFamily}; margin: 0; padding: 0; border: none; color: #333333;">${edu.school}</td>
          <td align="right" style="font-size: 10pt; font-weight: bold; color: #444444; font-family: ${fontFamily}; margin: 0; padding: 0; border: none;">${edu.period}</td>
        </tr>
      </table>
      <div style="font-size: 10pt; font-style: italic; color: #444444; margin-top: 2px; margin-bottom: 0; font-family: ${fontFamily}; padding: 0;">${isEng ? 'Major' : 'Jurusan'}: ${edu.major}</div>
    </div>
  `).join('')

  // Create table rows for experience
  const experienceHtml = experienceList.length > 0 ? `
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Work Experience' : 'Pengalaman Kerja'}</div>
      ${experienceList.map(exp => `
        <div style="margin-bottom: 10px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
          <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0; padding: 0; border: none;">
            <tr>
              <td align="left" style="font-size: 11pt; font-weight: bold; font-family: ${fontFamily}; margin: 0; padding: 0; border: none; color: #333333;">${exp.company}</td>
              <td align="right" style="font-size: 10pt; font-weight: bold; color: #444444; font-family: ${fontFamily}; margin: 0; padding: 0; border: none;">${exp.period}</td>
            </tr>
          </table>
          <div style="font-size: 10pt; font-style: italic; color: #444444; margin-top: 2px; margin-bottom: 4px; font-family: ${fontFamily}; padding: 0;">Role: ${exp.role}</div>
          <ul style="margin-top: 2px; margin-bottom: 0; margin-left: 15pt; padding-left: 0; font-size: 10pt; line-height: 1.35; font-family: ${fontFamily}; list-style-type: disc;">
            ${exp.jobdesk.split('\n').map(bullet => bullet.trim() ? `<li style="margin-top: 0; margin-bottom: 2px; font-family: ${fontFamily}; padding: 0;">${bullet}</li>` : '').join('')}
          </ul>
        </div>
      `).join('')}
    </div>
  ` : ''

  // Create table rows for organization
  const organizationHtml = organizationList.length > 0 ? `
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Organizational Experience' : 'Pengalaman Organisasi'}</div>
      ${organizationList.map(org => `
        <div style="margin-bottom: 10px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
          <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0; padding: 0; border: none;">
            <tr>
              <td align="left" style="font-size: 11pt; font-weight: bold; font-family: ${fontFamily}; margin: 0; padding: 0; border: none; color: #333333;">${org.name}</td>
              <td align="right" style="font-size: 10pt; font-weight: bold; color: #444444; font-family: ${fontFamily}; margin: 0; padding: 0; border: none;">${org.period}</td>
            </tr>
          </table>
          <div style="font-size: 10pt; font-style: italic; color: #444444; margin-top: 2px; margin-bottom: 4px; font-family: ${fontFamily}; padding: 0;">Role: ${org.role}</div>
          <ul style="margin-top: 2px; margin-bottom: 0; margin-left: 15pt; padding-left: 0; font-size: 10pt; line-height: 1.35; font-family: ${fontFamily}; list-style-type: disc;">
            ${org.jobdesk.split('\n').map(bullet => bullet.trim() ? `<li style="margin-top: 0; margin-bottom: 2px; font-family: ${fontFamily}; padding: 0;">${bullet}</li>` : '').join('')}
          </ul>
        </div>
      `).join('')}
    </div>
  ` : ''

  // Create table rows for certifications
  const certificationsHtml = certificationsList.length > 0 ? `
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Certifications & Awards' : 'Sertifikasi & Penghargaan'}</div>
      ${certificationsList.map(cert => `
        <div style="margin-bottom: 8px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
          <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0; padding: 0; border: none;">
            <tr>
              <td align="left" style="font-size: 11pt; font-weight: bold; font-family: ${fontFamily}; margin: 0; padding: 0; border: none; color: #333333;">${cert.name}</td>
              <td align="right" style="font-size: 10pt; font-weight: bold; color: #444444; font-family: ${fontFamily}; margin: 0; padding: 0; border: none;">${cert.period}</td>
            </tr>
          </table>
          <div style="font-size: 10pt; color: #444444; margin-top: 2px; margin-bottom: 0; font-family: ${fontFamily}; padding: 0;">${isEng ? 'Issuer' : 'Penerbit'}: ${cert.issuer}</div>
        </div>
      `).join('')}
    </div>
  ` : ''

  // Build Header HTML
  const headerHtml = photoUrl ? `
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border-bottom: 2px solid #333333; margin-bottom: 15px; padding-bottom: 10px; border: none; margin-top: 0;">
      <tr>
        <td align="left" valign="top" style="border: none; padding: 0; margin: 0;">
          <div style="font-size: 20pt; font-weight: bold; text-transform: uppercase; font-family: ${fontFamily}; margin: 0;">${fullName}</div>
          <div style="font-size: 10pt; color: #555555; margin-top: 5px; font-family: ${fontFamily}; margin-bottom: 0;">
            ${address} &nbsp;|&nbsp; ${phone} &nbsp;|&nbsp; ${email} ${linkedin ? `&nbsp;|&nbsp; ${linkedin}` : ''}
          </div>
        </td>
        <td align="right" valign="top" width="120" style="padding-left: 15px; border: none; margin: 0;">
          <img src="${photoUrl}" width="113" height="151" style="border: 1px solid #cccccc; border-radius: 4px; margin: 0; display: block;" alt="Foto Profil" />
        </td>
      </tr>
    </table>
  ` : `
    <div style="text-align: center; border-bottom: 2px solid #333333; margin-bottom: 15px; padding-bottom: 10px; width: 100%; margin-top: 0; padding-top: 0;">
      <div style="font-size: 20pt; font-weight: bold; text-transform: uppercase; font-family: ${fontFamily}; margin: 0; text-align: center;">${fullName}</div>
      <div style="font-size: 10pt; color: #555555; margin-top: 5px; font-family: ${fontFamily}; text-align: center; margin-bottom: 0;">
        ${address} &nbsp;|&nbsp; ${phone} &nbsp;|&nbsp; ${email} ${linkedin ? `&nbsp;|&nbsp; ${linkedin}` : ''}
      </div>
    </div>
  `

  // Build full MHTML Document string
  const mhtml = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <title>CV - ${fullName}</title>
  <!--[if gte mso 9]>
  <xml>
    <w:WordDocument>
      <w:View>Print</w:View>
      <w:Zoom>100</w:Zoom>
      <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
  </xml>
  <![endif]-->
  <style>
    @page {
      size: 8.5in 11in;
      margin: 0.79in 0.79in 0.79in 0.79in;
      mso-header-margin: 0.5in;
      mso-footer-margin: 0.5in;
      mso-paper-source: 0;
    }
    body {
      font-family: ${fontFamily.split(',')[0].trim()};
      font-size: 11pt;
      line-height: 1.4;
      color: #333333;
      margin: 0;
      padding: 0;
    }
    div, p, table, tr, td, ul, li {
      margin: 0;
      padding: 0;
    }
    h1, h2, h3, h4, h5, h6 {
      font-family: ${fontFamily.split(',')[0].trim()};
      margin: 0;
      padding: 0;
    }
  </style>
</head>
<body>
  <div style="width: 100%;">
    <!-- Header -->
    ${headerHtml}

    <!-- Ringkasan Profesional -->
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Professional Summary' : 'Ringkasan Profesional'}</div>
      <div style="font-size: 10.5pt; text-align: justify; line-height: 1.4; font-family: ${fontFamily}; margin: 0; padding: 0;">${aboutMe}</div>
    </div>
 
    <!-- Pendidikan -->
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Education' : 'Pendidikan'}</div>
      ${educationHtml}
    </div>
 
    <!-- Pengalaman Kerja -->
    ${experienceHtml}
 
    <!-- Pengalaman Organisasi -->
    ${organizationHtml}
 
    <!-- Sertifikasi -->
    ${certificationsHtml}
 
    <!-- Keahlian -->
    <div style="margin-bottom: 15px; font-family: ${fontFamily}; margin-top: 0; padding: 0;">
      <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #555555; margin-bottom: 8px; padding-bottom: 2px; font-family: ${fontFamily}; margin-top: 0;">${isEng ? 'Skills' : 'Keahlian (Skills)'}</div>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 10.5pt; line-height: 1.4; font-family: ${fontFamily}; border: none; margin: 0; padding: 0;">
        <tr>
          <td width="120" valign="top" style="font-weight: bold; font-family: ${fontFamily}; padding: 0; border: none; margin: 0;">Hard Skills:</td>
          <td valign="top" style="font-family: ${fontFamily}; padding: 0; border: none; margin: 0;">${hardSkills.join(', ')}</td>
        </tr>
        <tr style="height: 6px; border: none;"><td colspan="2" style="border: none; padding: 0; margin: 0;"></td></tr>
        <tr>
          <td width="120" valign="top" style="font-weight: bold; font-family: ${fontFamily}; padding: 0; border: none; margin: 0;">Soft Skills:</td>
          <td valign="top" style="font-family: ${fontFamily}; padding: 0; border: none; margin: 0;">${softSkills.join(', ')}</td>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>
  `

  const blob = new Blob(['\ufeff' + mhtml], { type: 'application/msword;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `CV_${fullName.replace(/\s+/g, '_')}.doc`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="container dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
      <div>
        <h1 class="page-title">Dashboard Saya</h1>
        <p class="text-muted">Kelola data pemesanan CV ATS dan cetak dokumen Anda.</p>
      </div>
      <router-link :to="{ name: 'CVBuilder' }" class="btn btn-primary">
        + Buat CV Baru
      </router-link>
    </div>

    <!-- Error message -->
    <div v-if="error" class="alert-danger">{{ error }}</div>

    <!-- Loading -->
    <div v-if="isLoading" class="loader">Memuat data dashboard...</div>

    <!-- Empty State -->
    <div v-else-if="cvRequests.length === 0" class="empty-state glass-card">
      <div class="empty-icon">📄</div>
      <h3>Belum Ada CV yang Dibuat</h3>
      <p>Anda belum memiliki riwayat pembuatan CV. Mulai buat CV ATS profesional pertama Anda sekarang!</p>
      <router-link :to="{ name: 'CVBuilder' }" class="btn btn-primary">Buat CV ATS Sekarang</router-link>
    </div>

    <!-- CV Requests Table -->
    <div v-else class="cv-table-wrapper glass-card">
      <table class="cv-table">
        <thead>
          <tr>
            <th>ID Request</th>
            <th>Nama Pelamar</th>
            <th>Pilihan Paket</th>
            <th>Harga</th>
            <th>Tanggal Pembuatan</th>
            <th>Status Pembayaran</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="req in cvRequests" :key="req.id">
            <td><strong>#{{ req.id }}</strong></td>
            <td>{{ req.full_name }}</td>
            <td>{{ req.package_name }}</td>
            <td>Rp {{ parseFloat(req.price).toLocaleString('id-ID') }}</td>
            <td>{{ new Date(req.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</td>
            <td>
              <span class="badge" :class="req.payment_status === 'verified' ? 'badge-verified' : 'badge-pending'">
                {{ req.payment_status === 'verified' ? 'Lunas / Aktif' : 'Menunggu Verifikasi' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <!-- If verified, user can preview and print -->
                <button 
                  v-if="req.payment_status === 'verified'" 
                  @click="previewCV(req.id)" 
                  class="btn btn-secondary btn-table"
                >
                  👁️ Lihat & Cetak CV
                </button>
                <!-- If pending, user needs to click WhatsApp for verify -->
                <button 
                  v-else 
                  @click="openPaymentModal(req)" 
                  class="btn btn-outline btn-table"
                >
                  💬 Verifikasi Bayar
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAYMENT MODAL -->
    <div v-if="showPaymentModal" class="modal-overlay">
      <div class="modal-card glass-card">
        <div class="modal-header">
          <h3>Konfirmasi Pembayaran</h3>
          <button @click="closePaymentModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" v-if="activeRequest">
          <p class="modal-desc">
            Untuk mengunduh dan mencetak CV, lakukan pembayaran sebesar 
            <strong>Rp {{ parseFloat(activeRequest.price).toLocaleString('id-ID') }}</strong> ke salah satu rekening di bawah ini:
          </p>
          <div class="bank-accounts">
            <div class="bank-item">
              <span class="bank-name">🏦 Bank BCA</span>
              <span class="bank-number">1234567890</span>
              <span class="bank-owner">a.n. CV KUBERKAH INDONESIA</span>
            </div>
            <div class="bank-item">
              <span class="bank-name">📱 Mandiri</span>
              <span class="bank-number">0987654321</span>
              <span class="bank-owner">a.n. CV KUBERKAH INDONESIA</span>
            </div>
          </div>
          
          <div class="wa-action-container">
            <p>Setelah melakukan transfer, silakan klik tombol di bawah untuk verifikasi otomatis via WhatsApp Admin:</p>
            <button @click="openWhatsApp" class="btn btn-secondary wa-btn">
              💬 Verifikasi Via WhatsApp
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- CV DETAILS PREVIEW MODAL -->
    <div v-if="showPreviewModal && selectedCVDetails" class="modal-overlay">
      <!-- Exclude header/close button from Print output -->
      <div class="modal-card preview-modal-card">
        <div class="modal-header no-print" style="display: flex; justify-content: space-between; align-items: center;">
          <h3>{{ translateToEnglish ? 'Official ATS CV Preview' : 'Pratinjau CV ATS Resmi' }}</h3>
          <div class="header-actions" style="display: flex; gap: 0.5rem; align-items: center;">
            <!-- Language Toggle Button -->
            <button class="btn btn-outline btn-sm lang-toggle-btn" @click="translateToEnglish = !translateToEnglish" style="background: rgba(99, 102, 241, 0.1); border-color: var(--primary); color: var(--primary); font-weight: bold;">
              🌐 {{ translateToEnglish ? 'Bahasa Indonesia' : 'English Mode' }}
            </button>
            <button @click="printCV" class="btn btn-primary btn-sm">🖨️ {{ translateToEnglish ? 'Print / Save PDF' : 'Cetak / Simpan PDF' }}</button>
            <button @click="downloadWord" class="btn btn-secondary btn-sm">📝 {{ translateToEnglish ? 'Save as Word' : 'Simpan File Word' }}</button>
            <button @click="showPreviewModal = false" class="close-btn">&times;</button>
          </div>
        </div>
        <div class="modal-body preview-modal-body">
          <!-- Tab selector in Dashboard view -->
          <div class="preview-tabs no-print" v-if="selectedCVDetails.cover_letter">
            <button class="preview-tab-btn" :class="{ 'active': activePreviewTab === 'cv' }" @click="activePreviewTab = 'cv'">
              📄 {{ translateToEnglish ? 'ATS CV Friendly' : 'CV ATS Friendly' }}
            </button>
            <button class="preview-tab-btn" :class="{ 'active': activePreviewTab === 'cover_letter' }" @click="activePreviewTab = 'cover_letter'">
              ✉️ {{ translateToEnglish ? 'Cover Letter' : 'Surat Lamaran Kerja' }}
            </button>
          </div>

          <p class="print-instruction no-print">
            💡 <strong>Tips Cetak:</strong> Di halaman print browser Anda, centang pilihan <strong>"Background graphics"</strong> dan atur margin ke <strong>"None"</strong> atau <strong>"Default"</strong> untuk hasil terbaik. Pilih tujuan ke <strong>"Save as PDF"</strong> untuk mengunduh dokumen.
          </p>
          
          <!-- PRINT AREA -->
          <div id="ats-cv-print-area" class="cv-preview-container">
            <!-- CV PAPER -->
            <div v-if="activePreviewTab === 'cv' || !selectedCVDetails.cover_letter" class="cv-paper" :style="{ fontFamily: selectedCVDetails.font_family || 'Times New Roman, serif' }">
              <!-- Header -->
              <div :class="selectedCVDetails.photo_url ? 'cv-header-photo' : 'cv-header'">
                <div class="cv-header-text">
                  <div class="cv-name">{{ selectedCVDetails.full_name }}</div>
                  <div class="cv-contact">
                    <span>{{ selectedCVDetails.address }}</span>
                    <span>{{ selectedCVDetails.phone }}</span>
                    <span>{{ selectedCVDetails.email }}</span>
                    <span v-if="selectedCVDetails.linkedin">{{ selectedCVDetails.linkedin }}</span>
                  </div>
                </div>
                <div v-if="selectedCVDetails.photo_url" class="cv-photo-wrapper">
                  <img :src="selectedCVDetails.photo_url" class="cv-photo-img" alt="Foto Profil" />
                </div>
              </div>

              <!-- Tentang Saya -->
              <div class="cv-section">
                <div class="cv-section-title">{{ translateToEnglish ? 'Professional Summary' : 'Ringkasan Profesional' }}</div>
                <div class="cv-section-content">
                  <p v-if="translateToEnglish && selectedCVDetails.about_me_answers">
                    <span v-if="selectedCVDetails.about_me_answers.lulusan">I am a graduate of {{ selectedCVDetails.about_me_answers.lulusan }}. </span>
                    <span v-if="selectedCVDetails.about_me_answers.karir">I have a strong career interest in the field of {{ selectedCVDetails.about_me_answers.karir }}. </span>
                    <span v-if="selectedCVDetails.about_me_answers.pengalaman">With a background in {{ selectedCVDetails.about_me_answers.pengalaman }}, </span>
                    <span v-if="selectedCVDetails.about_me_answers.skill">and expertise in {{ selectedCVDetails.about_me_answers.skill }}, </span>
                    <span v-if="selectedCVDetails.about_me_answers.meyakinkan">I am highly committed to delivering maximum results because {{ selectedCVDetails.about_me_answers.meyakinkan }}.</span>
                  </p>
                  <p v-else>{{ selectedCVDetails.about_me }}</p>
                </div>
              </div>

              <!-- Pendidikan -->
              <div class="cv-section">
                <div class="cv-section-title">{{ translateToEnglish ? 'Education' : 'Pendidikan' }}</div>
                <div class="cv-section-content">
                  <div v-for="(edu, idx) in selectedCVDetails.education" :key="idx" class="cv-item">
                    <div class="cv-item-header">
                      <span>{{ edu.school }}</span>
                      <span>{{ edu.period }}</span>
                    </div>
                    <div class="cv-item-sub">
                      <span>{{ translateToEnglish ? 'Major' : 'Jurusan' }}: {{ edu.major }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pengalaman Kerja -->
              <div class="cv-section" v-if="selectedCVDetails.experience && selectedCVDetails.experience.length > 0">
                <div class="cv-section-title">{{ translateToEnglish ? 'Work Experience' : 'Pengalaman Kerja' }}</div>
                <div class="cv-section-content">
                  <div v-for="(exp, idx) in selectedCVDetails.experience" :key="idx" class="cv-item">
                    <div class="cv-item-header">
                      <span>{{ exp.company }}</span>
                      <span>{{ exp.period }}</span>
                    </div>
                    <div class="cv-item-sub">
                      <span>Role: {{ exp.role }}</span>
                    </div>
                    <ul class="cv-item-bullets">
                      <li v-for="(bullet, bidx) in exp.jobdesk.split('\n')" :key="bidx">
                        <span v-if="bullet.trim()">{{ bullet }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Pengalaman Organisasi -->
              <div class="cv-section" v-if="selectedCVDetails.organization && selectedCVDetails.organization.length > 0">
                <div class="cv-section-title">{{ translateToEnglish ? 'Organizational Experience' : 'Pengalaman Organisasi' }}</div>
                <div class="cv-section-content">
                  <div v-for="(org, idx) in selectedCVDetails.organization" :key="idx" class="cv-item">
                    <div class="cv-item-header">
                      <span>{{ org.name }}</span>
                      <span>{{ org.period }}</span>
                    </div>
                    <div class="cv-item-sub">
                      <span>Role: {{ org.role }}</span>
                    </div>
                    <ul class="cv-item-bullets">
                      <li v-for="(bullet, bidx) in org.jobdesk.split('\n')" :key="bidx">
                        <span v-if="bullet.trim()">{{ bullet }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Sertifikat -->
              <div class="cv-section" v-if="selectedCVDetails.certifications && selectedCVDetails.certifications.length > 0">
                <div class="cv-section-title">{{ translateToEnglish ? 'Certifications & Awards' : 'Sertifikasi & Penghargaan' }}</div>
                <div class="cv-section-content">
                  <div v-for="(cert, idx) in selectedCVDetails.certifications" :key="idx" class="cv-item">
                    <div class="cv-item-header">
                      <span>{{ cert.name }}</span>
                      <span>{{ cert.period }}</span>
                    </div>
                    <div class="cv-item-sub">
                      <span>{{ translateToEnglish ? 'Issuer' : 'Penerbit' }}: {{ cert.issuer }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Skill -->
              <div class="cv-section">
                <div class="cv-section-title">{{ translateToEnglish ? 'Skills' : 'Keahlian (Skills)' }}</div>
                <div class="cv-section-content cv-skills-grid">
                  <div class="cv-skills-title">Hard Skills:</div>
                  <div>{{ selectedCVDetails.hard_skills.join(', ') }}</div>
                  
                  <div class="cv-skills-title">Soft Skills:</div>
                  <div>{{ selectedCVDetails.soft_skills.join(', ') }}</div>
                </div>
              </div>
            </div>

            <!-- COVER LETTER PAPER -->
            <div v-if="activePreviewTab === 'cover_letter' && selectedCVDetails.cover_letter" class="cv-paper" :style="{ fontFamily: selectedCVDetails.font_family || 'Times New Roman, serif' }">
              <!-- Centered Title -->
              <div style="text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; margin-bottom: 2rem;">
                {{ translateToEnglish ? 'COVER LETTER' : 'SURAT LAMARAN KERJA' }}
              </div>

              <!-- Date -->
              <div style="margin-bottom: 1.5rem; font-size: 10pt;">
                {{ selectedCVDetails.cover_letter_answers?.kota_pembuatan || '[Kota Pembuatan]' }}, {{ selectedCVDetails.cover_letter_answers?.tanggal_pembuatan || '[Tanggal]' }}
              </div>

              <!-- Hal -->
              <div style="font-weight: bold; margin-bottom: 1.5rem; font-size: 10pt;">
                {{ translateToEnglish ? 'Subject: Job Application –' : 'Hal: Lamaran Pekerjaan –' }} {{ selectedCVDetails.cover_letter_answers?.posisi_dilamar || '[Nama Posisi]' }}
              </div>

              <!-- Recipient -->
              <div style="margin-bottom: 1.5rem; line-height: 1.4; font-size: 10pt;">
                <strong>{{ translateToEnglish ? 'To:' : 'Yth.' }} {{ selectedCVDetails.cover_letter_answers?.penerima_surat || '[HRD Manager]' }}</strong><br />
                {{ selectedCVDetails.cover_letter_answers?.nama_perusahaan || '[Nama Perusahaan]' }}<br />
                {{ selectedCVDetails.cover_letter_answers?.alamat_perusahaan || '[Alamat]' }}
              </div>

              <!-- Salutation -->
              <div style="margin-bottom: 1rem; font-size: 10pt;">
                {{ translateToEnglish ? 'Dear Hiring Team,' : 'Dengan hormat,' }}
              </div>

              <!-- Content Paragraphs (with dynamic translation & biodata block inside) -->
              <div style="line-height: 1.6; text-align: justify; display: flex; flex-direction: column; gap: 1.25rem; font-size: 10pt;">
                <p style="margin: 0; text-indent: 36px;">
                  {{ 
                    translateToEnglish 
                      ? `Based on the job vacancy information published through ${selectedCVDetails.cover_letter_answers?.sumber_info || '[LinkedIn/Situs Resmi]'}, I intend to apply for the ${selectedCVDetails.cover_letter_answers?.posisi_dilamar || '[Posisi]'} position at ${selectedCVDetails.cover_letter_answers?.nama_perusahaan || '[Perusahaan]'}. My brief personal biodata details are as follows:` 
                      : `Berdasarkan informasi lowongan pekerjaan yang dipublikasikan melalui ${selectedCVDetails.cover_letter_answers?.sumber_info || '[LinkedIn/Situs Resmi]'}, saya bermaksud untuk mengajukan diri guna mengisi posisi ${selectedCVDetails.cover_letter_answers?.posisi_dilamar || '[Posisi]'} di ${selectedCVDetails.cover_letter_answers?.nama_perusahaan || '[Perusahaan]'}. Adapun kualifikasi biodata singkat saya adalah sebagai berikut:`
                  }}
                </p>

                <!-- Biodata table inside modal Cover Letter view -->
                <div style="margin-left: 36px; margin-top: 0.5rem; margin-bottom: 0.5rem;">
                  <table style="border: none; border-collapse: collapse; font-size: 10pt;">
                    <tr style="border: none;">
                      <td style="padding: 2px 8px 2px 0; font-weight: bold; border: none; width: 120px;">{{ translateToEnglish ? 'Name' : 'Nama' }}</td>
                      <td style="padding: 2px 8px; border: none;">:</td>
                      <td style="padding: 2px 8px; border: none;">{{ selectedCVDetails.full_name }}</td>
                    </tr>
                    <tr style="border: none;">
                      <td style="padding: 2px 8px 2px 0; font-weight: bold; border: none;">{{ translateToEnglish ? 'Address' : 'Alamat' }}</td>
                      <td style="padding: 2px 8px; border: none;">:</td>
                      <td style="padding: 2px 8px; border: none;">{{ selectedCVDetails.address }}</td>
                    </tr>
                    <tr style="border: none;">
                      <td style="padding: 2px 8px 2px 0; font-weight: bold; border: none;">{{ translateToEnglish ? 'Phone/WA' : 'No. HP/WhatsApp' }}</td>
                      <td style="padding: 2px 8px; border: none;">:</td>
                      <td style="padding: 2px 8px; border: none;">{{ selectedCVDetails.phone }}</td>
                    </tr>
                    <tr style="border: none;">
                      <td style="padding: 2px 8px 2px 0; font-weight: bold; border: none;">Email</td>
                      <td style="padding: 2px 8px; border: none;">:</td>
                      <td style="padding: 2px 8px; border: none;">{{ selectedCVDetails.email }}</td>
                    </tr>
                    <tr style="border: none;" v-if="selectedCVDetails.linkedin">
                      <td style="padding: 2px 8px 2px 0; font-weight: bold; border: none;">LinkedIn</td>
                      <td style="padding: 2px 8px; border: none;">:</td>
                      <td style="padding: 2px 8px; border: none;">{{ selectedCVDetails.linkedin }}</td>
                    </tr>
                  </table>
                </div>

                <p style="margin: 0; text-indent: 36px;">
                  {{
                    translateToEnglish
                      ? `I am a professional with ${selectedCVDetails.cover_letter_answers?.tahun_pengalaman || '[X]'} years of experience in the field of ${selectedCVDetails.cover_letter_answers?.bidang_keahlian || '[Keahlian]'}. In my previous role at ${selectedCVDetails.cover_letter_answers?.perusahaan_sebelumnya || '[Perusahaan Sebelumnya]'} as ${selectedCVDetails.cover_letter_answers?.jabatan_terakhir || '[Jabatan Terakhir]'}, I was fully responsible for ${selectedCVDetails.cover_letter_answers?.jobdesc_singkat || '[Jobdesk]'}. One of my greatest achievements was successfully ${selectedCVDetails.cover_letter_answers?.prestasi || '[Prestasi]'} by implementing data-driven strategies.`
                      : `Saya merupakan seorang profesional yang berpengalaman selama ${selectedCVDetails.cover_letter_answers?.tahun_pengalaman || '[X]'} tahun di bidang ${selectedCVDetails.cover_letter_answers?.bidang_keahlian || '[Keahlian]'}. Pada peran saya sebelumnya di ${selectedCVDetails.cover_letter_answers?.perusahaan_sebelumnya || '[Perusahaan Sebelumnya]'} sebagai ${selectedCVDetails.cover_letter_answers?.jabatan_terakhir || '[Jabatan Terakhir]'}, saya bertanggung jawab penuh dalam ${selectedCVDetails.cover_letter_answers?.jobdesc_singkat || '[Jobdesk]'}. Salah satu pencapaian terbesar saya adalah berhasil ${selectedCVDetails.cover_letter_answers?.prestasi || '[Prestasi]'} dengan menerapkan strategi berbasis data.`
                  }}
                </p>
                <p style="margin: 0; text-indent: 36px;">
                  {{
                    translateToEnglish
                      ? `In addition to practical experience, I have also mastered technical skills and the use of modern tools such as ${selectedCVDetails.cover_letter_answers?.tools_kunci || '[Tools Kunci]'}. I am known as an adaptive, goal-oriented individual with excellent communication and team collaboration skills, which are crucial to supporting the performance of the ${selectedCVDetails.cover_letter_answers?.posisi_dilamar || '[Posisi]'} position at your company.`
                      : `Selain pengalaman praktis, saya juga menguasai keahlian teknis serta penggunaan instrumen kerja modern seperti ${selectedCVDetails.cover_letter_answers?.tools_kunci || '[Tools Kunci]'}. Saya dikenal sebagai individu yang adaptif, berorientasi pada target, serta memiliki kemampuan komunikasi dan kolaborasi tim yang sangat baik, yang mana aspek-aspek tersebut sangat krusial untuk menunjang performa posisi ${selectedCVDetails.cover_letter_answers?.posisi_dilamar || '[Posisi]'} di perusahaan Bapak/Ibu.`
                  }}
                </p>
                <p style="margin: 0; text-indent: 36px;">
                  {{
                    translateToEnglish
                      ? `For further consideration, I have attached my latest Curriculum Vitae (CV) and other supporting documents. I hope to be given the opportunity to attend an interview, so that I can explain in more detail my qualifications, vision, and the tangible contribution I am ready to provide for ${selectedCVDetails.cover_letter_answers?.nama_perusahaan || '[Perusahaan]'}. Thank you for your time, attention, and the opportunity.`
                      : `Sebagai bahan pertimbangan lebih lanjut, bersama surat ini saya lampirkan berkas Curriculum Vitae (CV) terbaru beserta dokumen pendukung lainnya. Besar harapan saya untuk diberikan kesempatan menghadiri sesi wawancara, agar saya dapat memaparkan lebih mendalam mengenai kualifikasi, visi, dan bentuk kontribusi nyata yang siap saya berikan untuk ${selectedCVDetails.cover_letter_answers?.nama_perusahaan || '[Perusahaan]'}. Terima kasih atas waktu, perhatian, dan kesempatan yang Bapak/Ibu berikan.`
                  }}
                </p>
              </div>

              <!-- Closing Signature block (Rata Kanan) -->
              <div style="margin-top: 3rem; text-align: right; width: 100%; display: flex; justify-content: flex-end; font-size: 10pt; line-height: 1.4;">
                <div style="text-align: left; width: 220px;">
                  <p style="margin: 0 0 3.5rem 0;">{{ translateToEnglish ? 'Sincerely,' : 'Hormat saya,' }}</p>
                  <strong>{{ selectedCVDetails.full_name }}</strong>
                </div>
              </div>
              <div style="clear: both;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-container {
  padding-top: 3rem;
  padding-bottom: 5rem;
}
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
}
.page-title {
  font-size: 2.2rem;
  font-weight: 800;
}
.cv-table-wrapper {
  overflow-x: auto;
  padding: 0;
}
.cv-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.cv-table th, .cv-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.95rem;
}
.cv-table th {
  background-color: rgba(99, 102, 241, 0.03);
  font-weight: 600;
  color: var(--text-muted);
}
.cv-table tbody tr:last-child td {
  border-bottom: none;
}
.action-buttons {
  display: flex;
  gap: 0.5rem;
}
.btn-table {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  border-radius: var(--radius-sm);
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}
.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}
.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}
.empty-state p {
  color: var(--text-muted);
  max-width: 450px;
  margin: 0 auto 2rem;
}

/* Modals */
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
  overflow-y: auto;
}
.modal-card {
  width: 100%;
  max-width: 550px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  padding: 2.5rem;
}
.preview-modal-card {
  width: 100%;
  max-width: 900px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
}
.modal-header h3 {
  font-size: 1.4rem;
}
.close-btn {
  font-size: 2rem;
  background: transparent;
  border: none;
  cursor: pointer;
  line-height: 1;
}
.modal-desc {
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}
.bank-accounts {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}
.bank-item {
  background-color: var(--bg-main);
  padding: 1rem 1.25rem;
  border-radius: var(--radius-sm);
  border-left: 4px solid var(--primary);
  display: flex;
  flex-direction: column;
}
.bank-name {
  font-weight: 700;
  font-size: 0.95rem;
}
.bank-number {
  font-size: 1.2rem;
  font-weight: 800;
  letter-spacing: 1px;
  margin: 0.25rem 0;
  color: var(--primary);
}
.bank-owner {
  font-size: 0.85rem;
  color: var(--text-muted);
}
.wa-action-container {
  text-align: center;
  border-top: 1px solid var(--border-color);
  padding-top: 1.5rem;
}
.wa-btn {
  width: 100%;
  margin-top: 1rem;
}
.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.preview-modal-body {
  overflow-y: auto;
  padding-right: 10px;
}
.print-instruction {
  background-color: var(--primary-light);
  color: var(--primary);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  margin-bottom: 1.5rem;
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

/* Preview tabs styling in dashboard modal */
.preview-tabs {
  display: flex;
  gap: 0.5rem;
  background-color: var(--bg-main);
  border-radius: 8px;
  padding: 6px;
  margin-bottom: 1.5rem;
}
.preview-tab-btn {
  flex: 1;
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-muted);
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.preview-tab-btn:hover {
  background-color: rgba(99, 102, 241, 0.05);
  color: var(--primary);
}
.preview-tab-btn.active {
  background-color: white;
  color: var(--primary);
  box-shadow: var(--shadow-sm);
}
</style>
