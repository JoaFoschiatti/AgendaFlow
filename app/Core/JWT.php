<?php

namespace App\Core;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class JWT
{
    private static string $secretKey;
    private static string $algorithm = 'HS256';
    private static int $tokenLifetime = 86400; // 24 hours
    private static int $refreshLifetime = 604800; // 7 days

    public static function init(): void
    {
        $apiConfig = require dirname(__DIR__, 2) . '/config/api.config.php';
        self::$secretKey = $apiConfig['jwt']['secret_key'] ?? bin2hex(random_bytes(32));
    }

    public static function generateToken(array $payload): array
    {
        self::init();

        $issuedAt = time();
        $expire = $issuedAt + self::$tokenLifetime;
        $refreshExpire = $issuedAt + self::$refreshLifetime;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => $_SERVER['HTTP_HOST'] ?? 'agendaflow.com',
            'type' => 'access'
        ]);

        $refreshPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $refreshExpire,
            'iss' => $_SERVER['HTTP_HOST'] ?? 'agendaflow.com',
            'type' => 'refresh'
        ]);

        return [
            'access_token' => FirebaseJWT::encode($tokenPayload, self::$secretKey, self::$algorithm),
            'refresh_token' => FirebaseJWT::encode($refreshPayload, self::$secretKey, self::$algorithm),
            'token_type' => 'Bearer',
            'expires_in' => self::$tokenLifetime,
            'expires_at' => date('Y-m-d H:i:s', $expire)
        ];
    }

    public static function validateToken(string $token): ?array
    {
        self::init();

        try {
            $decoded = FirebaseJWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Invalid token signature', 401);
        } catch (BeforeValidException $e) {
            throw new \Exception('Token not valid yet', 401);
        } catch (\Exception $e) {
            throw new \Exception('Invalid token', 401);
        }
    }

    public static function refreshToken(string $refreshToken): array
    {
        $payload = self::validateToken($refreshToken);

        if ($payload['type'] !== 'refresh') {
            throw new \Exception('Invalid refresh token', 401);
        }

        unset($payload['iat'], $payload['exp'], $payload['type']);

        return self::generateToken($payload);
    }

    public static function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}