<?php
/**
 * Home page: list tasks, filter them, and link to the other actions.
 */

require_once __DIR__ . '/db.php';

$db = db();
$filter = selected_filter($_GET['filter'] ?? 'all');

// Newest tasks first. The filter is limited to known values before it is used.
if ($filter === 'all') {
    $statement = $db->prepare(
        'SELECT id, title, description, due_date, status, created_at
         FROM tasks
         ORDER BY created_at DESC, id DESC'
    );
} else {
    $statement = $db->prepare(
        'SELECT id, title, description, due_date, status, created_at
         FROM tasks
         WHERE status = ?
         ORDER BY created_at DESC, id DESC'
    );
    $statement->bind_param('s', $filter);
}

$statement->execute();
$tasks = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

$countStatement = $db->prepare(
    "SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(status = 'pending'), 0) AS pending_count,
        COALESCE(SUM(status = 'completed'), 0) AS completed_count
     FROM tasks"
);
$countStatement->execute();
$counts = $countStatement->get_result()->fetch_assoc();
$countStatement->close();

$filters = [
    'all' => ['label' => 'All Tasks', 'count' => (int) $counts['total_count']],
    'pending' => ['label' => 'Pending Tasks', 'count' => (int) $counts['pending_count']],
    'completed' => ['label' => 'Completed Tasks', 'count' => (int) $counts['completed_count']],
];

render_header('Tasks');
?>

<div class="text-center mb-4">
    <a class="btn btn-primary btn-lg" href="add.php">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Task
    </a>
</div>

<div class="d-flex flex-wrap justify-content-center gap-2 mb-2" role="group" aria-label="Filter tasks">
    <?php foreach ($filters as $key => $item): ?>
        <a
            class="btn <?= $filter === $key ? 'btn-primary' : 'btn-outline-primary' ?>"
            href="<?= h(filter_url($key)) ?>"
            <?= $filter === $key ? 'aria-current="page"' : '' ?>
        >
            <?= h($item['label']) ?>
            <span class="count-pill"><?= $item['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>
<p class="text-center text-secondary small mb-4">Sorted by creation date, newest first.</p>

<?php if (!$tasks): ?>
    <div class="card empty-state text-center">
        <div class="card-body p-5">
            <i class="bi bi-inbox empty-icon" aria-hidden="true"></i>
            <h2 class="h4 mt-3">No tasks in this view</h2>
            <p class="text-secondary mb-4">
                <?php if ($filter === 'all'): ?>
                    Add your first task to get started.
                <?php else: ?>
                    There are no <?= h($filter) ?> tasks right now.
                <?php endif; ?>
            </p>
            <a class="btn btn-primary" href="add.php">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Task
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($tasks as $task): ?>
            <?php
            $isCompleted = $task['status'] === 'completed';
            $nextStatus = $isCompleted ? 'pending' : 'completed';
            $statusLabel = $isCompleted ? 'Completed' : 'Pending';
            $statusButton = $isCompleted ? 'Mark pending' : 'Mark complete';
            $statusIcon = $isCompleted ? 'bi-arrow-counterclockwise' : 'bi-check2-circle';
            ?>
            <div class="col-12 col-md-6">
                <article class="card task-card h-100<?= $isCompleted ? ' task-card-completed' : '' ?><?= is_overdue($task) ? ' task-card-overdue' : '' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="h5 task-title mb-0"><?= h($task['title']) ?></h2>
                            <span class="badge <?= $isCompleted ? 'text-bg-success' : 'text-bg-warning' ?>">
                                <?= h($statusLabel) ?>
                            </span>
                        </div>

                        <p class="task-description flex-grow-1">
                            <?php if ($task['description'] !== null && $task['description'] !== ''): ?>
                                <?= nl2br(h($task['description'])) ?>
                            <?php else: ?>
                                <span class="text-secondary fst-italic">No description</span>
                            <?php endif; ?>
                        </p>

                        <div class="task-meta small mb-3">
                            <div>
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <?php if (!empty($task['due_date'])): ?>
                                    Due <time datetime="<?= h($task['due_date']) ?>"><?= h(format_date($task['due_date'])) ?></time>
                                <?php else: ?>
                                    No due date
                                <?php endif; ?>
                                <?php if (is_overdue($task)): ?>
                                    <span class="badge text-bg-danger">Overdue</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                Added <?= h(format_datetime($task['created_at'])) ?>
                            </div>
                        </div>

                        <div class="task-actions d-flex flex-wrap gap-2">
                            <form method="post" action="update_status.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                <input type="hidden" name="status" value="<?= h($nextStatus) ?>">
                                <input type="hidden" name="filter" value="<?= h($filter) ?>">
                                <button class="btn btn-sm <?= $isCompleted ? 'btn-outline-secondary' : 'btn-success' ?>" type="submit">
                                    <i class="bi <?= h($statusIcon) ?>" aria-hidden="true"></i> <?= h($statusButton) ?>
                                </button>
                            </form>
                            <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int) $task['id'] ?>">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                            </a>
                            <a class="btn btn-sm btn-outline-danger" href="delete.php?id=<?= (int) $task['id'] ?>&amp;filter=<?= h($filter) ?>">
                                <i class="bi bi-trash" aria-hidden="true"></i> Delete
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php render_footer(); ?>
