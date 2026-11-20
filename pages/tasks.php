<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!is_logged_in()) {
    respond(['error' => 'Not logged in.'], 401);
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $connection = db();
    // ---- Read tasks ----
    if ($method === 'GET') {
        $filter = $_GET['filter'] ?? 'all';
        if (!in_array($filter, ['all', 'pending', 'completed'], true)) {
            respond(['error' => 'Invalid filter.'], 422);
        }

        $sql = 'SELECT id, title, description, priority, due_date, is_done, created_at
                FROM tasks WHERE user_id = ?';
        if ($filter === 'pending') {
            $sql .= ' AND is_done = 0';
        } elseif ($filter === 'completed') {
            $sql .= ' AND is_done = 1';
        }
        $sql .= ' ORDER BY is_done ASC, due_date IS NULL, due_date ASC, id DESC';

        $stmt = db_prepare($connection, $sql, 'i', [$userId]);
        mysqli_stmt_execute($stmt);
        $taskResult = mysqli_stmt_get_result($stmt);
        $tasks = mysqli_fetch_all($taskResult, MYSQLI_ASSOC);
        mysqli_free_result($taskResult);
        mysqli_stmt_close($stmt);

        $counts = db_prepare(
            $connection,
            'SELECT COUNT(*) AS total, COALESCE(SUM(is_done = 0), 0) AS pending,
                    COALESCE(SUM(is_done = 1), 0) AS done
             FROM tasks WHERE user_id = ?',
            'i',
            [$userId]
        );
        mysqli_stmt_execute($counts);
        $countsResult = mysqli_stmt_get_result($counts);
        $c = mysqli_fetch_assoc($countsResult);
        mysqli_free_result($countsResult);
        mysqli_stmt_close($counts);
        respond([
            'tasks' => array_map(static function (array $t): array {
                $t['id'] = (int) $t['id'];
                $t['is_done'] = (bool) $t['is_done'];
                return $t;
            }, $tasks),
            'stats' => [
                'total'   => (int) $c['total'],
                'pending' => (int) $c['pending'],
                'done'    => (int) $c['done'],
            ],
        ]);
    }

    // ---- Write actions (POST, JSON body, CSRF header) ----
    if ($method !== 'POST') {
        respond(['error' => 'Method not allowed.'], 405);
    }

    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        respond(['error' => 'Session expired. Please refresh the page.'], 403);
    }

    $input  = json_decode(file_get_contents('php://input') ?: '', true);
    $input  = is_array($input) ? $input : [];
    $action = $input['action'] ?? '';

    if ($action === 'create') {
        $title = trim((string) ($input['title'] ?? ''));
        $desc  = trim((string) ($input['description'] ?? ''));
        $prio  = (string) ($input['priority'] ?? 'medium');
        $due   = trim((string) ($input['due_date'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            respond(['error' => 'Title is required (up to 200 characters).'], 422);
        }
        if (!in_array($prio, ['low', 'medium', 'high'], true)) {
            respond(['error' => 'Invalid priority.'], 422);
        }
        if ($due !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $due);
            if (!$d || $d->format('Y-m-d') !== $due) {
                respond(['error' => 'Invalid due date.'], 422);
            }
        }

        $stmt = db_prepare(
            $connection,
            'INSERT INTO tasks (user_id, title, description, priority, due_date)
             VALUES (?, ?, ?, ?, ?)',
            'issss',
            [$userId, $title, $desc === '' ? null : $desc, $prio, $due === '' ? null : $due]
        );
        mysqli_stmt_execute($stmt);
        $taskId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);
        respond(['ok' => true, 'id' => $taskId], 201);
    }

    if ($action === 'toggle' || $action === 'delete') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            respond(['error' => 'Invalid task id.'], 422);
        }

        if ($action === 'toggle') {
            $stmt = db_prepare(
                $connection,
                'UPDATE tasks SET is_done = 1 - is_done WHERE id = ? AND user_id = ?',
                'ii',
                [$id, $userId]
            );
        } else {
            $stmt = db_prepare(
                $connection,
                'DELETE FROM tasks WHERE id = ? AND user_id = ?',
                'ii',
                [$id, $userId]
            );
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        respond(['ok' => true]);
    }

    respond(['error' => 'Unknown action.'], 400);

} catch (mysqli_sql_exception $ex) {
    error_log($ex->getMessage());
    respond(['error' => 'Database error. Please try again.'], 500);
}
