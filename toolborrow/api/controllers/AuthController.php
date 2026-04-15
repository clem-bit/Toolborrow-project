<?php
class AuthController {
    public function handle(string $method, ?string $action, array $body): void {
        match ($action) {
            'register' => $this->register($body),
            'login'    => $this->login($body),
            'me'       => $this->me(),
            default    => Response::error('Not found', 404),
        };
    }

    private function register(array $b): void {
        if (empty($b['name']) || empty($b['email']) || empty($b['password'])) {
            Response::error('Name, email and password are required');
        }
        if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address');
        }
        if (strlen($b['password']) < 6) {
            Response::error('Password must be at least 6 characters');
        }

        $db    = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$b['email']]);
        if ($check->fetch()) {
            Response::error('That email is already registered', 409);
        }

        $hash = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$b['name'], $b['email'], $hash, 'user']);
        Response::json(['message' => 'Account created successfully'], 201);
    }

    private function login(array $b): void {
        if (empty($b['email']) || empty($b['password'])) {
            Response::error('Email and password are required');
        }

        $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$b['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($b['password'], $user['password_hash'])) {
            Response::error('Incorrect email or password', 401);
        }

        if (!empty($b['role']) && $b['role'] !== $user['role']) {
            Response::error('You do not have ' . $b['role'] . ' access. Please select the correct role.', 403);
        }

        $token = Auth::createJWT([
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'exp'   => time() + (1 * 3600)
        ]);

        Response::json([
            'token' => $token,
            'user'  => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    private function me(): void {
        $user = Auth::check();
        $stmt = getDB()->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$user['id']]);
        $data = $stmt->fetch();
        $data ? Response::json($data) : Response::error('User not found', 404);
    }
}
?>
