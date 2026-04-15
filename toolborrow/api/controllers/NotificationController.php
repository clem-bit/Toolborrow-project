<?php
class NotificationController {
    public function handle(string $method, ?string $id, ?string $action, array $body): void {
        $user = Auth::check();

        if ($method === 'GET' && !$id)                    $this->listAll($user);
        if ($method === 'PUT' && $action === 'read' && $id) $this->markRead((int)$id, $user);
        if ($method === 'PUT' && $id === 'read-all')      $this->markAllRead($user);
        Response::error('Not found', 404);
    }

    private function listAll(array $user): void {
        $stmt = getDB()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
        );
        $stmt->execute([$user['id']]);
        Response::json($stmt->fetchAll());
    }

    private function markRead(int $id, array $user): void {
        $stmt = getDB()->prepare('SELECT * FROM notifications WHERE id = ?');
        $stmt->execute([$id]);
        $n = $stmt->fetch();
        if (!$n) Response::error('Notification not found', 404);
        if ((int)$n['user_id'] !== (int)$user['id']) Response::error('Forbidden', 403);
        getDB()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);
        Response::json(['message' => 'Marked as read']);
    }

    private function markAllRead(array $user): void {
        getDB()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')
               ->execute([$user['id']]);
        Response::json(['message' => 'All notifications marked as read']);
    }
}
?>
