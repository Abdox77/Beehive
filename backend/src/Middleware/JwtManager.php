<?php

namespace App\Middleware;

use Psr\Log\LoggerInterface;


class JwtManager {
    private $secretkey;
    private int $ttl;

    public function __construct($secretkey, private LoggerInterface $logger)
    {
        $this->ttl = 3600;
        $this->secretkey = $secretkey;
    }

    public function createToken($payload): string
    {
        $now = time();
        $payloadHead = [
            'iat' => $now,
            'exp' => $now + $this->ttl
        ];
        $payload = array_merge($payloadHead, $payload);
        
        $this->logger->debug('Creating token', [
            'iat' => $payloadHead['iat'],
            'exp' => $payloadHead['exp'],
            'ttl_seconds' => $this->ttl,
            'expires_at' => date('Y-m-d H:i:s', $payloadHead['exp'])
        ]);
        
        $base64UrlHeader = $this->base64UrlEncode(json_encode([ "alg" => "HS256", "typ" => "JWT"], JSON_UNESCAPED_SLASHES));
        $base64UrlPayload = $this->base64UrlEncode(json_encode( $payload, JSON_UNESCAPED_SLASHES));
        $signatureBin = hash_hmac("sha256", $base64UrlHeader . '.' . $base64UrlPayload, $this->secretkey, true);
        $base64UrlSignature = $this->base64UrlEncode($signatureBin);
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    public function validateToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
        {
            $this->logger->warning('Token validation failed: invalid format');
            return false;
        }
        
        [$b64h, $b64p, $b64s] = $parts;
        $payload = $this->base64UrlDecode($b64p);
        $payload = json_decode($payload, true);

        if (!isset($payload['exp']) || !isset($payload['iat']))
        {
            $this->logger->warning('Token validation failed: missing exp or iat');
            return false;
        }

        $now = time();
        $exp = $payload['exp'];
        
        $this->logger->debug('Token validation', [
            'now' => $now,
            'exp' => $exp,
            'now_date' => date('Y-m-d H:i:s', $now),
            'exp_date' => date('Y-m-d H:i:s', $exp),
            'is_expired' => $exp < $now,
            'seconds_remaining' => $exp - $now
        ]);

        if ($exp < $now)
        {
            $this->logger->info('Token expired', [
                'expired_seconds_ago' => $now - $exp
            ]);
            return false;
        }
        
        $b64s = $this->base64UrlDecode($b64s); 
        $expectedSignature = hash_hmac("sha256", $b64h . '.' . $b64p, $this->secretkey, true);
        
        $signatureValid = hash_equals($expectedSignature, $b64s);
        
        if (!$signatureValid) {
            $this->logger->warning('Token validation failed: invalid signature');
        }
        
        return $signatureValid;
    }

    public function decodeToken(string $token): ?array 
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
        {
            return null;
        }

        [,$b64p,] = $parts;
        $payloadJson = $this->base64UrlDecode($b64p);
        if ($payloadJson === false)
        {
            return null;
        }

        $data = json_decode($payloadJson, true);
        return is_array($data) ? $data : null;
    }

    private function base64UrlEncode($data): string | false
    {
        $b64 = base64_encode($data);
        return strtr(rtrim($b64, '='), '+/','-_');
    }

    private function base64UrlDecode($data): string
    {
        $b64 = strtr($data, '-_', '+/');
        $padLen = (4 - (strlen($b64) % 4)) % 4;
        if ($padLen) {
            $b64 .= str_repeat('=', $padLen);
        }
        return base64_decode($b64, true);
    }
}
