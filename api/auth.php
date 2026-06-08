<?php
require_once 'config.php';
sendJSONHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["error" => "Semua field (Nama, Email, Password) wajib diisi."]);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["error" => "Format email tidak valid."]);
            exit;
        }
        
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "Password minimal 6 karakter."]);
            exit;
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "Email sudah terdaftar. Silakan gunakan email lain atau login."]);
            exit;
        }
        
        // Insert user
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
            $insert->execute([$name, $email, $hashedPassword]);
            
            $userId = $db->lastInsertId();
            
            echo json_encode([
                "message" => "Registrasi berhasil!",
                "user" => [
                    "id" => $userId,
                    "name" => $name,
                    "email" => $email,
                    "role" => "customer",
                    "token" => $userId // Simulating a simple token using user ID
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Registrasi gagal: " . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["error" => "Email dan Password wajib diisi."]);
            exit;
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(["error" => "Email atau Password salah."]);
            exit;
        }
        
        echo json_encode([
            "message" => "Login berhasil!",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role'],
                "token" => $user['id'] // Simple simulation of a Bearer token
            ]
        ]);
        exit;
    }

    if ($action === 'google_login') {
        $email = trim($input['email'] ?? '');
        $name = trim($input['name'] ?? '');
        
        if (empty($email) || empty($name)) {
            http_response_code(400);
            echo json_encode(["error" => "Email dan Nama dari Google wajib diisi."]);
            exit;
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Register a new customer user from Google OAuth
            try {
                $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $insert = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
                $insert->execute([$name, $email, $randomPassword]);
                
                $userId = $db->lastInsertId();
                $user = [
                    "id" => $userId,
                    "name" => $name,
                    "email" => $email,
                    "role" => "customer"
                ];
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Login Google gagal: " . $e->getMessage()]);
                exit;
            }
        }
        
        echo json_encode([
            "message" => "Login Google berhasil!",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role'],
                "token" => $user['id']
            ]
        ]);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'check') {
        $user = verifyToken();
        echo json_encode([
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Endpoint tidak ditemukan."]);
