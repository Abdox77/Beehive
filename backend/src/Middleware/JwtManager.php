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

    /**
     * Summary of createToken
     * @param mixed $payload
     * @return string $jwtToken
     */
    public function createToken($payload): string
    {
        $now = time();
        $payloadHead = [
            'iat' => $now,
            'exp' => $now + $this->ttl
        ];
        $payload = array_merge($payloadHead, $payload);
        $base64UrlHeader = $this->base64UrlEncode(json_encode([ "alg" => "HS256", "typ" => "JWT"], JSON_UNESCAPED_SLASHES));
        $base64UrlPayload = $this->base64UrlEncode(json_encode( $payload, JSON_UNESCAPED_SLASHES));
        $signatureBin = hash_hmac("sha256", $base64UrlHeader . '.' . $base64UrlPayload, $this->secretkey, true);
        $base64UrlSignature = $this->base64UrlEncode($signatureBin);
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
 
    /**
     * 
     * @param string $jwtToken
     * @return bool $isValid
     */
    public function validateToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
        {
            return false;
        }
        
        [$b64h, $b64p, $b64s] = $parts;
        $payload = $this->base64UrlDecode($b64p);
        $payload = json_decode($payload, true);

        // $this->logger->debug('payload values', [
        //     'values' => array_values($payload ?? []),
        // ]);
        // $this->logger->debug('Payload keys', [
        //     'keys' => array_keys($payload ?? []),
        // ]);

        if ($payload['exp'] < time())
        {
            return false;
        }
        $b64s = $this->base64UrlDecode($b64s); 
        $expectedSignature = hash_hmac("sha256", $b64h . '.' . $b64p, $this->secretkey, true);
        return hash_equals($expectedSignature, $b64s);
    }

    /**
     * Summary of decodeToken
     * @param string $token
     * @return array|null
     */
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
