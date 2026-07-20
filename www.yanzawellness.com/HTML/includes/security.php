<?php
/**
 * Security & Cryptography Utilities
 */

/**
 * Generates a cryptographically secure UUID version 4.
 * @return string
 */
function generateUUIDv4() {
    try {
        $data = random_bytes(16);
    } catch (Exception $e) {
        $data = openssl_random_pseudo_bytes(16);
    }
    
    // Set version to 0100 (version 4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Encrypts data using AES-256-GCM.
 * @param string $plaintext
 * @param string $key (32-byte key)
 * @return string|false (Base64 encoded string containing iv, tag, and ciphertext or false)
 */
function encryptAES256GCM($plaintext, $key) {
    if (empty($plaintext)) {
        return '';
    }
    
    // Ensure the key is hashed to exactly 32 bytes (SHA-256 binary format)
    $key_hash = hash('sha256', $key, true);
    
    // GCM IV length is 12 bytes
    $iv_length = 12;
    $iv = random_bytes($iv_length);
    
    // Encrypt the plain text
    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key_hash,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '', // Additional Authenticated Data (AAD)
        16  // Tag length (16 bytes)
    );
    
    if ($ciphertext === false) {
        return false;
    }
    
    // Return Base64 encoded string combining IV (12B) + Tag (16B) + Ciphertext
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypts AES-256-GCM encrypted data.
 * @param string $encryptedBase64
 * @param string $key (32-byte key)
 * @return string|false (Decrypted plain text or false on failure)
 */
function decryptAES256GCM($encryptedBase64, $key) {
    if (empty($encryptedBase64)) {
        return '';
    }
    
    $data = base64_decode($encryptedBase64);
    if ($data === false) {
        return false;
    }
    
    // Ensure data length is at least IV (12 bytes) + Tag (16 bytes)
    if (strlen($data) < 28) {
        return false;
    }
    
    $key_hash = hash('sha256', $key, true);
    
    // Extract pieces
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    
    // Decrypt
    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key_hash,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    return $plaintext;
}

/**
 * Sanitizes input strings for output rendering to prevent XSS.
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifies if the currently logged-in user is an administrator.
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

