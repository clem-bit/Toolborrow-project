<?php
class ReservationController {
    public function handle(string $method, ?string $id, ?string $action, array $body): void {
        $user = Auth::check();

        if ($method === 'GET'  && !$id)                 $this->listAll($user);
        if ($method === 'POST' && !$id)                 $this->create($user, $body);
        if ($method === 'PUT'  && $action === 'cancel') $this->cancel((int)$id, $user);
        Response::error('Not found', 404);
    }

    private function listAll(array $user): void {
        if ($user['role'] === 'admin') {
            $stmt = getDB()->query(
                'SELECT r.*, u.name AS user_name, t.name AS tool_name
                 FROM reservations r
                 JOIN users u ON u.id = r.user_id
                 JOIN tools t ON t.id = r.tool_id
                 ORDER BY r.reserved_from DESC'
            );
        } else {
            $stmt = getDB()->prepare(
                'SELECT r.*, t.name AS tool_name FROM reservations r
                 JOIN tools t ON t.id = r.tool_id
                 WHERE r.user_id = ? ORDER BY r.reserved_from DESC'
            );
            $stmt->execute([$user['id']]);
        }
        Response::json($stmt->fetchAll());
    }

    private function create(array $user, array $b): void {
        if (empty($b['tool_id']) || empty($b['reserved_from']) || empty($b['reserved_to'])) {
            Response::error('tool_id, reserved_from and reserved_to are required');
        }
        if ($b['reserved_from'] >= $b['reserved_to']) {
            Response::error('reserved_to must be after reserved_from');
        }

        
        $conflict = getDB()->prepare(
            'SELECT id FROM reservations
             WHERE tool_id = ? AND status != "cancelled"
             AND reserved_from < ? AND reserved_to > ?'
        );
        $conflict->execute([(int)$b['tool_id'], $b['reserved_to'], $b['reserved_from']]);
        if ($conflict->fetch()) {
            Response::error('This equipment is already reserved for those dates', 409);
        }

        $stmt = getDB()->prepare(
            'INSERT INTO reservations (user_id, tool_id, reserved_from, reserved_to) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], (int)$b['tool_id'], $b['reserved_from'], $b['reserved_to']]);
        Response::json(['message' => 'Reservation created', 'id' => getDB()->lastInsertId()], 201);
    }

    private function cancel(int $id, array $user): void {
        $stmt = getDB()->prepare('SELECT * FROM reservations WHERE id = ?');
        $stmt->execute([$id]);
        $res = $stmt->fetch();

        if (!$res) Response::error('Reservation not found', 404);
        if ($user['role'] !== 'admin' && (int)$res['user_id'] !== (int)$user['id']) {
            Response::error('Forbidden', 403);
        }

        getDB()->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?')
               ->execute([$id]);
        Response::json(['message' => 'Reservation cancelled']);
    }
}
?>
