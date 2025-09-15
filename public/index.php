<?php

// Simple standalone version since Laravel routing isn't working
// Connect to SQLite database
$db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');

// Get current path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper function to get tasks
function getTasks($db, $status = null)
{
    if ($status) {
        $stmt = $db->prepare('SELECT * FROM tasks WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([$status]);
    } else {
        $stmt = $db->query('SELECT * FROM tasks ORDER BY created_at DESC');
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function to render view
function renderView($view, $data = [])
{
    extract($data);
    ob_start();
    include __DIR__ . "/../resources/views/$view";
    return ob_get_clean();
}

// Helper function to get task by ID
function getTask($db, $id)
{
    $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Route handling
try {
    // Handle different routes
    if ($path === '/' || $path === '/tasks') {
        // Show task list
        $status = $_GET['status'] ?? null;
        $tasks = getTasks($db, $status);

        // Simple HTML output since we can't use Blade easily
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Task Manager</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        </head>

        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand" href="/">
                        <i class="fas fa-tasks"></i> Task Manager
                    </a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="/">All Tasks</a>
                        <a class="nav-link" href="/tasks/create">Add Task</a>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-list"></i> My Tasks</h1>
                    <a href="/tasks/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Task
                    </a>
                </div>

                <!-- Filter buttons -->
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <a href="/" class="btn <?= !$status ? 'btn-primary' : 'btn-outline-primary' ?>">All Tasks</a>
                        <a href="/?status=pending"
                            class="btn <?= $status == 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
                        <a href="/?status=in_progress"
                            class="btn <?= $status == 'in_progress' ? 'btn-info' : 'btn-outline-info' ?>">In Progress</a>
                        <a href="/?status=completed"
                            class="btn <?= $status == 'completed' ? 'btn-success' : 'btn-outline-success' ?>">Completed</a>
                    </div>
                </div>

                <?php if (!empty($tasks)): ?>
                    <div class="row">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?= htmlspecialchars($task['title']) ?></h5>
                                            <span
                                                class="badge bg-<?= $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </div>

                                        <p class="card-text text-muted">
                                            <?= htmlspecialchars(substr($task['description'] ?? '', 0, 100)) ?>
                                        </p>

                                        <div class="mb-3">
                                            <span
                                                class="badge bg-<?= $task['status'] == 'completed' ? 'success' : ($task['status'] == 'in_progress' ? 'info' : 'warning') ?>">
                                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                            </span>
                                        </div>

                                        <?php if ($task['due_date']): ?>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i>
                                                    Due: <?= date('M d, Y', strtotime($task['due_date'])) ?>
                                                </small>
                                            </p>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <a href="/tasks/<?= $task['id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/tasks/<?= $task['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>

                                            <div>
                                                <?php if ($task['status'] !== 'completed'): ?>
                                                    <a href="/tasks/<?= $task['id'] ?>/complete" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a href="/tasks/<?= $task['id'] ?>/delete" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this task?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer text-muted">
                                        <small>Created <?= date('M d, Y', strtotime($task['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-5x text-muted mb-3"></i>
                        <h3>No Tasks Found</h3>
                        <p class="text-muted">Start by creating your first task!</p>
                        <a href="/tasks/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Task
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </body>

        </html>
        <?php

    } elseif (preg_match('/^\/tasks\/(\d+)\/complete$/', $path, $matches)) {
        // Complete task
        $id = $matches[1];
        $stmt = $db->prepare('UPDATE tasks SET status = "completed" WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: /');
        exit;

    } elseif (preg_match('/^\/tasks\/(\d+)\/delete$/', $path, $matches)) {
        // Delete task
        $id = $matches[1];
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: /');
        exit;

    } elseif ($path === '/tasks/create') {
        if ($method === 'POST') {
            // Handle task creation
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'pending';
            $priority = $_POST['priority'] ?? 'medium';
            $due_date = $_POST['due_date'] ?? null;

            if ($title) {
                $stmt = $db->prepare('INSERT INTO tasks (title, description, status, priority, due_date) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$title, $description, $status, $priority, $due_date]);
                header('Location: /');
                exit;
            }
        }

        // Show create form
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Create Task - Task Manager</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        </head>

        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand" href="/">
                        <i class="fas fa-tasks"></i> Task Manager
                    </a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="/">All Tasks</a>
                        <a class="nav-link" href="/tasks/create">Add Task</a>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-plus"></i> Create New Task</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/tasks/create">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Task Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="pending">Pending</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="priority" class="form-label">Priority</label>
                                                <select class="form-select" id="priority" name="priority" required>
                                                    <option value="low">Low</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="high">High</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="due_date" class="form-label">Due Date</label>
                                        <input type="datetime-local" class="form-control" id="due_date" name="due_date">
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="/" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Tasks
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Create Task
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>

        </html>
        <?php

    } else {
        // 404 for other routes
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }

} catch (Exception $e) {
    echo "<h1>Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
}