<?php
require_once 'config.php';
sendJSONHeaders();

try {
    // 1. Connect to MySQL Server (without selecting DB)
    $conn = getDBConnection(false);

    // 2. Create Database
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 3. Connect to created Database
    $db = getDBConnection(true);

    // 4. Create Tables
    
    // Users Table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // CV Requests Table
    $db->exec("CREATE TABLE IF NOT EXISTS cv_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        linkedin VARCHAR(150) DEFAULT NULL,
        about_me TEXT NOT NULL,
        about_me_answers TEXT NOT NULL,
        education TEXT NOT NULL,
        organization TEXT DEFAULT NULL,
        experience TEXT DEFAULT NULL,
        certifications TEXT DEFAULT NULL,
        soft_skills TEXT NOT NULL,
        hard_skills TEXT NOT NULL,
        photo_url LONGTEXT DEFAULT NULL,
        font_family VARCHAR(100) DEFAULT 'Times New Roman, serif',
        cover_letter TEXT DEFAULT NULL,
        cover_letter_answers TEXT DEFAULT NULL,
        payment_status ENUM('pending', 'verified') DEFAULT 'pending',
        package_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Content Settings Table
    $db->exec("CREATE TABLE IF NOT EXISTS content_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value LONGTEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table migrations: Add font_family if it doesn't exist yet
    try {
        $db->exec("ALTER TABLE cv_requests ADD COLUMN font_family VARCHAR(100) DEFAULT 'Times New Roman, serif' AFTER photo_url");
    } catch (PDOException $e) {
        // Column already exists, safe to ignore
    }

    // Table migrations: Add cover_letter and cover_letter_answers if they don't exist yet
    try {
        $db->exec("ALTER TABLE cv_requests ADD COLUMN cover_letter TEXT DEFAULT NULL AFTER font_family");
    } catch (PDOException $e) {
        // Column already exists, safe to ignore
    }
    try {
        $db->exec("ALTER TABLE cv_requests ADD COLUMN cover_letter_answers TEXT DEFAULT NULL AFTER cover_letter");
    } catch (PDOException $e) {
        // Column already exists, safe to ignore
    }

    // 5. Seed Initial Data

    // Admin User Seed (if not exists)
    $adminEmail = 'admin@cvkuberkah.com';
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if (!$stmt->fetch()) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $insertAdmin->execute(['Admin Kuberkah', $adminEmail, $adminPassword, 'admin']);
    }

    // Default Customer User Seed (if not exists, for quick testing)
    $customerEmail = 'customer@gmail.com';
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$customerEmail]);
    if (!$stmt->fetch()) {
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $insertCust = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $insertCust->execute(['Ahmad Customer', $customerEmail, $customerPassword, 'customer']);
    }

    // Content settings seed helper
    function seedSetting($db, $key, $value) {
        $stmt = $db->prepare("SELECT setting_key FROM content_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if (!$stmt->fetch()) {
            $insert = $db->prepare("INSERT INTO content_settings (setting_key, setting_value) VALUES (?, ?)");
            $insert->execute([$key, json_encode($value)]);
        } else {
            $update = $db->prepare("UPDATE content_settings SET setting_value = ? WHERE setting_key = ?");
            $update->execute([json_encode($value), $key]);
        }
    }

    // Seed Packages
    $packages = [
        [
            "id" => "basic",
            "name" => "Paket Basic CV ATS",
            "price" => 25000,
            "recommended" => false,
            "image_url" => "",
            "features" => [
                "Desain CV ATS-Friendly Standard",
                "Format PDF Berkualitas Tinggi",
                "Auto-generasi Deskripsi Tentang Saya",
                "1x Verifikasi Download"
            ]
        ],
        [
            "id" => "premium",
            "name" => "Paket Premium CV ATS",
            "price" => 45000,
            "recommended" => true,
            "image_url" => "",
            "features" => [
                "Desain CV ATS-Friendly Premium",
                "Format PDF & Panduan Penulisan",
                "Auto-generasi Deskripsi Tentang Saya",
                "Prioritas Review Admin (Fast Track)",
                "3x Revisi Manual oleh Admin via WA"
            ]
        ],
        [
            "id" => "bundle",
            "name" => "Paket Bundle CV & Surat Lamaran",
            "price" => 60000,
            "recommended" => false,
            "image_url" => "",
            "features" => [
                "Desain CV ATS-Friendly Premium",
                "Template Surat Lamaran Kerja ATS",
                "Format PDF + File Editor",
                "Review Portfolio Singkat",
                "Revisi Unlimited via WA",
                "Prioritas Utama Verifikasi"
            ]
        ]
    ];
    seedSetting($db, 'packages', $packages);

    // Seed Testimonials
    $testimonials = [
        [
            "id" => 1,
            "name" => "Rizky Pratama",
            "role" => "Fresh Graduate S1 Teknik",
            "message" => "Gokil! Baru submit CV buatan CV Kuberkah kemarin lusa, hari ini langsung dipanggil interview. Template ATS-nya beneran tembus screening HRD!",
            "avatar" => "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80"
        ],
        [
            "id" => 2,
            "name" => "Sarah Fitriani",
            "role" => "Admin Specialist",
            "message" => "Awalnya bingung nulis 'Tentang Saya'. Untung ada fitur panduan 5 poin di form CV Kuberkah, hasilnya langsung otomatis jadi profesional banget!",
            "avatar" => "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80"
        ],
        [
            "id" => 3,
            "name" => "Dicky Hermawan",
            "role" => "Junior Web Developer",
            "message" => "Proses pembayaran via WhatsApp adminnya cepet banget di-verify. Hasil download PDF-nya rapi, teks bisa dicopy, beneran standar ATS modern.",
            "avatar" => "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80"
        ]
    ];
    seedSetting($db, 'testimonials', $testimonials);

    // Seed Templates Examples (CV ATS & Surat Lamaran)
    $templates = [
        [
            "id" => "ats_classic",
            "name" => "Classic Charcoal ATS",
            "type" => "CV ATS",
            "image" => "https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=500&auto=format&fit=crop&q=60",
            "description" => "Desain ATS klasik berkolom tunggal dengan penekanan hirarki teks tebal (charcoal). Sangat ideal untuk bidang keuangan, hukum, dan manajemen."
        ],
        [
            "id" => "ats_modern",
            "name" => "Minimalist Slate ATS",
            "type" => "CV ATS",
            "image" => "https://images.unsplash.com/photo-1586282301770-852d0e722513?w=500&auto=format&fit=crop&q=60",
            "description" => "Desain minimalis dengan aksen warna slate abu-abu modern. Sempurna untuk lulusan IT, kreatif, pemasaran digital, dan startup."
        ],
        [
            "id" => "cover_letter_formal",
            "name" => "Modern Cover Letter Template",
            "type" => "Surat Lamaran",
            "image" => "https://images.unsplash.com/photo-1586281380117-5a60ae2010cd?w=500&auto=format&fit=crop&q=60",
            "description" => "Format surat lamaran kerja terstruktur yang ringkas, formal, dan relevan dengan standar rekrutmen terbaru."
        ]
    ];
    seedSetting($db, 'templates', $templates);

    // Seed Company Profile Info
    $companyProfile = [
        "name" => "CV Kuberkah",
        "tagline" => "Partner Karir Terpercaya Anda",
        "description" => "CV Kuberkah hadir untuk membantu pencari kerja membuat CV ATS-friendly dan Surat Lamaran Kerja secara praktis dan profesional. Sistem pembentukan deskripsi otomatis dan validasi manual memastikan CV Anda siap bersaing di pasar kerja modern.",
        "whatsapp" => "6285143606723", // Admin WhatsApp target
        "email" => "support@cvkuberkah.com",
        "address" => "Gedung Karir Indonesia, Lt. 3, Jl. Margonda Raya No. 12, Depok, Jawa Barat",
        "instagram" => "@cv.kuberkah",
        "facebook" => "CV Kuberkah Official"
    ];
    seedSetting($db, 'company_profile', $companyProfile);

    echo json_encode([
        "status" => "success",
        "message" => "Database and tables successfully setup!",
        "seeded_data" => [
            "admin_user" => $adminEmail,
            "test_customer" => $customerEmail,
            "packages_count" => count($packages),
            "testimonials_count" => count($testimonials),
            "templates_count" => count($templates),
            "company_profile" => "configured"
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database Setup failed: " . $e->getMessage()
    ]);
}
