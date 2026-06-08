<?php
require_once 'config.php';
sendJSONHeaders();

// Verify user is logged in
$user = verifyToken();
$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cvId = $_GET['id'] ?? '';
    
    if (!empty($cvId)) {
        // Fetch specific CV details
        $stmt = $db->prepare("SELECT * FROM cv_requests WHERE id = ?");
        $stmt->execute([$cvId]);
        $cv = $stmt->fetch();
        
        if (!$cv) {
            http_response_code(404);
            echo json_encode(["error" => "CV tidak ditemukan."]);
            exit;
        }
        
        // Ensure user owns this CV or is admin
        if ($cv['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["error" => "Anda tidak memiliki akses ke dokumen ini."]);
            exit;
        }
        
        // Decode JSON structures for frontend convenience
        $cv['about_me_answers'] = json_decode($cv['about_me_answers'], true);
        $cv['education'] = json_decode($cv['education'], true);
        $cv['organization'] = json_decode($cv['organization'], true);
        $cv['experience'] = json_decode($cv['experience'], true);
        $cv['certifications'] = json_decode($cv['certifications'], true);
        $cv['soft_skills'] = json_decode($cv['soft_skills'], true);
        $cv['hard_skills'] = json_decode($cv['hard_skills'], true);
        
        echo json_encode($cv);
        exit;
    } else {
        // Fetch list of user's CV requests
        $stmt = $db->prepare("SELECT id, full_name, package_name, price, payment_status, created_at FROM cv_requests WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $cvList = $stmt->fetchAll();
        
        echo json_encode($cvList);
        exit;
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullName = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $linkedin = trim($input['linkedin'] ?? '');
    $packageName = trim($input['package_name'] ?? 'Paket Basic CV ATS');
    $price = floatval($input['price'] ?? 25000);
    $photoUrl = $input['photo_url'] ?? ''; // Expecting base64 representation or URL
    $fontFamily = trim($input['font_family'] ?? 'Times New Roman, serif');
    
    // Read the 5 points for "Tentang Saya"
    $aboutMeAnswers = $input['about_me_answers'] ?? [];
    $lulusan = trim($aboutMeAnswers['lulusan'] ?? '');
    $karir = trim($aboutMeAnswers['karir'] ?? '');
    $pengalaman = trim($aboutMeAnswers['pengalaman'] ?? '');
    $skill = trim($aboutMeAnswers['skill'] ?? '');
    $meyakinkan = trim($aboutMeAnswers['meyakinkan'] ?? '');
    
    // Compile "Tentang Saya" if not manually provided
    $aboutMe = trim($input['about_me'] ?? '');
    if (empty($aboutMe)) {
        $aboutMe = "Saya adalah seorang lulusan {$lulusan}. Saya memiliki ketertarikan karir yang tinggi di bidang {$karir}. Dengan bekal pengalaman di mana saya telah {$pengalaman}, serta keahlian di bidang {$skill}, saya siap memberikan kontribusi terbaik bagi perusahaan. Hal yang meyakinkan mengenai saya adalah {$meyakinkan}.";
    }
    
    // Arrays representing lists (Education, Experience, etc.)
    $education = $input['education'] ?? [];
    $organization = $input['organization'] ?? [];
    $experience = $input['experience'] ?? [];
    $certifications = $input['certifications'] ?? [];
    $softSkills = $input['soft_skills'] ?? [];
    $hardSkills = $input['hard_skills'] ?? [];
    
    // Validation
    if (empty($fullName) || empty($email) || empty($phone) || empty($address) || empty($education) || empty($softSkills) || empty($hardSkills)) {
        http_response_code(400);
        echo json_encode(["error" => "Harap lengkapi semua field utama (Nama, Email, No.HP, Alamat, Pendidikan, dan Skill)."]);
        exit;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO cv_requests (
            user_id, full_name, email, phone, address, linkedin, 
            about_me, about_me_answers, education, organization, 
            experience, certifications, soft_skills, hard_skills, 
            photo_url, font_family, payment_status, package_name, price
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
        
        $stmt->execute([
            $user['id'],
            $fullName,
            $email,
            $phone,
            $address,
            $linkedin ?: null,
            $aboutMe,
            json_encode($aboutMeAnswers),
            json_encode($education),
            json_encode($organization),
            json_encode($experience),
            json_encode($certifications),
            json_encode($softSkills),
            json_encode($hardSkills),
            $photoUrl ?: null,
            $fontFamily,
            $packageName,
            $price
        ]);
        
        $cvId = $db->lastInsertId();
        
        echo json_encode([
            "message" => "CV berhasil dibuat dan disimpan!",
            "cv_id" => $cvId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal membuat CV: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Metode tidak diizinkan."]);
