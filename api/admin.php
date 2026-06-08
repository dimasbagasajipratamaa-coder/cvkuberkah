<?php
require_once 'config.php';
sendJSONHeaders();

// 1. Verify User is Authenticated & Admin
$user = verifyToken();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Akses ditolak. Endpoint ini hanya untuk Admin."]);
    exit;
}

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'dashboard_stats') {
        try {
            // Count total users
            $userCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
            
            // Count total cv requests
            $requestCount = $db->query("SELECT COUNT(*) FROM cv_requests")->fetchColumn();
            
            // Count pending verifications
            $pendingCount = $db->query("SELECT COUNT(*) FROM cv_requests WHERE payment_status = 'pending'")->fetchColumn();
            
            // Calculate total revenue (verified requests only)
            $totalRevenue = $db->query("SELECT SUM(price) FROM cv_requests WHERE payment_status = 'verified'")->fetchColumn();
            $totalRevenue = $totalRevenue ? floatval($totalRevenue) : 0.0;
            
            // Monthly revenue simulation/fetch
            $monthlyRevenue = $db->query("SELECT SUM(price) FROM cv_requests WHERE payment_status = 'verified' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            $monthlyRevenue = $monthlyRevenue ? floatval($monthlyRevenue) : 0.0;

            echo json_encode([
                "stats" => [
                    "total_users" => intval($userCount),
                    "total_requests" => intval($requestCount),
                    "pending_requests" => intval($pendingCount),
                    "total_revenue" => $totalRevenue,
                    "monthly_revenue" => $monthlyRevenue
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal memuat statistik admin: " . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'list_requests') {
        try {
            $stmt = $db->query("
                SELECT r.id, r.user_id, r.full_name, r.email, r.phone, r.package_name, r.price, r.payment_status, r.created_at, u.name as customer_name 
                FROM cv_requests r 
                JOIN users u ON r.user_id = u.id 
                ORDER BY r.created_at DESC
            ");
            $requests = $stmt->fetchAll();
            echo json_encode($requests);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal memuat daftar request: " . $e->getMessage()]);
        }
        exit;
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'verify_payment') {
        $cvId = intval($input['cv_id'] ?? 0);
        $status = trim($input['status'] ?? 'verified'); // 'verified' or 'pending'
        
        if ($cvId <= 0 || !in_array($status, ['verified', 'pending'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID CV atau status tidak valid."]);
            exit;
        }
        
        try {
            $stmt = $db->prepare("UPDATE cv_requests SET payment_status = ? WHERE id = ?");
            $stmt->execute([$status, $cvId]);
            
            echo json_encode([
                "message" => "Status pembayaran berhasil diubah menjadi: " . $status,
                "cv_id" => $cvId,
                "payment_status" => $status
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal memperbarui status pembayaran: " . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_content') {
        $key = trim($input['key'] ?? '');
        $value = $input['value'] ?? null; // Can be array or string, will be JSON encoded
        
        if (empty($key) || $value === null) {
            http_response_code(400);
            echo json_encode(["error" => "Key dan Value wajib diisi."]);
            exit;
        }
        
        try {
            // UPSERT simulation in MySQL
            $stmt = $db->prepare("
                INSERT INTO content_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, json_encode($value)]);
            
            echo json_encode([
                "message" => "Konten {$key} berhasil diperbarui!",
                "key" => $key
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal memperbarui konten: " . $e->getMessage()]);
        }
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Endpoint admin tidak ditemukan."]);
