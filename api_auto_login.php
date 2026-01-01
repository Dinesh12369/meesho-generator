<?php
// ============================================================================
// MEESHO AUTO-LOGIN - GET FRESH COOKIES
// ============================================================================

require_once 'config.php';

header('Content-Type: application/json');

// Your Meesho credentials
$email = 'admin@gmail.com';
$password = 'Meesho@123';
$device_id = 'admin@gmail.com';
$instance = 'HZwYqgPfu0okIqI9ZrYOw';

try {
    // Meesho login endpoint
    $url = 'https://supplier.meesho.com/v3/api/user/v2-login';
    
    // Prepare login payload
    $payload = [
        'password' => $password,
        'device_id' => $device_id,
        'instance' => $instance,
        'email' => $email
    ];
    
    // Your current cookies from the curl
    $current_cookies = [
        's_b' => 'true',
        'download_button' => 'true',
        'excel_updated' => 'true',
        'browser_id' => 'NnQgKyAyMzMgKyAxMXlsYzExeWs4bw==',
        '_fbp' => 'fb.1.1766152741420.721181334106590622',
        '_ga' => 'GA1.1.1532202540.1766146315',
        'enable_eid_creation' => 'true',
        '_gcl_au' => '1.1.1727614622.1766146314.1792595275.1767023842.1767023862'
    ];
    
    $cookie_string = '';
    foreach ($current_cookies as $name => $value) {
        $cookie_string .= $name . '=' . $value . '; ';
    }
    $cookie_string = rtrim($cookie_string, '; ');
    
    // Prepare headers matching your curl
    $headers = [
        'accept: application/json, text/plain, */*',
        'accept-language: en-US,en;q=0.9,hi;q=0.8',
        'browser-id: NnQgKyAyMzMgKyAxMXlsYzExeWs4bw==',
        'client-package-version: 1.0.28',
        'client-type: d-web',
        'content-type: application/json;charset=UTF-8',
        'dnt: 1',
        'identifier: login',
        'origin: https://supplier.meesho.com',
        'priority: u=1, i',
        'referer: https://supplier.meesho.com/panel/v3/new/root/login',
        'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'
    ];
    
    if (!function_exists('curl_init')) {
        json_response(false, ['error' => 'cURL not available'], 500);
    }
    
    // Initialize cURL
    $ch = curl_init($url);
    
    // Enable header capture
    $response_headers = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) return $len;
        
        $name = strtolower(trim($header[0]));
        $value = trim($header[1]);
        $response_headers[$name][] = $value;
        
        return $len;
    });
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIE => $cookie_string,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => false
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($curl_error) {
        json_response(false, [
            'error' => 'cURL error: ' . $curl_error,
            'step' => 'network_error'
        ], 500);
    }
    
    if ($http_code !== 200) {
        json_response(false, [
            'error' => 'Login failed with HTTP ' . $http_code,
            'step' => 'http_error',
            'response' => substr($response, 0, 500)
        ], $http_code);
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!$result) {
        json_response(false, [
            'error' => 'Invalid JSON response',
            'step' => 'parse_error'
        ], 500);
    }
    
    // Extract cookies from Set-Cookie headers
    $new_cookies = [];
    if (isset($response_headers['set-cookie'])) {
        foreach ($response_headers['set-cookie'] as $cookie_header) {
            // Parse cookie: "name=value; Path=/; ..."
            $parts = explode(';', $cookie_header);
            $cookie_pair = explode('=', $parts[0], 2);
            if (count($cookie_pair) == 2) {
                $name = trim($cookie_pair[0]);
                $value = trim($cookie_pair[1]);
                $new_cookies[$name] = $value;
            }
        }
    }
    
    // Check if login was successful
    if (isset($result['supplier_id']) && isset($result['identifier'])) {
        // Success! Update session
        $supplier_id = $result['supplier_id'];
        $identifier = $result['identifier'];
        
        $new_session = [
            'email' => $email,
            'password' => $password,
            'supplier_id' => (string)$supplier_id,
            'identifier' => $identifier,
            'session' => [
                'response' => $result,
                'cookies' => $new_cookies,
                'headers' => []
            ],
            'logged_in_at' => date('c')
        ];
        
        // Update config session
        set_meesho_session($new_session);
        
        json_response(true, [
            'message' => 'Login successful! Fresh cookies obtained.',
            'supplier_id' => $supplier_id,
            'identifier' => $identifier,
            'cookies_count' => count($new_cookies),
            'cookies' => array_keys($new_cookies),
            'session_data' => $new_session
        ]);
        
    } else {
        json_response(false, [
            'error' => 'Login response missing required fields',
            'step' => 'missing_data',
            'response' => $result
        ], 500);
    }
    
} catch (Exception $e) {
    json_response(false, [
        'error' => 'Exception: ' . $e->getMessage(),
        'step' => 'exception'
    ], 500);
}
?>
