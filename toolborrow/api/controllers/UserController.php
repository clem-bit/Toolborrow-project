<?php
class UserController {
    public function handle(string $method, ?string $id, array $body): void {
        match (true) {
            $method === 'GET'    && !$id  => $this->listAll(),
            $method === 'GET'    && !!$id => $this->getOne((int)$id),
            $method === 'POST'   && !$id  => $this->create($body),
            $method === 'PUT'    && !!$id => $this->update((int)$id, $body),
            $method === 'DELETE' && !!$id => $this->delete((int)$id),
            default => Response::error('Not found', 404),
        };
    }

    private function listAll(): void {
        Auth::requireAdmin();
        $stmt = getDB()->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC'
        );
        Response::json($stmt->fetchAll());
    }

    private function getOne(int $id): void {
        Auth::requireAdmin();
        $stmt = getDB()->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        $u ? Response::json($u) : Response::error('User not found', 404);
    }

    private function create(array $b): void {
        Auth::requireAdmin();
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
        if ($check->fetch()) Response::error('Email already registered', 409);

        $role = in_array($b['role'] ?? '', ['user', 'admin']) ? $b['role'] : 'user';
        $hash = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([trim($b['name']), trim($b['email']), $hash, $role]);
        Response::json(['message' => 'User created', 'id' => $db->lastInsertId()], 201);
    }

    private function update(int $id, array $b): void {
        $caller = Auth::requireAdmin();

        $fields = [];
        $params = [];

        if (!empty($b['name']))  { $fields[] = 'name = ?';  $params[] = trim($b['name']); }
        if (!empty($b['email'])) {
            if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) Response::error('Invalid email');
            $fields[] = 'email = ?';
            $params[] = trim($b['email']);
        }
        if (!empty($b['role']) && in_array($b['role'], ['user', 'admin'])) {
            $fields[] = 'role = ?';
            $params[] = $b['role'];
        }
        if (!empty($b['password'])) {
            if (strlen($b['password']) < 6) Response::error('Password must be at least 6 characters');
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields)) Response::error('Nothing to update');

        $params[] = $id;
        $stmt = getDB()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);

        $stmt->rowCount()
            ? Response::json(['message' => 'User updated'])
            : Response::error('User not found', 404);
    }

    private function delete(int $id): void {
        $caller = Auth::requireAdmin();
        if ((int)$caller['id'] === $id) {
            Response::error('You cannot delete your own account', 422);
        }

        $check = getDB()->prepare(
            "SELECT COUNT(*) FROM loans WHERE user_id = ? AND status IN ('active','overdue')"
        );
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) {
            Response::error('Cannot delete — user has active loans', 422);
        }

        $stmt = getDB()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $stmt->rowCount()
            ? Response::json(['message' => 'User deleted'])
            : Response::error('User not found', 404);
    }
}
?>
