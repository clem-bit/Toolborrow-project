<?php
class MaintenanceController {
    public function handle(string $method, ?string $id, ?string $action, array $body): void {
        Auth::requireAdmin();

        if ($method === 'GET'  && !$id)                      $this->listAll();
        if ($method === 'POST' && !$id)                      $this->create($body);
        if ($method === 'PUT'  && $action === 'complete')    $this->complete((int)$id);
        Response::error('Not found', 404);
    }

    private function listAll(): void {
        $stmt = getDB()->query(
            'SELECT m.*, t.name AS tool_name, u.name AS performed_by_name
             FROM maintenance_log m
             JOIN tools t ON t.id = m.tool_id
             LEFT JOIN users u ON u.id = m.performed_by
             ORDER BY m.performed_at DESC'
        );
        Response::json($stmt->fetchAll());
    }

    private function create(array $b): void {
        if (empty($b['tool_id']) || empty($b['description'])) {
            Response::error('tool_id and description are required');
        }

        $user = Auth::requireAdmin();
        $db   = getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO maintenance_log (tool_id, description, cost, performed_by)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                (int)$b['tool_id'],
                trim($b['description']),
                (float)($b['cost'] ?? 0.00),
                $user['id'],
            ]);
            $logId = $db->lastInsertId();

            $db->prepare("UPDATE tools SET status = 'maintenance' WHERE id = ?")
               ->execute([(int)$b['tool_id']]);

            $db->commit();
            Response::json(['message' => 'Maintenance logged, equipment marked for maintenance', 'id' => $logId], 201);
        } catch (Exception $e) {
            $db->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }

    private function complete(int $id): void {
        $db = getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT tool_id FROM maintenance_log WHERE id = ?');
            $stmt->execute([$id]);
            $log = $stmt->fetch();
            if (!$log) { $db->rollBack(); Response::error('Log not found', 404); }

            $db->prepare("UPDATE tools SET status = 'available' WHERE id = ?")
               ->execute([$log['tool_id']]);

            $db->commit();
            Response::json(['message' => 'Maintenance complete — equipment is now available']);
        } catch (Exception $e) {
            $db->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }
}
?>
