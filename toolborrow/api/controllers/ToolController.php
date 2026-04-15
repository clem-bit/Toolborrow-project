<?php
class ToolController {
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
        $where  = [];
        $params = [];

        if (!empty($_GET['name'])) {
            $where[]  = 't.name LIKE ?';
            $params[] = '%' . $_GET['name'] . '%';
        }
        if (!empty($_GET['category_id'])) {
            $where[]  = 't.category_id = ?';
            $params[] = (int)$_GET['category_id'];
        }
        if (!empty($_GET['status'])) {
            $where[]  = 't.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['condition_rating'])) {
            $where[]  = 't.condition_rating = ?';
            $params[] = $_GET['condition_rating'];
        }

        $sql  = 'SELECT t.*, c.name AS category_name, c.icon AS category_icon
                 FROM tools t
                 LEFT JOIN categories c ON c.id = t.category_id';
        $sql .= $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql .= ' ORDER BY t.name';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    private function getOne(int $id): void {
        $stmt = getDB()->prepare(
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon
             FROM tools t
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $tool = $stmt->fetch();
        $tool ? Response::json($tool) : Response::error('Tool not found', 404);
    }

    private function create(array $b): void {
        Auth::requireAdmin();
        if (empty($b['name'])) Response::error('Equipment name is required');

        $qty = max(1, (int)($b['quantity'] ?? 1));

        $stmt = getDB()->prepare(
            'INSERT INTO tools (name, category_id, serial_number, description, quantity, quantity_available, condition_rating, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($b['name']),
            !empty($b['category_id']) ? (int)$b['category_id'] : null,
            !empty($b['serial_number']) ? trim($b['serial_number']) : null,
            !empty($b['description']) ? trim($b['description']) : null,
            $qty,
            $qty,
            $b['condition_rating'] ?? 'good',
            !empty($b['image_url']) ? trim($b['image_url']) : null,
        ]);
        Response::json(['message' => 'Equipment added', 'id' => getDB()->lastInsertId()], 201);
    }

    private function update(int $id, array $b): void {
        Auth::requireAdmin();

        $allowed = ['name', 'category_id', 'serial_number', 'description', 'quantity', 'condition_rating', 'status', 'image_url'];
        $fields  = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $b)) {
                if ($field === 'name' && empty(trim((string)$b[$field]))) continue;
                $fields[] = "$field = ?";
                $params[] = $b[$field] === '' ? null : $b[$field];
            }
        }

        if (empty($fields)) Response::error('Nothing to update');

        $params[] = $id;
        $stmt = getDB()->prepare('UPDATE tools SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);

        $stmt->rowCount()
            ? Response::json(['message' => 'Equipment updated'])
            : Response::error('Equipment not found', 404);
    }

    private function delete(int $id): void {
        Auth::requireAdmin();

        $check = getDB()->prepare(
            "SELECT COUNT(*) FROM loans WHERE tool_id = ? AND status IN ('active','overdue')"
        );
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            Response::error('Cannot delete — this equipment has active loans', 422);
        }

        $stmt = getDB()->prepare('DELETE FROM tools WHERE id = ?');
        $stmt->execute([$id]);
        $stmt->rowCount()
            ? Response::json(['message' => 'Equipment deleted'])
            : Response::error('Equipment not found', 404);
    }
}
?>
