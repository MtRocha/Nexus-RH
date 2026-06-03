<?php

declare(strict_types=1);

namespace NexusRH\Support;

final class Totp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        $secret = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($bytes) as $char) {
            $buffer = ($buffer << 8) | ord($char);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $index = ($buffer >> ($bitsLeft - 5)) & 31;
                $bitsLeft -= 5;
                $secret .= self::BASE32_ALPHABET[$index];
            }
        }

        if ($bitsLeft > 0) {
            $index = ($buffer << (5 - $bitsLeft)) & 31;
            $secret .= self::BASE32_ALPHABET[$index];
        }

        return $secret;
    }

    public static function buildProvisioningUri(string $issuer, string $accountName, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&period=30&digits=6',
            $label,
            rawurlencode($secret),
            rawurlencode($issuer)
        );
    }

    public static function verify(string $secret, string $code, int $window = 1, int $digits = 6): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($code === '' || strlen($code) !== $digits) {
            return false;
        }

        $currentSlice = (int) floor(time() / 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::generateCode($secret, $currentSlice + $offset, $digits), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function generateCode(string $secret, ?int $timeSlice = null, int $digits = 6): string
    {
        $timeSlice ??= (int) floor(time() / 30);
        $secretKey = self::base32Decode($secret);
        $binaryTime = pack('N2', 0, $timeSlice);
        $hash = hash_hmac('sha1', $binaryTime, $secretKey, true);
        $offset = ord($hash[19]) & 15;
        $value = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);
        $modulo = 10 ** $digits;

        return str_pad((string) ($value % $modulo), $digits, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $position;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 255);
            }
        }

        return $output;
    }
}