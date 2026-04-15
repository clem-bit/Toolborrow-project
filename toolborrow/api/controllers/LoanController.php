<?php
class LoanController {
    public function handle(string $method, ?string $id, ?string $action, array $body): void {
        $user = Auth::check();

        
        $this->flagOverdue();

        if ($method === 'GET'  && $id === 'overdue')           $this->listOverdue($user);
        if ($method === 'GET'  && !$id)                        $this->listLoans($user);
        if ($method === 'POST' && !$id)                        $this->borrow($user, $body);
        if ($method === 'PUT'  && $action === 'return' && $id) $this->returnTool((int)$id, $user);
        Response::error('Not found', 404);
    }

    
    private function flagOverdue(): void {
        getDB()->exec(
            "UPDATE loans SET status = 'overdue'
             WHERE status = 'active' AND due_date < CURDATE()"
        );
    }

    
    private function listLoans(array $user): void {
        if ($user['role'] === 'admin') {
            $stmt = getDB()->query(
                'SELECT l.*, u.name AS user_name, u.email AS user_email, t.name AS tool_name
                 FROM loans l
                 JOIN users u ON u.id = l.user_id
                 JOIN tools t ON t.id = l.tool_id
                 ORDER BY l.borrowed_at DESC'
            );
        } else {
            $stmt = getDB()->prepare(
                'SELECT l.*, t.name AS tool_name, c.name AS category_name
                 FROM loans l
                 JOIN tools t ON t.id = l.tool_id
                 LEFT JOIN categories c ON c.id = t.category_id
                 WHERE l.user_id = ?
                 ORDER BY l.borrowed_at DESC'
            );
            $stmt->execute([$user['id']]);
        }
        Response::json($stmt->fetchAll());
    }

    
    private function listOverdue(array $user): void {
        if ($user['role'] !== 'admin') Response::error('Forbidden', 403);
        $stmt = getDB()->query(
            'SELECT l.*, u.name AS user_name, u.email AS user_email, t.name AS tool_name
             FROM loans l
             JOIN users u ON u.id = l.user_id
             JOIN tools t ON t.id = l.tool_id
             WHERE l.status = "overdue"
             ORDER BY l.due_date ASC'
        );
        Response::json($stmt->fetchAll());
    }

    
    private function borrow(array $user, array $b): void {
        if (empty($b['tool_id']) || empty($b['due_date'])) {
            Response::error('tool_id and due_date are required');
        }
        if ($b['due_date'] <= date('Y-m-d')) {
            Response::error('Due date must be a future date');
        }

        $db = getDB();
        $db->beginTransaction();
        try {
            
            $limitStmt = $db->prepare(
                "SELECT COUNT(*) FROM loans WHERE user_id = ? AND status IN ('active','overdue')"
            );
            $limitStmt->execute([$user['id']]);
            if ((int)$limitStmt->fetchColumn() >= MAX_ACTIVE_LOANS) {
                $db->rollBack();
                Response::error(
                    'Rental limit reached — you can have at most ' . MAX_ACTIVE_LOANS . ' active rentals',
                    422
                );
            }

            
            $toolStmt = $db->prepare('SELECT * FROM tools WHERE id = ? FOR UPDATE');
            $toolStmt->execute([(int)$b['tool_id']]);
            $tool = $toolStmt->fetch();

            if (!$tool) {
                $db->rollBack();
                Response::error('Equipment not found', 404);
            }
            if ($tool['status'] === 'maintenance') {
                $db->rollBack();
                Response::error('Equipment is under maintenance', 409);
            }
            if ((int)$tool['quantity_available'] < 1) {
                $db->rollBack();
                Response::error('No units of this equipment are available', 409);
            }

            
            $ins = $db->prepare(
                'INSERT INTO loans (user_id, tool_id, due_date, notes) VALUES (?, ?, ?, ?)'
            );
            $ins->execute([
                $user['id'],
                (int)$b['tool_id'],
                $b['due_date'],
                !empty($b['notes']) ? trim($b['notes']) : null,
            ]);
            $loanId = $db->lastInsertId();

            
            $newAvail  = (int)$tool['quantity_available'] - 1;
            $newStatus = $newAvail === 0 ? 'borrowed' : 'available';
            $db->prepare('UPDATE tools SET quantity_available = ?, status = ? WHERE id = ?')
               ->execute([$newAvail, $newStatus, (int)$b['tool_id']]);

            $db->commit();
            Response::json(['message' => 'Equipment rented successfully', 'loan_id' => $loanId], 201);

        } catch (Exception $e) {
            $db->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }

    
    private function returnTool(int $loanId, array $user): void {
        $db = getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM loans WHERE id = ?');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) { $db->rollBack(); Response::error('Loan not found', 404); }
            if ($loan['status'] === 'returned') { $db->rollBack(); Response::error('Already returned', 409); }
            if ($user['role'] !== 'admin' && (int)$loan['user_id'] !== (int)$user['id']) {
                $db->rollBack(); Response::error('Forbidden', 403);
            }

            
            $db->prepare('UPDATE loans SET status = "returned", returned_at = NOW() WHERE id = ?')
               ->execute([$loanId]);

            
            $ts = $db->prepare('SELECT quantity_available, status FROM tools WHERE id = ?');
            $ts->execute([$loan['tool_id']]);
            $t = $ts->fetch();

            $newAvail  = (int)$t['quantity_available'] + 1;
            $newStatus = ($t['status'] === 'maintenance') ? 'maintenance' : 'available';
            $db->prepare('UPDATE tools SET quantity_available = ?, status = ? WHERE id = ?')
               ->execute([$newAvail, $newStatus, $loan['tool_id']]);

            $db->commit();
            Response::json(['message' => 'Equipment returned successfully']);

        } catch (Exception $e) {
            $db->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }
}
?>
