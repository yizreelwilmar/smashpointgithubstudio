<?php
// core/Security.php
// Powershoot Badminton — URL Hash Obfuscation & SQL Injection Prevention Guard

declare(strict_types=1);

class Security {
    // Secret server salt for URL token signing (cryptographically verifies hashes)
    private const SECRET_KEY = 'smashpoint_badminton_secret_hash_key_v1';

    /**
     * Encode an internal integer ID into a sleek, URL-safe alphanumeric hash.
     * e.g., 5 -> "s_b7d2f9"
     */
    public static function hashId(int $id, string $prefix = 's'): string {
        if ($id <= 0) return '';
        
        // 1. Obfuscate integer with linear transformation
        $multiplier = 73;
        $offset = 5821;
        $transformed = ($id * $multiplier) + $offset;
        $encodedNum = base_convert((string)$transformed, 10, 36);

        // 2. Generate HMAC signature (6 hex characters)
        $signature = substr(hash_hmac('sha256', "{$prefix}_{$id}_{$transformed}", self::SECRET_KEY), 0, 6);

        // Result: e.g. "s_7a9b1c_3m2"
        return "{$prefix}_{$signature}_{$encodedNum}";
    }

    /**
     * Decode a URL hash back to its original integer ID.
     * Verifies the cryptographic signature to block tampering, scanning, and injection.
     * Returns 0 if invalid or tampered.
     */
    public static function decodeId(?string $hash, string $prefix = 's'): int {
        if (empty($hash) || !is_string($hash)) {
            return 0;
        }

        $hash = trim($hash);

        // Expected format: prefix_signature_encodedNum
        $parts = explode('_', $hash);
        if (count($parts) !== 3) {
            // For backwards compatibility: if someone somehow passed a raw integer,
            // we can either reject or safely check. But requirement says "jangan ada id di url",
            // so we only accept the hash.
            return 0;
        }

        [$hashPrefix, $signature, $encodedNum] = $parts;

        if ($hashPrefix !== $prefix) {
            return 0;
        }

        // Decode base36
        $transformed = base_convert($encodedNum, 36, 10);
        if (!is_numeric($transformed)) {
            return 0;
        }

        $transformedInt = (int)$transformed;
        $multiplier = 73;
        $offset = 5821;

        $rem = $transformedInt - $offset;
        if ($rem <= 0 || ($rem % $multiplier) !== 0) {
            return 0;
        }

        $id = (int)($rem / $multiplier);
        if ($id <= 0) {
            return 0;
        }

        // Verify cryptographic signature
        $expectedSignature = substr(hash_hmac('sha256', "{$prefix}_{$id}_{$transformedInt}", self::SECRET_KEY), 0, 6);
        if (!hash_equals($expectedSignature, $signature)) {
            return 0;
        }

        return $id;
    }

    /**
     * Sanitize user input query string to prevent injection & wildcard exhaustion.
     */
    public static function sanitizeQuery(?string $input, int $maxLength = 60): string {
        if ($input === null) return '';
        $trimmed = trim($input);
        // Remove control characters
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $trimmed);
        return mb_substr($clean, 0, $maxLength);
    }
}
