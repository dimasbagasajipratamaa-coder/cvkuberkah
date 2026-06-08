<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', '127.0.0.1'); // using IP instead of localhost avoids slow DNS resolutions in macOS XAMPP
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cvkuberkah_db');

// Connect to Database using PDO
function getDBConnection($selectDb = true) {
    try {
        if ($selectDb) {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        } else {
            $conn = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        }
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }
}

// Helper to send JSON headers and handle CORS preflight
function sendJSONHeaders() {
    // Dynamically allow local development origin (Vite dev server)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json; charset=UTF-8");
    
    // Handle CORS preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

// Simple token verification helper
function verifyToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\d+)/i', $authHeader, $matches)) {
        $userId = intval($matches[1]);
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }
    }
    
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access. Please login first."]);
    exit;
}
