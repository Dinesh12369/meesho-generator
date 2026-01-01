<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['error' => 'Invalid request method'], 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    json_response(false, ['error' => 'Invalid JSON data'], 400);
}

$query = isset($data['query']) ? trim($data['query']) : '';
$offset = isset($data['offset']) ? (int)$data['offset'] : 0;
$size = isset($data['size']) ? (int)$data['size'] : 25;

if (empty($query) || strlen($query) < 2) {
    json_response(false, ['error' => 'Query must be at least 2 characters'], 400);
}

try {
    $meesho_session = get_meesho_session();
    
    if (!$meesho_session) {
        json_response(false, [
            'error' => 'No session available. Please login first.',
            'requires_login' => true
        ], 401);
    }
    
    $supplier_id = $meesho_session['supplier_id'];
    $identifier = $meesho_session['identifier'];
    
    // Meesho API endpoint
    $url = 'https://supplier.meesho.com/catalogingapi/api/catalog-upload/search-catalog';
    
    // Prepare payload
    $payload = [
        'query' => $query,
        'offset' => $offset,
        'size' => $size,
        'supplier_id' => (int)$supplier_id,
        'bulk_upload_enabled' => isset($data['bulk_upload_enabled']) ? $data['bulk_upload_enabled'] : false,
        'supplier_enabled' => isset($data['supplier_enabled']) ? $data['supplier_enabled'] : true,
        'identifier' => $identifier
    ];
    
    if (!function_exists('curl_init')) {
        json_response(false, [
            'error' => 'cURL not available on server',
            'requires_login' => false
        ], 500);
    }
    
    $ch = curl_init($url);
    
    // Build cookies string
    $cookies_string = '';
    if (isset($meesho_session['session']['cookies']) && is_array($meesho_session['session']['cookies'])) {
        $cookie_parts = [];
        foreach ($meesho_session['session']['cookies'] as $name => $value) {
            $cookie_parts[] = $name . '=' . $value;
        }
        $cookies_string = implode('; ', $cookie_parts);
    }
    
    // Prepare headers
    $headers = [
        'accept: application/json, text/plain, */*',
        'accept-language: en-US,en;q=0.9,hi;q=0.8',
        'browser-id: NnQgKyAyMzMgKyAxMXlsYzExeWs4bw==',
        'client-package-version: 1.0.28',
        'client-type: d-web',
        'content-type: application/json;charset=UTF-8',
        'dnt: 1',
        'identifier: ' . $identifier,
        'origin: https://supplier.meesho.com',
        'priority: u=1, i',
        'referer: https://supplier.meesho.com/panel/v3/new/cataloging/' . $identifier . '/catalogs/single/select-category',
        'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'supplier-id: ' . $supplier_id,
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    if (!empty($cookies_string)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies_string);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Log for debugging
    error_log("Meesho API - Query: $query, HTTP: $http_code, Response length: " . strlen($response));
    
    if ($curl_error) {
        error_log("cURL error: " . $curl_error);
        json_response(false, [
            'error' => 'Network error: ' . $curl_error,
            'requires_login' => false
        ], 500);
    }
    
    if ($http_code === 401 || $http_code === 403) {
        error_log("Authentication failed: " . $http_code);
        json_response(false, [
            'error' => 'Session expired. Please refresh your login.',
            'requires_login' => true
        ], $http_code);
    }
    
    if ($http_code !== 200) {
        error_log("HTTP error: " . $http_code);
        json_response(false, [
            'error' => 'API error (HTTP ' . $http_code . ')',
            'requires_login' => false
        ], $http_code);
    }
    
    if (empty($response) || ($response[0] !== '{' && $response[0] !== '[')) {
        error_log("Invalid response format from API");
        json_response(false, [
            'error' => 'Invalid API response format',
            'requires_login' => true
        ], 500);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        error_log("JSON decode error: " . json_last_error_msg());
        json_response(false, [
            'error' => 'Failed to parse API response',
            'requires_login' => false
        ], 500);
    }
    
    // Check for error in response
    if (isset($result['error']) || isset($result['message'])) {
        $error_msg = $result['error'] ?? $result['message'] ?? 'Unknown error';
        error_log("API returned error: " . $error_msg);
        json_response(false, [
            'error' => $error_msg,
            'requires_login' => true
        ], 400);
    }
    
    // SUCCESS - Return categories
    $categories = $result['categories'] ?? [];
    
    error_log("Success: Found " . count($categories) . " categories for query: " . $query);
    
    json_response(true, [
        'categories' => $categories,
        'total' => $result['total_results'] ?? count($categories),
        'query' => $query,
        'offset' => $offset,
        'size' => $size,
        'directMatch' => $result['directMatch'] ?? false,
        'is_direct_match' => $result['is_direct_match'] ?? false,
        'total_results' => $result['total_results'] ?? count($categories)
    ]);
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    json_response(false, [
        'error' => 'Server error: ' . $e->getMessage(),
        'requires_login' => false
    ], 500);
}
?>
