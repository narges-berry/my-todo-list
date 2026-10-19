<?php
/**
 * Shared helpers for the to-do list.
 *
 * Every page loads this file first. It starts the session, connects to
 * MySQL, and provides small functions for security, validation, and layout.
 */

// Show MySQLi problems as exceptions so each page can handle them clearly.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Change these values if your MySQL login is different.
// XAMPP default: user "root" and an empty password, port 3306.
// MAMP default:  user "root", password "root", port 8889.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todo_db');
define('DB_PORT', 3306);

session_start();

/**
 * Escape text before printing it in HTML.
 */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Open the database connection once and reuse it.
 */
function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $connection->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        render_db_error($exception->getMessage());
        exit;
    }

    return $connection;
}

/**
 * Create a CSRF token and keep it in the session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Hidden input added to every form that changes data.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/**
 * Return true when the posted token matches the session token.
 */
function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token) && hash_equals(csrf_token(), $token);
}

/**
 * Stop a request that did not include a valid CSRF token.
 */
function require_csrf(string $redirectTo): void
{
    if (!csrf_verify()) {
        set_flash('danger', 'The form expired. Please try again.');
        redirect($redirectTo);
    }
}

/**
 * Remember a message to show once after a redirect.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Read and remove the flash message.
 */
function get_flash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Send the browser to another page, then stop this script.
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Keep only the three filters the list page understands.
 */
function selected_filter(?string $filter): string
{
    if (in_array($filter, ['all', 'pending', 'completed'], true)) {
        return $filter;
    }

    return 'all';
}

/**
 * Build the task list URL for a filter.
 */
function filter_url(string $filter): string
{
    return 'index.php?filter=' . urlencode(selected_filter($filter));
}

/**
 * Read a positive task id from a request value.
 */
function task_id($value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        return 0;
    }

    return $id;
}

/**
 * Load one task, or return null when it does not exist.
 */
function find_task(mysqli $db, int $id): ?array
{
    $statement = $db->prepare(
        'SELECT id, title, description, due_date, status, created_at
         FROM tasks
         WHERE id = ?'
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $task = $statement->get_result()->fetch_assoc();
    $statement->close();

    return $task ?: null;
}

/**
 * Check that a date was entered as YYYY-MM-DD.
 */
function is_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

/**
 * Validate a task form and return errors plus cleaned values.
 */
function validate_task(array $input): array
{
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $dueDate = trim($input['due_date'] ?? '');
    $status = $input['status'] ?? 'pending';
    $errors = [];

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Title must be 255 characters or fewer.';
    }

    if (mb_strlen($description) > 5000) {
        $errors['description'] = 'Description must be 5000 characters or fewer.';
    }

    if ($dueDate !== '' && !is_valid_date($dueDate)) {
        $errors['due_date'] = 'Enter a valid due date.';
    }

    if (!in_array($status, ['pending', 'completed'], true)) {
        $errors['status'] = 'Choose pending or completed.';
        $status = 'pending';
    }

    return [
        'errors' => $errors,
        'data' => [
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'status' => $status,
        ],
    ];
}

/**
 * Values used to fill the add and edit forms.
 */
function form_values(array $task): array
{
    return [
        'title' => $task['title'] ?? '',
        'description' => $task['description'] ?? '',
        'due_date' => $task['due_date'] ?? '',
        'status' => $task['status'] ?? 'pending',
    ];
}

/**
 * Turn a stored date into a short label such as "Oct 20, 2026".
 */
function format_date(?string $date): string
{
    if ($date === null || $date === '') {
        return 'No due date';
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$parsed) {
        return $date;
    }

    return $parsed->format('M j, Y');
}

/**
 * Turn a timestamp into a short date and time.
 */
function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    try {
        return (new DateTime($value))->format('M j, Y g:i A');
    } catch (Exception $exception) {
        return $value;
    }
}

/**
 * A pending task is overdue when its due date is before today.
 */
function is_overdue(array $task): bool
{
    if (($task['status'] ?? '') !== 'pending' || empty($task['due_date'])) {
        return false;
    }

    return $task['due_date'] < date('Y-m-d');
}

