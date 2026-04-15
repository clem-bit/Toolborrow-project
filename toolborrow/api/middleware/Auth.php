<?php
require_once __DIR__ . '/../config.php';

class Auth {
    public static function check(): array {
        $headers = getallheaders();
        $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
            Response::error('Unauthorized', 401);
        }

        try {
            $payload = self::decodeJWT($matches[1]);
            if ($payload['exp'] < time()) {
                Response::error('Session expired — please log in again', 401);
            }
            return $payload;
        } catch (Exception $e) {
            Response::error('Invalid token', 401);
        }
    }

    public static function requireAdmin(): array {
        $user = self::check();
        if ($user['role'] !== 'admin') {
            Response::error('Forbidden — admin access required', 403);
        }
        return $user;
    }

    private static function b64u(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function createJWT(array $payload): string {
        $h = self::b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $p = self::b64u(json_encode($payload));
        $s = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
        return "$h.$p." . self::b64u($s);
    }

    private static function decodeJWT(string $token): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new Exception('Malformed token');
        [$h, $p, $s] = $parts;
        $expected = self::b64u(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
        if (!hash_equals($expected, $s)) throw new Exception('Bad signature');
        $decoded = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        if (!$decoded) throw new Exception('Invalid payload');
        return $decoded;
    }
}
?>
