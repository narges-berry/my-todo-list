<?php
/**
 * Ask for confirmation, then delete a task.
 * Opening this page never deletes anything. Only the POST form does.
 */

require_once __DIR__ . '/db.php';

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filter = selected_filter($_POST['filter'] ?? 'all');
    require_csrf(filter_url($filter));

    $id = task_id($_POST['id'] ?? 0);
    $task = $id ? find_task($db, $id) : null;

    if (!$task) {
        set_flash('danger', 'That task could not be found.');
        redirect(filter_url($filter));
    }

    $statement = $db->prepare('DELETE FROM tasks WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();

    set_flash('success', 'Task deleted.');
    redirect(filter_url($filter));
}

$filter = selected_filter($_GET['filter'] ?? 'all');
$id = task_id($_GET['id'] ?? 0);
$task = $id ? find_task($db, $id) : null;

if (!$task) {
    set_flash('danger', 'That task could not be found.');
    redirect(filter_url($filter));
}

render_header('Delete task');
?>

<div class="form-wrap">
    <div class="card form-card">
        <div class="card-body p-4 text-center">
            <div class="delete-icon mb-3" aria-hidden="true">
                <i class="bi bi-trash"></i>
            </div>
            <h2 class="h4">Delete this task?</h2>
            <p class="mb-1"><strong><?= h($task['title']) ?></strong></p>
            <p class="text-secondary mb-4">This cannot be undone.</p>

            <form method="post" action="delete.php" class="d-flex flex-wrap justify-content-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                <input type="hidden" name="filter" value="<?= h($filter) ?>">
                <button class="btn btn-danger" type="submit">
                    <i class="bi bi-trash" aria-hidden="true"></i> Delete task
                </button>
                <a class="btn btn-outline-secondary" href="<?= h(filter_url($filter)) ?>">Keep task</a>
            </form>
        </div>
    </div>
</div>

<?php render_footer(); ?>