/**
 * Print the shared page start, heading, and any flash message.
 */
function render_header(string $title): void
{
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · My To-Do List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <main class="container app-shell py-4 py-md-5">
        <header class="app-header text-center mb-4">
            <p class="app-kicker mb-2"><i class="bi bi-check2-square" aria-hidden="true"></i> Daily tasks</p>
            <h1>My To-Do List</h1>
            <p class="text-secondary mb-0">Add, edit, and complete the things you need to get done.</p>
        </header>
        <?php if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" role="alert">
                <?= h($flash['message']) ?>
            </div>
        <?php endif; ?>
    <?php
}

/**
 * Print the shared page end.
 */
function render_footer(): void
{
    ?>
        <footer class="app-footer text-center text-secondary small">
            A single-user to-do list stored in MySQL.
        </footer>
    </main>
</body>
</html>
    <?php
}

/**
 * Show one validation message under a form field.
 */
function field_error(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }

    return '<div class="invalid-feedback d-block">' . h($errors[$field]) . '</div>';
}

/**
 * Shared add/edit form.
 * Pass a task id when editing so the update knows which row to change.
 */
function render_task_form(string $action, array $values, array $errors, string $buttonLabel, int $id = 0, string $buttonIcon = 'bi-check2'): void
{
    $titleClass = isset($errors['title']) ? ' is-invalid' : '';
    $descriptionClass = isset($errors['description']) ? ' is-invalid' : '';
    $dueDateClass = isset($errors['due_date']) ? ' is-invalid' : '';
    $statusClass = isset($errors['status']) ? ' is-invalid' : '';
    ?>
    <form class="card form-card" method="post" action="<?= h($action) ?>" novalidate>
        <div class="card-body p-4">
            <?= csrf_field() ?>
            <?php if ($id > 0): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">Please correct the errors below.</div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input
                    class="form-control<?= $titleClass ?>"
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    required
                    value="<?= h($values['title']) ?>"
                >
                <?= field_error($errors, 'title') ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">Description <span class="text-secondary fw-normal">(optional)</span></label>
                <textarea
                    class="form-control<?= $descriptionClass ?>"
                    id="description"
                    name="description"
                    rows="4"
                ><?= h($values['description']) ?></textarea>
                <?= field_error($errors, 'description') ?>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="due_date">Due date <span class="text-secondary fw-normal">(optional)</span></label>
                    <input
                        class="form-control<?= $dueDateClass ?>"
                        type="date"
                        id="due_date"
                        name="due_date"
                        value="<?= h($values['due_date']) ?>"
                    >
                    <?= field_error($errors, 'due_date') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select<?= $statusClass ?>" id="status" name="status" required>
                        <option value="pending"<?= $values['status'] === 'pending' ? ' selected' : '' ?>>Pending</option>
                        <option value="completed"<?= $values['status'] === 'completed' ? ' selected' : '' ?>>Completed</option>
                    </select>
                    <?= field_error($errors, 'status') ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi <?= h($buttonIcon) ?>" aria-hidden="true"></i> <?= h($buttonLabel) ?>
                </button>
                <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
            </div>
        </div>
    </form>
    <?php
}

/**
 * Friendly message when MySQL is stopped or the database has not been imported.
 */
function render_db_error(string $message): void
{
    http_response_code(500);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database connection · My To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <main class="container app-shell py-5">
        <header class="app-header text-center mb-4">
            <h1>My To-Do List</h1>
        </header>
        <div class="card form-card">
            <div class="card-body p-4">
                <h2 class="h4">Cannot connect to the database</h2>
                <p class="mb-2">Start MySQL in XAMPP or MAMP, then import <code>database.sql</code>.</p>
                <p class="mb-0 text-secondary">You can change the login details at the top of <code>db.php</code>.</p>
                <p class="small text-secondary mt-3 mb-0"><?= h($message) ?></p>
            </div>
        </div>
    </main>
</body>
</html>
    <?php
}
