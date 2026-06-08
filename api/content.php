<?php
require_once 'config.php';
sendJSONHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM content_settings");
        $settingsRaw = $stmt->fetchAll();
        
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = json_decode($row['setting_value'], true);
        }
        
        // Ensure default structure if setup.php hasn't run yet
        $response = [
            "packages" => $settings['packages'] ?? [],
            "testimonials" => $settings['testimonials'] ?? [],
            "templates" => $settings['templates'] ?? [],
            "company_profile" => $settings['company_profile'] ?? [
                "name" => "CV Kuberkah",
                "tagline" => "Partner Karir Terpercaya",
                "whatsapp" => "6289656111199"
            ]
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal memuat konten: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Metode tidak diizinkan."]);
