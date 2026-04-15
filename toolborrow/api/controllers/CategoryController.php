<?php
class CategoryController {
    public function handle(string $method, ?string $id, array $body): void {
        match (true) {
            $method === 'GET'  && !$id => $this->listAll(),
            $method === 'GET'  && !!$id => $this->getOne((int)$id),
            $method === 'POST' && !$id  => $this->create($body),
            default => Response::error('Not found', 404),
        };
    }

    private function listAll(): void {
        $stmt = getDB()->query('SELECT * FROM categories ORDER BY name');
        Response::json($stmt->fetchAll());
    }

    private function getOne(int $id): void {
        $stmt = getDB()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        $cat ? Response::json($cat) : Response::error('Category not found', 404);


    }

    private function create(array $b): void {
        Auth::requireAdmin();
        if (empty($b['name'])) Response::error('Name is required');
        $stmt = getDB()->prepare('INSERT INTO categories (name, icon) VALUES (?,?)');
        $stmt->execute([$b['name'], $b['icon'] ?? null]);
        Response::json(['message' => 'Category created', 'id' => getDB()->lastInsertId()], 201);
    }
}
?>
