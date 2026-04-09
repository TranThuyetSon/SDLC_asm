<?php
/**
 * ARIA HOTEL - SECURITY FUNCTIONS
 * ============================================
 */

class Security {
    
    /**
     * Generate CSRF Token
     * @return string
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     * @param string $token
     * @return bool
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        
        // Xóa token sau khi verify (one-time use)
        unset($_SESSION['csrf_token']);
        
        return $valid;
    }
    
    /**
     * Get CSRF field HTML
     * @return string
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get CSRF token for URL
     * @return string
     */
    public static function csrfToken() {
        return self::generateCSRFToken();
    }
    
    /**
     * Sanitize input data
     * @param mixed $input
     * @return mixed
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Sanitize for HTML output (escape)
     * @param string $input
     * @return string
     */
    public static function escape($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     * @param string $phone
     * @return bool
     */
    public static function validatePhone($phone) {
        return preg_match('/^[0-9]{10,11}$/', $phone);
    }
    
    /**
     * Validate username
     * @param string $username
     * @return bool
     */
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    /**
     * Validate password strength
     * @param string $password
     * @return array [bool, string]
     */
    public static function validatePassword($password) {
        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters'];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return [false, 'Password must contain at least one uppercase letter'];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return [false, 'Password must contain at least one lowercase letter'];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return [false, 'Password must contain at least one number'];
        }
        
        return [true, ''];
    }
    
    /**
     * Rate limiting check
     * @param string $key - Unique identifier (e.g., IP + action)
     * @param int $maxAttempts - Maximum attempts allowed
     * @param int $timeWindow - Time window in seconds
     * @return bool
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 3600) {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        
        // Clean up old entries
        foreach ($_SESSION['rate_limit'] as $k => $data) {
            if ($now - $data['first_attempt'] > $timeWindow) {
                unset($_SESSION['rate_limit'][$k]);
            }
        }
        
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [
                'count' => 0,
                'first_attempt' => $now
            ];
        }
        
        $attempt = &$_SESSION['rate_limit'][$key];
        
        // Reset if time window expired
        if ($now - $attempt['first_attempt'] > $timeWindow) {
            $attempt = [
                'count' => 0,
                'first_attempt' => $now
            ];
        }
        
        if ($attempt['count'] >= $maxAttempts) {
            return false;
        }
        
        $attempt['count']++;
        return true;
    }
    
    /**
     * Get remaining attempts
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public static function getRemainingAttempts($key, $maxAttempts = 5) {
        if (!isset($_SESSION['rate_limit'][$key])) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $_SESSION['rate_limit'][$key]['count']);
    }
    
    /**
     * Get time until reset (in seconds)
     * @param string $key
     * @param int $timeWindow
     * @return int
     */
    public static function getTimeUntilReset($key, $timeWindow = 3600) {
        if (!isset($_SESSION['rate_limit'][$key])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['rate_limit'][$key]['first_attempt'];
        return max(0, $timeWindow - $elapsed);
    }
    
    /**
     * Clear rate limit for a key
     * @param string $key
     */
    public static function clearRateLimit($key) {
        if (isset($_SESSION['rate_limit'][$key])) {
            unset($_SESSION['rate_limit'][$key]);
        }
    }
    
    /**
     * Hash password
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehash
     * @param string $hash
     * @return bool
     */
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
    
    /**
     * Generate random token
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Validate and sanitize URL to prevent open redirect
     * @param string $url
     * @param array $allowedUrls
     * @param string $default
     * @return string
     */
    public static function validateRedirectUrl($url, $allowedUrls = [], $default = 'index.php') {
        // Default allowed URLs
        if (empty($allowedUrls)) {
            $allowedUrls = [
                'index.php',
                'rooms.php',
                'room-detail.php',
                'restaurant.php',
                'services.php',
                'contact.php',
                'profile.php',
                'my-bookings.php',
                'admin/dashboard.php'
            ];
        }
        
        // Remove any path traversal attempts
        $url = str_replace(['../', '..\\', '//', '\\\\'], '', $url);
        
        // Check if URL is in allowed list
        foreach ($allowedUrls as $allowed) {
            if (strpos($url, $allowed) !== false) {
                return $url;
            }
        }
        
        return $default;
    }
    
    /**
     * Get client IP address
     * @return string
     */
    public static function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Check for proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return $ip;
    }
    
    /**
     * Log security event
     * @param mysqli $conn
     * @param int|null $user_id
     * @param string $action
     * @param string $details
     * @return bool
     */
    public static function logEvent($conn, $user_id, $action, $details = '') {
        $ip = self::getClientIP();
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (user_id, action, new_data, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Check if request is AJAX
     * @return bool
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Require POST method
     * @return bool
     */
    public static function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method Not Allowed');
        }
        return true;
    }
    
    /**
     * Require GET method
     * @return bool
     */
    public static function requireGet() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            die('Method Not Allowed');
        }
        return true;
    }
    
    /**
     * Require AJAX request
     * @return bool
     */
    public static function requireAjax() {
        if (!self::isAjaxRequest()) {
            http_response_code(403);
            die('Forbidden');
        }
        return true;
    }
    
    /**
     * Regenerate session ID (prevent session fixation)
     * @param bool $deleteOld
     */
    public static function regenerateSession($deleteOld = true) {
        session_regenerate_id($deleteOld);
    }
    
    /**
     * Destroy session completely
     */
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

// DÒNG DƯỚI ĐÂY ĐÃ BỊ XÓA/COMMENT
// Security::secureSession();