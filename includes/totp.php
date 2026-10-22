<?php
/**
 * RFC 6238 Time-Based One-Time Password (TOTP) Implementation for Multi-Factor Authentication
 * 
 * Fully self-contained pure PHP implementation:
 * - Base32 encoding / decoding
 * - Standard RFC 6238 TOTP (HMAC-SHA1, 30s window, 6 digits)
 * - Clock drift tolerance window (t-1, t, t+1)
 * - Backup emergency recovery codes (one-time use, SHA-256 hashed)
 * - OTPAuth URI generation compatible with Google Authenticator, Microsoft Authenticator, 1Password, Authy
 */

class TOTP
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generates a secure random Base32 secret key (16 characters / 80 bits).
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $charCount = strlen(self::BASE32_CHARS);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $charCount - 1)];
        }
        return $secret;
    }

    /**
     * Decodes a Base32 string into binary bytes.
     */
    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(str_replace([' ', '-', '='], '', $b32));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
            $val = strpos(self::BASE32_CHARS, $b32[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Calculates the 6-digit TOTP code for a secret and timestamp.
     */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = (int)floor($timestamp / 30);

        $secretKey = self::base32Decode($secret);
        // Pack into 8-byte big-endian binary string
        $timeData = pack('N*', 0) . pack('N*', $timeSlice);

        $hmac = hash_hmac('sha1', $timeData, $secretKey, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;

        return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifies a 6-digit TOTP code with time window drift tolerance.
     * $discrepancy = 1 allows 30 seconds before and 30 seconds after current time.
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTime = time();
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $checkTime = $currentTime + ($i * 30);
            $calculatedCode = self::getCode($secret, $checkTime);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates standard OTPAuth URL for QR code scanners.
     */
    public static function getOtpAuthUri(string $accountEmail, string $secret, string $issuer = 'ShopEase'): string
    {
        $label = rawurlencode($issuer . ':' . $accountEmail);
        $secretParam = rawurlencode($secret);
        $issuerParam = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secretParam}&issuer={$issuerParam}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generates a set of single-use emergency backup recovery codes.
     * Format: e.g. "8A2F-9C1D"
     * 
     * Returns:
     * [
     *   'plain_codes' => ['8A2F-9C1D', ...],
     *   'hashed_records' => json_encoded string for DB storage
     * ]
     */
    public static function generateBackupCodes(int $count = 8): array
    {
        $plainCodes = [];
        $records = [];

        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(bin2hex(random_bytes(2)));
            $part2 = strtoupper(bin2hex(random_bytes(2)));
            $code = "{$part1}-{$part2}";
            $plainCodes[] = $code;
            $cleanCode = str_replace('-', '', $code);
            $records[] = [
                'hash' => hash('sha256', $cleanCode),
                'used' => false,
                'used_at' => null,
            ];
        }

        return [
            'plain_codes'    => $plainCodes,
            'hashed_records' => json_encode($records),
        ];
    }

    /**
     * Validates a backup recovery code against the user's stored backup codes JSON.
     * If valid and unused, marks the code as used and returns the updated JSON.
     * Returns false if code is invalid or already consumed.
     */
    public static function verifyAndConsumeBackupCode(?string $storedJson, string $inputCode): array|false
    {
        if (empty($storedJson)) {
            return false;
        }

        $records = json_decode($storedJson, true);
        if (!is_array($records)) {
            return false;
        }

        $cleanInput = strtoupper(str_replace(['-', ' '], '', trim($inputCode)));
        $inputHash = hash('sha256', $cleanInput);

        foreach ($records as $index => $rec) {
            if (empty($rec['used']) && hash_equals($rec['hash'], $inputHash)) {
                $records[$index]['used'] = true;
                $records[$index]['used_at'] = date('Y-m-d H:i:s');

                return [
                    'valid'        => true,
                    'updated_json' => json_encode($records),
                ];
            }
        }

        return false;
    }

    /**
     * Counts remaining unused backup codes.
     */
    public static function countRemainingBackupCodes(?string $storedJson): int
    {
        if (empty($storedJson)) {
            return 0;
        }
        $records = json_decode($storedJson, true);
        if (!is_array($records)) {
            return 0;
        }
        $unused = 0;
        foreach ($records as $rec) {
            if (empty($rec['used'])) {
                $unused++;
            }
        }
        return $unused;
    }
}
